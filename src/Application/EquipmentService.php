<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\EquipmentCatalog;
use Fiscaliza\Domain\EquipmentStatus;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Fiscaliza\Infrastructure\ConstraintViolation;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\EquipmentRepository;
use Fiscaliza\Infrastructure\Repository\MaintenanceRepository;
use Fiscaliza\Infrastructure\Repository\ReferenceRepository;

/** Casos de uso do inventário de equipamentos: cadastro, edição, mudança de situação e arquivamento. */
final class EquipmentService
{
    /** Campos do formulário comparados no histórico de alterações. */
    private const TRACKED = ['type_code', 'manufacturer', 'model', 'serial_number', 'asset_tag', 'agency_id', 'cep', 'street', 'address_ref',
        'region_id', 'lat', 'lng', 'location_kind', 'maint_interval_days', 'installed_on', 'notes', 'specs'];
    /** Local no mapa ou pelo endereço (ADR-0021): obrigatório para todo equipamento, inclusive o planejado. */
    private const POINT = ['lat' => 'lat', 'lng' => 'lng', 'street' => 'street', 'cep' => 'cep', 'number' => 'address_ref'];

    public function __construct(
        private readonly Database $db,
        private readonly EquipmentRepository $equipment,
        private readonly ReferenceRepository $references,
        private readonly MaintenanceRepository $orders,
        private readonly History $history,
        private readonly AlertService $alerts,
        private readonly Clock $clock,
        private readonly AddressPoint $point,
    ) {}

    /** @param array<string, mixed> $data campos do formulário */
    public function create(array $data, ?int $actorId, string $source = 'MANUAL'): int
    {
        $input = new Input($data);
        $status = EquipmentStatus::tryFrom((string) $input->choice('status', array_map(static fn($s) => $s->value, EquipmentStatus::cases()))) ?? EquipmentStatus::NaoInstalado;
        $values = $this->validate($input, $status);
        $values['region_id'] = $this->point->regionAt((float) $values['lat'], (float) $values['lng']);
        $now = Time::toDb($this->clock->now());
        $type = EquipmentCatalog::get($values['type_code']) ?? throw new \LogicException('tipo validado');

        return $this->db->transaction(function () use ($values, $status, $now, $type, $actorId, $source): int {
            $row = $values + [
                'code' => $this->equipment->nextCode($type->prefix),
                'status' => $status->value,
                'status_changed_at' => $now,
                'last_seen_at' => $status->expectsSignal() ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $id = $this->persist(fn() => $this->equipment->insert($row));
            $this->history->record('EQUIPAMENTO', $id, 'CADASTRO', $actorId, "Cadastro de {$type->label} {$row['code']} ({$status->label()}).", ['valores' => array_intersect_key($row, array_flip(self::TRACKED))], $source);
            $this->alerts->reconcile();

            return $id;
        });
    }

    /** @param array<string, mixed> $data campos do formulário, incluindo "version" lido ao abrir o formulário */
    public function update(int $id, array $data, int $actorId): void
    {
        $input = new Input($data);
        $version = $input->int('version', 1, PHP_INT_MAX);
        $current = $this->equipment->find($id) ?? throw new NotFound('Equipamento não encontrado.');
        if ($current['archived_at'] !== null) {
            throw new RuleViolation('Equipamento arquivado não pode ser editado.');
        }
        $values = $this->validate($input, EquipmentStatus::from($current['status']), $current);
        $values += $this->point->regionOnUpdate($current, $values, 'lat', 'lng');

        $this->db->transaction(function () use ($id, $version, $values, $current, $actorId): void {
            $saved = $this->persist(fn() => $this->equipment->updateVersioned($id, (int) $version, $values + ['updated_at' => Time::toDb($this->clock->now())]));
            if (!$saved) {
                // Só mudanças de cadastro avançam a versão (ver EquipmentRepository::updateState): posição e situação não chegam aqui.
                throw new ConcurrencyConflict('O cadastro foi alterado depois que você abriu o formulário.');
            }
            $changes = History::diff(array_intersect_key($current, array_flip(self::TRACKED)), $values);
            if ($changes !== []) {
                $this->history->record('EQUIPAMENTO', $id, 'ALTERACAO', $actorId, 'Cadastro alterado: ' . implode(', ', array_keys($changes)) . '.', ['alteracoes' => $changes]);
            }
        });
    }

    /**
     * O que salvar $data mudaria no cadastro gravado agora — base da tela de conflito de edição, que mostra
     * "salvo agora" ao lado de "sua versão" sem descartar o que a pessoa digitou.
     *
     * @param array<string, mixed> $data
     * @return array<string, array{antes: mixed, depois: mixed}> vazio se os dados enviados não forem válidos
     */
    public function pendingChanges(int $id, array $data): array
    {
        $current = $this->equipment->find($id) ?? throw new NotFound('Equipamento não encontrado.');
        try {
            $values = $this->validate(new Input($data), EquipmentStatus::from($current['status']), $current);
        } catch (ValidationFailed) {
            return [];
        }
        $values += $this->point->regionOnUpdate($current, $values, 'lat', 'lng');

        return History::diff(array_intersect_key($current, array_flip(self::TRACKED)), $values);
    }

    public function changeStatus(int $id, string $target, string $note, int $actorId): void
    {
        $status = EquipmentStatus::tryFrom($target) ?? throw new ValidationFailed(['status' => 'Situação inválida.']);
        $note = trim($note);
        if ($note === '' || mb_strlen($note) > 300) {
            throw new ValidationFailed(['note' => 'Descreva o motivo (até 300 caracteres).']);
        }
        $this->db->transaction(function () use ($id, $status, $note, $actorId): void {
            $current = $this->equipment->lock($id) ?? throw new NotFound('Equipamento não encontrado.');
            foreach ($this->orders->openFor($id) as $order) {
                if ($order['state'] === 'EM_EXECUCAO') {
                    throw new RuleViolation("A intervenção da ordem #{$order['id']} está em execução: a situação será definida ao concluí-la, com o resultado real.");
                }
            }
            $this->applyStatus($current, $status, $note, $actorId, 'MANUAL');
            $this->alerts->reconcile();
        });
    }

    /**
     * Aplica uma transição de situação já dentro de transação, com histórico antes/depois.
     * Usado também pelas ordens de manutenção (início e conclusão da intervenção).
     *
     * @param array<string, mixed> $current linha bloqueada do equipamento
     */
    public function applyStatus(array $current, EquipmentStatus $target, string $note, ?int $actorId, string $source): void
    {
        if ($current['archived_at'] !== null) {
            throw new RuleViolation('Equipamento arquivado não muda de situação.');
        }
        $from = EquipmentStatus::from($current['status']);
        if ($from === $target) {
            return;
        }
        if (!$from->canTransitionTo($target)) {
            throw new RuleViolation("Transição não permitida: {$from->label()} → {$target->label()}.");
        }
        $now = Time::toDb($this->clock->now());
        $changes = ['status' => $target->value, 'status_changed_at' => $now, 'updated_at' => $now];
        if ($target === EquipmentStatus::Operacional && $current['location_kind'] === 'PLANEJADO') {
            // Instalação concluída: a posição planejada passa a ser a de instalação.
            $changes['location_kind'] = 'INSTALADO';
            $changes['installed_on'] = Time::localDate($this->clock->now());
        }
        if ($target->expectsSignal()) {
            $changes['last_seen_at'] = $now;
        }
        $this->equipment->updateState((int) $current['id'], $changes);
        $this->history->record('EQUIPAMENTO', (int) $current['id'], 'SITUACAO', $actorId, "{$from->label()} → {$target->label()}: {$note}", [
            'de' => $from->value, 'para' => $target->value, 'motivo' => $note,
        ], $source);
    }

    /**
     * Retira o equipamento da operação. Recusado com intervenção em execução (ela precisa ser concluída com o
     * resultado real). Ordens apenas agendadas só são canceladas se $cancelScheduled confirmar isso explicitamente;
     * cada cancelamento recebe o motivo do arquivamento, autoria e histórico próprios.
     *
     * @return list<int> ordens agendadas canceladas junto com o arquivamento
     */
    public function archive(int $id, string $reason, int $actorId, bool $cancelScheduled = false): array
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 300) {
            throw new ValidationFailed(['reason' => 'Informe o motivo do arquivamento (até 300 caracteres).']);
        }

        return $this->db->transaction(function () use ($id, $reason, $actorId, $cancelScheduled): array {
            $current = $this->equipment->lock($id) ?? throw new NotFound('Equipamento não encontrado.');
            if ($current['archived_at'] !== null) {
                throw new RuleViolation('Equipamento já está arquivado.');
            }
            $open = $this->orders->openFor($id);
            foreach ($open as $order) {
                if ($order['state'] === 'EM_EXECUCAO') {
                    throw new RuleViolation("A ordem #{$order['id']} está em execução. Conclua a intervenção, informando o resultado, antes de arquivar.");
                }
            }
            $scheduled = array_map(static fn(array $o): int => (int) $o['id'], $open);
            if ($scheduled !== [] && !$cancelScheduled) {
                throw new RuleViolation('Há ' . count($scheduled) . ' ordem(ns) agendada(s) (#' . implode(', #', $scheduled) . '). Confirme o cancelamento delas para arquivar.');
            }
            $now = Time::toDb($this->clock->now());
            foreach ($scheduled as $orderId) {
                $this->orders->markClosed($orderId, 'CANCELADA', $now, $actorId, "Cancelada no arquivamento do equipamento: {$reason}", null);
                $this->history->record('EQUIPAMENTO', $id, 'MANUTENCAO_CANCELADA', $actorId, "Ordem #{$orderId} cancelada no arquivamento: {$reason}", ['ordem' => $orderId]);
            }
            $this->equipment->updateState($id, ['archived_at' => $now, 'updated_at' => $now]);
            $this->history->record('EQUIPAMENTO', $id, 'ARQUIVAMENTO', $actorId, "Arquivado: {$reason}", ['motivo' => $reason] + ($scheduled === [] ? [] : ['ordens_canceladas' => $scheduled]));
            $this->alerts->reconcile();

            return $scheduled;
        });
    }

    /**
     * Valida os campos do formulário (criação e edição) e devolve os valores prontos para gravar. O bairro não é
     * escolhido (vem do ponto) e o local é obrigatório: mapa ou endereço, nunca coordenadas digitadas.
     *
     * @param array<string, mixed>|null $current cadastro atual, na edição
     * @return array<string, mixed>
     */
    private function validate(Input $input, EquipmentStatus $status, ?array $current = null): array
    {
        $typeCode = $input->choice('type_code', array_keys(EquipmentCatalog::all()));
        $type = $typeCode === null ? null : EquipmentCatalog::get($typeCode);
        $values = [
            'type_code' => $typeCode,
            'manufacturer' => $input->text('manufacturer', 80),
            'model' => $input->text('model', 80),
            'serial_number' => $input->text('serial_number', 60, false),
            'asset_tag' => $input->text('asset_tag', 30, false),
            'agency_id' => $input->int('agency_id', 1, PHP_INT_MAX),
            'cep' => $input->cep('cep'),
            'street' => $input->text('street', 120, false),
            'address_ref' => $input->text('address_ref', 160, false),
            'lat' => $input->coordinate('lat', 'lat', false),
            'lng' => $input->coordinate('lng', 'lng', false),
            'location_kind' => $input->choice('location_kind', ['INSTALADO', 'PLANEJADO', 'BASE']),
            'maint_interval_days' => $input->int('maint_interval_days', 7, 730, false),
            'installed_on' => $input->date('installed_on', false),
            'notes' => $input->text('notes', 500, false),
        ];
        if ($values['agency_id'] !== null && !$this->references->agencyExists($values['agency_id'])) {
            $input->fail('agency_id', 'Órgão inexistente.');
        }
        if ($type !== null && $values['location_kind'] !== null) {
            if ($type->mobile && $values['location_kind'] !== 'BASE') {
                $input->fail('location_kind', 'Recurso móvel usa o endereço como base; a posição atual vem das observações.');
            } elseif (!$type->mobile && $values['location_kind'] === 'BASE') {
                $input->fail('location_kind', 'Equipamento fixo usa local instalado ou planejado.');
            } elseif ($status === EquipmentStatus::NaoInstalado && $values['location_kind'] === 'INSTALADO') {
                $input->fail('location_kind', 'Equipamento ainda não instalado: use "planejado".');
            }
        }
        if ($type !== null) {
            $specsInput = $input->raw('specs');
            [$specs, $errors] = $type->validateSpecs(is_array($specsInput) ? $specsInput : []);
            $input->merge($errors);
            $values['specs'] = $specs;
        }
        $this->point->resolve($input, $values, $current, self::POINT, 'o local');
        $input->assertValid();

        return $values;
    }

    /**
     * Traduz violações de unicidade em erros de campo compreensíveis.
     *
     * @template T
     * @param callable(): T $write
     * @return T
     */
    private function persist(callable $write): mixed
    {
        try {
            return $write();
        } catch (ConstraintViolation $e) {
            throw match ($e->constraint) {
                'UX_EQUIPMENT_SERIAL' => new ValidationFailed(['serial_number' => 'Já existe equipamento deste fabricante com esta série.']),
                'UQ_EQUIPMENT_ASSET_TAG' => new ValidationFailed(['asset_tag' => 'Patrimônio já cadastrado em outro equipamento.']),
                'UQ_EQUIPMENT_CODE' => new ConcurrencyConflict('Outro cadastro simultâneo usou o mesmo código. Envie novamente.'),
                default => $e,
            };
        }
    }
}
