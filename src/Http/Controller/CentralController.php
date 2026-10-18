<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Controller;

use Fiscaliza\Application\Filters;
use Fiscaliza\Domain\AgentRole;
use Fiscaliza\Domain\AgentStatus;
use Fiscaliza\Domain\EquipmentCatalog;
use Fiscaliza\Domain\EquipmentStatus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Central operacional: página (renderizada no servidor, útil sem JavaScript) e instantâneo JSON para atualização. */
final class CentralController extends Controller
{
    public function page(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $filters = Filters::fromQuery($request->getQueryParams());

        return $this->render($request, $response, 'central.twig', [
            'snapshot' => $this->services->central()->snapshot($filters),
            'filters' => $filters,
            'view' => ($request->getQueryParams()['visao'] ?? '') === 'lista' ? 'lista' : 'mapa',
            'options' => self::filterOptions($this->services->referenceRepository()->regions(), $this->services->referenceRepository()->agencies()),
        ]);
    }

    public function snapshot(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, $this->services->central()->snapshot(Filters::fromQuery($request->getQueryParams())));
    }

    /**
     * @param list<array<string, mixed>> $regions
     * @param list<array<string, mixed>> $agencies
     * @return array<string, mixed>
     */
    public static function filterOptions(array $regions, array $agencies): array
    {
        $categories = [];
        foreach (EquipmentCatalog::all() as $type) {
            $categories[] = ['value' => $type->code, 'label' => $type->label, 'group' => 'Equipamentos'];
        }
        foreach (AgentRole::cases() as $role) {
            $categories[] = ['value' => $role->category(), 'label' => $role->label(), 'group' => 'Agentes'];
        }
        $statuses = [];
        foreach (EquipmentStatus::cases() as $s) {
            $statuses[] = ['value' => $s->value, 'label' => $s->label(), 'group' => 'Equipamentos'];
        }
        foreach (AgentStatus::cases() as $s) {
            $statuses[] = ['value' => $s->value, 'label' => $s->label(), 'group' => 'Agentes'];
        }

        return ['categories' => $categories, 'statuses' => $statuses, 'regions' => $regions, 'agencies' => $agencies];
    }
}
