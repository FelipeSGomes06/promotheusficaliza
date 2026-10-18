<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Unit;

use Fiscaliza\Tests\Support\DatabaseSeparation;
use PHPUnit\Framework\TestCase;

/** Os testes de integração apagam o esquema: só rodam com evidência de banco de testes separado do principal. */
final class DatabaseSeparationTest extends TestCase
{
    private const MAIN = 'firebird:dbname=firebird/3050:/var/lib/firebird/data/fiscaliza.fdb;charset=UTF8;dialect=3';
    private const TEST = 'firebird:dbname=firebird/3050:/var/lib/firebird/data/fiscaliza_test.fdb;charset=UTF8;dialect=3';

    public function testSameTargetIsRefusedEvenWithDifferentOptions(): void
    {
        self::assertNotNull(DatabaseSeparation::problem(self::MAIN, 'firebird:dbname=FIREBIRD/3050:/var/lib/firebird/data/fiscaliza.fdb;charset=UTF8'));
    }

    public function testDifferentAliasesForTheSameFileAreRefused(): void
    {
        self::assertNotNull(DatabaseSeparation::problem(self::MAIN, 'firebird:dbname=firebird/3050:teste', '/var/lib/firebird/data/fiscaliza.fdb', '/var/lib/firebird/data/FISCALIZA.FDB'));
    }

    public function testSeparateDatabasesOrNoMainDatabaseAreAccepted(): void
    {
        self::assertNull(DatabaseSeparation::problem(self::MAIN, self::TEST, '/var/lib/firebird/data/fiscaliza.fdb', '/var/lib/firebird/data/fiscaliza_test.fdb'));
        self::assertNull(DatabaseSeparation::problem('', self::TEST), 'CI: só existe o banco de testes');
        self::assertNotNull(DatabaseSeparation::problem(self::MAIN, ''));
    }
}
