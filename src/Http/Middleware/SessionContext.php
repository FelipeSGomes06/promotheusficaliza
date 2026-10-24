<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Middleware;

use Fiscaliza\Application\AuthService;
use Fiscaliza\Http\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Retoma a sessão quando o navegador já tem o cookie, aplica expiração por inatividade e absoluta, e carrega
 * a conta ativa do banco a cada requisição (desativação e troca de papel valem imediatamente).
 */
final class SessionContext implements MiddlewareInterface
{
    public function __construct(private readonly Session $session, private readonly AuthService $auth) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->session->resumeIfPresent($request->getCookieParams());
        $user = null;
        $userId = $this->session->get('user_id');
        if (is_int($userId)) {
            $now = time();
            $idle = $now - (int) $this->session->get('last_activity', 0) > Session::IDLE_SECONDS;
            $tooOld = $now - (int) $this->session->get('auth_at', 0) > Session::ABSOLUTE_SECONDS;
            $reset = false;
            try {
                $user = ($idle || $tooOld) ? null : $this->auth->activeUser($userId);
                // Senha redefinida depois deste login: a sessão deixa de valer (a pessoa entra com a senha nova).
                $reset = $user !== null && (int) ($user['credential_no'] ?? 0) !== (int) $this->session->get('credential_no', 0);
                $user = $reset ? null : $user;
            } catch (\Fiscaliza\Infrastructure\DatabaseUnavailable) {
                // Banco fora: a conta não pôde ser conferida; RequireRole exibirá a página de indisponibilidade.
                return $handler->handle($request->withAttribute('session', $this->session)->withAttribute('user', null)->withAttribute('database_unavailable', true));
            }
            if ($user === null) {
                $this->session->remove('user_id');
                $this->session->regenerate();
                $this->session->flash('warning', $reset ? 'A senha desta conta foi redefinida pelo administrador. Entre com a senha nova.' : 'Sua sessão expirou. Entre novamente para continuar.');
            } else {
                $this->session->set('last_activity', $now);
            }
        }

        return $handler->handle($request->withAttribute('session', $this->session)->withAttribute('user', $user));
    }
}
