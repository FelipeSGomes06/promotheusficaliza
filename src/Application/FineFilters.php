<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\FineDocumentStatus;
use Fiscaliza\Domain\FineRules;
use Fiscaliza\Domain\Time;

/**
 * Filtros da lista de multas lidos da query string: só chaves conhecidas, valores de listas fechadas, datas estritas.
 * Lista, total e os três resumos usam exatamente este conjunto (FineRepository::search).
 */
final class FineFilters
{
    /** Situação do cadastro: chave pública => estados incluídos. */
    public const RECORDS = [
        'ativo' => ['RASCUNHO', 'REGISTRADO'], 'rascunho' => ['RASCUNHO'], 'registrado' => ['REGISTRADO'],
        'cancelado' => ['CANCELADO'], 'todos' => ['RASCUNHO', 'REGISTRADO', 'CANCELADO'],
    ];
    public const SORTS = ['ocorrencia' => 'Ocorrência mais recente', 'codigo' => 'Cadastro mais recente', 'atualizado' => 'Alterados recentemente'];

    /** @var array<string, string> */
    public array $errors = [];

    private function __construct(
        public readonly string $q = '',
        public readonly ?string $source = null,
        public readonly ?string $stage = null,
        public readonly ?int $agency = null,
        public readonly ?int $radar = null,
        public readonly ?int $agent = null,
        public readonly ?string $document = null,
        public readonly ?string $payment = null,
        public readonly ?string $from = null,
        public readonly ?string $until = null,
        public readonly string $record = 'ativo',
        public readonly string $sort = 'ocorrencia',
    ) {}

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $str = static fn(string $k): string => is_string($query[$k] ?? null) ? trim($query[$k]) : '';
        $pick = static fn(string $k, array $allowed): ?string => in_array($str($k), $allowed, true) ? $str($k) : null;
        $id = static function (string $k) use ($str): ?int {
            $v = filter_var($str($k), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            return $v === false ? null : $v;
        };
        $errors = [];
        $date = static function (string $k) use ($str, &$errors): ?string {
            $v = $str($k);
            if ($v === '') {
                return null;
            }
            $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $v);
            if ($d === false || $d->format('Y-m-d') !== $v) {
                $errors[$k] = 'Data inválida no filtro.';

                return null;
            }

            return $v;
        };
        $from = $date('de');
        $until = $date('ate');
        if ($from !== null && $until !== null && $from > $until) {
            $errors['ate'] = 'O fim do período é anterior ao início.';
            $until = null;
        }
        $filters = new self(
            mb_substr($str('q'), 0, 60),
            $pick('origem', array_keys(FineRules::SOURCES)),
            $pick('etapa', array_keys(FineRules::STAGES)),
            $id('orgao'),
            $id('radar'),
            $id('agente'),
            $pick('documentacao', array_keys(FineDocumentStatus::LABELS)),
            $pick('pagamento', array_keys(FineRules::PAYMENTS)),
            $from,
            $until,
            $pick('cadastro', array_keys(self::RECORDS)) ?? 'ativo',
            $pick('ordem', array_keys(self::SORTS)) ?? 'ocorrencia',
        );
        $filters->errors = $errors;

        return $filters;
    }

    /** @return list<string> */
    public function states(): array
    {
        return self::RECORDS[$this->record];
    }

    /**
     * Período inclusivo em datas locais convertido em intervalo UTC semiaberto [início de "de", início do dia após "até").
     *
     * @return array{0: ?string, 1: ?string}
     */
    public function utcRange(): array
    {
        $start = $this->from === null ? null : Time::toDb(new \DateTimeImmutable($this->from . ' 00:00:00', Time::local()));
        $end = $this->until === null ? null : Time::toDb((new \DateTimeImmutable($this->until . ' 00:00:00', Time::local()))->modify('+1 day'));

        return [$start, $end];
    }

    /** Filtros além da busca e da origem/etapa (abrem "Mais filtros"). */
    public function moreCount(): int
    {
        return count(array_filter([$this->agency, $this->radar, $this->agent, $this->document, $this->payment, $this->from, $this->until]))
            + ($this->record !== 'ativo' ? 1 : 0) + ($this->sort !== 'ocorrencia' ? 1 : 0);
    }

    public function isEmpty(): bool
    {
        return $this->toArray() === [];
    }

    /** @return array<string, string|int> parâmetros não padrão, para links (paginação, voltar à lista) */
    public function toArray(): array
    {
        return array_filter([
            'q' => $this->q, 'origem' => $this->source, 'etapa' => $this->stage, 'orgao' => $this->agency, 'radar' => $this->radar,
            'agente' => $this->agent, 'documentacao' => $this->document, 'pagamento' => $this->payment, 'de' => $this->from, 'ate' => $this->until,
            'cadastro' => $this->record === 'ativo' ? null : $this->record, 'ordem' => $this->sort === 'ocorrencia' ? null : $this->sort,
        ], static fn($v) => $v !== null && $v !== '');
    }
}
