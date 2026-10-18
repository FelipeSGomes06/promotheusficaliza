<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Controller;

use Fiscaliza\Application\Filters;
use Fiscaliza\Domain\EquipmentCatalog;
use Fiscaliza\Domain\EquipmentStatus;
use Fiscaliza\Domain\Freshness;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Http\HttpError;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Fiscaliza\Infrastructure\Repository\EquipmentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Inventário de equipamentos: consulta pública; cadastro, edição, situação e arquivamento para operadores. */
final class EquipmentController extends Controller
{
    private const PER_PAGE = 20;

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $filters = Filters::fromQuery($query);
        $text = mb_substr(trim(is_string($query['q'] ?? null) ? $query['q'] : ''), 0, 60);
        $sort = is_string($query['ordem'] ?? null) && isset(EquipmentRepository::SORTS[$query['ordem']]) ? $query['ordem'] : 'codigo';
        $archived = ($query['arquivados'] ?? '') === '1';
        $page = self::pageNumber($query);
        $result = $this->services->equipmentRepository()->search($filters, $text, $archived, $sort, $page, self::PER_PAGE);
        $now = $this->services->clock()->now();
        foreach ($result['rows'] as &$row) {
            $row['freshness'] = Freshness::of(Time::fromDb($row['last_seen_at']), $now, EquipmentStatus::from($row['status'])->expectsSignal());
        }
        unset($row);

        return $this->render($request, $response, 'equipment/index.twig', [
            'rows' => $result['rows'], 'total' => $result['total'], 'page' => $page, 'pages' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'filters' => $filters, 'q' => $text, 'sort' => $sort, 'archived' => $archived, 'types' => EquipmentCatalog::all(),
            'options' => CentralController::filterOptions($this->services->referenceRepository()->regions(), $this->services->referenceRepository()->agencies()),
        ]);
    }

    /** @param array<string, string> $args */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = self::id($args);
        $equipment = $this->services->equipmentRepository()->find($id) ?? throw new HttpError(404, 'Equipamento não encontrado.');
        $status = EquipmentStatus::from($equipment['status']);
        $now = $this->services->clock()->now();

        return $this->render($request, $response, 'equipment/show.twig', [
            'e' => $equipment,
            'freshness' => Freshness::of(Time::fromDb($equipment['last_seen_at']), $now, $status->expectsSignal()),
            'position_freshness' => Freshness::of(Time::fromDb($equipment['pos_observed_at']), $now, true, Freshness::POSITION_CURRENT, Freshness::POSITION_STALE),
            'type' => EquipmentCatalog::get($equipment['type_code']),
            'status' => $status,
            'transitions' => $status->allowedTransitions(),
            'specs' => EquipmentCatalog::get($equipment['type_code'])?->describe($equipment['specs']) ?? [],
            'events' => $this->services->eventRepository()->forResource('EQUIPAMENTO', $id),
            'orders' => $this->services->maintenanceRepository()->forEquipment($id),
            'alerts' => $this->services->alertRepository()->forResource('EQUIPAMENTO', $id),
            'analysis' => $this->services->analysisRepository()->latestForEquipment($id),
            'today' => Time::localDate($now),
        ]);
    }

    public function createForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $type = is_string($request->getQueryParams()['tipo'] ?? null) && EquipmentCatalog::get($request->getQueryParams()['tipo']) ? $request->getQueryParams()['tipo'] : 'RADAR';

        return $this->form($request, $response, ['type_code' => $type, 'status' => 'NAO_INSTALADO', 'location_kind' => 'PLANEJADO', 'specs' => []], [], null);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->body($request);
        try {
            $id = $this->services->equipment()->create($data, $this->userId($request));
        } catch (ValidationFailed $e) {
            return $this->form($request, $response, $data, $e->errors, null, 422);
        } catch (ConcurrencyConflict $e) {
            return $this->form($request, $response, $data, ['_form' => $e->getMessage()], null, 409);
        }
        $this->session($request)->flash('success', 'Equipamento cadastrado.');

        return $this->redirect($response, "/equipamentos/{$id}");
    }

    /** @param array<string, string> $args */
    public function editForm(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $equipment = $this->services->equipmentRepository()->find(self::id($args)) ?? throw new HttpError(404, 'Equipamento não encontrado.');
        if ($equipment['archived_at'] !== null) {
            throw new HttpError(409, 'Equipamento arquivado não pode ser editado.');
        }
        $equipment['version'] = $equipment['version_no'];

        return $this->form($request, $response, $equipment, [], $equipment);
    }

    /** @param array<string, string> $args */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = self::id($args);
        $equipment = $this->services->equipmentRepository()->find($id) ?? throw new HttpError(404, 'Equipamento não encontrado.');
        $data = $this->body($request);
        try {
            $this->services->equipment()->update($id, $data, $this->userId($request));
        } catch (ValidationFailed $e) {
            return $this->form($request, $response, $data + ['code' => $equipment['code']], $e->errors, $equipment, 422);
        } catch (RuleViolation $e) {
            return $this->form($request, $response, $data + ['code' => $equipment['code']], ['_form' => $e->getMessage()], $equipment, 409);
        } catch (ConcurrencyConflict $e) {
            return $this->conflict($request, $response, $id, $data);
        }
        $this->session($request)->flash('success', 'Alterações salvas.');

        return $this->redirect($response, "/equipamentos/{$id}");
    }

    /** @param array<string, string> $args */
    public function changeStatus(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = self::id($args);
        $data = $this->body($request);
        try {
            $this->services->equipment()->changeStatus($id, (string) ($data['status'] ?? ''), (string) ($data['note'] ?? ''), $this->userId($request));
            $this->session($request)->flash('success', 'Situação atualizada.');
        } catch (ValidationFailed $e) {
            $this->session($request)->flash('error', implode(' ', $e->errors));
        } catch (RuleViolation|ConcurrencyConflict $e) {
            $this->session($request)->flash('error', $e->getMessage());
        }

        return $this->redirect($response, "/equipamentos/{$id}");
    }

    /** @param array<string, string> $args */
    public function archive(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = self::id($args);
        $body = $this->body($request);
        try {
            $cancelled = $this->services->equipment()->archive($id, (string) ($body['reason'] ?? ''), $this->userId($request), ($body['cancelar_agendadas'] ?? '') === '1');
            $this->session($request)->flash('success', 'Equipamento arquivado. O histórico foi preservado.'
                . ($cancelled === [] ? '' : ' Ordens agendadas canceladas: #' . implode(', #', $cancelled) . '.'));
        } catch (ValidationFailed $e) {
            $this->session($request)->flash('error', implode(' ', $e->errors));
        } catch (RuleViolation|ConcurrencyConflict $e) {
            $this->session($request)->flash('error', $e->getMessage());
        }

        return $this->redirect($response, "/equipamentos/{$id}");
    }

    /**
     * Demonstração: injeta uma falha de telemetria pelo mesmo caminho do simulador (ingestão + alertas).
     *
     * @param array<string, string> $args
     */
    public function simulateFailure(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = self::id($args);
        $uid = 'demo-' . bin2hex(random_bytes(10));
        $outcome = $this->services->ingestor()->equipmentStatus($uid, $id, EquipmentStatus::Falha, $this->services->clock()->now(), 'falha simulada pelo operador (demonstração)', 'SIMULADOR', $this->userId($request));
        $this->services->database()->transaction(fn() => $this->services->alerts()->reconcile());
        $this->session($request)->flash($outcome === 'APLICADO' ? 'success' : 'warning', $outcome === 'APLICADO'
            ? 'Falha simulada registrada: veja o alerta na central.'
            : 'Evento registrado só no histórico: a situação atual prevalece (estado administrativo ou já em falha).');

        return $this->redirect($response, "/equipamentos/{$id}");
    }

    /**
     * Conflito de edição: o cadastro mudou depois que o formulário foi aberto. A digitação é mantida, as diferenças
     * em relação ao que está salvo aparecem campo a campo e o formulário passa a carregar a versão atual — salvar
     * de novo é então uma decisão consciente de manter a própria versão.
     *
     * @param array<string, mixed> $data
     */
    private function conflict(ServerRequestInterface $request, ResponseInterface $response, int $id, array $data): ResponseInterface
    {
        $current = $this->services->equipmentRepository()->find($id) ?? throw new HttpError(404, 'Equipamento não encontrado.');
        if ($current['archived_at'] !== null) {
            $this->session($request)->flash('error', 'Este equipamento foi arquivado enquanto você editava; o cadastro não pode mais ser alterado.');

            return $this->redirect($response, "/equipamentos/{$id}");
        }
        $last = null;
        foreach ($this->services->eventRepository()->forResource('EQUIPAMENTO', $id, 20) as $event) {
            if (in_array($event['event_type'], ['ALTERACAO', 'SITUACAO'], true)) {
                $last = $event;
                break;
            }
        }

        return $this->form($request, $response, ['version' => $current['version_no'], 'code' => $current['code']] + $data, [
            '_form' => 'O cadastro foi alterado (por outra pessoa ou em outra aba) depois que você abriu este formulário. Seus dados continuam abaixo.',
        ], $current, 409, ['changes' => $this->services->equipment()->pendingChanges($id, $data), 'last' => $last]);
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     * @param array<string, mixed>|null $existing
     * @param array{changes: array<string, array{antes: mixed, depois: mixed}>, last: array<string, mixed>|null}|null $conflict
     */
    private function form(ServerRequestInterface $request, ResponseInterface $response, array $values, array $errors, ?array $existing, int $status = 200, ?array $conflict = null): ResponseInterface
    {
        $refs = $this->services->referenceRepository();

        return $this->render($request, $response, 'equipment/form.twig', [
            'values' => $values, 'errors' => $errors, 'existing' => $existing, 'conflict' => $conflict, 'types' => EquipmentCatalog::all(),
            'statuses' => EquipmentStatus::cases(), 'agencies' => $refs->agencies(), 'regions' => $refs->regions(),
        ], $status);
    }
}
