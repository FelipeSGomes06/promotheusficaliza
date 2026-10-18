<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Controller;

use Fiscaliza\Application\Filters;
use Fiscaliza\Domain\AgentRole;
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
        $id = self::id($args);
        $agent = $this->services->agentRepository()->find($id) ?? throw new HttpError(404, 'Agente não encontrado.');
        $status = AgentStatus::from($agent['status']);
        $now = $this->services->clock()->now();

        return $this->render($request, $response, 'agents/show.twig', [
            'a' => $agent,
            'status' => $status,
            'role' => AgentRole::from($agent['role']),
            'statuses' => AgentStatus::cases(),
            'freshness' => Freshness::of(Time::fromDb($agent['pos_observed_at']), $now, $status->isOnShift(), Freshness::POSITION_CURRENT, Freshness::POSITION_STALE),
            'events' => $this->services->eventRepository()->forResource('AGENTE', $id),
            'alerts' => $this->services->alertRepository()->forResource('AGENTE', $id),
        ]);
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
        try {
            $this->services->agents()->changeStatus($id, (string) ($this->body($request)['status'] ?? ''), $this->userId($request));
            $this->session($request)->flash('success', 'Situação do agente atualizada.');
        } catch (ValidationFailed $e) {
            $this->session($request)->flash('error', implode(' ', $e->errors));
        } catch (RuleViolation|ConcurrencyConflict $e) {
            $this->session($request)->flash('error', $e->getMessage());
        }

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
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     * @param array<string, mixed>|null $existing
     */
    private function form(ServerRequestInterface $request, ResponseInterface $response, array $values, array $errors, ?array $existing, int $status = 200): ResponseInterface
    {
        $refs = $this->services->referenceRepository();

        return $this->render($request, $response, 'agents/form.twig', [
            'values' => $values, 'errors' => $errors, 'existing' => $existing, 'roles' => AgentRole::cases(), 'statuses' => AgentStatus::cases(),
            'agencies' => $refs->agencies(), 'regions' => $refs->regions(),
        ], $status);
    }
}
