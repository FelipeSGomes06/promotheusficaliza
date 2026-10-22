<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\Clock;
use Fiscaliza\Domain\FineDocumentStatus;
use Fiscaliza\Domain\FineRules;
use Fiscaliza\Domain\RuleViolation;
use Fiscaliza\Domain\Time;
use Fiscaliza\Domain\ValidationFailed;
use Fiscaliza\Infrastructure\ConcurrencyConflict;
use Fiscaliza\Infrastructure\ConstraintViolation;
use Fiscaliza\Infrastructure\Database;
use Fiscaliza\Infrastructure\Repository\AgentRepository;
use Fiscaliza\Infrastructure\Repository\EquipmentRepository;
use Fiscaliza\Infrastructure\Repository\FineRepository;
use Fiscaliza\Infrastructure\Repository\ReferenceRepository;

/**
 * Casos de uso da aba Multas: rascunho, edição/retificação, registro, conferência documental, andamento e cancelamento
 * interno. Cada mutação é uma transação curta com bloqueio da multa (depois equipamento, depois agente), versão do
 * formulário, um evento em FINE_EVENT e chave de comando idempotente. Nada aqui emite auto, consulta órgão ou cobra.
 */
final class FineService
{
    /** Campos do cadastro (comparados no histórico). AGENT_ID guarda o agente principal ou o operador associado ao radar. */
    public const CADASTRO = ['description', 'occurred_at', 'infraction_kind', 'legal_reference', 'infraction_code', 'infraction_detail', 'agency_id',
        'external_ait', 'ait_missing_reason', 'street', 'address_ref', 'region_id', 'lat', 'lng', 'notes', 'source_kind', 'equipment_id', 'agent_id',
        'source_note', 'speed_limit_kmh', 'speed_measured_kmh', 'speed_considered_kmh', 'radar_details', 'evidence_reference', 'vehicle_plate',
        'vehicle_uf', 'vehicle_manufacturer', 'vehicle_model', 'vehicle_species', 'vehicle_color', 'vehicle_year', 'vehicle_renavam', 'renavam_missing_reason'];

    public const PROGRESS = ['process_stage', 'payment_status', 'contest_status', 'contest_kind', 'nominal_amount_cents', 'autuation_issued_on',
        'autuation_received_on', 'penalty_issued_on', 'penalty_received_on', 'payment_due_on', 'defense_due_on', 'appeal_due_on', 'paid_on',
        'paid_amount_cents', 'payment_reference', 'contest_submitted_on', 'contest_reference', 'contest_decided_on', 'contest_result_note', 'closed_on',
        'closure_reference', 'closure_note'];

    public const PROGRESS_LABELS = ['process_stage' => 'Fase', 'payment_status' => 'Pagamento', 'contest_status' => 'Defesa ou recurso',
        'contest_kind' => 'Tipo de defesa ou recurso', 'nominal_amount_cents' => 'Valor da multa', 'nominal_amount' => 'Valor da multa',
        'autuation_issued_on' => 'Aviso de infração enviado em', 'autuation_received_on' => 'Aviso de infração recebido em',
        'penalty_issued_on' => 'Aviso da multa enviado em', 'penalty_received_on' => 'Aviso da multa recebido em',
        'payment_due_on' => 'Vencimento', 'defense_due_on' => 'Prazo para defesa', 'appeal_due_on' => 'Prazo para recurso',
        'paid_on' => 'Pago em', 'paid_amount_cents' => 'Valor pago', 'paid_amount' => 'Valor pago',
        'payment_reference' => 'Comprovante do pagamento', 'contest_submitted_on' => 'Defesa ou recurso entregue em', 'contest_reference' => 'Número do protocolo',
        'contest_decided_on' => 'Resposta em', 'contest_result_note' => 'O que o órgão respondeu', 'closed_on' => 'Encerrada em',
        'closure_reference' => 'Documento do encerramento', 'closure_note' => 'Como terminou', 'progress_reason' => 'Motivo da correção', 'check_reason' => 'Motivo'];

    /** Datas de fatos já ocorridos (não podem estar no futuro nem antes da ocorrência). Prazos e vencimentos podem. */
    private const FACT_DATES = ['autuation_issued_on', 'autuation_received_on', 'penalty_issued_on', 'penalty_received_on', 'paid_on', 'contest_submitted_on', 'contest_decided_on', 'closed_on'];

    /** Informações cuja correção exige motivo (a anterior continua no histórico). */
    private const CORRECTIONS = ['paid_on', 'paid_amount_cents', 'payment_reference', 'contest_decided_on', 'contest_result_note', 'closed_on', 'closure_reference', 'closure_note'];

    public function __construct(
        private readonly Database $db,
        private readonly FineRepository $fines,
        private readonly ReferenceRepository $references,
        private readonly EquipmentRepository $equipment,
        private readonly AgentRepository $agents,
        private readonly Clock $clock,
    ) {}

    /** Chave de comando para um formulário novo: aleatória, sem dado pessoal, mantida ao reenviar o mesmo formulário. */
    public static function newCommandUid(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Cadastro gravado → nomes dos campos do formulário (data/hora local, operador do radar, dados do medidor).
     *
     * @param array<string, mixed> $fine
     * @return array<string, mixed>
     */
    public static function formValues(array $fine): array
    {
        $values = array_intersect_key($fine, array_flip(self::CADASTRO));
        $values['occurred_at'] = Time::fromDb($fine['occurred_at'])?->setTimezone(Time::local())->format('Y-m-d\TH:i');
        if ($fine['source_kind'] === 'RADAR') {
            $values['operator_agent_id'] = $fine['agent_id'];
            $values['agent_id'] = null;
        }
        foreach (['meter_kind', 'inmetro_record', 'certificate', 'verified_on'] as $key) {
            $values['radar_' . $key] = $fine['radar_details'][$key] ?? null;
        }

        return $values;
    }

    /** @param array<string, mixed> $data campos do formulário + command_uid */
    public function create(array $data, ?int $actor, string $source = 'MANUAL'): int
    {
        self::assertAuthor($actor, $source);
        $input = new Input($data);
        $uid = self::uid($input);
        $values = $this->validate($input);
        $hash = self::fingerprint('CRIACAO', 0, 0, $actor, $values);

        return (int) $this->once($uid, $hash, $actor, fn() => $this->db->transaction(function () use ($values, $uid, $hash, $actor, $source): int {
            $this->lockSources($values);
            $now = $this->now();
            $row = $values + ['record_state' => 'RASCUNHO', 'ait_key' => FineRules::aitKey($values['external_ait']), 'occ_doc_status' => FineDocumentStatus::NOT_CHECKED,
                'created_at' => $now, 'updated_at' => $now, 'created_by' => $actor, 'updated_by' => $actor, 'created_source' => $source];
            $row['attention_count'] = count(FineRules::attention($row));
            $id = $this->persist(fn() => $this->fines->insert($row));
            $this->event($id, $uid, $hash, 'CRIACAO', $source, $actor, 'Rascunho criado: ' . $values['description'], null, ['valores' => $values]);

            return $id;
        }));
    }

    /**
     * Edição de rascunho ou retificação de registro (esta exige motivo e mantém os campos mínimos). Mudar a identidade
     * do veículo ou a data/hora da ocorrência avança SUBJECT_VERSION: as conferências anteriores ficam no histórico,
     * mas a ficha volta a "Não verificada" até uma nova conferência.
     *
     * @param array<string, mixed> $data campos do formulário + version + command_uid (+ reason se registrado)
     */
    public function update(int $id, array $data, ?int $actor, string $source = 'MANUAL'): void
    {
        self::assertAuthor($actor, $source);
        $input = new Input($data);
        $uid = self::uid($input);
        $version = (int) $input->int('version', 1, PHP_INT_MAX);
        $current = $this->fines->find($id) ?? throw new NotFound('Multa não encontrada.');
        $reason = $input->text('reason', 300, $current['record_state'] === 'REGISTRADO');
        $values = $this->validate($input);
        $hash = self::fingerprint('EDICAO', $id, $version, $actor, $values + ['reason' => $reason]);

        $this->once($uid, $hash, $actor, fn() => $this->db->transaction(function () use ($id, $version, $values, $reason, $uid, $hash, $actor, $source): int {
            $f = $this->lockActive($id, $version);
            $registered = $f['record_state'] === 'REGISTRADO';
            if ($registered && $reason === null) {
                throw new ValidationFailed(['reason' => 'Informe o motivo da retificação.']);
            }
            [$equipment, $agent] = $this->lockSources($values);
            if ($registered) {
                $errors = FineRules::missingForRegistration($values) + FineRules::registrationConflicts($values) + $this->divergence($values, $equipment, $agent);
                if ($errors !== []) {
                    throw new ValidationFailed($errors);
                }
            }
            $before = array_intersect_key($f, array_flip(self::CADASTRO));
            $changes = History::diff($before, $values);
            if ($changes === []) {
                return $id; // nada mudou: sem evento, sem nova versão
            }
            $row = $values + ['ait_key' => FineRules::aitKey($values['external_ait']), 'updated_at' => $this->now(), 'updated_by' => $actor];
            $subject = (int) $f['subject_version'];
            $identity = array_intersect(array_keys($changes), FineRules::IDENTITY) !== [];
            if ($identity) {
                $row['subject_version'] = $subject + 1;
                $row += $this->projection($id, $subject + 1);
            }
            if ($registered && array_intersect(array_keys($changes), ['source_kind', 'equipment_id', 'agent_id', 'agency_id']) !== []) {
                // Origem trocada na retificação: snapshot novo; o anterior fica no evento.
                $row['source_snapshot'] = $this->snapshot($values, $equipment, $agent);
            }
            $row['attention_count'] = count(FineRules::attention(array_merge($f, $row)));
            if (!$this->persist(fn() => $this->fines->updateVersioned($id, $version, $row))) {
                throw new ConcurrencyConflict('O registro mudou depois que você abriu o formulário.');
            }
            $labels = array_map(static fn(string $k): string => FineRules::LABELS[$k] ?? $k, array_keys($changes));
            $this->event(
                $id,
                $uid,
                $hash,
                $registered ? 'RETIFICACAO' : 'EDICAO_RASCUNHO',
                $source,
                $actor,
                ($registered ? 'Retificação: ' : 'Rascunho alterado: ') . self::fieldList($labels) . '.',
                $reason,
                ['alteracoes' => $changes]
                    + ($registered ? ['antes' => $before] + ($row['source_snapshot'] ?? null ? ['origem_anterior' => $f['source_snapshot']] : []) : [])
                    + ($identity ? ['sujeito' => ['de' => $subject, 'para' => $subject + 1]] : []),
            );

            return $id;
        }));
    }

    /**
     * Rascunho completo → registrado. Confere de novo, com bloqueio, se o radar ainda é radar e o agente ainda é de
     * trânsito, e grava o snapshot da origem (o cadastro atual pode mudar depois sem reescrever o registro).
     *
     * @param array<string, mixed> $data version + command_uid
     */
    public function register(int $id, array $data, ?int $actor, string $source = 'MANUAL'): void
    {
        self::assertAuthor($actor, $source);
        $input = new Input($data);
        $uid = self::uid($input);
        $version = (int) $input->int('version', 1, PHP_INT_MAX);
        $input->assertValid();
        $hash = self::fingerprint('REGISTRO', $id, $version, $actor, []);

        $this->once($uid, $hash, $actor, fn() => $this->db->transaction(function () use ($id, $version, $uid, $hash, $actor, $source): int {
            $f = $this->lockActive($id, $version);
            if ($f['record_state'] === 'REGISTRADO') {
                throw new RuleViolation('Este cadastro já está registrado.');
            }
            [$equipment, $agent] = $this->lockSources($f);
            $errors = FineRules::missingForRegistration($f) + FineRules::registrationConflicts($f) + $this->divergence($f, $equipment, $agent);
            if ($errors !== []) {
                throw new ValidationFailed($errors);
            }
            $snapshot = $this->snapshot($f, $equipment, $agent);
            $row = ['record_state' => 'REGISTRADO', 'registered_at' => $this->now(), 'source_snapshot' => $snapshot, 'updated_at' => $this->now(), 'updated_by' => $actor];
            $row['attention_count'] = count(FineRules::attention(array_merge($f, $row)));
            if (!$this->fines->updateVersioned($id, $version, $row)) {
                throw new ConcurrencyConflict('O registro mudou depois que você abriu esta página.');
            }
            $this->event($id, $uid, $hash, 'REGISTRO', $source, $actor, 'Registrado após conferência dos campos mínimos (não é emissão de auto oficial).', null, ['origem' => $snapshot]);

            return $id;
        }));
    }

    /**
     * Acrescenta uma constatação documental (nunca altera as anteriores) e recalcula a situação na data da ocorrência.
     *
     * @param array<string, mixed> $data
     */
    public function addCheck(int $id, array $data, ?int $actor, string $source = 'MANUAL'): void
    {
        self::assertAuthor($actor, $source);
        $input = new Input($data);
        $uid = self::uid($input);
        $version = (int) $input->int('version', 1, PHP_INT_MAX);
        $scope = $input->choice('scope', array_keys(FineDocumentStatus::SCOPES));
        $status = $input->choice('document_status', array_keys(FineDocumentStatus::LABELS));
        $year = (int) $this->clock->now()->setTimezone(Time::local())->format('Y');
        $c = [
            'scope' => $scope,
            'checked_at' => $input->localDateTime('checked_at'),
            'document_status' => $status,
            'source_type' => $input->choice('source_type', array_keys(FineDocumentStatus::SOURCE_TYPES)),
            'source_reference' => $input->text('source_reference', 240, false),
            'crlv_exercise' => $input->int('crlv_exercise', 1990, $year + 1, false),
            'pending_items' => $input->text('pending_items', 500, false),
            'review_on' => $input->date('review_on', false),
            'effective_on' => $scope === 'POSTERIOR' ? $input->date('effective_on') : null,
            'reason' => $input->text('check_reason', 300, false),
        ];
        if ($c['checked_at'] !== null && $c['checked_at'] > $this->now()) {
            $input->fail('checked_at', 'A conferência não pode estar no futuro.');
        }
        if ($status !== null && $status !== FineDocumentStatus::NOT_CHECKED) {
            if ($c['source_type'] === 'NAO_INFORMADA') {
                $input->fail('source_type', 'Para informar um resultado, indique a fonte (CRLV, consulta feita fora do sistema ou relato).');
            }
            if ($c['source_reference'] === null) {
                $input->fail('source_reference', 'Informe a referência do documento ou do relato (sem dados pessoais).');
            }
        }
        if ($status === FineDocumentStatus::PENDING && $c['pending_items'] === null) {
            $input->fail('pending_items', 'Descreva a pendência apontada.');
        }
        if ($status === FineDocumentStatus::NOT_CHECKED && $c['reason'] === null) {
            $input->fail('check_reason', 'Explique por que registrar "Não verificada" (por exemplo, retirar uma conclusão anterior).');
        }
        if ($status !== FineDocumentStatus::PENDING) {
            $c['pending_items'] = null;
        }
        $input->assertValid();
        $hash = self::fingerprint('CONFERENCIA', $id, $version, $actor, $c);

        $this->once($uid, $hash, $actor, fn() => $this->db->transaction(function () use ($id, $version, $c, $uid, $hash, $actor, $source): int {
            $f = $this->lockActive($id, $version);
            $occurred = Time::fromDb($f['occurred_at']) ?? throw new ValidationFailed(['scope' => 'Informe primeiro a data e a hora da ocorrência no cadastro: a conferência se refere a essa data.']);
            $occDate = Time::localDate($occurred);
            $today = Time::localDate($this->clock->now());
            $effective = $c['scope'] === 'OCORRENCIA' ? $occDate : (string) $c['effective_on'];
            $errors = [];
            if ($c['scope'] === 'POSTERIOR' && ($effective < $occDate || $effective > $today)) {
                $errors['effective_on'] = 'Use uma data entre a da ocorrência (' . self::br($occDate) . ') e hoje.';
            }
            if (Time::localDate(Time::fromDb($c['checked_at']) ?? $occurred) < $effective) {
                $errors['checked_at'] = 'A conferência não pode ser anterior à data a que ela se refere (' . self::br($effective) . ').';
            }
            if ($c['review_on'] !== null && $c['review_on'] < $effective) {
                $errors['review_on'] = 'A revisão deve ser no mesmo dia ou depois de ' . self::br($effective) . '.';
            }
            if ($errors !== []) {
                throw new ValidationFailed($errors);
            }
            $subject = (int) $f['subject_version'];
            $checkId = $this->fines->insertCheck(['effective_on' => $effective] + $c + ['fine_id' => $id, 'subject_version' => $subject, 'source' => $source,
                'actor_user_id' => $actor, 'created_at' => $this->now()]);
            $row = $this->projection($id, $subject) + ['updated_at' => $this->now(), 'updated_by' => $actor];
            $row['attention_count'] = count(FineRules::attention(array_merge($f, $row)));
            if (!$this->fines->updateVersioned($id, $version, $row)) {
                throw new ConcurrencyConflict('O registro mudou depois que você abriu esta página.');
            }
            $this->event($id, $uid, $hash, 'CONFERENCIA_DOCUMENTAL', $source, $actor, sprintf(
                '%s: %s (%s).',
                FineDocumentStatus::SCOPES[$c['scope']],
                FineDocumentStatus::LABELS[$c['document_status']],
                FineDocumentStatus::SOURCE_TYPES[$c['source_type']],
            ), $c['reason'], [
                'conferencia' => $checkId, 'referencia' => $effective, 'versao_do_sujeito' => $subject,
                'situacao_na_ocorrencia' => ['antes' => $f['occ_doc_status'], 'depois' => $row['occ_doc_status']],
            ]);

            return $id;
        }));
    }

    /**
     * Lança o andamento informado: etapa, pagamento e contestação são independentes. Datas impossíveis são erro;
     * sequências incomuns voltam como avisos. Nenhuma decisão, prazo ou valor é calculado.
     *
     * @param array<string, mixed> $data
     * @return list<string> avisos
     */
    public function updateProgress(int $id, array $data, ?int $actor, string $source = 'MANUAL'): array
    {
        self::assertAuthor($actor, $source);
        $input = new Input($data);
        $uid = self::uid($input);
        $version = (int) $input->int('version', 1, PHP_INT_MAX);
        $p = [
            'process_stage' => $input->choice('process_stage', array_keys(FineRules::STAGES)),
            'payment_status' => $input->choice('payment_status', array_keys(FineRules::PAYMENTS)),
            'contest_status' => $input->choice('contest_status', array_keys(FineRules::CONTESTS)),
            'contest_kind' => $input->choice('contest_kind', array_keys(FineRules::CONTEST_KINDS), false),
            'nominal_amount_cents' => $input->cents('nominal_amount', 0, 100_000_000),
        ];
        foreach (['autuation_issued_on', 'autuation_received_on', 'penalty_issued_on', 'penalty_received_on', 'payment_due_on', 'defense_due_on', 'appeal_due_on', 'paid_on'] as $d) {
            $p[$d] = $input->date($d, false);
        }
        $p += [
            'paid_amount_cents' => $input->cents('paid_amount', 1, 100_000_000),
            'payment_reference' => $input->text('payment_reference', 240, false),
            'contest_submitted_on' => $input->date('contest_submitted_on', false),
            'contest_reference' => $input->text('contest_reference', 240, false),
            'contest_decided_on' => $input->date('contest_decided_on', false),
            'contest_result_note' => $input->text('contest_result_note', 500, false),
            'closed_on' => $input->date('closed_on', false),
            'closure_reference' => $input->text('closure_reference', 240, false),
            'closure_note' => $input->text('closure_note', 500, false),
        ];
        $reason = $input->text('progress_reason', 300, false);
        $require = static function (array $fields, string $why) use ($input, $p): void {
            foreach ($fields as $field) {
                $key = $field === 'paid_amount_cents' ? 'paid_amount' : $field;
                if ($p[$field] === null && !isset($input->errors()[$key])) {
                    $input->fail($key, $why);
                }
            }
        };
        if ($p['payment_status'] === 'PAGAMENTO_INFORMADO') {
            $require(['paid_on', 'paid_amount_cents', 'payment_reference'], 'Obrigatório para "Pagamento informado".');
        }
        if (in_array($p['contest_status'], ['EM_ANALISE', 'DEFERIDA', 'INDEFERIDA'], true)) {
            $require(['contest_kind', 'contest_submitted_on', 'contest_reference'], 'Obrigatório quando há contestação apresentada.');
        }
        if (in_array($p['contest_status'], ['DEFERIDA', 'INDEFERIDA'], true)) {
            $require(['contest_decided_on', 'contest_result_note'], 'Obrigatório quando o resultado foi informado.');
        }
        if ($p['process_stage'] === 'ENCERRAMENTO_INFORMADO') {
            $require(['closed_on', 'closure_reference', 'closure_note'], 'Obrigatório para "Encerramento informado": o sistema não conclui procedência.');
        }
        $today = Time::localDate($this->clock->now());
        foreach (self::FACT_DATES as $d) {
            if ($p[$d] !== null && $p[$d] > $today) {
                $input->fail($d, 'Data de um fato já ocorrido não pode estar no futuro.');
            }
        }
        foreach ([['autuation_issued_on', 'autuation_received_on', 'O recebimento não pode ser anterior à expedição.'],
            ['penalty_issued_on', 'penalty_received_on', 'O recebimento não pode ser anterior à expedição.'],
            ['contest_submitted_on', 'contest_decided_on', 'O resultado não pode ser anterior ao protocolo.']] as [$first, $then, $message]) {
            if ($p[$first] !== null && $p[$then] !== null && $p[$then] < $p[$first]) {
                $input->fail($then, $message);
            }
        }
        $input->assertValid();
        $hash = self::fingerprint('ANDAMENTO', $id, $version, $actor, $p + ['reason' => $reason]);

        $result = $this->once($uid, $hash, $actor, fn() => $this->db->transaction(function () use ($id, $version, $p, $reason, $uid, $hash, $actor, $source): array {
            $f = $this->lockActive($id, $version);
            $occurred = Time::fromDb($f['occurred_at']);
            if ($occurred !== null) {
                $errors = [];
                foreach (self::FACT_DATES as $d) {
                    if ($p[$d] !== null && $p[$d] < Time::localDate($occurred)) {
                        $errors[$d] = 'A data é anterior à ocorrência (' . self::br(Time::localDate($occurred)) . ').';
                    }
                }
                if ($errors !== []) {
                    throw new ValidationFailed($errors);
                }
            }
            $changes = History::diff(array_intersect_key($f, array_flip(self::PROGRESS)), $p);
            if ($changes === []) {
                return [];
            }
            $corrected = array_filter(self::CORRECTIONS, static fn(string $k): bool => isset($changes[$k]) && $changes[$k]['antes'] !== null);
            if ($corrected !== [] && $reason === null) {
                throw new ValidationFailed(['progress_reason' => 'Você está corrigindo informação já registrada (' . implode(', ', array_map(static fn($k) => mb_strtolower(self::PROGRESS_LABELS[$k]), $corrected)) . '): informe o motivo. A anterior fica no histórico.']);
            }
            $row = $p + ['updated_at' => $this->now(), 'updated_by' => $actor];
            if (!$this->fines->updateVersioned($id, $version, $row)) {
                throw new ConcurrencyConflict('O registro mudou depois que você abriu esta página.');
            }
            $this->event($id, $uid, $hash, 'ANDAMENTO', $source, $actor, 'Andamento informado: ' . self::fieldList(array_map(static fn($k) => mb_strtolower(self::PROGRESS_LABELS[$k]), array_keys($changes))) . '.', $reason, ['alteracoes' => $changes]);

            return FineRules::progressWarnings($p);
        }));

        return is_array($result) ? $result : [];
    }

    /**
     * Cancelamento interno (engano, duplicidade…): o registro fica somente leitura. Não é anulação do auto nem decisão
     * oficial e não mexe em pagamento ou contestação.
     *
     * @param array<string, mixed> $data reason + version + command_uid
     */
    public function cancel(int $id, array $data, ?int $actor, string $source = 'MANUAL'): void
    {
        self::assertAuthor($actor, $source);
        $input = new Input($data);
        $uid = self::uid($input);
        $version = (int) $input->int('version', 1, PHP_INT_MAX);
        $reason = $input->text('reason', 300);
        $input->assertValid();
        $hash = self::fingerprint('CANCELAMENTO', $id, $version, $actor, ['reason' => $reason]);

        $this->once($uid, $hash, $actor, fn() => $this->db->transaction(function () use ($id, $version, $reason, $uid, $hash, $actor, $source): int {
            $f = $this->lockActive($id, $version);
            $now = $this->now();
            $row = ['record_state' => 'CANCELADO', 'cancelled_at' => $now, 'cancelled_by' => $actor, 'cancel_source' => $source, 'cancel_reason' => $reason,
                'attention_count' => 0, 'updated_at' => $now, 'updated_by' => $actor];
            if (!$this->fines->updateVersioned($id, $version, $row)) {
                throw new ConcurrencyConflict('O registro mudou depois que você abriu esta página.');
            }
            $this->event($id, $uid, $hash, 'CANCELAMENTO', $source, $actor, 'Registro cancelado internamente (não é anulação do auto): ' . $reason, $reason, ['de' => $f['record_state']]);

            return $id;
        }));
    }

    /**
     * O que salvar $data mudaria no cadastro gravado agora (tela de conflito de edição).
     *
     * @param array<string, mixed> $data
     * @return array<string, array{antes: mixed, depois: mixed}>
     */
    public function pendingChanges(int $id, array $data): array
    {
        $current = $this->fines->find($id) ?? throw new NotFound('Multa não encontrada.');
        try {
            $values = $this->validate(new Input($data));
        } catch (ValidationFailed) {
            return [];
        }

        return History::diff(array_intersect_key($current, array_flip(self::CADASTRO)), $values);
    }

    /**
     * Campos do cadastro validados e normalizados. Rascunho aceita ausências; o que vier precisa estar bem formado.
     * Só o bloco da origem escolhida vale: radar enviado com origem Agente (ou agente principal com origem Radar) é
     * recusado; dados do medidor sem origem Radar e velocidades fora de "Excesso de velocidade" são descartados.
     *
     * @return array<string, mixed>
     */
    private function validate(Input $in): array
    {
        $now = $this->clock->now();
        $kind = $in->choice('infraction_kind', array_keys(FineRules::KINDS), false);
        $source = $in->choice('source_kind', array_keys(FineRules::SOURCES), false);
        $v = [
            'description' => $in->text('description', 240),
            'occurred_at' => $in->localDateTime('occurred_at', false),
            'infraction_kind' => $kind,
            'legal_reference' => $in->text('legal_reference', 120, false),
            'infraction_code' => $in->text('infraction_code', 20, false),
            'infraction_detail' => $in->text('infraction_detail', 10, false),
            'agency_id' => $in->int('agency_id', 1, PHP_INT_MAX, false),
            'external_ait' => $in->text('external_ait', 40, false),
            'ait_missing_reason' => $in->text('ait_missing_reason', 160, false),
            'street' => $in->text('street', 120, false),
            'address_ref' => $in->text('address_ref', 160, false),
            'region_id' => $in->int('region_id', 1, PHP_INT_MAX, false),
            'lat' => $in->coordinate('lat', 'lat', false),
            'lng' => $in->coordinate('lng', 'lng', false),
            'notes' => $in->text('notes', 1000, false),
            'source_kind' => $source,
            'equipment_id' => $in->int('equipment_id', 1, PHP_INT_MAX, false),
            'agent_id' => null,
            'source_note' => $in->text('source_note', 300, false),
            'speed_limit_kmh' => $in->int('speed_limit_kmh', 1, 350, false),
            'speed_measured_kmh' => $in->int('speed_measured_kmh', 1, 350, false),
            'speed_considered_kmh' => null, // calculada pelo servidor a partir da medida (FineRules::consideredSpeed)
            'radar_details' => array_filter([
                'meter_kind' => $in->text('radar_meter_kind', 120, false),
                'inmetro_record' => $in->text('radar_inmetro_record', 120, false),
                'certificate' => $in->text('radar_certificate', 120, false),
                'verified_on' => $in->date('radar_verified_on', false),
            ], static fn($x) => $x !== null),
            'evidence_reference' => $in->text('evidence_reference', 240, false),
            'vehicle_plate' => null,
            'vehicle_uf' => $in->choice('vehicle_uf', FineRules::UFS, false),
            'vehicle_manufacturer' => $in->text('vehicle_manufacturer', 80, false),
            'vehicle_model' => $in->text('vehicle_model', 80, false),
            'vehicle_species' => $in->text('vehicle_species', 40, false),
            'vehicle_color' => $in->text('vehicle_color', 30, false),
            'vehicle_year' => $in->int('vehicle_year', 1900, (int) $now->setTimezone(Time::local())->format('Y') + 1, false),
            'vehicle_renavam' => null,
            'renavam_missing_reason' => $in->text('renavam_missing_reason', 160, false),
        ];
        if ($v['description'] !== null && mb_strlen($v['description']) < 5) {
            $in->fail('description', 'Use de 5 a 240 caracteres para identificar o registro.');
        }
        if ($v['occurred_at'] !== null && $v['occurred_at'] > Time::toDb($now)) {
            $in->fail('occurred_at', 'A ocorrência não pode estar no futuro.');
        }
        if (($v['lat'] === null) !== ($v['lng'] === null)) {
            $in->fail('lat', 'Informe latitude e longitude juntas, ou nenhuma.');
        }
        $plate = $in->text('vehicle_plate', 10, false);
        if ($plate !== null) {
            $plate = FineRules::plate($plate);
            FineRules::isPlate($plate) ? $v['vehicle_plate'] = $plate : $in->fail('vehicle_plate', 'Use o formato AAA1234 ou AAA1A23 (só o formato é conferido, não a existência).');
        } elseif ($source === 'RADAR') {
            // O radar identifica o veículo pela placa na imagem: sem placa legível não há registro de radar, nem em rascunho.
            $in->fail('vehicle_plate', 'Origem Radar: informe a placa lida na imagem. Sem placa legível o radar não gera registro.');
        }
        $renavam = $in->text('vehicle_renavam', 20, false);
        if ($renavam !== null) {
            // Texto de 11 dígitos: zeros à esquerda são parte do número e nada é completado automaticamente.
            preg_match('/^\d{11}$/', $renavam) === 1 ? $v['vehicle_renavam'] = $renavam : $in->fail('vehicle_renavam', 'O RENAVAM tem 11 dígitos, inclusive os zeros à esquerda.');
        }
        $primary = $in->int('agent_id', 1, PHP_INT_MAX, false);
        $operator = $in->int('operator_agent_id', 1, PHP_INT_MAX, false);
        if ($source === null) {
            if ($v['equipment_id'] !== null || $primary !== null || $operator !== null) {
                $in->fail('source_kind', 'Escolha se foi constatada por Radar ou por Agente de trânsito antes de indicar o recurso.');
            }
        } elseif ($source === 'AGENTE') {
            if ($v['equipment_id'] !== null) {
                $in->fail('equipment_id', 'Origem "Agente de trânsito": deixe o radar em branco.');
            }
            if ($operator !== null) {
                $in->fail('operator_agent_id', 'Origem "Agente de trânsito": indique o agente no bloco do agente, não como operador de radar.');
            }
            $v['agent_id'] = $primary;
        } else {
            if ($primary !== null) {
                $in->fail('agent_id', 'Origem "Radar": indique o agente em "Agente operador associado".');
            }
            $v['agent_id'] = $operator;
        }
        if ($source !== 'RADAR') {
            $v['radar_details'] = [];
            $v['evidence_reference'] = null;
        }
        if ($kind !== 'VELOCIDADE') {
            $v['speed_limit_kmh'] = $v['speed_measured_kmh'] = $v['speed_considered_kmh'] = null;
        } elseif ($v['speed_measured_kmh'] !== null) {
            $v['speed_considered_kmh'] = FineRules::consideredSpeed($v['speed_measured_kmh']);
        }
        if ($v['external_ait'] !== null) {
            $v['ait_missing_reason'] = null;
        }
        if ($v['vehicle_renavam'] !== null) {
            $v['renavam_missing_reason'] = null;
        }
        if ($v['agency_id'] !== null && !$this->references->agencyExists($v['agency_id'])) {
            $in->fail('agency_id', 'Órgão inexistente.');
        }
        if ($v['region_id'] !== null && !$this->references->regionExists($v['region_id'])) {
            $in->fail('region_id', 'Bairro inexistente.');
        }
        $in->assertValid();

        return $v;
    }

    /**
     * Bloqueia (equipamento antes de agente) e confere o tipo dos recursos indicados: FK sozinha não garante que o
     * equipamento é radar nem que o agente é de trânsito. Arquivamento, pausa ou manutenção atuais não bloqueiam um
     * registro histórico.
     *
     * @param array<string, mixed> $f
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private function lockSources(array $f): array
    {
        $errors = [];
        $equipment = $f['equipment_id'] === null ? null : $this->equipment->lock((int) $f['equipment_id']);
        if ($f['equipment_id'] !== null && ($equipment === null || $equipment['type_code'] !== 'RADAR')) {
            $errors['equipment_id'] = $equipment === null ? 'Radar inexistente.' : 'O equipamento escolhido não é um radar.';
        }
        $agent = $f['agent_id'] === null ? null : $this->agents->lock((int) $f['agent_id']);
        if ($f['agent_id'] !== null && ($agent === null || $agent['role'] !== 'TRANSITO')) {
            $errors[$f['source_kind'] === 'RADAR' ? 'operator_agent_id' : 'agent_id'] = $agent === null ? 'Agente inexistente.' : 'Escolha um agente de trânsito (guarda e polícia não entram como agente desta multa).';
        }
        if ($errors !== []) {
            throw new ValidationFailed($errors);
        }

        return [$equipment, $agent];
    }

    /**
     * Órgão autuador diferente do órgão do recurso principal exige justificativa (não é proibido, nem conclusão de validade).
     *
     * @param array<string, mixed> $f
     * @param array<string, mixed>|null $equipment
     * @param array<string, mixed>|null $agent
     * @return array<string, string>
     */
    private function divergence(array $f, ?array $equipment, ?array $agent): array
    {
        $resource = $f['source_kind'] === 'RADAR' ? $equipment : $agent;
        if ($resource === null || $f['agency_id'] === null || (int) $resource['agency_id'] === (int) $f['agency_id'] || $f['source_note'] !== null) {
            return [];
        }

        return ['source_note' => 'O órgão autuador é diferente do órgão do ' . ($f['source_kind'] === 'RADAR' ? 'radar' : 'agente') . ': explique a divergência na observação sobre a origem.'];
    }

    /**
     * Snapshot da origem montado só pelo servidor, com o que o cadastro dizia no momento do registro.
     *
     * @param array<string, mixed> $f
     * @param array<string, mixed>|null $equipment
     * @param array<string, mixed>|null $agent
     * @return array<string, mixed>
     */
    private function snapshot(array $f, ?array $equipment, ?array $agent): array
    {
        $agency = fn($id): ?array => $id === null ? null : (($a = $this->references->agency((int) $id)) === null ? null : ['codigo' => $a['code'], 'nome' => $a['name']]);
        $snapshot = ['capturado_em' => $this->now(), 'origem' => $f['source_kind'], 'orgao_autuador' => $agency($f['agency_id'])];
        if ($equipment !== null) {
            $snapshot['radar'] = ['id' => (int) $equipment['id'], 'codigo' => $equipment['code'], 'fabricante' => $equipment['manufacturer'], 'modelo' => $equipment['model'],
                'serie' => $equipment['serial_number'], 'orgao' => $agency($equipment['agency_id']), 'funcoes' => $equipment['specs']['functions'] ?? [],
                'limite_cadastrado_kmh' => $equipment['specs']['speed_limit_kmh'] ?? null, 'situacao' => $equipment['status'], 'arquivado' => $equipment['archived_at'] !== null];
        }
        if ($agent !== null) {
            $snapshot['agente'] = ['id' => (int) $agent['id'], 'codigo' => $agent['code'], 'indicativo' => $agent['call_sign'], 'funcao' => $agent['role'],
                'orgao' => $agency($agent['agency_id']), 'papel' => $f['source_kind'] === 'RADAR' ? 'operador associado' : 'agente da constatação',
                'arquivado' => $agent['archived_at'] !== null];
        }

        return $snapshot;
    }

    /**
     * Situação na data da ocorrência para a versão do sujeito (a última conferência compatível, pelo instante).
     *
     * @return array{occ_doc_status: string, occ_doc_review_on: ?string}
     */
    private function projection(int $id, int $subjectVersion): array
    {
        $check = FineDocumentStatus::select($this->fines->checks($id), $subjectVersion, 'OCORRENCIA');

        return ['occ_doc_status' => $check['document_status'] ?? FineDocumentStatus::NOT_CHECKED, 'occ_doc_review_on' => $check['review_on'] ?? null];
    }

    /** @return array<string, mixed> */
    private function lockActive(int $id, int $version): array
    {
        $f = $this->fines->lock($id) ?? throw new NotFound('Multa não encontrada.');
        if ($f['record_state'] === 'CANCELADO') {
            throw new RuleViolation('Registro cancelado internamente: fica somente para consulta.');
        }
        if ((int) $f['version_no'] !== $version) {
            throw new ConcurrencyConflict('O registro mudou depois que você abriu esta página (por outra pessoa ou em outra aba).');
        }

        return $f;
    }

    /**
     * Executa o comando uma vez. Mesma chave, mesmo ator e mesmos dados: devolve o resultado gravado sem novo efeito
     * (duplo clique, reenvio). Mesma chave com outro ator ou outros dados: conflito, sem revelar o registro alheio.
     *
     * @param callable(): mixed $work
     */
    private function once(string $uid, string $hash, ?int $actor, callable $work): mixed
    {
        $replay = function (array $event) use ($hash, $actor): int {
            if ((int) ($event['actor_user_id'] ?? 0) !== (int) ($actor ?? 0) || $event['request_hash'] !== $hash) {
                throw new ConcurrencyConflict('Este formulário já foi usado para outra operação. Recarregue a página e tente de novo.');
            }

            return (int) $event['fine_id'];
        };
        $existing = $this->fines->eventByUid($uid);
        if ($existing !== null) {
            return $replay($existing);
        }
        try {
            return $work();
        } catch (ConstraintViolation|ValidationFailed $e) {
            // Outra requisição com a mesma chave terminou primeiro (a corrida pode aparecer como chave de evento ou como
            // AIT repetido): esta foi desfeita por inteiro e vale o resultado já gravado.
            $existing = $this->fines->eventByUid($uid);
            if ($existing === null) {
                throw $e;
            }

            return $replay($existing);
        }
    }

    /** @param array<string, mixed> $payload */
    private function event(int $fineId, string $uid, string $hash, string $type, string $source, ?int $actor, string $summary, ?string $reason, array $payload): void
    {
        $this->fines->appendEvent(['fine_id' => $fineId, 'uid' => $uid, 'hash' => $hash, 'type' => $type, 'source' => $source, 'actor' => $actor,
            'recorded_at' => $this->now(), 'summary' => $summary, 'reason' => $reason, 'payload' => $payload]);
    }

    /**
     * @template T
     * @param callable(): T $write
     * @return T
     */
    private function persist(callable $write): mixed
    {
        try {
            return $write();
        } catch (ConstraintViolation $e) {
            throw $e->constraint === 'UX_FINE_AIT'
                ? new ValidationFailed(['external_ait' => 'Já existe um registro com este AIT neste órgão autuador. Abra o registro anterior em vez de criar outro.'])
                : $e;
        }
    }

    private static function uid(Input $input): string
    {
        $uid = $input->raw('command_uid');
        if (!is_string($uid) || preg_match('/^[a-f0-9]{32}$/', $uid) !== 1) {
            $input->fail('command_uid', 'Formulário incompleto ou expirado: recarregue a página.');

            return '';
        }

        return $uid;
    }

    /**
     * Impressão digital estável do comando: operação, alvo, versão recebida, ator e dados normalizados ordenados.
     *
     * @param array<string, mixed> $values
     */
    private static function fingerprint(string $operation, int $target, int $version, ?int $actor, array $values): string
    {
        $canonical = static function (mixed $v) use (&$canonical): mixed {
            if (!is_array($v)) {
                return $v;
            }
            ksort($v);

            return array_map($canonical, $v);
        };

        return hash('sha256', json_encode([$operation, $target, $version, $actor, $canonical($values)], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private static function assertAuthor(?int $actor, string $source): void
    {
        // Autoria de conta é obrigatória para operação manual; só a carga confiável de demonstração usa autor nulo.
        if (!in_array($source, ['MANUAL', 'SEMENTE'], true) || ($source === 'MANUAL' && $actor === null)) {
            throw new \LogicException('Operação manual sem conta autenticada.');
        }
    }

    private function now(): string
    {
        return Time::toDb($this->clock->now());
    }

    /**
     * Campos alterados no resumo do evento: até três pelo nome; o detalhe completo fica no antes/depois.
     *
     * @param list<string> $labels
     */
    private static function fieldList(array $labels): string
    {
        return count($labels) <= 3 ? implode(', ', $labels) : implode(', ', array_slice($labels, 0, 3)) . ' e mais ' . (count($labels) - 3) . ' campo(s)';
    }

    private static function br(string $date): string
    {
        return implode('/', array_reverse(explode('-', $date)));
    }
}
