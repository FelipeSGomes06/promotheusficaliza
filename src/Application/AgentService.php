<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\AgentRole;
use Fiscaliza\Domain\AgentStatus;
use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Fiscaliza\Infrastructure\ConstraintViolation;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\AgentRepository;
use Fiscaliza\Infrastructure\Repository\ReferenceRepository;

/**
 * Casos de uso dos agentes (trânsito, guarda, polícia). A base é o endereço de trabalho; a última posição
 * vem das observações e é guardada à parte.
 */
final class AgentService
{
    private const TRACKED = ['call_sign', 'role', 'agency_id', 'base_name', 'base_street', 'base_ref', 'base_cep', 'region_id', 'base_lat', 'base_lng'];
    private const PREFIX = ['TRANSITO' => 'AGT', 'GUARDA' => 'GCM', 'POLICIA' => 'POL'];

    public function __construct(
        private readonly Database $db,
        private readonly AgentRepository $agents,
        private readonly ReferenceRepository $references,
        private readonly History $history,
        private readonly AlertService $alerts,
        private readonly Clock $clock,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, ?int $actorId, string $source = 'MANUAL'): int
    {
        $input = new Input($data);
        $status = AgentStatus::tryFrom((string) $input->choice('status', array_map(static fn($s) => $s->value, AgentStatus::cases())));
        $values = $this->validate($input);
        $now = Time::toDb($this->clock->now());

        return $this->db->transaction(function () use ($values, $status, $now, $actorId, $source): int {
            $row = $values + [
                'code' => $this->agents->nextCode(self::PREFIX[$values['role']]),
                'status' => ($status ?? AgentStatus::ForaDeServico)->value,
                'status_changed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            try {
                $id = $this->agents->insert($row);
            } catch (ConstraintViolation $e) {
                throw $e->constraint === 'UQ_AGENT_CODE' ? new ConcurrencyConflict('Outro cadastro simultâneo usou o mesmo código. Envie novamente.') : $e;
            }
            $this->history->record('AGENTE', $id, 'CADASTRO', $actorId, "Cadastro de {$row['call_sign']} ({$row['code']}).", ['valores' => array_intersect_key($row, array_flip(self::TRACKED))], $source);
            $this->alerts->reconcile();

            return $id;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data, int $actorId): void
    {
        $input = new Input($data);
        $version = $input->int('version', 1, PHP_INT_MAX);
        $values = $this->validate($input);
        $current = $this->agents->find($id) ?? throw new NotFound('Agente não encontrado.');
        if ($current['archived_at'] !== null) {
            throw new RuleViolation('Agente arquivado não pode ser editado.');
        }
        $this->db->transaction(function () use ($id, $version, $values, $current, $actorId): void {
            if (!$this->agents->updateVersioned($id, (int) $version, $values + ['updated_at' => Time::toDb($this->clock->now())])) {
                throw new ConcurrencyConflict('Outra pessoa alterou este agente depois que você abriu o formulário. Recarregue para ver a versão atual.');
            }
            $changes = History::diff(array_intersect_key($current, array_flip(self::TRACKED)), $values);
            if ($changes !== []) {
                $this->history->record('AGENTE', $id, 'ALTERACAO', $actorId, 'Cadastro alterado: ' . implode(', ', array_keys($changes)) . '.', ['alteracoes' => $changes]);
            }
        });
    }

    public function changeStatus(int $id, string $target, ?int $actorId, string $source = 'MANUAL', ?string $note = null): void
    {
        $status = AgentStatus::tryFrom($target) ?? throw new ValidationFailed(['status' => 'Situação inválida.']);
        $this->db->transaction(function () use ($id, $status, $actorId, $source, $note): void {
            $current = $this->agents->lock($id) ?? throw new NotFound('Agente não encontrado.');
            if ($current['archived_at'] !== null) {
                throw new RuleViolation('Agente arquivado não muda de situação.');
            }
            $from = AgentStatus::from($current['status']);
            if ($from === $status) {
                return;
            }
            $now = Time::toDb($this->clock->now());
            $this->agents->updateState($id, ['status' => $status->value, 'status_changed_at' => $now, 'updated_at' => $now]);
            $summary = "{$from->label()} → {$status->label()}" . ($note ? ": {$note}" : '');
            $this->history->record('AGENTE', $id, 'SITUACAO', $actorId, $summary, ['de' => $from->value, 'para' => $status->value], $source);
            $this->alerts->reconcile();
        });
    }

    public function archive(int $id, string $reason, int $actorId): void
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 300) {
            throw new ValidationFailed(['reason' => 'Informe o motivo do arquivamento (até 300 caracteres).']);
        }
        $this->db->transaction(function () use ($id, $reason, $actorId): void {
            $current = $this->agents->lock($id) ?? throw new NotFound('Agente não encontrado.');
            if ($current['archived_at'] !== null) {
                throw new RuleViolation('Agente já está arquivado.');
            }
            $now = Time::toDb($this->clock->now());
            $this->agents->updateState($id, ['archived_at' => $now, 'updated_at' => $now]);
            $this->history->record('AGENTE', $id, 'ARQUIVAMENTO', $actorId, "Arquivado: {$reason}", ['motivo' => $reason]);
            $this->alerts->reconcile();
        });
    }

    /** @return array<string, mixed> */
    private function validate(Input $input): array
    {
        $values = [
            'call_sign' => $input->text('call_sign', 40),
            'role' => $input->choice('role', array_map(static fn($r) => $r->value, AgentRole::cases())),
            'agency_id' => $input->int('agency_id', 1, PHP_INT_MAX),
            'base_name' => $input->text('base_name', 80),
            'base_street' => $input->text('base_street', 120, false),
            'base_ref' => $input->text('base_ref', 160, false),
            'base_cep' => $input->cep('base_cep'),
            'region_id' => $input->int('region_id', 1, PHP_INT_MAX, false),
            'base_lat' => $input->coordinate('base_lat', 'lat'),
            'base_lng' => $input->coordinate('base_lng', 'lng'),
        ];
        if ($values['agency_id'] !== null && !$this->references->agencyExists($values['agency_id'])) {
            $input->fail('agency_id', 'Órgão inexistente.');
        }
        if ($values['region_id'] !== null && !$this->references->regionExists($values['region_id'])) {
            $input->fail('region_id', 'Bairro inexistente.');
        }
        $input->assertValid();

        return $values;
    }
}
