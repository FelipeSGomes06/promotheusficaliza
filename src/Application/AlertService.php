<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use DateTimeImmutable;
use Fiscaliza\Domain\AlertFollowUp;
use Fiscaliza\Domain\AlertRules;
use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\AgentRepository;
use Fiscaliza\Infrastructure\Repository\AlertRepository;
use Fiscaliza\Infrastructure\Repository\EquipmentRepository;
use Fiscaliza\Infrastructure\Repository\MaintenanceRepository;
use Fiscaliza\Infrastructure\Repository\UserRepository;

/**
 * Mantém os alertas coerentes com as condições atuais e registra o trabalho humano: ciência, responsável (uma conta
 * ativa, pelo ID) e providências. Nada disso resolve a causa; o alerta só encerra quando a condição some.
 */
final class AlertService
{
    public function __construct(
        private readonly Database $db,
        private readonly AlertRepository $alerts,
        private readonly EquipmentRepository $equipment,
        private readonly AgentRepository $agents,
        private readonly MaintenanceRepository $maintenance,
        private readonly UserRepository $users,
        private readonly Clock $clock,
    ) {}

    /**
     * Abre alertas para condições novas e resolve os de condições encerradas, gravando o motivo. Idempotente: rodar
     * duas vezes seguidas não cria duplicatas (OPEN_KEY único). Deve rodar dentro da transação que mudou o estado.
     *
     * @return array{opened: int, resolved: int}
     */
    public function reconcile(): array
    {
        $now = $this->clock->now();
        $agents = [];
        foreach ($this->agents->allActive() as $a) {
            $agents[(int) $a['id']] = $a;
        }
        $conditions = AlertRules::conditions($this->equipment->allActive(), array_values($agents), $this->maintenance->open(), $now);
        $open = $this->alerts->openKeys();
        $at = Time::toDb($now);
        $opened = 0;
        $resolved = 0;
        foreach (array_diff_key($conditions, $open) as $c) {
            $opened += $this->alerts->open($c['kind'], $c['resource_id'], $c['reason'], $c['severity'], $c['message'], $at) ? 1 : 0;
        }
        foreach (array_diff_key($open, $conditions) as $key => $id) {
            [$kind, $resource, $reason] = explode(':', (string) $key) + ['', '0', ''];
            $this->alerts->resolve($id, $at, AlertRules::resolutionReason($kind, $reason, $agents[(int) $resource] ?? null));
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

    /**
     * Atribui ou reatribui o acompanhamento a uma conta ativa. Independe da ciência: reconhecer não atribui.
     *
     * @param array<string, mixed> $data assignee (ID da conta), version (FOLLOWUP_NO lido pelo formulário)
     */
    public function assign(int $alertId, array $data, int $userId): void
    {
        $errors = [];
        $to = $this->accountId($data['assignee'] ?? '', $errors);
        if ($to === null) {
            throw new ValidationFailed($errors + ['assignee' => 'Escolha a conta responsável pelo acompanhamento.']);
        }
        $this->db->transaction(function () use ($alertId, $data, $userId, $to): void {
            $alert = $this->pending($alertId, $data['version'] ?? null);
            $from = $alert['assignee_id'] === null ? null : (int) $alert['assignee_id'];
            if ($from === $to) {
                throw new ValidationFailed(['assignee' => 'Esta conta já é a responsável pelo acompanhamento.']);
            }
            $this->eligible($to);
            $at = Time::toDb($this->clock->now());
            $this->alerts->advance($alertId, $to, $alert['next_check_at']);
            $this->alerts->addFollowUp(['alert_id' => $alertId, 'kind' => 'ATRIBUICAO', 'from' => $from, 'to' => $to, 'by' => $userId, 'at' => $at]);
        });
    }

    /**
     * Registra uma providência depois da ciência, sem apagar as anteriores. A próxima conferência informada substitui a
     * anterior (vazia = não definida). O responsável muda só pela atribuição (assign), registrada à parte.
     *
     * @param array<string, mixed> $data action, note, next_check (Y-m-d\TH:i, hora de Marília), version
     */
    public function recordFollowUp(int $alertId, array $data, int $userId): void
    {
        $action = is_string($data['action'] ?? null) ? $data['action'] : '';
        $note = trim(is_string($data['note'] ?? null) ? $data['note'] : '');
        $errors = [];
        if (!isset(AlertFollowUp::ACTIONS[$action])) {
            $errors['action'] = 'Escolha o que foi feito.';
        }
        if (mb_strlen($note) > 500) {
            $errors['note'] = 'Use até 500 caracteres.';
        } elseif ($action === 'OUTRA' && $note === '') {
            $errors['note'] = 'Descreva a providência.';
        }
        $check = $this->nextCheck($data['next_check'] ?? '', $errors);
        if ($errors !== []) {
            throw new ValidationFailed($errors);
        }
        $this->db->transaction(function () use ($alertId, $data, $userId, $action, $note, $check): void {
            $alert = $this->pending($alertId, $data['version'] ?? null);
            if ($alert['state'] === 'ABERTO') {
                throw new RuleViolation('Registre a ciência antes de registrar providências.');
            }
            $this->alerts->advance($alertId, $alert['assignee_id'] === null ? null : (int) $alert['assignee_id'], $check);
            $this->alerts->addFollowUp(['alert_id' => $alertId, 'kind' => 'PROVIDENCIA', 'action_code' => $action, 'note' => $note ?: null, 'next_check_at' => $check,
                'by' => $userId, 'at' => Time::toDb($this->clock->now())]);
        });
    }

    /**
     * Alerta ainda pendente, travado para esta transação, na versão que o formulário leu.
     *
     * @return array<string, mixed>
     */
    private function pending(int $alertId, mixed $version): array
    {
        $alert = $this->alerts->lock($alertId) ?? throw new NotFound('Alerta não encontrado.');
        if ($alert['state'] === 'RESOLVIDO') {
            $when = Time::fromDb($alert['resolved_at']);
            throw new RuleViolation('Este alerta foi encerrado' . ($when ? ' em ' . Time::short($when) : '') . ' enquanto você preenchia o formulário. '
                . (AlertFollowUp::RESOLUTIONS[$alert['resolved_reason'] ?? ''] ?? ''));
        }
        if (!is_scalar($version) || (string) $version !== (string) $alert['followup_no']) {
            throw new ConcurrencyConflict('O acompanhamento deste alerta mudou depois que você abriu a ficha: outra pessoa registrou uma providência ou trocou o responsável.');
        }

        return $alert;
    }

    /** Só contas ativas recebem atribuições novas; o vínculo de uma conta desativada depois continua no histórico. */
    private function eligible(int $userId): void
    {
        $user = $this->users->find($userId);
        if ($user === null || !$user['active']) {
            throw new ValidationFailed(['assignee' => 'Esta conta não está ativa: escolha outra conta cadastrada em Contas.']);
        }
    }

    /** @param array<string, string> $errors */
    private function accountId(mixed $value, array &$errors): ?int
    {
        if ($value === '' || $value === null) {
            return null;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            $errors['assignee'] = 'Escolha uma conta da lista.';

            return null;
        }

        return $id;
    }

    /** @param array<string, string> $errors */
    private function nextCheck(mixed $value, array &$errors): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $local = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', trim($value), Time::local());
        $now = $this->clock->now();
        if ($local === false) {
            $errors['next_check'] = 'Informe data e hora válidas.';
        } elseif ($local <= $now) {
            $errors['next_check'] = 'Escolha um horário depois de agora.';
        } elseif ($local > $now->modify('+' . AlertFollowUp::MAX_CHECK_DAYS . ' days')) {
            $errors['next_check'] = 'Escolha até ' . AlertFollowUp::MAX_CHECK_DAYS . ' dias a partir de agora.';
        } else {
            return Time::toDb($local);
        }

        return null;
    }
}
