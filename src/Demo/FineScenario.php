<?php

declare(strict_types=1);

namespace Fiscaliza\Demo;

use Fiscaliza\Domain\Time;
use Fiscaliza\Services;

/**
 * Cenário sintético da aba Multas: 12 casos curados (perfil compacto) ou 40 (completo), gravados pelos mesmos casos de
 * uso da interface, com origem SEMENTE e sem conta. Placas, RENAVAM, AIT, marcas e referências são fictícios (começam
 * com DEMO- quando são códigos); datas relativas ao relógio. Não altera recursos, contas nem histórico existentes.
 */
final class FineScenario
{
    /** Versão da carga, gravada em APP_META "fines_seed": repetir a mesma versão não duplica nada. */
    public const VERSION = 'multas-v1';

    /** @return int multas criadas */
    public static function seed(Services $s, string $profile): int
    {
        $db = $s->database();
        $now = $s->clock()->now()->setTimezone(Time::local());
        $agency = (int) $db->value("SELECT ID FROM AGENCY WHERE CODE = 'DMT'");
        $radars = array_column($db->all("SELECT ID, SERIAL_NUMBER FROM EQUIPMENT WHERE TYPE_CODE = 'RADAR' AND SERIAL_NUMBER IN ('VS5-24-00201', 'PCL-0042', 'PCL-0063')"), 'id', 'serial_number');
        $agents = array_column($db->all("SELECT ID, CALL_SIGN FROM AGENT WHERE ROLE = 'TRANSITO' AND CALL_SIGN IN ('Hermes 01', 'Hermes 04', 'Hermes 06')"), 'id', 'call_sign');
        $radars = array_combine(array_map('rtrim', array_keys($radars)), $radars);
        $agents = array_combine(array_map('rtrim', array_keys($agents)), $agents);
        if ($agency === 0 || count($radars) < 3 || count($agents) < 3) {
            throw new \RuntimeException('O cenário de multas usa os radares e agentes da demonstração (DMT, VS5-24-00201, PCL-0042, PCL-0063, Hermes 01/04/06). Carregue a demonstração antes (demo:seed).');
        }
        $regions = [];
        foreach ($s->referenceRepository()->regions() as $r) {
            $regions[$r['name']] = (int) $r['id'];
        }

        $at = static fn(int $daysAgo, string $time): string => $now->modify(sprintf('%+d days', -$daysAgo))->format('Y-m-d') . 'T' . $time;
        $day = static fn(int $daysAgo): string => $now->modify(sprintf('%+d days', -$daysAgo))->format('Y-m-d');
        $uid = static fn(string $key): string => md5('promotheus-multas:' . self::VERSION . ':' . $key);
        $fines = $s->fines();
        $version = static fn(int $id): int => (int) $s->fineRepository()->find($id)['version_no'];
        $year = (int) $now->format('Y');

        $vehicle = static fn(string $plate, ?string $renavam, string $maker, string $model, string $species, string $color, int $vehicleYear, ?string $whyNoRenavam = null): array => [
            'vehicle_plate' => $plate, 'vehicle_uf' => 'SP', 'vehicle_manufacturer' => $maker, 'vehicle_model' => $model, 'vehicle_species' => $species,
            'vehicle_color' => $color, 'vehicle_year' => (string) $vehicleYear, 'vehicle_renavam' => $renavam, 'renavam_missing_reason' => $whyNoRenavam,
        ];
        $radar = static fn(string $serial, string $kind, string $street, string $ref, string $region, ?string $operator = null): array => [
            'source_kind' => 'RADAR', 'equipment_id' => (string) $radars[$serial], 'operator_agent_id' => $operator === null ? null : (string) $agents[$operator],
            'infraction_kind' => $kind, 'street' => $street, 'address_ref' => $ref, 'region_id' => (string) $regions[$region], 'agency_id' => (string) $agency,
        ];
        $agent = static fn(string $callSign, string $kind, string $street, string $ref, string $region): array => [
            'source_kind' => 'AGENTE', 'agent_id' => (string) $agents[$callSign], 'infraction_kind' => $kind, 'street' => $street, 'address_ref' => $ref,
            'region_id' => (string) $regions[$region], 'agency_id' => (string) $agency,
        ];
        $metrology = static fn(string $tag, int $verifiedDaysAgo): array => [
            'radar_meter_kind' => 'Medidor fixo (informado no documento)', 'radar_inmetro_record' => "DEMO-INM-{$tag}", 'radar_certificate' => "DEMO-CERT-{$tag}",
            'radar_verified_on' => $day($verifiedDaysAgo), 'evidence_reference' => "DEMO-IMG-{$tag} (imagem fictícia)",
        ];
        $legal = ['VELOCIDADE' => 'Referência fictícia DEMO-ENQ-VEL (transcrição ilustrativa)', 'AVANCO_SEMAFORO' => 'Referência fictícia DEMO-ENQ-SEM (transcrição ilustrativa)',
            'PARADA_FAIXA' => 'Referência fictícia DEMO-ENQ-FXP (transcrição ilustrativa)', 'CONVERSAO_PROIBIDA' => 'Referência fictícia DEMO-ENQ-CNV (transcrição ilustrativa)',
            'FAIXA_EXCLUSIVA' => 'Referência fictícia DEMO-ENQ-FXE (transcrição ilustrativa)'];

        $create = static function (string $key, array $data) use ($fines, $uid, $legal): int {
            $data += ['legal_reference' => $legal[$data['infraction_kind'] ?? ''] ?? null, 'notes' => 'Registro sintético de demonstração.'];

            return $fines->create($data + ['command_uid' => $uid("{$key}:criar")], null, 'SEMENTE');
        };
        $register = static fn(string $key, int $id) => $fines->register($id, ['version' => $version($id), 'command_uid' => $uid("{$key}:registrar")], null, 'SEMENTE');
        $check = static fn(string $key, int $id, array $c) => $fines->addCheck($id, $c + ['version' => (string) $version($id), 'command_uid' => $uid("{$key}:conferencia:{$c['scope']}")], null, 'SEMENTE');
        $progress = static fn(string $key, int $id, array $p) => $fines->updateProgress($id, $p + ['version' => (string) $version($id), 'command_uid' => $uid("{$key}:andamento"),
            'process_stage' => 'NAO_INFORMADA', 'payment_status' => 'NAO_INFORMADO', 'contest_status' => 'NAO_INFORMADA'], null, 'SEMENTE');
        $regular = static fn(int $daysAgo, string $time, string $sourceType, string $reference): array => ['scope' => 'OCORRENCIA', 'checked_at' => $at($daysAgo, $time),
            'document_status' => 'REGULARIDADE_INFORMADA', 'source_type' => $sourceType, 'source_reference' => $reference, 'crlv_exercise' => (string) $year];

        $count = 0;
        // 1. Radar de velocidade completo: três velocidades coerentes, autuação, pagamento não informado.
        $id = $create('c01', $radar('VS5-24-00201', 'VELOCIDADE', 'Avenida Sampaio Vidal', 'Cruzamento semaforizado', 'Palmital') + $metrology('0201', 200)
            + ['description' => 'Excesso de velocidade registrado pelo radar da Av. Sampaio Vidal', 'occurred_at' => $at(6, '09:10'), 'external_ait' => 'DEMO-AIT-26-000101',
                'speed_limit_kmh' => '50', 'speed_measured_kmh' => '71', 'speed_considered_kmh' => '64']
            + $vehicle('DMO1A23', '01234567890', 'Movix', 'Urbano 1.0', 'Passageiro', 'Prata', 2021));
        $register('c01', $id);
        $check('c01', $id, $regular(5, '10:00', 'CONSULTA_OFICIAL_INFORMADA', 'Consulta feita fora do sistema e informada à mão (fictícia): DEMO-CONS-0001'));
        $progress('c01', $id, ['process_stage' => 'AUTUACAO', 'nominal_amount' => '130,16', 'autuation_issued_on' => $day(3)]);
        $count++;

        // 2. Medidor com agente operador associado e dados metrológicos incompletos (A conferir).
        $id = $create('c02', $radar('PCL-0042', 'VELOCIDADE', 'Avenida Rio Branco', 'Lombada eletrônica próxima à escola', 'Fragata', 'Hermes 04')
            + ['description' => 'Velocidade acima do limite junto à escola, medidor com operador', 'occurred_at' => $at(4, '07:45'), 'external_ait' => 'DEMO-AIT-26-000102',
                'speed_limit_kmh' => '40', 'speed_measured_kmh' => '58', 'speed_considered_kmh' => '51', 'radar_meter_kind' => 'Medidor operado por agente (informado)']
            + $vehicle('DMO2B34', '09876543210', 'Rodafort', 'Carga 2', 'Carga', 'Branca', 2019));
        $register('c02', $id);
        $count++;

        // 3. Radar com avanço de sinal: sem bloco de velocidade.
        $id = $create('c03', $radar('VS5-24-00201', 'AVANCO_SEMAFORO', 'Avenida Sampaio Vidal', 'Cruzamento semaforizado', 'Palmital')
            + ['description' => 'Avanço de sinal vermelho no cruzamento da Av. Sampaio Vidal', 'occurred_at' => $at(8, '18:22'), 'external_ait' => 'DEMO-AIT-26-000103',
                'evidence_reference' => 'DEMO-IMG-0303 (sequência fictícia de imagens)']
            + $vehicle('DMO3C45', '04561237890', 'Cidália', 'Hatch 1.3', 'Passageiro', 'Vermelha', 2022));
        $register('c03', $id);
        $check('c03', $id, $regular(7, '11:30', 'CONSULTA_OFICIAL_INFORMADA', 'Consulta feita fora do sistema e informada à mão (fictícia): DEMO-CONS-0003'));
        $count++;

        // 4. Agente, placa no formato antigo e RENAVAM com zeros à esquerda.
        $id = $create('c04', $agent('Hermes 01', 'PARADA_FAIXA', 'Rua São Luiz', 'Calçadão, faixa de pedestres', 'Centro')
            + ['description' => 'Parada sobre a faixa de pedestres no calçadão', 'occurred_at' => $at(3, '12:05'), 'external_ait' => 'DEMO-AIT-26-000104']
            + $vehicle('DMO1234', '00012345678', 'Trilha Motors', 'Sedã 2.0', 'Passageiro', 'Preta', 2012));
        $register('c04', $id);
        $check('c04', $id, $regular(3, '12:10', 'CRLV_APRESENTADO', 'CRLV-e exibido no local (fictício)'));
        $count++;

        // 5. Agente, RENAVAM desconhecido com justificativa, documentação não verificada.
        $id = $create('c05', $agent('Hermes 06', 'CONVERSAO_PROIBIDA', 'Avenida República', 'Conversão à esquerda sinalizada como proibida', 'Fragata')
            + ['description' => 'Conversão proibida na Av. República', 'occurred_at' => $at(2, '16:40'), 'external_ait' => 'DEMO-AIT-26-000105']
            + $vehicle('DMO5D67', null, 'Movix', 'Urbano 1.0', 'Passageiro', 'Azul', 2020, 'Documento do veículo não apresentado no local'));
        $register('c05', $id);
        $count++;

        // 6. Pendência documental informada na ocorrência (com revisão marcada que já passou).
        $id = $create('c06', $agent('Hermes 01', 'PARADA_FAIXA', 'Avenida Santo Antônio', 'Frente ao ponto de ônibus', 'Centro')
            + ['description' => 'Parada sobre a faixa na Av. Santo Antônio', 'occurred_at' => $at(30, '08:15'), 'external_ait' => 'DEMO-AIT-26-000106']
            + $vehicle('DMO6E78', '07418529630', 'Rodafort', 'Picape 1.6', 'Misto', 'Cinza', 2016));
        $register('c06', $id);
        $check('c06', $id, ['scope' => 'OCORRENCIA', 'checked_at' => $at(30, '08:20'), 'document_status' => 'PENDENCIA_INFORMADA', 'source_type' => 'CRLV_APRESENTADO',
            'source_reference' => 'CRLV de exercício anterior exibido no local (fictício)', 'crlv_exercise' => (string) ($year - 1),
            'pending_items' => 'Licenciamento do exercício não comprovado no local (informado).', 'review_on' => $day(10)]);
        $count++;

        // 7. Pendência na ocorrência e regularidade informada depois: a ocorrência conserva a pendência.
        $id = $create('c07', $agent('Hermes 04', 'FAIXA_EXCLUSIVA', 'Avenida Tiradentes', 'Faixa exclusiva de ônibus', 'Fragata')
            + ['description' => 'Circulação em faixa exclusiva na Av. Tiradentes', 'occurred_at' => $at(25, '17:50'), 'external_ait' => 'DEMO-AIT-26-000107']
            + $vehicle('DMO7F89', '03692581470', 'Cidália', 'Hatch 1.3', 'Passageiro', 'Branca', 2018));
        $register('c07', $id);
        $check('c07', $id, ['scope' => 'OCORRENCIA', 'checked_at' => $at(25, '17:55'), 'document_status' => 'PENDENCIA_INFORMADA', 'source_type' => 'RELATO_MANUAL',
            'source_reference' => 'Relato do agente no local (fictício)', 'pending_items' => 'CRLV do exercício não apresentado.']);
        $check('c07', $id, ['scope' => 'POSTERIOR', 'effective_on' => $day(2), 'checked_at' => $at(2, '14:00'), 'document_status' => 'REGULARIDADE_INFORMADA',
            'source_type' => 'CRLV_APRESENTADO', 'source_reference' => 'CRLV-e do exercício apresentado na base (fictício)', 'crlv_exercise' => (string) $year]);
        $count++;

        // 8. Penalidade notificada, pagamento informado e recurso em análise ao mesmo tempo.
        $id = $create('c08', $radar('PCL-0042', 'VELOCIDADE', 'Avenida Rio Branco', 'Lombada eletrônica próxima à escola', 'Fragata') + $metrology('0042', 150)
            + ['description' => 'Excesso de velocidade na lombada eletrônica da Av. Rio Branco', 'occurred_at' => $at(60, '13:30'), 'external_ait' => 'DEMO-AIT-26-000108',
                'speed_limit_kmh' => '40', 'speed_measured_kmh' => '62', 'speed_considered_kmh' => '55']
            + $vehicle('DMO8G90', '05544332211', 'Trilha Motors', 'SUV 1.5', 'Passageiro', 'Verde', 2023));
        $register('c08', $id);
        $check('c08', $id, $regular(58, '09:00', 'CONSULTA_OFICIAL_INFORMADA', 'Consulta feita fora do sistema e informada à mão (fictícia): DEMO-CONS-0008'));
        $progress('c08', $id, ['process_stage' => 'PENALIDADE_NOTIFICADA', 'nominal_amount' => '195,23', 'autuation_issued_on' => $day(50), 'autuation_received_on' => $day(45),
            'penalty_issued_on' => $day(15), 'penalty_received_on' => $day(10), 'appeal_due_on' => $day(-10),
            'payment_status' => 'PAGAMENTO_INFORMADO', 'paid_on' => $day(5), 'paid_amount' => '195,23', 'payment_reference' => 'DEMO-PAG-0008 (comprovante fictício)',
            'contest_status' => 'EM_ANALISE', 'contest_kind' => 'RECURSO_1', 'contest_submitted_on' => $day(6), 'contest_reference' => 'DEMO-PROT-0008 (protocolo fictício)']);
        $count++;

        // 9. Rascunho incompleto, com o caminho para completar na ficha.
        $create('c09', $radar('VS5-24-00201', 'AVANCO_SEMAFORO', 'Avenida Sampaio Vidal', 'Cruzamento semaforizado', 'Palmital')
            + ['description' => 'Rascunho: avanço de sinal a completar com os dados do veículo', 'occurred_at' => $at(1, '18:40')]);
        $count++;

        // 10. Registro cancelado internamente com motivo (não é anulação do auto).
        $id = $create('c10', $agent('Hermes 04', 'PARADA_FAIXA', 'Avenida Rio Branco', 'Faixa de pedestres da praça', 'Fragata')
            + ['description' => 'Parada sobre a faixa lançada em duplicidade', 'occurred_at' => $at(9, '10:30'), 'ait_missing_reason' => 'Auto ainda não recebido do órgão']
            + $vehicle('DMO0H12', '06655443322', 'Movix', 'Urbano 1.0', 'Passageiro', 'Prata', 2017));
        $register('c10', $id);
        $fines->cancel($id, ['reason' => 'Lançado em duplicidade nesta demonstração; o registro correto é outro (fictício).', 'version' => (string) $version($id), 'command_uid' => $uid('c10:cancelar')], null, 'SEMENTE');
        $count++;

        // 11. Origem cujo cadastro atual difere da ocorrência (radar hoje em mau funcionamento): o registro guarda o retrato.
        $id = $create('c11', $radar('PCL-0063', 'VELOCIDADE', 'Rodovia Comandante João Ribeiro de Barros', 'Acesso ao Parque São Jorge', 'Parque São Jorge') + $metrology('0063', 90)
            + ['description' => 'Excesso de velocidade no acesso ao Parque São Jorge (antes da falha atual do radar)', 'occurred_at' => $at(20, '06:55'), 'external_ait' => 'DEMO-AIT-26-000111',
                'speed_limit_kmh' => '80', 'speed_measured_kmh' => '104', 'speed_considered_kmh' => '97']
            + $vehicle('DMO1J34', '02233445566', 'Rodafort', 'Carga 2', 'Carga', 'Azul', 2015));
        $register('c11', $id);
        $count++;

        // 12. Retificação da placa: a conferência anterior fica só no histórico; a nova identidade pede nova conferência.
        $base = $agent('Hermes 06', 'CONVERSAO_PROIBIDA', 'Avenida Brigadeiro Eduardo Gomes', 'Retorno sinalizado como proibido', 'Jardim Maria Izabel')
            + ['description' => 'Conversão proibida na Av. Brigadeiro Eduardo Gomes', 'occurred_at' => $at(12, '15:20'), 'external_ait' => 'DEMO-AIT-26-000112',
                'legal_reference' => $legal['CONVERSAO_PROIBIDA'], 'notes' => 'Registro sintético de demonstração.']
            + $vehicle('DMO4E21', '08877665544', 'Cidália', 'Sedã 1.6', 'Passageiro', 'Bege', 2014);
        $id = $create('c12', $base);
        $register('c12', $id);
        $check('c12', $id, $regular(12, '15:30', 'CRLV_APRESENTADO', 'CRLV-e exibido no local (fictício)'));
        $fines->update($id, ['vehicle_plate' => 'DMO4E27', 'reason' => 'Placa transcrita com erro no lançamento; corrigida conforme o auto (fictício).',
            'version' => (string) $version($id), 'command_uid' => $uid('c12:retificar')] + $base, null, 'SEMENTE');
        $count++;

        if ($profile === DemoScenario::FULL) {
            // Volume: 28 registros simples alternando radar de velocidade e agente, com conferência e pagamento em parte deles.
            $streets = [['Avenida Sampaio Vidal', 'Centro'], ['Avenida Rio Branco', 'Fragata'], ['Avenida República', 'Fragata'], ['Rua São Luiz', 'Centro']];
            for ($i = 1; $i <= 28; $i++) {
                $key = sprintf('v%02d', $i);
                [$street, $region] = $streets[$i % 4];
                $plate = sprintf('DM%s%d%s%02d', chr(65 + $i % 26), $i % 10, chr(65 + ($i * 7) % 26), $i);
                $data = $i % 2 === 0
                    ? $radar($i % 4 === 0 ? 'VS5-24-00201' : 'PCL-0042', 'VELOCIDADE', $street, 'Trecho fiscalizado', $region) + $metrology(sprintf('V%02d', $i), 120)
                        + ['speed_limit_kmh' => $i % 4 === 0 ? '50' : '40', 'speed_measured_kmh' => (string) (55 + $i), 'speed_considered_kmh' => (string) (48 + $i)]
                    : $agent(['Hermes 01', 'Hermes 04', 'Hermes 06'][$i % 3], ['PARADA_FAIXA', 'CONVERSAO_PROIBIDA', 'FAIXA_EXCLUSIVA'][$i % 3], $street, 'Fiscalização de rotina', $region);
                $id = $create($key, $data + ['description' => "Registro de volume {$i} da demonstração", 'occurred_at' => $at(3 + $i * 3, sprintf('%02d:%02d', 7 + $i % 12, ($i * 13) % 60)),
                    'external_ait' => sprintf('DEMO-AIT-26-%06d', 200 + $i)] + $vehicle($plate, sprintf('%011d', $i * 104729), 'Movix', 'Urbano 1.0', 'Passageiro', 'Prata', 2010 + $i % 14));
                $register($key, $id);
                if ($i % 3 === 0) {
                    $check($key, $id, $regular(2 + $i * 3, '10:00', 'CONSULTA_OFICIAL_INFORMADA', sprintf('Consulta feita fora do sistema e informada à mão (fictícia): DEMO-CONS-%04d', 100 + $i)));
                }
                if ($i % 4 === 0) {
                    $progress($key, $id, ['process_stage' => 'PENALIDADE_NOTIFICADA', 'nominal_amount' => '130,16', 'payment_status' => 'PAGAMENTO_INFORMADO',
                        'paid_on' => $day(1 + $i), 'paid_amount' => '130,16', 'payment_reference' => sprintf('DEMO-PAG-%04d (fictício)', 100 + $i)]);
                }
                $count++;
            }
        }

        $s->metaRepository()->set('fines_seed', self::VERSION, $count, Time::toDb($s->clock()->now()));

        return $count;
    }
}
