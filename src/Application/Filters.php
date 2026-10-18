<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\AgentRole;
use Fiscaliza\Domain\AgentStatus;
use Fiscaliza\Domain\EquipmentCatalog;
use Fiscaliza\Domain\EquipmentStatus;

/**
 * Filtros combinados da central e das listas (tipo/categoria, situação, bairro, órgão e, na central, busca por
 * texto e "só o que precisa de atenção"). Valores desconhecidos ou malformados são descartados; o servidor devolve
 * os filtros efetivamente aplicados.
 *
 * Um tipo de recurso (equipamento ou agente) entra no resultado somente se o filtro de categoria e o de situação,
 * quando preenchidos, contiverem ao menos um valor daquele tipo. Ex.: categoria "Radar" exclui agentes.
 */
final readonly class Filters
{
    private const MAX_VALUES = 30;

    /**
     * @param list<string> $categories códigos de tipo de equipamento ou AG_<papel>
     * @param list<string> $statuses   situações de equipamento ou de agente
     * @param list<int>    $regions
     * @param list<int>    $agencies
     */
    public function __construct(
        public array $categories = [],
        public array $statuses = [],
        public array $regions = [],
        public array $agencies = [],
        public string $text = '',
        public bool $attention = false,
    ) {}

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $categoryOptions = array_merge(array_keys(EquipmentCatalog::all()), array_map(static fn(AgentRole $r) => $r->category(), AgentRole::cases()));
        $statusOptions = array_merge(
            array_map(static fn(EquipmentStatus $s) => $s->value, EquipmentStatus::cases()),
            array_map(static fn(AgentStatus $s) => $s->value, AgentStatus::cases()),
        );

        return new self(
            self::pick($query['categoria'] ?? null, $categoryOptions),
            self::pick($query['situacao'] ?? null, $statusOptions),
            self::ids($query['bairro'] ?? null),
            self::ids($query['orgao'] ?? null),
            is_string($query['q'] ?? null) ? mb_substr(trim($query['q']), 0, 60) : '',
            ($query['atencao'] ?? '') === '1',
        );
    }

    /** @return list<string> */
    public function equipmentTypes(): array
    {
        return array_values(array_filter($this->categories, static fn($c) => EquipmentCatalog::get($c) !== null));
    }

    /** @return list<string> */
    public function agentRoles(): array
    {
        return array_values(array_filter(array_map(static fn($c) => AgentRole::fromCategory($c)?->value, $this->categories)));
    }

    /** @return list<string> */
    public function equipmentStatuses(): array
    {
        return array_values(array_filter($this->statuses, static fn($s) => EquipmentStatus::tryFrom($s) !== null));
    }

    /** @return list<string> */
    public function agentStatuses(): array
    {
        return array_values(array_filter($this->statuses, static fn($s) => AgentStatus::tryFrom($s) !== null));
    }

    public function includesEquipment(): bool
    {
        return ($this->categories === [] || $this->equipmentTypes() !== [])
            && ($this->statuses === [] || $this->equipmentStatuses() !== []);
    }

    public function includesAgents(): bool
    {
        return ($this->categories === [] || $this->agentRoles() !== [])
            && ($this->statuses === [] || $this->agentStatuses() !== []);
    }

    public function isEmpty(): bool
    {
        return $this->categories === [] && $this->statuses === [] && $this->regions === [] && $this->agencies === [] && $this->text === '' && !$this->attention;
    }

    /** @return array<string, list<string|int>|string> */
    public function toArray(): array
    {
        return ['categoria' => $this->categories, 'situacao' => $this->statuses, 'bairro' => $this->regions, 'orgao' => $this->agencies,
            'q' => $this->text, 'atencao' => $this->attention ? '1' : ''];
    }

    /** Query string equivalente (para links e paginação). */
    public function toQueryString(): string
    {
        return http_build_query(array_filter($this->toArray()), '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param list<string> $allowed
     * @return list<string>
     */
    private static function pick(mixed $raw, array $allowed): array
    {
        $values = is_array($raw) ? $raw : (is_string($raw) && $raw !== '' ? explode(',', $raw) : []);
        $values = array_filter($values, 'is_string');

        return array_slice(array_values(array_unique(array_intersect($values, $allowed))), 0, self::MAX_VALUES);
    }

    /** @return list<int> */
    private static function ids(mixed $raw): array
    {
        $values = is_array($raw) ? $raw : (is_string($raw) && $raw !== '' ? explode(',', $raw) : []);
        $ids = [];
        foreach ($values as $value) {
            $id = is_scalar($value) ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
            if ($id !== false) {
                $ids[] = $id;
            }
        }

        return array_slice(array_values(array_unique($ids)), 0, self::MAX_VALUES);
    }
}
