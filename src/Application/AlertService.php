<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\AlertRules;
use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\AgentRepository;
use Fiscaliza\Infrastructure\Repository\AlertRepository;
use Fiscaliza\Infrastructure\Repository\EquipmentRepository;
use Fiscaliza\Infrastructure\Repository\MaintenanceRepository;

/** Mantém os alertas coerentes com as condições atuais e registra o reconhecimento humano. */
final class AlertService
{
    public function __construct(
        private readonly Database $db,
        private readonly AlertRepository $alerts,
        private readonly EquipmentRepository $equipment,
        private readonly AgentRepository $agents,
        private readonly MaintenanceRepository $maintenance,
        private readonly Clock $clock,
    ) {}

    /**
     * Abre alertas para condições novas e resolve os de condições encerradas. Idempotente: rodar duas vezes
     * seguidas não cria duplicatas (OPEN_KEY único). Deve rodar dentro da transação que mudou o estado.
     *
     * @return array{opened: int, resolved: int}
     */
    public function reconcile(): array
    {
        $now = $this->clock->now();
        $conditions = AlertRules::conditions($this->equipment->allActive(), $this->agents->allActive(), $this->maintenance->open(), $now);
        $open = $this->alerts->openKeys();
        $at = Time::toDb($now);
        $opened = 0;
        $resolved = 0;
        foreach (array_diff_key($conditions, $open) as $c) {
            $opened += $this->alerts->open($c['kind'], $c['resource_id'], $c['reason'], $c['severity'], $c['message'], $at) ? 1 : 0;
        }
        foreach (array_diff_key($open, $conditions) as $id) {
            $this->alerts->resolve($id, $at);
            $resolved++;
        }

        return ['opened' => $opened, 'resolved' => $resolved];
    }

    public function acknowledge(int $alertId, int $userId, ?string $note): void
    {
        $note = $note === null ? null : mb_substr(trim($note), 0, 300);
        $this->db->transaction(function () use ($alertId, $userId, $note): void {
            $alert = $this->alerts->find($alertId) ?? throw new NotFound('Alerta não encontrado.');
            if ($alert['state'] !== 'ABERTO') {
                throw new RuleViolation($alert['state'] === 'RESOLVIDO' ? 'Este alerta já foi resolvido: a causa deixou de existir.' : 'Este alerta já foi reconhecido.');
            }
            $this->alerts->acknowledge($alertId, $userId, Time::toDb($this->clock->now()), $note ?: null);
        });
    }
}
