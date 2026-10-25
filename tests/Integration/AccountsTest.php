<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Integration;

use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Tests\Support\FirebirdTestCase;
use Fiscaliza\Tests\Support\Interleaving;

/** Contas no Firebird real: a instalação nunca fica sem administrador ativo, nem com duas desativações ao mesmo tempo. */
final class AccountsTest extends FirebirdTestCase
{
    private function activeAdmins(): int
    {
        return (int) $this->services->database()->value("SELECT COUNT(*) FROM APP_USER WHERE ROLE = 'ADMIN' AND ACTIVE = TRUE");
    }

    public function testTheLastActiveAdminCannotBeDeactivated(): void
    {
        $auth = $this->services->auth();
        $a = (int) $auth->createUser('admin.a', 'Admin A', 'ADMIN', 'senha-de-teste-bem-longa')['id'];
        $b = (int) $auth->createUser('admin.b', 'Admin B', 'ADMIN', 'senha-de-teste-bem-longa')['id'];
        $auth->setActive($b, false, $a);

        $this->expectException(RuleViolation::class);
        $this->expectExceptionMessage('ao menos um administrador ativo');
        $auth->setActive($a, false, $b);
    }

    /** A02 da auditoria de 25/10/2026: A desativa B enquanto B desativa A, antes de a primeira confirmar. */
    public function testCrossDeactivationAtTheSameTimeKeepsOneActiveAdmin(): void
    {
        $auth = $this->services->auth();
        $a = (int) $auth->createUser('admin.a', 'Admin A', 'ADMIN', 'senha-de-teste-bem-longa')['id'];
        $b = (int) $auth->createUser('admin.b', 'Admin B', 'ADMIN', 'senha-de-teste-bem-longa')['id'];
        $one = Interleaving::connect($this->newServices());
        $two = Interleaving::connect($this->newServices());
        // WITH LOCK trava ao ler as linhas: a primeira pausa depois do seu UPDATE, com a contagem feita e as linhas travadas.
        [$first, $r1] = Interleaving::case(fn() => $one->auth()->setActive($b, false, $a), '/^UPDATE APP_USER SET ACTIVE/');
        [$second, $r2] = Interleaving::case(fn() => $two->auth()->setActive($a, false, $b), '/(?!)/'); // não pausa

        $first->start();  // A trava e conta os administradores e desativa B, ainda sem confirmar
        $second->start(); // B vai até o fim enquanto A está aberta: sem a trava, contaria 2 e desativaria A
        $first->resume(); // A desativa B e confirma

        self::assertTrue($r1['ok'], (string) $r1['message']);
        self::assertSame('ConcurrencyConflict', $r2['error'], 'a segunda desativação é recusada, não confirmada em falso');
        self::assertSame(1, $this->activeAdmins());
    }
}
