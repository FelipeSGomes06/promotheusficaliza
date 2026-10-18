<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Controller;

use Fiscaliza\Domain\EquipmentCatalog;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Páginas informativas e verificação de saúde mínima (sem versões, caminhos ou dados). */
final class PageController extends Controller
{
    public function about(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'about.twig', ['types' => EquipmentCatalog::all()]);
    }

    public function health(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->services->database()->value('SELECT 1 FROM RDB$DATABASE');
            $ok = true;
        } catch (\RuntimeException) {
            $ok = false;
        }

        return $this->json($response, ['status' => $ok ? 'ok' : 'indisponivel'], $ok ? 200 : 503);
    }
}
