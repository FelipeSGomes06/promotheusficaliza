<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

/**
 * Regras puras da aba Multas (sem HTTP nem SQL): vocabulário, identificadores, campos mínimos para registrar e os
 * motivos de "A conferir". Registrar aqui é um cadastro interno conferido — não emite nem certifica um auto oficial.
 */
final class FineRules
{
    public const STATES = ['RASCUNHO' => 'Rascunho', 'REGISTRADO' => 'Registrado', 'CANCELADO' => 'Registro cancelado'];

    /** Categorias de interface (não são o catálogo nacional de enquadramentos). */
    public const KINDS = [
        'VELOCIDADE' => 'Excesso de velocidade',
        'AVANCO_SEMAFORO' => 'Avanço de sinal vermelho',
        'PARADA_FAIXA' => 'Parada sobre a faixa de pedestres',
        'CONVERSAO_PROIBIDA' => 'Conversão proibida',
        'FAIXA_EXCLUSIVA' => 'Circulação em faixa exclusiva',
        'OUTRA' => 'Outra infração',
    ];

    public const SOURCES = ['RADAR' => 'Radar', 'AGENTE' => 'Agente de trânsito'];

    public const STAGES = [
        'NAO_INFORMADA' => 'Etapa não informada',
        'AUTUACAO' => 'Autuação',
        'PENALIDADE_NOTIFICADA' => 'Penalidade notificada',
        'ENCERRAMENTO_INFORMADO' => 'Encerramento informado',
    ];

    public const PAYMENTS = [
        'NAO_INFORMADO' => 'Pagamento não informado',
        'SEM_COBRANCA_INFORMADA' => 'Sem cobrança informada',
        'EM_ABERTO' => 'Em aberto (informado)',
        'PAGAMENTO_INFORMADO' => 'Pagamento informado',
    ];

    public const CONTESTS = [
        'NAO_INFORMADA' => 'Contestação não informada',
        'NAO_APRESENTADA' => 'Nenhuma contestação apresentada',
        'EM_ANALISE' => 'Contestação em análise',
        'DEFERIDA' => 'Contestação deferida (informado)',
        'INDEFERIDA' => 'Contestação indeferida (informado)',
    ];

    public const CONTEST_KINDS = ['DEFESA_PREVIA' => 'Defesa prévia', 'RECURSO_1' => 'Recurso em 1ª instância', 'RECURSO_2' => 'Recurso em 2ª instância'];

    public const UFS = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

    /** Campos que identificam o sujeito da conferência documental: mudar qualquer um pede nova conferência. */
    public const IDENTITY = ['vehicle_plate', 'vehicle_uf', 'vehicle_renavam', 'vehicle_manufacturer', 'vehicle_model', 'vehicle_species', 'occurred_at'];

    /** Rótulos dos campos do cadastro (formulário, resumo de erros, pendências e histórico). */
    public const LABELS = [
        'description' => 'Descrição', 'occurred_at' => 'Data e hora da ocorrência', 'infraction_kind' => 'Tipo de infração',
        'legal_reference' => 'Enquadramento informado', 'infraction_code' => 'Código da infração', 'infraction_detail' => 'Desdobramento',
        'agency_id' => 'Órgão autuador', 'external_ait' => 'Número do AIT', 'ait_missing_reason' => 'Por que o AIT não está disponível',
        'street' => 'Logradouro', 'address_ref' => 'Número ou referência', 'region_id' => 'Bairro', 'lat' => 'Latitude', 'lng' => 'Longitude',
        'notes' => 'Relato', 'source_kind' => 'Como foi constatada', 'equipment_id' => 'Radar', 'agent_id' => 'Agente de trânsito',
        'operator_agent_id' => 'Agente operador associado', 'source_note' => 'Observação sobre a origem',
        'speed_limit_kmh' => 'Velocidade regulamentada', 'speed_measured_kmh' => 'Velocidade medida', 'speed_considered_kmh' => 'Velocidade considerada',
        'radar_details' => 'Dados do medidor', 'radar_meter_kind' => 'Tipo de medidor informado', 'radar_inmetro_record' => 'Registro no Inmetro',
        'radar_certificate' => 'Certificado de verificação', 'radar_verified_on' => 'Última verificação metrológica', 'evidence_reference' => 'Referência da evidência',
        'vehicle_plate' => 'Placa', 'vehicle_uf' => 'UF de registro', 'vehicle_manufacturer' => 'Marca', 'vehicle_model' => 'Modelo',
        'vehicle_species' => 'Espécie', 'vehicle_color' => 'Cor', 'vehicle_year' => 'Ano', 'vehicle_renavam' => 'RENAVAM',
        'renavam_missing_reason' => 'Por que o RENAVAM não está disponível', 'reason' => 'Motivo', 'version' => 'Versão', 'command_uid' => 'Formulário',
    ];

    /** Regra usada no cálculo da velocidade considerada (mostrada na ficha junto com o valor). */
    public const SPEED_RULE = 'Resolução Contran 798/2020 (art. 8º e Anexo III): até 107 km/h medidos, menos 7 km/h; acima, menos 7%, arredondado.';

    /**
     * Velocidade considerada = medida menos o erro máximo admitido do medidor: 7 km/h até 107 km/h medidos e 7% acima
     * disso, com arredondamento matemático (as duas fórmulas coincidem em 100 km/h considerados).
     */
    public static function consideredSpeed(int $measured): int
    {
        return $measured <= 107 ? max(0, $measured - 7) : intdiv($measured * 93 + 50, 100);
    }

    public static function code(int $id): string
    {
        return sprintf('DEMO-MUL-%06d', $id);
    }

    /** ID a partir do código interno ("DEMO-MUL-000023", com ou sem zeros); null se o texto não for um código. */
    public static function idFromCode(string $text): ?int
    {
        if (preg_match('/^DEMO-MUL-0*(\d{1,9})$/i', trim($text), $m) !== 1) {
            return null;
        }
        $id = (int) $m[1];

        return $id >= 1 && $id <= 2_147_483_647 ? $id : null;
    }

    /** Placa sem a pontuação de apresentação (hífen e espaços), em maiúsculas. */
    public static function plate(string $text): string
    {
        return strtoupper((string) preg_replace('/[\s-]+/', '', trim($text)));
    }

    /** Formato antigo (AAA1234) ou Mercosul (AAA1A23). Valida só o formato, não a existência. */
    public static function isPlate(string $plate): bool
    {
        return preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $plate) === 1;
    }

    /** Chave de comparação do AIT: espaços das pontas e caixa; a pontuação significativa fica. */
    public static function aitKey(?string $ait): ?string
    {
        return $ait === null || trim($ait) === '' ? null : mb_strtoupper(trim($ait));
    }

    /**
     * O que ainda falta para registrar (campo => orientação). Rascunho pode ficar incompleto; registrado, não.
     *
     * @param array<string, mixed> $f
     * @return array<string, string>
     */
    public static function missingForRegistration(array $f): array
    {
        $missing = [];
        $need = static function (string $field, string $message) use ($f, &$missing): void {
            if (($f[$field] ?? null) === null || $f[$field] === '') {
                $missing[$field] = $message;
            }
        };
        $need('occurred_at', 'Informe a data e a hora da ocorrência.');
        $need('infraction_kind', 'Escolha o tipo de infração.');
        $need('legal_reference', 'Transcreva o enquadramento informado no documento.');
        $need('agency_id', 'Escolha o órgão autuador.');
        if (($f['external_ait'] ?? null) === null && ($f['ait_missing_reason'] ?? null) === null) {
            $missing['external_ait'] = 'Informe o número do AIT ou explique por que ele não está disponível.';
        }
        if (($f['street'] ?? null) === null && ($f['address_ref'] ?? null) === null) {
            $missing['street'] = 'Informe o logradouro ou uma referência do local (o ponto no mapa sozinho não basta).';
        }
        $need('source_kind', 'Escolha se foi constatada por radar ou por agente de trânsito.');
        if (($f['source_kind'] ?? null) === 'RADAR') {
            $need('equipment_id', 'Escolha o radar.');
        } elseif (($f['source_kind'] ?? null) === 'AGENTE') {
            $need('agent_id', 'Escolha o agente de trânsito.');
        }
        if (($f['infraction_kind'] ?? null) === 'VELOCIDADE') {
            $need('speed_limit_kmh', 'Informe a velocidade regulamentada do local.');
            $need('speed_measured_kmh', 'Informe a velocidade medida.');
        }
        $need('vehicle_plate', 'Informe a placa.');
        $need('vehicle_uf', 'Escolha a UF de registro do veículo.');
        $need('vehicle_manufacturer', 'Informe a marca do veículo.');
        $need('vehicle_model', 'Informe o modelo do veículo.');
        $need('vehicle_species', 'Informe a espécie do veículo.');
        if (($f['vehicle_renavam'] ?? null) === null && ($f['renavam_missing_reason'] ?? null) === null) {
            $missing['vehicle_renavam'] = 'Informe o RENAVAM ou explique por que ele não está disponível.';
        }

        return $missing;
    }

    /**
     * Incoerências que impedem registrar (o rascunho continua disponível). Não calcula tolerância nem enquadramento.
     *
     * @param array<string, mixed> $f
     * @return array<string, string>
     */
    public static function registrationConflicts(array $f): array
    {
        $errors = [];
        if (($f['infraction_kind'] ?? null) === 'VELOCIDADE') {
            if (($f['source_kind'] ?? null) !== 'RADAR') {
                $errors['source_kind'] = 'Velocidade medida exige origem Radar. Medidor operado por agente: escolha Radar e indique o agente operador.';
            }
            if (isset($f['speed_considered_kmh'], $f['speed_limit_kmh']) && (int) $f['speed_considered_kmh'] <= (int) $f['speed_limit_kmh']) {
                $errors['speed_measured_kmh'] = 'Descontada a margem de erro do aparelho, a velocidade considerada não passa do limite: não há excesso para registrar. Revise a velocidade medida.';
            }
        }

        return $errors;
    }

    /**
     * Motivos de "A conferir". Com $today (AAAA-MM-DD) inclui a revisão de conferência cuja data já passou; sem ele,
     * só os motivos que não dependem do dia (base da contagem gravada em ATTENTION_COUNT). Nenhum motivo significa
     * "multa inválida": são pendências de cadastro ou de conferência.
     *
     * @param array<string, mixed> $f projeção atual (record_state, occ_doc_status, occ_doc_review_on e campos do cadastro)
     * @return list<string>
     */
    public static function attention(array $f, ?string $today = null): array
    {
        if (($f['record_state'] ?? null) === 'CANCELADO') {
            return [];
        }
        $reasons = [];
        if (($f['record_state'] ?? 'RASCUNHO') === 'RASCUNHO') {
            $missing = count(self::missingForRegistration($f));
            $reasons[] = $missing > 0 ? "Rascunho: faltam {$missing} informação(ões) para registrar." : 'Rascunho completo: falta registrar após conferência.';
        }
        $doc = $f['occ_doc_status'] ?? FineDocumentStatus::NOT_CHECKED;
        if ($doc === FineDocumentStatus::NOT_CHECKED) {
            $reasons[] = 'Documentação na data da ocorrência: não verificada.';
        } elseif ($doc === FineDocumentStatus::PENDING) {
            $reasons[] = 'Pendência documental informada na data da ocorrência.';
        }
        $review = $f['occ_doc_review_on'] ?? null;
        if ($today !== null && is_string($review) && $review !== '' && $review < $today) {
            $reasons[] = 'A data marcada para revisar a conferência (' . self::brDate($review) . ') já passou.';
        }
        if (($f['infraction_kind'] ?? null) === 'VELOCIDADE' && ($f['source_kind'] ?? null) === 'RADAR') {
            $radar = is_array($f['radar_details'] ?? null) ? $f['radar_details'] : [];
            if (!isset($radar['inmetro_record'], $radar['certificate'], $radar['verified_on']) || ($f['evidence_reference'] ?? null) === null) {
                $reasons[] = 'Dados metrológicos do radar ou referência da evidência a conferir.';
            }
            $occurred = Time::fromDb($f['occurred_at'] ?? null);
            if (isset($radar['verified_on']) && $occurred !== null && $radar['verified_on'] > Time::localDate($occurred)) {
                $reasons[] = 'Última verificação do medidor informada é posterior à ocorrência: revisar o documento.';
            }
            // A verificação periódica do Inmetro vale 12 meses: mais antiga que isso no dia do fato pede atenção.
            if (isset($radar['verified_on']) && $occurred !== null
                && $radar['verified_on'] < $occurred->setTimezone(Time::local())->modify('-12 months')->format('Y-m-d')) {
                $reasons[] = 'No dia da ocorrência, a última verificação do radar tinha mais de 12 meses (vencida): confira, pode ser motivo de contestação.';
            }
        }

        return $reasons;
    }

    /**
     * Sequências administrativas incomuns: só avisos (a informação é gravada como foi informada).
     *
     * @param array<string, mixed> $p campos de andamento
     * @return list<string>
     */
    public static function progressWarnings(array $p): array
    {
        $warnings = [];
        if (in_array($p['process_stage'] ?? null, ['NAO_INFORMADA', 'AUTUACAO'], true) && (($p['penalty_issued_on'] ?? null) !== null || ($p['penalty_received_on'] ?? null) !== null)) {
            $warnings[] = 'Há data de notificação da penalidade, mas a etapa informada não é "Penalidade notificada".';
        }
        if (($p['payment_status'] ?? null) === 'PAGAMENTO_INFORMADO' && ($p['process_stage'] ?? null) === 'NAO_INFORMADA') {
            $warnings[] = 'Pagamento informado sem etapa administrativa informada.';
        }
        if (isset($p['paid_amount_cents'], $p['nominal_amount_cents']) && $p['paid_amount_cents'] !== $p['nominal_amount_cents']) {
            $warnings[] = 'O valor pago informado difere do valor nominal: o sistema não calcula descontos.';
        }

        return $warnings;
    }

    private static function brDate(string $date): string
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $date, $m) === 1 ? "{$m[3]}/{$m[2]}/{$m[1]}" : $date;
    }
}
