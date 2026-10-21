<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Integration;

use Fiscaliza\Application\FineFilters;
use Fiscaliza\Application\FineService;
use Fiscaliza\Cli\Console;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Fiscaliza\Infrastructure\Migrator;
use Fiscaliza\Services;
use Fiscaliza\Tests\Support\FirebirdTestCase;
use Fiscaliza\Tests\Support\Parallel;

/** Aba Multas no Firebird real, com a identidade da aplicação: casos de uso, integridade, histórico e concorrência. */
final class FinesTest extends FirebirdTestCase
{
    private int $user;
    private int $radar;
    private int $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = (int) $this->services->auth()->createUser('operadora', 'Operadora Teste', 'OPERADOR', 'senha-de-teste-bem-longa')['id'];
        $this->radar = $this->createEquipment(['type_code' => 'RADAR', 'specs' => ['functions' => ['VELOCIDADE'], 'speed_limit_kmh' => '50', 'lanes' => '2']]);
        $this->agent = $this->createAgent();
    }

    /** @return array<string, mixed> */
    private function radarFine(array $override = []): array
    {
        return $override + [
            'command_uid' => FineService::newCommandUid(), 'description' => 'Excesso de velocidade de teste', 'occurred_at' => '2026-10-18T09:10',
            'infraction_kind' => 'VELOCIDADE', 'legal_reference' => 'Ref. fictícia', 'agency_id' => (string) $this->agencyId, 'external_ait' => 'DEMO-AIT-1',
            'street' => 'Avenida Sampaio Vidal', 'source_kind' => 'RADAR', 'equipment_id' => (string) $this->radar, 'operator_agent_id' => (string) $this->agent,
            'speed_limit_kmh' => '50', 'speed_measured_kmh' => '71', 'speed_considered_kmh' => '64', 'vehicle_plate' => 'abc-1d23', 'vehicle_uf' => 'SP',
            'vehicle_manufacturer' => 'Movix', 'vehicle_model' => 'Urbano', 'vehicle_species' => 'Passageiro', 'vehicle_renavam' => '00123456789',
            'radar_inmetro_record' => 'DEMO-INM-1', 'radar_certificate' => 'DEMO-CERT-1', 'radar_verified_on' => '2026-03-01', 'evidence_reference' => 'DEMO-IMG-1',
        ];
    }

    private function version(int $id): int
    {
        return (int) $this->services->fineRepository()->find($id)['version_no'];
    }

    /** @param array<string, mixed> $data */
    private function cmd(int $id, array $data = []): array
    {
        return $data + ['version' => (string) $this->version($id), 'command_uid' => FineService::newCommandUid()];
    }

    private function rows(string $table, int $fineId): int
    {
        return (int) $this->services->database()->value("SELECT COUNT(*) FROM {$table} WHERE " . ($table === 'FINE' ? 'ID' : 'FINE_ID') . ' = ?', [$fineId]);
    }

    public function testDraftIsCompletedRegisteredAndKeepsInternalCodeApartFromAit(): void
    {
        $fines = $this->services->fines();
        $id = $fines->create(['command_uid' => FineService::newCommandUid(), 'description' => 'Rascunho mínimo'], $this->user);
        $draft = $this->services->fineRepository()->find($id);
        self::assertSame('RASCUNHO', $draft['record_state']);
        self::assertSame(sprintf('DEMO-MUL-%06d', $id), $draft['code']);
        self::assertNull($draft['external_ait'], 'código interno nunca supre o AIT');
        self::assertNotEmpty($this->services->fineQuery()->detail($id, 1)['missing']);

        try {
            $fines->register($id, $this->cmd($id), $this->user);
            self::fail('rascunho incompleto não registra');
        } catch (ValidationFailed $e) {
            self::assertArrayHasKey('vehicle_plate', $e->errors);
        }
        $fines->update($id, $this->cmd($id, $this->radarFine()), $this->user);
        $fines->register($id, $this->cmd($id), $this->user);

        $f = $this->services->fineRepository()->find($id);
        self::assertSame('REGISTRADO', $f['record_state']);
        self::assertSame('DEMO-AIT-1', $f['external_ait']);
        self::assertSame('ABC1D23', $f['vehicle_plate'], 'placa sem pontuação de apresentação');
        self::assertSame('RADAR', $f['source_snapshot']['origem']);
        self::assertSame((int) $this->agent, $f['source_snapshot']['agente']['id'], 'radar com operador associado é aceito');
        self::assertSame('operador associado', $f['source_snapshot']['agente']['papel']);
        $types = array_column($this->services->fineRepository()->events($id, 1, 20)['rows'], 'event_type');
        self::assertSame(['REGISTRO', 'EDICAO_RASCUNHO', 'CRIACAO'], $types);
        self::assertSame(0, (int) $this->services->database()->value("SELECT COUNT(*) FROM EQUIPMENT WHERE TYPE_CODE = 'VEICULO'"), 'veículo autuado não entra no inventário');
    }

    public function testSourceIsCheckedOnTheServerEvenWithManipulatedPost(): void
    {
        $fines = $this->services->fines();
        $camera = $this->createEquipment();
        $guard = $this->createAgent(['call_sign' => 'Atena 99', 'role' => 'GUARDA']);
        $cases = [
            ['equipment_id' => (string) $camera, 'equipment_id'],
            ['operator_agent_id' => (string) $guard, 'operator_agent_id'],
            ['source_kind' => 'AGENTE', 'agent_id' => (string) $this->agent, 'equipment_id'],
            ['source_kind' => 'AGENTE', 'equipment_id' => '', 'operator_agent_id' => '', 'agent_id' => (string) $guard, 'agent_id'],
            ['agent_id' => (string) $this->agent, 'agent_id'],
            ['source_kind' => '', 'source_kind'],
            ['vehicle_plate' => '', 'vehicle_plate'], // radar lê a placa na imagem: obrigatória até no rascunho
        ];
        foreach ($cases as $case) {
            $field = array_pop($case);
            try {
                $fines->create($this->radarFine($case), $this->user);
                self::fail("deveria recusar {$field}");
            } catch (ValidationFailed $e) {
                self::assertArrayHasKey($field, $e->errors, json_encode($case));
            }
        }
        self::assertSame(0, (int) $this->services->database()->value('SELECT COUNT(*) FROM FINE'));

        // Campos que só o servidor define são ignorados.
        $id = $fines->create($this->radarFine(['record_state' => 'CANCELADO', 'created_source' => 'SEMENTE', 'source_snapshot' => '{"x":1}', 'created_by' => '999']), $this->user);
        $f = $this->services->fineRepository()->find($id);
        self::assertSame(['RASCUNHO', 'MANUAL', []], [$f['record_state'], $f['created_source'], $f['source_snapshot']]);
        self::assertSame($this->user, (int) $f['created_by']);
    }

    public function testRenavamKeepsLeadingZerosAndMayBeJustifiedlyMissing(): void
    {
        $fines = $this->services->fines();
        $id = $fines->create($this->radarFine(), $this->user);
        self::assertSame('00123456789', $this->services->fineRepository()->find($id)['vehicle_renavam']);
        $found = $this->services->fineQuery()->list(FineFilters::fromQuery(['q' => '00123456789']), 1);
        self::assertSame([$id], array_map('intval', array_column($found['rows'], 'id')));

        try {
            $fines->create($this->radarFine(['vehicle_renavam' => '123456789', 'external_ait' => 'DEMO-AIT-2']), $this->user);
            self::fail('RENAVAM malformado');
        } catch (ValidationFailed $e) {
            self::assertSame(['vehicle_renavam'], array_keys($e->errors));
        }
        $missing = $fines->create($this->radarFine(['vehicle_renavam' => '', 'renavam_missing_reason' => 'Documento não apresentado', 'external_ait' => 'DEMO-AIT-3']), $this->user);
        $fines->register($missing, $this->cmd($missing), $this->user);
        self::assertSame('REGISTRADO', $this->services->fineRepository()->find($missing)['record_state']);
    }

    public function testSpeedsStayTranscribedAndIncoherentSpeedBlocksOnlyRegistration(): void
    {
        $fines = $this->services->fines();
        $id = $fines->create($this->radarFine(['speed_measured_kmh' => '', 'speed_considered_kmh' => '']), $this->user);
        $f = $this->services->fineRepository()->find($id);
        self::assertNull($f['speed_measured_kmh'], 'vazio não vira zero');
        self::assertNull($f['speed_considered_kmh']);

        $low = $fines->create($this->radarFine(['speed_considered_kmh' => '50', 'external_ait' => 'DEMO-AIT-9']), $this->user);
        try {
            $fines->register($low, $this->cmd($low), $this->user);
            self::fail('considerada igual ao limite não registra');
        } catch (ValidationFailed $e) {
            self::assertArrayHasKey('speed_considered_kmh', $e->errors);
        }
        self::assertSame('RASCUNHO', $this->services->fineRepository()->find($low)['record_state'], 'o rascunho continua disponível');
        $this->expectException(ValidationFailed::class);
        $fines->create($this->radarFine(['speed_considered_kmh' => '80']), $this->user); // considerada > medida
    }

    public function testDocumentChecksAreTemporalAndRetificationRequiresANewOne(): void
    {
        $fines = $this->services->fines();
        $id = $fines->create($this->radarFine(), $this->user);
        $fines->register($id, $this->cmd($id), $this->user);
        self::assertSame('NAO_VERIFICADA', $this->services->fineRepository()->find($id)['occ_doc_status']);

        $base = ['scope' => 'OCORRENCIA', 'checked_at' => '2026-10-18T10:00', 'source_type' => 'CRLV_APRESENTADO', 'source_reference' => 'CRLV-e fictício'];
        try {
            $fines->addCheck($id, $this->cmd($id, ['document_status' => 'REGULARIDADE_INFORMADA', 'source_type' => 'NAO_INFORMADA', 'source_reference' => ''] + $base), $this->user);
            self::fail('regularidade sem fonte');
        } catch (ValidationFailed $e) {
            self::assertEqualsCanonicalizing(['source_type', 'source_reference'], array_keys($e->errors));
        }
        try {
            $fines->addCheck($id, $this->cmd($id, ['document_status' => 'PENDENCIA_INFORMADA'] + $base), $this->user);
            self::fail('pendência sem descrição');
        } catch (ValidationFailed $e) {
            self::assertSame(['pending_items'], array_keys($e->errors));
        }
        $fines->addCheck($id, $this->cmd($id, ['document_status' => 'PENDENCIA_INFORMADA', 'pending_items' => 'Licenciamento não comprovado'] + $base), $this->user);
        // Regularidade posterior não corrige a ocorrência.
        $fines->addCheck($id, $this->cmd($id, ['scope' => 'POSTERIOR', 'effective_on' => '2026-10-18', 'document_status' => 'REGULARIDADE_INFORMADA', 'checked_at' => '2026-10-18T11:00'] + $base), $this->user);
        // Lançamento atrasado (conferido antes) não substitui a conferência mais recente.
        $fines->addCheck($id, $this->cmd($id, ['document_status' => 'REGULARIDADE_INFORMADA', 'checked_at' => '2026-10-18T09:30'] + $base), $this->user);
        self::assertSame('PENDENCIA_INFORMADA', $this->services->fineRepository()->find($id)['occ_doc_status']);
        $detail = $this->services->fineQuery()->detail($id, 1);
        self::assertSame('REGULARIDADE_INFORMADA', $detail['later_check']['document_status']);

        try {
            $fines->addCheck($id, $this->cmd($id, ['scope' => 'POSTERIOR', 'effective_on' => '2026-10-17', 'document_status' => 'REGULARIDADE_INFORMADA'] + $base), $this->user);
            self::fail('posterior antes da ocorrência');
        } catch (ValidationFailed $e) {
            self::assertArrayHasKey('effective_on', $e->errors);
        }

        // Retificar a placa: nova versão do sujeito, conferências antigas legíveis mas sem valer.
        $fines->update($id, $this->cmd($id, ['vehicle_plate' => 'ABC1D24', 'reason' => 'Placa transcrita com erro'] + $this->radarFine()), $this->user);
        $f = $this->services->fineRepository()->find($id);
        self::assertSame([2, 'NAO_VERIFICADA'], [(int) $f['subject_version'], $f['occ_doc_status']]);
        self::assertSame(3, $this->rows('FINE_DOCUMENT_CHECK', $id));
        $detail = $this->services->fineQuery()->detail($id, 1);
        self::assertNull($detail['occurrence_check']);
        self::assertNull($detail['later_check']);
        $event = $detail['events'][0];
        self::assertSame(['RETIFICACAO', 'Placa transcrita com erro', ['de' => 1, 'para' => 2]], [$event['event_type'], $event['reason'], $event['payload']['sujeito']]);
        self::assertSame(['antes' => 'ABC1D23', 'depois' => 'ABC1D24'], $event['payload']['alteracoes']['vehicle_plate']);

        // Só uma observação: não invalida a conferência.
        $fines->addCheck($id, $this->cmd($id, ['document_status' => 'REGULARIDADE_INFORMADA'] + $base), $this->user);
        $fines->update($id, $this->cmd($id, ['notes' => 'Observação nova', 'reason' => 'Complemento do relato', 'vehicle_plate' => 'ABC1D24'] + $this->radarFine()), $this->user);
        self::assertSame([2, 'REGULARIDADE_INFORMADA'], [(int) $this->services->fineRepository()->find($id)['subject_version'], $this->services->fineRepository()->find($id)['occ_doc_status']]);
    }

    public function testProgressDimensionsAreIndependentAndCorrectionsNeedAReason(): void
    {
        $fines = $this->services->fines();
        $id = $fines->create($this->radarFine(), $this->user);
        $fines->register($id, $this->cmd($id), $this->user);
        $progress = ['process_stage' => 'PENALIDADE_NOTIFICADA', 'payment_status' => 'PAGAMENTO_INFORMADO', 'paid_on' => '2026-10-18', 'paid_amount' => '130,16',
            'payment_reference' => 'DEMO-PAG-1', 'contest_status' => 'EM_ANALISE', 'contest_kind' => 'RECURSO_1', 'contest_submitted_on' => '2026-10-18',
            'contest_reference' => 'DEMO-PROT-1', 'nominal_amount' => '195,23', 'penalty_issued_on' => '2026-10-18', 'appeal_due_on' => '2026-11-30'];
        $warnings = $fines->updateProgress($id, $this->cmd($id, $progress), $this->user);
        self::assertSame(['O valor pago informado difere do valor nominal: o sistema não calcula descontos.'], $warnings);
        $f = $this->services->fineRepository()->find($id);
        self::assertSame(['PAGAMENTO_INFORMADO', 'EM_ANALISE', 'REGISTRADO', 13016, 19523], [$f['payment_status'], $f['contest_status'], $f['record_state'], (int) $f['paid_amount_cents'], (int) $f['nominal_amount_cents']]);

        foreach ([[['process_stage' => 'ENCERRAMENTO_INFORMADO'], 'closure_note'], [['paid_on' => '2026-10-19'], 'paid_on'], [['paid_on' => '2026-10-10'], 'paid_on'],
            [['penalty_received_on' => '2026-10-17'], 'penalty_received_on'], [['paid_amount' => '120,00'], 'progress_reason']] as [$change, $field]) {
            try {
                $fines->updateProgress($id, $this->cmd($id, $change + $progress), $this->user);
                self::fail("deveria recusar {$field}");
            } catch (ValidationFailed $e) {
                self::assertArrayHasKey($field, $e->errors, json_encode($change));
            }
        }
        $fines->updateProgress($id, $this->cmd($id, ['paid_amount' => '120,00', 'progress_reason' => 'Comprovante relido'] + $progress), $this->user);
        $event = $this->services->fineQuery()->detail($id, 1)['events'][0];
        self::assertEquals(['antes' => 13016, 'depois' => 12000], $event['payload']['alteracoes']['paid_amount_cents']);
        self::assertSame('Comprovante relido', $event['reason']);
    }

    public function testCancellationIsInternalReadOnlyAndRepeatedCommandsDoNotDuplicate(): void
    {
        $fines = $this->services->fines();
        $id = $fines->create($this->radarFine(), $this->user);
        $cancel = $this->cmd($id, ['reason' => 'Lançado em duplicidade']);
        $fines->cancel($id, $cancel, $this->user);
        $fines->cancel($id, $cancel, $this->user); // mesmo comando reenviado: sem novo efeito
        self::assertSame(2, $this->rows('FINE_EVENT', $id));
        $f = $this->services->fineRepository()->find($id);
        self::assertSame(['CANCELADO', 'MANUAL', 'Lançado em duplicidade'], [$f['record_state'], $f['cancel_source'], $f['cancel_reason']]);
        self::assertSame('NAO_INFORMADO', $f['payment_status'], 'cancelar não mexe no andamento');

        foreach ([fn() => $fines->register($id, $this->cmd($id), $this->user), fn() => $fines->cancel($id, $this->cmd($id, ['reason' => 'de novo']), $this->user),
            fn() => $fines->update($id, $this->cmd($id, $this->radarFine()), $this->user)] as $attempt) {
            try {
                $attempt();
                self::fail('cancelado é somente leitura');
            } catch (RuleViolation) {
            }
        }
    }

    public function testVersionAndCommandKeyProtectAgainstLostUpdatesAndReplays(): void
    {
        $fines = $this->services->fines();
        $data = $this->radarFine();
        $id = $fines->create($data, $this->user);
        self::assertSame($id, $fines->create($data, $this->user), 'reenvio idêntico devolve o mesmo registro');
        self::assertSame(1, (int) $this->services->database()->value('SELECT COUNT(*) FROM FINE'));
        self::assertSame(1, $this->rows('FINE_EVENT', $id));

        try {
            $fines->create(['description' => 'Outro conteúdo com a mesma chave'] + $data, $this->user);
            self::fail('mesma chave, outros dados');
        } catch (ConcurrencyConflict) {
        }
        $other = (int) $this->services->auth()->createUser('outra', 'Outra Pessoa', 'OPERADOR', 'senha-de-teste-bem-longa')['id'];
        try {
            $fines->create($data, $other);
            self::fail('mesma chave, outra conta: não devolve o registro alheio');
        } catch (ConcurrencyConflict) {
        }

        // Duas abas com a mesma versão: a segunda recebe conflito e nada é sobrescrito.
        $tabA = $this->cmd($id, ['notes' => 'aba A', 'command_uid' => FineService::newCommandUid()] + $data);
        $tabB = $this->cmd($id, ['notes' => 'aba B', 'command_uid' => FineService::newCommandUid()] + $data);
        $fines->update($id, $tabA, $this->user);
        try {
            $this->newServices()->fines()->update($id, $tabB, $this->user);
            self::fail('versão consumida');
        } catch (ConcurrencyConflict) {
        }
        self::assertSame('aba A', $this->services->fineRepository()->find($id)['notes']);
        $fines->update($id, $tabA, $this->user); // reenvio da aba A depois do conflito: reconhecido, sem novo evento
        self::assertSame(2, $this->rows('FINE_EVENT', $id));
        self::assertArrayHasKey('notes', $fines->pendingChanges($id, $tabB));
    }

    public function testAitIsUniquePerAgencyAndAbsentAitsNeverCollide(): void
    {
        $fines = $this->services->fines();
        $fines->create($this->radarFine(['external_ait' => 'demo-ait-x ']), $this->user);
        try {
            $fines->create($this->radarFine(['external_ait' => 'DEMO-AIT-X']), $this->user);
            self::fail('mesmo AIT no mesmo órgão');
        } catch (ValidationFailed $e) {
            self::assertArrayHasKey('external_ait', $e->errors);
        }
        $other = $this->services->referenceRepository()->insertAgency('GCD', 'Outro órgão (teste)', 'GUARDA');
        $fines->create($this->radarFine(['external_ait' => 'DEMO-AIT-X', 'agency_id' => (string) $other, 'source_note' => 'teste']), $this->user);
        $fines->create($this->radarFine(['external_ait' => '']), $this->user);
        $fines->create($this->radarFine(['external_ait' => '']), $this->user);
        self::assertSame(4, (int) $this->services->database()->value('SELECT COUNT(*) FROM FINE'));
    }

    public function testConcurrentCreationsResolveAitAndCommandKeyInTheDatabase(): void
    {
        $uid = FineService::newCommandUid();
        $results = Parallel::run([
            ['fine-create', 'DEMO-AIT-CORRIDA', (string) $this->agencyId, FineService::newCommandUid(), (string) $this->user],
            ['fine-create', 'DEMO-AIT-CORRIDA', (string) $this->agencyId, FineService::newCommandUid(), (string) $this->user],
        ], $this->clock->now());
        self::assertSame(1, count(array_filter($results, static fn($r) => $r['ok'])), json_encode($results));
        self::assertSame(1, (int) $this->services->database()->value("SELECT COUNT(*) FROM FINE WHERE AIT_KEY = 'DEMO-AIT-CORRIDA'"));

        $results = Parallel::run([
            ['fine-create', 'DEMO-AIT-MESMA-CHAVE', (string) $this->agencyId, $uid, (string) $this->user],
            ['fine-create', 'DEMO-AIT-MESMA-CHAVE', (string) $this->agencyId, $uid, (string) $this->user],
        ], $this->clock->now());
        self::assertSame(2, count(array_filter($results, static fn($r) => $r['ok'])), 'a repetição concorrente devolve o resultado já gravado: ' . json_encode($results));
        self::assertSame(1, (int) $this->services->database()->value("SELECT COUNT(*) FROM FINE WHERE AIT_KEY = 'DEMO-AIT-MESMA-CHAVE'"));
        self::assertSame(1, (int) $this->services->database()->value('SELECT COUNT(*) FROM FINE_EVENT WHERE EVENT_UID = ?', [$uid]));
    }

    public function testFailureWritingHistoryRollsBackTheWholeOperation(): void
    {
        $owner = self::ownerDatabase();
        $user = strtoupper(self::config()->require('DB_USER'));
        $owner->pdo()->exec("REVOKE INSERT ON FINE_EVENT FROM USER {$user}");
        try {
            $this->newServices()->fines()->create($this->radarFine(), $this->user); // conexão nova: privilégios lidos de novo
            self::fail('sem histórico não há multa');
        } catch (\RuntimeException) {
        } finally {
            $owner->pdo()->exec("GRANT INSERT ON FINE_EVENT TO USER {$user}");
            $owner->disconnect();
        }
        self::assertSame(0, (int) $this->services->database()->value('SELECT COUNT(*) FROM FINE'), 'nenhuma multa sem evento');
    }

    public function testApplicationIdentityCannotRewriteFineHistory(): void
    {
        $fines = $this->services->fines();
        $id = $fines->create($this->radarFine(), $this->user);
        $fines->addCheck($id, $this->cmd($id, ['scope' => 'OCORRENCIA', 'checked_at' => '2026-10-18T10:00', 'document_status' => 'NAO_VERIFICADA', 'source_type' => 'NAO_INFORMADA', 'check_reason' => 'teste']), $this->user);
        $db = $this->services->database();
        foreach (['DELETE FROM FINE_EVENT', 'UPDATE FINE_EVENT SET SUMMARY = SUMMARY', 'DELETE FROM FINE_DOCUMENT_CHECK', 'UPDATE FINE_DOCUMENT_CHECK SET REASON = REASON'] as $sql) {
            try {
                $db->execute($sql);
                self::fail("deveria ser negado: {$sql}");
            } catch (\RuntimeException) {
            }
        }
        self::assertSame(2, $this->rows('FINE_EVENT', $id));
        // Migração repetida com privilégio indevido herdado: o REVOKE devolve a tabela ao modo só-acréscimo.
        $owner = self::ownerDatabase();
        $user = self::config()->require('DB_USER');
        $owner->pdo()->exec('GRANT UPDATE, DELETE ON FINE_EVENT TO USER ' . strtoupper($user));
        (new Migrator($owner, self::ROOT . '/database/migrations'))->grantApplicationAccess($user);
        $owner->disconnect();
        $this->expectException(\RuntimeException::class);
        $this->newServices()->database()->execute('DELETE FROM FINE_EVENT');
    }

    public function testSnapshotSurvivesLaterArchivingAndArchivedRadarStillServesHistory(): void
    {
        $fines = $this->services->fines();
        $id = $fines->create($this->radarFine(), $this->user);
        $fines->register($id, $this->cmd($id), $this->user);
        $this->services->equipment()->archive($this->radar, 'Retirado da via', $this->user);
        $f = $this->services->fineRepository()->find($id);
        self::assertFalse($f['source_snapshot']['radar']['arquivado'], 'o retrato da ocorrência não muda');
        self::assertNotNull($f['equipment_archived_at']);

        $late = $fines->create($this->radarFine(['external_ait' => 'DEMO-AIT-HIST']), $this->user);
        $fines->register($late, $this->cmd($late), $this->user);
        self::assertTrue($this->services->fineRepository()->find($late)['source_snapshot']['radar']['arquivado'], 'arquivado é aceito para registro histórico, com o fato registrado');
    }

    public function testListTotalsAndSummariesShareTheSameFilterWithoutDuplicates(): void
    {
        $fines = $this->services->fines();
        $ids = [];
        for ($i = 1; $i <= 23; $i++) {
            $ids[] = $fines->create($this->radarFine(['external_ait' => "DEMO-AIT-L{$i}", 'occurred_at' => sprintf('2026-10-%02dT08:00', 1 + $i % 15)]), $this->user);
        }
        $agentFine = $fines->create($this->radarFine(['source_kind' => 'AGENTE', 'equipment_id' => '', 'operator_agent_id' => '', 'agent_id' => (string) $this->agent,
            'infraction_kind' => 'PARADA_FAIXA', 'external_ait' => 'DEMO-AIT-AG']), $this->user);
        $fines->register($ids[0], $this->cmd($ids[0]), $this->user);
        foreach (range(1, 3) as $n) { // várias conferências e eventos para uma só multa
            $fines->addCheck($ids[0], $this->cmd($ids[0], ['scope' => 'OCORRENCIA', 'checked_at' => "2026-10-18T0{$n}:00", 'document_status' => 'REGULARIDADE_INFORMADA',
                'source_type' => 'CRLV_APRESENTADO', 'source_reference' => 'CRLV']), $this->user);
        }
        $fines->updateProgress($ids[0], $this->cmd($ids[0], ['process_stage' => 'AUTUACAO', 'payment_status' => 'PAGAMENTO_INFORMADO', 'contest_status' => 'NAO_INFORMADA',
            'paid_on' => '2026-10-18', 'paid_amount' => '10', 'payment_reference' => 'X']), $this->user);
        $fines->cancel($ids[1], $this->cmd($ids[1], ['reason' => 'duplicado']), $this->user);

        $query = $this->services->fineQuery();
        $all = $query->list(FineFilters::fromQuery([]), 1);
        self::assertSame([23, 20, 2], [$all['total'], count($all['rows']), $all['pages']], 'cancelado sai do padrão; 20 por página');
        self::assertSame(22, $all['attention'], 'rascunhos + o registrado completo não conta');
        self::assertSame(1, $all['paid']);
        $page2 = $query->list(FineFilters::fromQuery([]), 2);
        self::assertSame([23, 3], [$page2['total'], count($page2['rows'])], 'paginação não muda o denominador');
        self::assertSame([], array_intersect(array_column($all['rows'], 'id'), array_column($page2['rows'], 'id')));

        $radarOnly = $query->list(FineFilters::fromQuery(['origem' => 'RADAR', 'cadastro' => 'todos']), 1);
        self::assertSame([23, 1], [$radarOnly['total'], $radarOnly['paid']]);
        self::assertSame(1, $query->list(FineFilters::fromQuery(['origem' => 'AGENTE']), 1)['total']);
        self::assertSame(24, $query->list(FineFilters::fromQuery(['agente' => (string) $this->agent, 'cadastro' => 'todos']), 1)['total'], 'agente principal ou operador associado');
        self::assertSame(1, $query->list(FineFilters::fromQuery(['documentacao' => 'REGULARIDADE_INFORMADA']), 1)['total']);
        self::assertSame(1, $query->list(FineFilters::fromQuery(['q' => sprintf('DEMO-MUL-%06d', $agentFine)]), 1)['total']);
        $day = $query->list(FineFilters::fromQuery(['de' => '2026-10-02', 'ate' => '2026-10-02', 'cadastro' => 'todos']), 1);
        self::assertSame(2, $day['total'], 'período local inclusivo');
    }

    public function testNonDestructiveSeedAddsTheScenarioOnceAndRefusesUnmarkedDatabases(): void
    {
        $owner = new Services(self::config()->with(['DB_USER' => self::config()->require('DB_MIGRATION_USER'), 'DB_PASSWORD' => self::config()->require('DB_MIGRATION_PASSWORD')]), self::ROOT, null, $this->clock);
        $console = new Console(new Services(self::config(), self::ROOT, null, $this->clock));
        $run = static function () use ($console): array {
            ob_start();
            $code = $console->run(['demo:seed-multas']);

            return [$code, (string) ob_get_clean()];
        };
        self::assertSame(1, $run()[0], 'sem o marcador do conjunto sintético, recusa');

        foreach (['EQUIPMENT', 'AGENT', 'REGION'] as $table) { // a demonstração recria referências e recursos
            $owner->database()->execute("DELETE FROM {$table}");
        }
        $owner->database()->execute('DELETE FROM AGENCY');
        $owner->seeder()->run(false); // demonstração compacta já inclui as multas
        self::assertSame(12, (int) $owner->database()->value("SELECT COUNT(*) FROM FINE WHERE CREATED_SOURCE = 'SEMENTE'"));
        $manual = $this->services->fines()->create(['command_uid' => FineService::newCommandUid(), 'description' => 'Registro manual que deve ficar'], $this->user);

        [$code, $out] = $run();
        self::assertSame([0, true], [$code, str_contains($out, 'nada foi duplicado')]);
        // Carga da versão anterior: atualizada no lugar (relatos), com evento, sem apagar nem duplicar.
        $owner->metaRepository()->set('fines_seed', 'multas-v1', 12, '2026-10-18 15:00:00');
        [$code, $out] = $run();
        self::assertSame([0, true], [$code, str_contains($out, 'atualizado')], $out);
        self::assertSame('multas-v2', $owner->metaRepository()->text('fines_seed'));
        self::assertSame(0, (int) $owner->database()->value("SELECT COUNT(*) FROM FINE WHERE CREATED_SOURCE = 'SEMENTE' AND RECORD_STATE <> 'CANCELADO' AND NOTES IS NULL"));
        self::assertSame(0, (int) $owner->database()->value("SELECT COUNT(*) FROM FINE WHERE SOURCE_KIND = 'RADAR' AND VEHICLE_PLATE IS NULL"), 'todo registro de radar tem placa');
        self::assertSame(13, (int) $owner->database()->value('SELECT COUNT(*) FROM FINE'));
        $owner->metaRepository()->set('fines_seed', 'versao-antiga', 0, '2026-10-18 15:00:00');
        self::assertSame(1, $run()[0], 'carga de outra versão já presente: conflito, sem tocar em nada');
        self::assertSame(13, (int) $owner->database()->value('SELECT COUNT(*) FROM FINE'));
        self::assertNotNull($this->services->fineRepository()->find($manual));
        self::assertSame('versao-antiga', $owner->metaRepository()->text('fines_seed'), 'a recusa não grava marcador');

        $states = array_count_values(array_map('rtrim', array_column($owner->database()->all('SELECT RECORD_STATE FROM FINE'), 'record_state')));
        ksort($states);
        self::assertSame(['CANCELADO' => 1, 'RASCUNHO' => 2, 'REGISTRADO' => 10], $states);
        self::assertSame(2, (int) $owner->database()->value('SELECT MAX(SUBJECT_VERSION) FROM FINE'), 'caso de retificação da identidade');
        self::assertSame(1, (int) $owner->database()->value("SELECT COUNT(*) FROM FINE WHERE PAYMENT_STATUS = 'PAGAMENTO_INFORMADO' AND CONTEST_STATUS = 'EM_ANALISE'"));
    }
}
