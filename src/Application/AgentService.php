<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\AgentRole;
use Fiscaliza\Domain\AgentStatus;
use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\NearestRegion;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Fiscaliza\Infrastructure\ConstraintViolation;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\AgentRepository;
use Fiscaliza\Infrastructure\Repository\ReferenceRepository;

/**
 * Casos de uso dos agentes (trânsito, guarda, polícia). A base é um ponto fixo marcado no mapa ou, sem ele, posto no
 * endereço; a rua vem da consulta do CEP (ou é digitada quando a consulta não a dá) e o bairro é derivado do ponto. A
 * última posição vem das observações e é guardada à parte.
 */
final class AgentService
{
    private const TRACKED = ['call_sign', 'role', 'agency_id', 'base_name', 'base_street', 'base_ref', 'base_city', 'base_cep', 'region_id', 'base_lat', 'base_lng'];
    /** Rótulos do histórico ("Cadastro alterado: …"), como a pessoa vê no formulário e na ficha. */
    private const LABELS = ['call_sign' => 'nome de exibição', 'role' => 'função', 'agency_id' => 'órgão', 'base_name' => 'nome da base',
        'base_street' => 'rua', 'base_ref' => 'número', 'base_city' => 'cidade', 'base_cep' => 'CEP', 'region_id' => 'bairro',
        'base_lat' => 'ponto no mapa', 'base_lng' => 'ponto no mapa'];
    private const PREFIX = ['TRANSITO' => 'AGT', 'GUARDA' => 'GCM', 'POLICIA' => 'POL'];

    /** @param \Closure(): AddressLocator $locator criado só quando um cadastro chega sem o ponto do mapa */
    public function __construct(
        private readonly Database $db,
        private readonly AgentRepository $agents,
        private readonly ReferenceRepository $references,
        private readonly History $history,
        private readonly AlertService $alerts,
        private readonly Clock $clock,
        private readonly \Closure $locator,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, ?int $actorId, string $source = 'MANUAL'): int
    {
        $input = new Input($data);
        $status = AgentStatus::tryFrom((string) $input->choice('status', array_map(static fn($s) => $s->value, AgentStatus::cases())));
        $values = $this->validate($input);
        $values['region_id'] = $this->regionAt((float) $values['base_lat'], (float) $values['base_lng']);
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
        $values += $this->derivedOnUpdate($current, $values);
        $this->db->transaction(function () use ($id, $version, $values, $current, $actorId): void {
            if (!$this->agents->updateVersioned($id, (int) $version, $values + ['updated_at' => Time::toDb($this->clock->now())])) {
                throw new ConcurrencyConflict('Outra pessoa alterou este agente depois que você abriu o formulário. Recarregue para ver a versão atual.');
            }
            $changes = History::diff(array_intersect_key($current, array_flip(self::TRACKED)), $values);
            if ($changes !== []) {
                $this->history->record('AGENTE', $id, 'ALTERACAO', $actorId, self::changeSummary(array_keys($changes)), ['alteracoes' => $changes]);
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

    /**
     * "Cadastro alterado: rua, CEP, ponto no mapa." Também refaz o texto de alterações antigas, gravadas com o nome
     * técnico dos campos ("base_street, region_id"), a partir das chaves guardadas no evento.
     *
     * @param list<string> $fields
     */
    public static function changeSummary(array $fields): string
    {
        return 'Cadastro alterado: ' . implode(', ', array_unique(array_map(static fn(string $f): string => self::LABELS[$f] ?? $f, $fields))) . '.';
    }

    /**
     * O que o formulário traz: nome, função, órgão, nome da base e o ponto (obrigatórios), CEP e rua (opcionais). O
     * ponto vem do mapa ou, sem ele, do endereço (resolvePoint); a rua chega preenchida pela consulta do CEP ou digitada
     * quando a consulta não a dá; número opcional (`BASE_REF`); cidade digitada (padrão Marília); o bairro é derivado do ponto.
     * Campos não lidos são ignorados.
     *
     * @return array<string, mixed>
     */
    private function validate(Input $input): array
    {
        $values = [
            'call_sign' => $input->text('call_sign', 40),
            'role' => $input->choice('role', array_map(static fn($r) => $r->value, AgentRole::cases())),
            'agency_id' => $input->int('agency_id', 1, PHP_INT_MAX),
            'base_name' => $input->text('base_name', 80),
            'base_street' => $input->text('base_street', 120, false),
            'base_ref' => $input->text('base_ref', 160, false),
            'base_city' => self::city($input->text('base_city', 80, false)),
            'base_cep' => $input->cep('base_cep'),
            'base_lat' => $input->coordinate('base_lat', 'lat', false),
            'base_lng' => $input->coordinate('base_lng', 'lng', false),
        ];
        if ($values['agency_id'] !== null && !$this->references->agencyExists($values['agency_id'])) {
            $input->fail('agency_id', 'Órgão inexistente.');
        }
        $this->resolvePoint($input, $values);
        $input->assertValid();

        return $values;
    }

    /**
     * Ponto da base: o do mapa (latitude e longitude vêm da tela, nunca digitadas) ou, sem mapa, o do endereço: a rua
     * informada ou a do CEP, localizada pelo provedor (ADR-0021). A consulta externa só acontece com o resto do
     * formulário válido e fora da transação; a rua achada pelo CEP passa a ser a rua gravada.
     *
     * @param array<string, mixed> $values
     */
    private function resolvePoint(Input $input, array &$values): void
    {
        $errors = $input->errors();
        if (isset($errors['base_lat']) || isset($errors['base_lng']) || ($values['base_lat'] !== null && $values['base_lng'] !== null)) {
            return;
        }
        if ($values['base_lat'] !== null || $values['base_lng'] !== null || ($values['base_street'] === null && $values['base_cep'] === null)) {
            $input->fail('base_lat', 'Marque a base no mapa ou, sem o mapa, informe o CEP e a rua.');

            return;
        }
        if ($errors !== []) {
            return;
        }
        $found = ($this->locator)()->locate($values['base_street'], $values['base_cep'], $values['base_ref'], $values['base_city']);
        if ($found['situacao'] !== 'ENCONTRADO') {
            $input->fail($found['situacao'] === 'INDISPONIVEL' ? 'base_lat' : 'base_street', AddressLocator::MESSAGES[$found['situacao']]);

            return;
        }
        $values['base_lat'] = $found['lat'];
        $values['base_lng'] = $found['lng'];
        $values['base_street'] = $found['rua'];
    }

    /**
     * Na edição, o bairro acompanha o ponto: é recalculado quando o ponto muda (ou se ainda não havia bairro); fora
     * isso, fica o gravado, inclusive o escolhido à mão em cadastros antigos.
     *
     * @param array<string, mixed> $current
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function derivedOnUpdate(array $current, array $values): array
    {
        $moved = number_format((float) $current['base_lat'], 6, '.', '') !== $values['base_lat']
            || number_format((float) $current['base_lng'], 6, '.', '') !== $values['base_lng'];
        $derived = [];
        if ($moved || $current['region_id'] === null) {
            $derived['region_id'] = $this->regionAt((float) $values['base_lat'], (float) $values['base_lng']);
        }

        return $derived;
    }

    /** Cidade da base: Marília é o padrão e fica vazia no banco (cadastros antigos não mudam ao salvar). */
    private static function city(?string $city): ?string
    {
        return $city === null || in_array(mb_strtolower($city), ['marília', 'marilia', 'marília/sp', 'marília / sp', 'marilia/sp'], true) ? null : $city;
    }

    /** Bairro de referência de centro mais próximo do ponto, a mesma régua do Planejar (ADR-0021). */
    private function regionAt(float $lat, float $lng): ?int
    {
        $region = NearestRegion::of($lat, $lng, $this->references->regions());

        return $region === null ? null : (int) $region['id'];
    }
}
