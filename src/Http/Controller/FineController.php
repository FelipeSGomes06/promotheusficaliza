<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Controller;

use Fiscaliza\Application\FineFilters;
use Fiscaliza\Application\FineService;
use Fiscaliza\Domain\FineDocumentStatus;
use Fiscaliza\Domain\FineRules;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Http\HttpError;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Aba Multas: lista com filtros e resumos, cadastro em rascunho, ficha com registro, conferência documental,
 * andamento e cancelamento interno. Tudo funciona por formulários comuns (POST → 303 → GET); erros reapresentam a
 * mesma tela com o que foi digitado.
 */
final class FineController extends Controller
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $filters = FineFilters::fromQuery($query);
        $result = $this->services->fineQuery()->list($filters, self::pageNumber($query));

        return $this->render($request, $response, 'fines/index.twig', [
            'result' => $result, 'filters' => $filters, 'raw' => ['de' => (string) ($query['de'] ?? ''), 'ate' => (string) ($query['ate'] ?? '')],
            'agencies' => $this->services->referenceRepository()->agencies(), 'sources' => $this->services->fineRepository()->sourceOptions(),
            'today' => Time::localDate($this->services->clock()->now()),
        ] + self::vocabulary(), $filters->errors === [] ? 200 : 422);
    }

    /** @param array<string, string> $args */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->page($request, $response, self::id($args));
    }

    public function createForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // ?radar=<id> ou ?agente=<id> (links das fichas de recurso) só pré-selecionam a origem; nada é gravado.
        $query = $request->getQueryParams();
        $values = ['agency_id' => null];
        $radar = filter_var($query['radar'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $agent = filter_var($query['agente'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($radar !== false && ($e = $this->services->equipmentRepository()->find($radar)) !== null && $e['type_code'] === 'RADAR') {
            $values = ['source_kind' => 'RADAR', 'equipment_id' => $radar, 'agency_id' => $e['agency_id']];
        } elseif ($agent !== false && ($g = $this->services->agentRepository()->find($agent)) !== null && $g['role'] === 'TRANSITO') {
            $values = ['source_kind' => 'AGENTE', 'agent_id' => $agent, 'agency_id' => $g['agency_id']];
        }

        return $this->form($request, $response, $values + ['command_uid' => FineService::newCommandUid()], [], null);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->body($request);
        try {
            $id = $this->services->fines()->create($data, $this->userId($request));
        } catch (ValidationFailed $e) {
            return $this->form($request, $response, $data, $e->errors, null, 422);
        } catch (ConcurrencyConflict $e) {
            return $this->form($request, $response, ['command_uid' => FineService::newCommandUid()] + $data, ['_form' => $e->getMessage()], null, 409);
        }
        $this->session($request)->flash('success', 'Rascunho salvo. Complete o que faltar e registre após a conferência.');

        return $this->redirect($response, "/multas/{$id}");
    }

    /** @param array<string, string> $args */
    public function editForm(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $fine = $this->services->fineRepository()->find(self::id($args)) ?? throw new HttpError(404, 'Multa não encontrada.');
        if ($fine['record_state'] === 'CANCELADO') {
            throw new HttpError(409, 'Registro cancelado internamente: fica somente para consulta.');
        }

        return $this->form($request, $response, self::formValues($fine) + ['version' => $fine['version_no'], 'command_uid' => FineService::newCommandUid()], [], $fine);
    }

    /** @param array<string, string> $args */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = self::id($args);
        $fine = $this->services->fineRepository()->find($id) ?? throw new HttpError(404, 'Multa não encontrada.');
        $data = $this->body($request);
        try {
            $this->services->fines()->update($id, $data, $this->userId($request));
        } catch (ValidationFailed $e) {
            return $this->form($request, $response, $data, $e->errors, $fine, 422);
        } catch (RuleViolation $e) {
            $this->session($request)->flash('error', $e->getMessage());

            return $this->redirect($response, "/multas/{$id}");
        } catch (ConcurrencyConflict $e) {
            // A digitação fica; o formulário passa a carregar a versão atual: salvar de novo é uma decisão consciente.
            $current = $this->services->fineRepository()->find($id) ?? throw new HttpError(404, 'Multa não encontrada.');
            if ($current['record_state'] === 'CANCELADO') {
                $this->session($request)->flash('error', 'Este registro foi cancelado enquanto você editava; ele não pode mais ser alterado.');

                return $this->redirect($response, "/multas/{$id}");
            }

            return $this->form($request, $response, ['version' => $current['version_no'], 'command_uid' => FineService::newCommandUid()] + $data, [
                '_form' => $e->getMessage() . ' Seus dados continuam abaixo.',
            ], $current, 409, $this->services->fines()->pendingChanges($id, $data));
        }
        $this->session($request)->flash('success', $fine['record_state'] === 'REGISTRADO' ? 'Retificação salva; o original continua no histórico.' : 'Rascunho atualizado.');

        return $this->redirect($response, "/multas/{$id}");
    }

    /** @param array<string, string> $args */
    public function register(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->action($request, $response, $args, 'registrar', function (int $id, array $data, int $user): array {
            $this->services->fines()->register($id, $data, $user);

            return [];
        }, 'Registrado após conferência. Isto não emite um auto oficial.');
    }

    /** @param array<string, string> $args */
    public function addCheck(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->action($request, $response, $args, 'conferencia', function (int $id, array $data, int $user): array {
            $this->services->fines()->addCheck($id, $data, $user);

            return [];
        }, 'Conferência documental registrada (informação manual).');
    }

    /** @param array<string, string> $args */
    public function progress(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->action($request, $response, $args, 'andamento', fn(int $id, array $data, int $user): array => $this->services->fines()->updateProgress($id, $data, $user), 'Andamento atualizado.');
    }

    /** @param array<string, string> $args */
    public function cancel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->action($request, $response, $args, 'cancelar', function (int $id, array $data, int $user): array {
            $this->services->fines()->cancel($id, $data, $user);

            return [];
        }, 'Registro cancelado internamente. Ele continua legível, com o motivo no histórico.');
    }

    /**
     * Ações da ficha. Erro de campo (422) ou de regra/versão (409) reapresenta a ficha com o formulário aberto e o que
     * foi digitado; sucesso volta à ficha com a mensagem (e avisos de sequência incomum, se houver).
     *
     * @param array<string, string> $args
     * @param callable(int, array<string, mixed>, int): list<string> $run
     */
    private function action(ServerRequestInterface $request, ResponseInterface $response, array $args, string $form, callable $run, string $success): ResponseInterface
    {
        $id = self::id($args);
        $data = $this->body($request);
        try {
            $warnings = $run($id, $data, $this->userId($request));
        } catch (ValidationFailed $e) {
            return $this->page($request, $response, $id, 422, ['name' => $form, 'values' => $data, 'errors' => $e->errors]);
        } catch (RuleViolation $e) {
            return $this->page($request, $response, $id, 409, ['name' => $form, 'values' => $data, 'errors' => ['_form' => $e->getMessage()]]);
        } catch (ConcurrencyConflict $e) {
            return $this->page($request, $response, $id, 409, ['name' => $form, 'values' => ['command_uid' => FineService::newCommandUid()] + $data,
                'errors' => ['_form' => $e->getMessage() . ' O que você digitou continua no formulário: confira a ficha atualizada e envie de novo.'], 'conflict' => true]);
        }
        $session = $this->session($request);
        $session->flash('success', $success);
        foreach ($warnings as $warning) {
            $session->flash('warning', 'Aviso: ' . $warning);
        }

        return $this->redirect($response, "/multas/{$id}");
    }

    /** @param array{name?: string, values?: array<string, mixed>, errors?: array<string, string>, conflict?: bool} $form */
    private function page(ServerRequestInterface $request, ResponseInterface $response, int $id, int $status = 200, array $form = []): ResponseInterface
    {
        $query = $request->getQueryParams();
        $eventsPage = filter_var($query['eventos_pagina'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]);
        $detail = $this->services->fineQuery()->detail($id, $eventsPage === false ? 1 : $eventsPage) ?? throw new HttpError(404, 'Multa não encontrada.');
        // "Voltar à lista" reconstrói os filtros a partir de uma query validada: nunca uma URL recebida.
        parse_str(is_string($query['voltar'] ?? null) ? $query['voltar'] : '', $back);
        $now = $this->services->clock()->now()->setTimezone(Time::local());

        return $this->render($request, $response, 'fines/show.twig', $detail + [
            'form' => $form + ['name' => null, 'values' => [], 'errors' => [], 'conflict' => false],
            'uid' => FineService::newCommandUid(),
            'back' => FineFilters::fromQuery($back)->toArray(),
            'now_local' => $now->format('Y-m-d\TH:i'),
        ] + self::vocabulary(), $status);
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     * @param array<string, mixed>|null $existing
     * @param array<string, array{antes: mixed, depois: mixed}>|null $changes diferenças no conflito de edição
     */
    private function form(ServerRequestInterface $request, ResponseInterface $response, array $values, array $errors, ?array $existing, int $status = 200, ?array $changes = null): ResponseInterface
    {
        $refs = $this->services->referenceRepository();

        return $this->render($request, $response, 'fines/form.twig', [
            'values' => $values, 'errors' => $errors, 'existing' => $existing, 'changes' => $changes,
            'agencies' => $refs->agencies(), 'regions' => $refs->regions(), 'sources' => $this->services->fineRepository()->sourceOptions(),
        ] + self::vocabulary(), $status);
    }

    /**
     * Cadastro gravado → nomes dos campos do formulário (data/hora local, operador do radar, dados do medidor).
     *
     * @param array<string, mixed> $fine
     * @return array<string, mixed>
     */
    private static function formValues(array $fine): array
    {
        $values = array_intersect_key($fine, array_flip(FineService::CADASTRO));
        $occurred = Time::fromDb($fine['occurred_at']);
        $values['occurred_at'] = $occurred?->setTimezone(Time::local())->format('Y-m-d\TH:i');
        if ($fine['source_kind'] === 'RADAR') {
            $values['operator_agent_id'] = $fine['agent_id'];
            $values['agent_id'] = null;
        }
        foreach (['meter_kind', 'inmetro_record', 'certificate', 'verified_on'] as $key) {
            $values['radar_' . $key] = $fine['radar_details'][$key] ?? null;
        }

        return $values;
    }

    /** @return array<string, mixed> listas fechadas e rótulos usados pelos templates */
    private static function vocabulary(): array
    {
        return [
            'kinds' => FineRules::KINDS, 'source_kinds' => FineRules::SOURCES, 'states' => FineRules::STATES, 'stages' => FineRules::STAGES,
            'payments' => FineRules::PAYMENTS, 'contests' => FineRules::CONTESTS, 'contest_kinds' => FineRules::CONTEST_KINDS, 'ufs' => FineRules::UFS,
            'doc_labels' => FineDocumentStatus::LABELS, 'doc_sources' => FineDocumentStatus::SOURCE_TYPES, 'doc_scopes' => FineDocumentStatus::SCOPES,
            'labels' => FineRules::LABELS + FineService::PROGRESS_LABELS + ['scope' => 'Referência', 'checked_at' => 'Conferido em', 'document_status' => 'Resultado',
                'source_type' => 'Fonte', 'source_reference' => 'Referência da fonte', 'crlv_exercise' => 'Exercício do CRLV', 'pending_items' => 'Pendência apontada',
                'review_on' => 'Revisar em', 'effective_on' => 'Data a que se refere'],
            'records' => FineFilters::RECORDS, 'sorts' => FineFilters::SORTS,
        ];
    }
}
