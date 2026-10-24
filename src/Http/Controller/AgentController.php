<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Controller;

use Fiscaliza\Application\AgentService;
use Fiscaliza\Application\Filters;
use Fiscaliza\Domain\AgentRole;
use Fiscaliza\Domain\AlertFollowUp;
use Fiscaliza\Domain\AgentStatus;
use Fiscaliza\Domain\Freshness;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Http\HttpError;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Fiscaliza\Infrastructure\Repository\AgentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Cadastro de agentes (trânsito, guarda, polícia) — fictícios; não são contas de acesso. */
final class AgentController extends Controller
{
    private const PER_PAGE = 20;

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $filters = Filters::fromQuery($query);
        $text = mb_substr(trim(is_string($query['q'] ?? null) ? $query['q'] : ''), 0, 60);
        $sort = is_string($query['ordem'] ?? null) && isset(AgentRepository::SORTS[$query['ordem']]) ? $query['ordem'] : 'codigo';
        $archived = ($query['arquivados'] ?? '') === '1';
        $page = self::pageNumber($query);
        $result = $this->services->agentRepository()->search($filters, $text, $archived, $sort, $page, self::PER_PAGE);
        $now = $this->services->clock()->now();
        foreach ($result['rows'] as &$row) {
            $row['freshness'] = Freshness::of(Time::fromDb($row['pos_observed_at']), $now, AgentStatus::from($row['status'])->isOnShift(), Freshness::POSITION_CURRENT, Freshness::POSITION_STALE);
        }
        unset($row);

        return $this->render($request, $response, 'agents/index.twig', [
            'rows' => $result['rows'], 'total' => $result['total'], 'page' => $page, 'pages' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'filters' => $filters, 'q' => $text, 'sort' => $sort, 'archived' => $archived,
            'options' => CentralController::filterOptions($this->services->referenceRepository()->regions(), $this->services->referenceRepository()->agencies()),
        ]);
    }

    /** @param array<string, string> $args */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->page($request, $response, self::id($args));
    }

    /** @param array<string, string> $args */
    public function acknowledgeAlert(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->alertAction($request, $response, $args, 'ciencia', function (int $alert, array $data, int $user): string {
            $this->services->alerts()->acknowledge($alert, $user, is_string($data['note'] ?? null) ? $data['note'] : null);

            return 'Ciência registrada. O alerta continua pendente até chegar posição recente ou o agente sair de turno.';
        });
    }

    /** @param array<string, string> $args */
    public function assignAlert(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->alertAction($request, $response, $args, 'responsavel', function (int $alert, array $data, int $user): string {
            $before = $this->services->alertRepository()->find($alert)['assignee_id'] ?? null;
            $this->services->alerts()->assign($alert, $data, $user);
            $name = $this->services->userRepository()->find((int) $data['assignee'])['display_name'] ?? 'conta escolhida';

            return ($before === null ? 'Acompanhamento atribuído a ' : 'Acompanhamento reatribuído a ') . $name . '.';
        });
    }

    /** @param array<string, string> $args */
    public function recordFollowUp(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->alertAction($request, $response, $args, 'providencia', function (int $alert, array $data, int $user): string {
            $this->services->alerts()->recordFollowUp($alert, $data, $user);

            return 'Providência registrada. O alerta continua pendente até chegar posição recente ou o agente sair de turno.';
        });
    }

    /**
     * Recurso da demonstração: uma posição fictícia pelo mesmo caminho do simulador (ingestão + reconciliação), com
     * origem identificada no histórico. Nenhuma mensagem é enviada; é um recebimento pontual, não reativa o rádio.
     *
     * @param array<string, string> $args
     */
    public function simulatePosition(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = self::id($args);
        $agent = $this->services->agentRepository()->find($id) ?? throw new HttpError(404, 'Agente não encontrado.');
        if ($agent['archived_at'] !== null) {
            $this->session($request)->flash('error', 'Agente arquivado não recebe posições.');

            return $this->redirect($response, "/agentes/{$id}");
        }
        $alerts = $this->services->alertRepository();
        $before = $alerts->unresolvedFor('AGENTE', $id) !== [];
        // Até ~200 m da base: ponto plausível na área urbana, como os do simulador.
        $jitter = static fn(mixed $v): string => number_format((float) $v + random_int(-20, 20) / 10000, 6, '.', '');
        $this->services->ingestor()->position('demo-pos-' . bin2hex(random_bytes(10)), 'AGENTE', $id, $jitter($agent['base_lat']), $jitter($agent['base_lng']), $this->services->clock()->now(), true, 'SIMULADOR', $this->userId($request), 'recebimento simulado pela demonstração');
        $this->services->database()->transaction(fn() => $this->services->alerts()->reconcile());
        $closed = $before && $alerts->unresolvedFor('AGENTE', $id) === [];
        $this->session($request)->flash('success', match (true) {
            $closed => 'Posição simulada recebida: o alerta foi encerrado por posição recente. É um recebimento pontual; se o rádio simulado seguir sem transmitir, um alerta novo abre depois de 30 min.',
            AgentStatus::from($agent['status'])->isOnShift() => 'Posição simulada recebida e gravada no histórico.',
            default => 'Posição simulada gravada no histórico. Fora de turno, ela não aparece no mapa.',
        });

        return $this->redirect($response, "/agentes/{$id}");
    }

    public function createForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->form($request, $response, ['role' => 'TRANSITO', 'status' => 'FORA_DE_SERVICO'], [], null);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->body($request);
        try {
            $id = $this->services->agents()->create($data, $this->userId($request));
        } catch (ValidationFailed $e) {
            return $this->form($request, $response, $data, $e->errors, null, 422);
        } catch (ConcurrencyConflict $e) {
            return $this->form($request, $response, $data, ['_form' => $e->getMessage()], null, 409);
        }
        $this->session($request)->flash('success', 'Agente cadastrado.');

        return $this->redirect($response, "/agentes/{$id}");
    }

    /** @param array<string, string> $args */
    public function editForm(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $agent = $this->services->agentRepository()->find(self::id($args)) ?? throw new HttpError(404, 'Agente não encontrado.');
        if ($agent['archived_at'] !== null) {
            throw new HttpError(409, 'Agente arquivado não pode ser editado.');
        }
        $agent['version'] = $agent['version_no'];

        return $this->form($request, $response, $agent, [], $agent);
    }

    /** @param array<string, string> $args */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = self::id($args);
        $agent = $this->services->agentRepository()->find($id) ?? throw new HttpError(404, 'Agente não encontrado.');
        $data = $this->body($request);
        try {
            $this->services->agents()->update($id, $data, $this->userId($request));
        } catch (ValidationFailed $e) {
            return $this->form($request, $response, $data + ['code' => $agent['code']], $e->errors, $agent, 422);
        } catch (RuleViolation $e) {
            return $this->form($request, $response, $data + ['code' => $agent['code']], ['_form' => $e->getMessage()], $agent, 409);
        } catch (ConcurrencyConflict) {
            // Mantém a digitação e passa a versão atual: salvar de novo é a decisão consciente de manter esta versão.
            $agents = $this->services->agentRepository();
            $current = $agents->find($id) ?? $agent;

            return $this->form($request, $response, ['version' => $current['version_no'], 'code' => $agent['code']] + $data, [
                '_form' => 'O cadastro deste agente foi alterado (por outra pessoa ou em outra aba) depois que você abriu o formulário. Confira a ficha atual antes de salvar de novo; seus dados continuam abaixo.',
            ], $current, 409);
        }
        $this->session($request)->flash('success', 'Alterações salvas.');

        return $this->redirect($response, "/agentes/{$id}");
    }

    /** @param array<string, string> $args */
    public function changeStatus(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = self::id($args);
        $data = $this->body($request);
        $alerts = $this->services->alertRepository();
        $before = $alerts->unresolvedFor('AGENTE', $id) !== [];
        try {
            $this->services->agents()->changeStatus($id, (string) ($data['status'] ?? ''), $this->userId($request), 'MANUAL', is_string($data['note'] ?? null) ? $data['note'] : '');
        } catch (ValidationFailed $e) {
            return $this->page($request, $response, $id, 422, ['name' => 'situacao', 'values' => $data, 'errors' => $e->errors]);
        } catch (RuleViolation|ConcurrencyConflict $e) {
            return $this->page($request, $response, $id, 409, ['name' => 'situacao', 'values' => $data, 'errors' => ['_form' => $e->getMessage()]]);
        }
        $closed = $before && $alerts->unresolvedFor('AGENTE', $id) === [];
        $this->session($request)->flash('success', 'Situação do agente atualizada.' . ($closed ? ' O alerta sem posição recente deixou de se aplicar com a saída de turno; isso não indica que a comunicação voltou.' : ''));

        return $this->redirect($response, "/agentes/{$id}");
    }

    /** @param array<string, string> $args */
    public function archive(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = self::id($args);
        try {
            $this->services->agents()->archive($id, (string) ($this->body($request)['reason'] ?? ''), $this->userId($request));
            $this->session($request)->flash('success', 'Agente arquivado. O histórico foi preservado.');
        } catch (ValidationFailed $e) {
            $this->session($request)->flash('error', implode(' ', $e->errors));
        } catch (RuleViolation|ConcurrencyConflict $e) {
            $this->session($request)->flash('error', $e->getMessage());
        }

        return $this->redirect($response, "/agentes/{$id}");
    }

    /**
     * Ações do acompanhamento na ficha. Erro de campo (422), de regra ou de versão (409) reapresenta a ficha com o
     * formulário aberto e o que foi digitado; sucesso volta ao acompanhamento com a mensagem.
     *
     * @param array<string, string> $args
     * @param callable(int, array<string, mixed>, int): string $run devolve a mensagem de sucesso
     */
    private function alertAction(ServerRequestInterface $request, ResponseInterface $response, array $args, string $form, callable $run): ResponseInterface
    {
        $id = self::id($args);
        $alertId = self::id($args, 'alert');
        $alert = $this->services->alertRepository()->find($alertId);
        if ($alert === null || $alert['resource_kind'] !== 'AGENTE' || (int) $alert['resource_id'] !== $id) {
            throw new HttpError(404, 'Alerta não encontrado nesta ficha.');
        }
        $data = $this->body($request);
        $state = ['name' => $form, 'alert' => $alertId, 'values' => $data];
        try {
            $message = $run($alertId, $data, $this->userId($request));
        } catch (ValidationFailed $e) {
            return $this->page($request, $response, $id, 422, $state + ['errors' => $e->errors]);
        } catch (RuleViolation $e) {
            $now = $this->services->alertRepository()->find($alertId);
            if ($form === 'ciencia' && ($now['state'] ?? '') === 'RECONHECIDO') {
                // Outra pessoa registrou a ciência antes: a nota digitada segue no formulário de providência.
                return $this->page($request, $response, $id, 409, ['name' => 'providencia', 'alert' => $alertId, 'values' => ['note' => $data['note'] ?? ''],
                    'errors' => ['_form' => 'Outra pessoa já registrou a ciência deste alerta. Sua nota continua abaixo: registre-a como providência, se for o caso.']]);
            }

            return $this->page($request, $response, $id, 409, $state + ['errors' => ['_form' => $e->getMessage()]]);
        } catch (ConcurrencyConflict $e) {
            return $this->page($request, $response, $id, 409, $state + ['errors' => ['_form' => $e->getMessage() . ' O que você digitou continua no formulário: confira o acompanhamento atualizado e envie de novo.']]);
        }
        $this->session($request)->flash('success', $message);

        return $this->redirect($response, "/agentes/{$id}#acompanhamento");
    }

    /**
     * Ficha do agente: próxima ação (AlertFollowUp, a mesma política da central), acompanhamento do alerta pendente,
     * situação, missão vigente e histórico. Pendentes são lidos sem limite; o limite vale só para os encerrados.
     *
     * @param array{name?: string, alert?: int, values?: array<string, mixed>, errors?: array<string, string>} $form
     */
    private function page(ServerRequestInterface $request, ResponseInterface $response, int $id, int $status = 200, array $form = []): ResponseInterface
    {
        $agent = $this->services->agentRepository()->find($id) ?? throw new HttpError(404, 'Agente não encontrado.');
        $agentStatus = AgentStatus::from($agent['status']);
        $now = $this->services->clock()->now();
        $alerts = $this->services->alertRepository();
        $pending = $alerts->unresolvedFor('AGENTE', $id);
        $followUps = $alerts->followUps(array_map(static fn($a) => (int) $a['id'], $pending));
        $current = $pending[0] ?? null;
        $last = $current === null ? null : AlertFollowUp::lastAction($followUps[(int) $current['id']] ?? []);
        $missions = $this->services->missionRepository();
        $missionId = $missions->activeForAgent($id);
        $local = $now->setTimezone(Time::local());

        return $this->render($request, $response, 'agents/show.twig', [
            'a' => $agent,
            'status' => $agentStatus,
            'role' => AgentRole::from($agent['role']),
            'statuses' => AgentStatus::cases(),
            'freshness' => Freshness::of(Time::fromDb($agent['pos_observed_at']), $now, $agentStatus->isOnShift(), Freshness::POSITION_CURRENT, Freshness::POSITION_STALE),
            'events' => array_map(self::readableChange(...), $this->services->eventRepository()->forResource('AGENTE', $id)),
            'next' => AlertFollowUp::nextAction($agent, $current, $last, $now),
            'pending' => $pending,
            'follow_ups' => $followUps,
            'last_follow_up' => $last,
            'closed' => $alerts->resolvedFor('AGENTE', $id),
            'closed_total' => $alerts->countResolvedFor('AGENTE', $id),
            'actions' => AlertFollowUp::ACTIONS,
            'resolutions' => AlertFollowUp::RESOLUTIONS,
            'assignees' => $this->services->userRepository()->assignable(),
            'mission' => $missionId === null ? null : $missions->find($missionId),
            'form' => $form + ['name' => null, 'alert' => null, 'values' => [], 'errors' => []],
            'now_local' => $local->format('Y-m-d\TH:i'),
            'max_local' => $local->modify('+' . AlertFollowUp::MAX_CHECK_DAYS . ' days')->format('Y-m-d\TH:i'),
        ], $status);
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     * @param array<string, mixed>|null $existing
     */
    private function form(ServerRequestInterface $request, ResponseInterface $response, array $values, array $errors, ?array $existing, int $status = 200): ResponseInterface
    {
        $refs = $this->services->referenceRepository();

        return $this->render($request, $response, 'agents/form.twig', [
            'values' => $values, 'errors' => $errors, 'existing' => $existing, 'roles' => AgentRole::cases(), 'statuses' => AgentStatus::cases(),
            'agencies' => $refs->agencies(),
        ], $status);
    }

    /**
     * Alterações gravadas antes de 23/10/2026 listavam o nome técnico dos campos ("base_street, region_id"): a ficha
     * mostra os rótulos, refeitos a partir das chaves guardadas no evento. O evento gravado não muda.
     *
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    private static function readableChange(array $event): array
    {
        $changes = $event['payload']['alteracoes'] ?? null;
        if ($event['event_type'] === 'ALTERACAO' && is_array($changes) && $changes !== [] && str_starts_with((string) $event['summary'], 'Cadastro alterado: ')) {
            $event['summary'] = AgentService::changeSummary(array_map(strval(...), array_keys($changes)));
        }

        return $event;
    }
}
