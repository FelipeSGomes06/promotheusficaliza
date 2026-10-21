<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\FineDocumentStatus;
use Fiscaliza\Domain\FineRules;
use Fiscaliza\Domain\Time;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\AgentRepository;
use Fiscaliza\Infrastructure\Repository\EquipmentRepository;
use Fiscaliza\Infrastructure\Repository\FineRepository;

/** Leituras da aba Multas, cada tela num único instante do banco (transação SNAPSHOT somente leitura). */
final class FineQuery
{
    public const PER_PAGE = 20;
    public const EVENTS_PER_PAGE = 20;

    public function __construct(
        private readonly Database $db,
        private readonly FineRepository $fines,
        private readonly EquipmentRepository $equipment,
        private readonly AgentRepository $agents,
        private readonly Clock $clock,
    ) {}

    /**
     * Página da lista + total + "A conferir" + "Pagamento informado", todos do mesmo filtro e do mesmo instante.
     *
     * @return array{rows: list<array<string, mixed>>, total: int, attention: int, paid: int, page: int, pages: int}
     */
    public function list(FineFilters $filters, int $page): array
    {
        $today = Time::localDate($this->clock->now());
        $result = $this->db->transaction(fn() => $this->fines->search($filters, $today, $page, self::PER_PAGE), true);
        foreach ($result['rows'] as &$row) {
            $row['attention'] = FineRules::attention($row, $today);
        }
        unset($row);

        return $result + ['page' => $page, 'pages' => max(1, (int) ceil($result['total'] / self::PER_PAGE))];
    }

    /**
     * Ficha: cadastro, conferências (a que vale na ocorrência, a posterior e as de identidades anteriores), histórico
     * paginado e o cadastro atual da origem, para comparar com o snapshot gravado.
     *
     * @return array<string, mixed>|null
     */
    public function detail(int $id, int $eventsPage): ?array
    {
        $today = Time::localDate($this->clock->now());

        return $this->db->transaction(function () use ($id, $eventsPage, $today): ?array {
            $f = $this->fines->find($id);
            if ($f === null) {
                return null;
            }
            $checks = $this->fines->checks($id);
            $subject = (int) $f['subject_version'];
            $events = $this->fines->events($id, $eventsPage, self::EVENTS_PER_PAGE);

            return [
                'f' => $f,
                'occurrence_check' => FineDocumentStatus::select($checks, $subject, 'OCORRENCIA'),
                'later_check' => FineDocumentStatus::select($checks, $subject, 'POSTERIOR'),
                'checks' => $checks,
                'events' => $events['rows'],
                'events_page' => $eventsPage,
                'events_pages' => max(1, (int) ceil($events['total'] / self::EVENTS_PER_PAGE)),
                'attention' => FineRules::attention($f, $today),
                'missing' => $f['record_state'] === 'RASCUNHO' ? FineRules::missingForRegistration($f) + FineRules::registrationConflicts($f) : [],
                'equipment' => $f['equipment_id'] === null ? null : $this->equipment->find((int) $f['equipment_id']),
                'agent' => $f['agent_id'] === null ? null : $this->agents->find((int) $f['agent_id']),
                'today' => $today,
            ];
        }, true);
    }
}
