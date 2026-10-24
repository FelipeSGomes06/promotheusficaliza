<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Controller;

use Fiscaliza\Domain\EquipmentCatalog;
use Fiscaliza\Domain\MaintenanceUrgency;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Manutenção (quadro e ciclo das ordens), reconhecimento de alertas e análise preventiva. */
final class OperationsController extends Controller
{
    public function maintenance(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $today = Time::localDate($this->services->clock()->now());
        // Uma lista por andamento e urgência: primeiro o que está em execução, depois o que precisa começar.
        $groups = ['EM_EXECUCAO' => [], 'URGENTE' => [], 'PROXIMA' => [], 'FUTURA' => []];
        $running = [];
        foreach ($this->services->maintenanceRepository()->open() as $order) {
            $urgency = MaintenanceUrgency::classify((string) $order['priority'], $order['due_date'], $today);
            $row = $order + ['urgency' => $urgency];
            if ($order['state'] === 'EM_EXECUCAO') {
                $groups['EM_EXECUCAO'][] = $row;
                $running[(int) $order['equipment_id']] = (int) $order['id'];
            } else {
                $groups[$urgency->isUrgent() ? 'URGENTE' : $urgency->value][] = $row;
            }
        }

        return $this->render($request, $response, 'maintenance/index.twig', [
            'groups' => $groups, 'running' => $running, 'today' => $today, 'types' => EquipmentCatalog::all(),
            'closed' => $this->services->maintenanceRepository()->recentlyClosed(),
        ]);
    }

    /** @param array<string, string> $args */
    public function open(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $equipmentId = self::id($args);

        return $this->attempt(
            $request,
            $response,
            "/equipamentos/{$equipmentId}#manutencao",
            'Ordem de manutenção aberta.',
            fn() => $this->services->maintenance()->open($equipmentId, $this->body($request), $this->userId($request)),
        );
    }

    /** @param array<string, string> $args */
    public function start(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = self::id($args);

        return $this->attempt(
            $request,
            $response,
            $this->back($request),
            'Intervenção iniciada: equipamento em manutenção.',
            fn() => $this->services->maintenance()->start($id, $this->userId($request)),
        );
    }

    /** @param array<string, string> $args */
    public function complete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = self::id($args);

        return $this->attempt(
            $request,
            $response,
            $this->back($request),
            'Ordem concluída e situação do equipamento registrada.',
            fn() => $this->services->maintenance()->complete($id, $this->body($request), $this->userId($request)),
        );
    }

    /** @param array<string, string> $args */
    public function cancel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = self::id($args);

        return $this->attempt(
            $request,
            $response,
            $this->back($request),
            'Ordem cancelada.',
            fn() => $this->services->maintenance()->cancel($id, (string) ($this->body($request)['reason'] ?? ''), $this->userId($request)),
        );
    }

    /** @param array<string, string> $args */
    public function acknowledge(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = self::id($args);
        $note = $this->body($request)['note'] ?? null;
        try {
            $this->services->alerts()->acknowledge($id, $this->userId($request), is_string($note) ? $note : null);
            $message = ['success', 'Alerta reconhecido. Ele será resolvido automaticamente quando a causa deixar de existir.'];
        } catch (RuleViolation $e) {
            $message = ['warning', $e->getMessage()];
        }
        if ($request->getHeaderLine('Accept') === 'application/json') {
            return $this->json($response, ['ok' => $message[0] === 'success', 'message' => $message[1]], $message[0] === 'success' ? 200 : 409);
        }
        $this->session($request)->flash(...$message);

        return $this->redirect($response, $this->back($request));
    }

    public function analysis(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $run = $this->services->analysisRepository()->latestRun();
        // Ordem que já cuida de cada equipamento, lida agora (a análise pode ser de outro dia): a em execução ou a
        // primeira da fila (open() ordena imediatas e depois pela data).
        $orders = [];
        foreach ($this->services->maintenanceRepository()->open() as $order) {
            $id = (int) $order['equipment_id'];
            if (!isset($orders[$id]) || $order['state'] === 'EM_EXECUCAO') {
                $orders[$id] = $order;
            }
        }

        return $this->render($request, $response, 'analysis/index.twig', [
            'run' => $run,
            'results' => $run === null ? [] : $this->services->analysisRepository()->results((int) $run['id']),
            'types' => EquipmentCatalog::all(),
            'orders' => $orders,
            'today' => Time::localDate($this->services->clock()->now()),
        ]);
    }

    public function recalculate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $run = $this->services->analysis()->run($this->userId($request));
        $this->session($request)->flash('success', "Análise recalculada (execução #{$run['id']}).");

        return $this->redirect($response, '/antecipacao');
    }

    /** Volta para a página de origem informada pelo formulário, apenas se for caminho interno conhecido. */
    private function back(ServerRequestInterface $request): string
    {
        $back = $this->body($request)['voltar'] ?? '';

        return is_string($back) && preg_match('#^/(manutencao|central|equipamentos/\d+)(\#[a-z-]+)?$#', $back) === 1 ? $back : '/manutencao';
    }

    private function attempt(ServerRequestInterface $request, ResponseInterface $response, string $back, string $success, callable $action): ResponseInterface
    {
        try {
            $action();
            $this->session($request)->flash('success', $success);
        } catch (ValidationFailed $e) {
            $this->session($request)->flash('error', implode(' ', $e->errors));
        } catch (RuleViolation|ConcurrencyConflict $e) {
            $this->session($request)->flash('error', $e->getMessage());
        }

        return $this->redirect($response, $back);
    }
}
