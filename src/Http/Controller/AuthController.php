<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Controller;

use Fiscaliza\Application\AuthenticationFailed;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\ValidationFailed;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Login/logout e administração de contas (sem cadastro público). */
final class AuthController extends Controller
{
    public function loginForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($request->getAttribute('user') !== null) {
            return $this->redirect($response, '/');
        }

        return $this->render($request, $response, 'auth/login.twig', ['needs_csrf' => true, 'back' => $this->safeBack($request->getQueryParams()['voltar'] ?? null), 'username' => '']);
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->body($request);
        $username = is_string($data['username'] ?? null) ? $data['username'] : '';
        $back = $this->safeBack($data['voltar'] ?? null);
        try {
            $user = $this->services->auth()->attempt($username, is_string($data['password'] ?? null) ? $data['password'] : '', $this->clientIp($request));
        } catch (AuthenticationFailed $e) {
            return $this->render($request, $response, 'auth/login.twig', ['needs_csrf' => true, 'error' => $e->getMessage(), 'back' => $back, 'username' => mb_substr($username, 0, 40)], 401);
        }
        $session = $this->session($request);
        $session->regenerate();
        $session->set('user_id', (int) $user['id']);
        $session->set('auth_at', time());
        $session->set('last_activity', time());
        $session->set('_csrf', bin2hex(random_bytes(32)));
        $session->flash('success', "Olá, {$user['display_name']}. Alterações agora ficam registradas com sua autoria.");

        return $response->withStatus(303)->withHeader('Location', $this->services->config->basePath() . $back);
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->session($request)->destroy();

        return $this->redirect($response, '/');
    }

    public function accounts(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($request, $response, 'accounts/index.twig', ['users' => $this->services->auth()->users(), 'created' => null, 'errors' => []]);
    }

    public function createAccount(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->body($request);
        try {
            $created = $this->services->auth()->createUser((string) ($data['username'] ?? ''), (string) ($data['display_name'] ?? ''), (string) ($data['role'] ?? 'OPERADOR'));
        } catch (ValidationFailed $e) {
            return $this->render($request, $response, 'accounts/index.twig', ['users' => $this->services->auth()->users(), 'created' => null, 'errors' => $e->errors, 'values' => $data], 422);
        }

        // A senha gerada aparece uma única vez nesta resposta (não é guardada em sessão nem em log).
        return $this->render($request, $response, 'accounts/index.twig', [
            'users' => $this->services->auth()->users(), 'created' => ['username' => $data['username'], 'password' => $created['password']], 'errors' => [],
        ], 201);
    }

    /** @param array<string, string> $args */
    public function toggleAccount(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $this->services->auth()->setActive(self::id($args), ($this->body($request)['active'] ?? '') === '1', $this->userId($request));
            $this->session($request)->flash('success', 'Conta atualizada.');
        } catch (RuleViolation $e) {
            $this->session($request)->flash('error', $e->getMessage());
        }

        return $this->redirect($response, '/contas');
    }

    /** Destino pós-login: somente caminho interno do próprio prefixo (evita redirecionamento aberto). */
    private function safeBack(mixed $back): string
    {
        $base = $this->services->config->basePath();
        if (!is_string($back) || $back === '') {
            return '/';
        }
        $path = str_starts_with($back, $base . '/') ? substr($back, strlen($base)) : $back;

        return preg_match('#^/(?!/)[A-Za-z0-9/_\-?=&%.]*$#', $path) === 1 && !str_contains($path, '..') ? $path : '/';
    }

    private function clientIp(ServerRequestInterface $request): string
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? '';

        return is_string($ip) ? $ip : '';
    }
}
