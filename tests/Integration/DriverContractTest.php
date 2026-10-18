<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Integration;

use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Fiscaliza\Infrastructure\ConstraintViolation;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Tests\Support\FirebirdTestCase;

/** Contrato PHP ↔ PDO_FIREBIRD ↔ Firebird 5 usado pela aplicação (ver docs/evidencias/2026-10-18-prova-firebird.md). */
final class DriverContractTest extends FirebirdTestCase
{
    public function testUtf8TextBlobDecimalAndBooleanRoundTrip(): void
    {
        $text = 'Marília — Rua São João, ç ã é ü 😀';
        $id = $this->createEquipment(['notes' => $text, 'specs' => ['resolution' => '4K', 'night_vision' => '1'], 'lat' => '-22.213912']);
        $row = $this->services->equipmentRepository()->find($id);

        self::assertSame($text, $row['notes']);
        self::assertSame(-22.213912, $row['lat']);
        self::assertSame(['resolution' => '4K', 'night_vision' => true, 'color_image' => false, 'ptz' => false], $row['specs']);
        $this->services->auth()->createUser('booleano', 'Teste booleano', 'OPERADOR', 'senha-de-teste-longa');
        self::assertTrue($this->services->database()->value('SELECT ACTIVE FROM APP_USER WHERE USERNAME = ?', ['booleano']));
        self::assertSame('Marília', $row['city'], 'valor padrão UTF-8 definido no DDL');
    }

    public function testInsertReturningAndOffsetFetchPagination(): void
    {
        $ids = [];
        foreach (range(1, 5) as $i) {
            $ids[] = $this->createEquipment(['serial_number' => "S-{$i}"]);
        }
        self::assertSame($ids, array_values(array_unique($ids)));
        $page = $this->services->equipmentRepository()->search(new \Fiscaliza\Application\Filters(), '', false, 'codigo', 2, 2);

        self::assertSame(5, $page['total']);
        self::assertSame(['CAM-0003', 'CAM-0004'], array_column($page['rows'], 'code'));
    }

    public function testFailureInsideTransactionRollsBackEveryWrite(): void
    {
        $db = $this->services->database();
        try {
            $db->transaction(function (Database $db): void {
                $this->services->referenceRepository()->insertAgency('TMP', 'Temporário', 'GUARDA');
                $this->services->referenceRepository()->insertAgency('TMP', 'Duplicado', 'GUARDA');
            });
            self::fail('esperava violação de unicidade');
        } catch (ConstraintViolation $e) {
            self::assertSame('UQ_AGENCY_CODE', $e->constraint);
        }
        self::assertSame(0, (int) $db->value("SELECT COUNT(*) FROM AGENCY WHERE CODE = 'TMP'"));
        self::assertFalse($db->pdo()->inTransaction());
    }

    public function testLockWaitIsBoundedAndBecomesConcurrencyConflict(): void
    {
        $id = $this->createEquipment();
        $a = $this->newServices()->database();
        $b = $this->newServices()->database();
        $a->pdo()->beginTransaction();
        $a->execute('UPDATE EQUIPMENT SET NOTES = ? WHERE ID = ?', ['sessão A', $id]);

        $started = microtime(true);
        try {
            $b->execute('UPDATE EQUIPMENT SET NOTES = ? WHERE ID = ?', ['sessão B', $id]);
            self::fail('esperava conflito');
        } catch (ConcurrencyConflict) {
            self::assertLessThan(5.0, microtime(true) - $started, 'a espera respeita o timeout de instrução');
        } finally {
            $a->pdo()->rollBack();
        }
        self::assertSame(1, $b->execute('UPDATE EQUIPMENT SET NOTES = ? WHERE ID = ?', ['sessão B', $id]), 'conexão continua utilizável');
    }

    public function testApplicationIdentityCannotRewriteHistoryOrChangeSchema(): void
    {
        $this->createEquipment();
        $db = $this->services->database();
        foreach (['DELETE FROM RESOURCE_EVENT', 'UPDATE RESOURCE_EVENT SET SUMMARY = SUMMARY', 'DROP TABLE AGENCY', 'CREATE TABLE INVASORA (X INT)'] as $sql) {
            try {
                $db->execute($sql);
                self::fail("deveria ser negado: {$sql}");
            } catch (\RuntimeException $e) {
                self::assertStringNotContainsString('firebird:', $e->getMessage(), 'mensagem pública sem DSN');
            }
        }
        self::assertGreaterThan(0, (int) $db->value('SELECT COUNT(*) FROM RESOURCE_EVENT'));
    }
}
