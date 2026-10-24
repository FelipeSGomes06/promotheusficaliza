<?php

declare(strict_types=1);

namespace Fiscaliza\Demo;

/**
 * Cenário sintético de demonstração em Marília/SP.
 *
 * - Órgãos, marcas, modelos, séries, prefixos e agentes são fictícios.
 * - Bairros, logradouros e CEPs de logradouro são referências geográficas públicas; as coordenadas vieram de uma
 *   consulta pontual ao Nominatim (© colaboradores do OpenStreetMap, ODbL) e são ilustrativas: nenhum ponto
 *   representa equipamento, base ou agente real.
 * - "fragility" é uma variável latente usada apenas pelo gerador de histórico e pelo simulador (propensão a falhas).
 *   A análise preventiva NÃO a lê: ela só enxerga o histórico de eventos e ordens.
 */
final class DemoScenario
{
    /** Perfil padrão da apresentação: 17 equipamentos e 7 agentes que cobrem todas as categorias e situações. */
    public const COMPACT = 'compacto';
    /** Perfil completo (48 equipamentos e 21 agentes), para testes de volume e para a metodologia da análise. */
    public const FULL = 'completo';

    /**
     * Curadoria do perfil compacto (por série). Cada item cobre mais de uma capacidade:
     * - radares: multifunção com evento atrasado; lombada com preventiva vencida; falha com corretiva imediata;
     *   ponto planejado (não instalado); portátil operado por agente (multa com operador associado);
     * - câmeras: PTZ em operação; instalação recente (histórico curto) com sinal atrasado; furtada; sem sinal;
     * - balança com preventiva próxima; cancela com preventiva futura; totem desativado;
     * - veículos: dois em circulação (posição × base) e um em manutenção em execução;
     * - patinetes: um em circulação e um furtado (última posição antiga).
     */
    private const COMPACT_SERIALS = [
        'VS5-24-00201', 'PCL-0042', 'VS3-23-00088', 'PCL-0063', 'VS5-26-00012',
        'LV30-7781', 'OPD-4K-1199', 'OPD-4K-1140', 'OPB-1080-2290',
        'PPW-0007', 'BVF-3301', 'TXC-0555',
        'AUR-P-0301', 'AUR-E-0110', 'AUR-P-0340',
        'MOB-2-0041', 'MOB-2-0043',
    ];
    /** Agentes do perfil compacto: as quatro situações, base × posição e um em turno sem posição recente. */
    private const COMPACT_AGENTS = ['Hermes 01', 'Hermes 04', 'Hermes 06', 'Atena 01', 'Atena 06', 'Têmis 01', 'Têmis 04'];

    /**
     * Preventivas abertas com data fixa relativa à carga (dias a partir de hoje), para que vencida, próxima e futura
     * existam em qualquer semente — o histórico aleatório decidia isso antes.
     */
    public const PENDING_PREVENTIVE = ['PCL-0042' => -4, 'PPW-0007' => 3, 'BVF-3301' => 18];

    public static function profile(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            '', self::COMPACT => self::COMPACT,
            self::FULL => self::FULL,
            default => throw new \InvalidArgumentException("Perfil de demonstração desconhecido: \"{$value}\" (use compacto ou completo)."),
        };
    }
    /** @return list<array{0: string, 1: string, 2: string}> código, nome, tipo */
    public static function agencies(): array
    {
        return [
            ['DMT', 'Departamento de Mobilidade e Trânsito (fictício)', 'TRANSITO'],
            ['GCD', 'Guarda Civil de Demonstração', 'GUARDA'],
            ['PDS', 'Policiamento de Demonstração (fictício)', 'POLICIA'],
            ['SMV', 'Serviço de Manutenção Viária (fictício)', 'MANUTENCAO'],
            ['STU', 'Transporte Urbano de Demonstração', 'TRANSPORTE'],
        ];
    }

    /**
     * Bairros de referência. A zona é um agrupamento manual para a demonstração, não uma divisão oficial.
     *
     * @return list<array{0: string, 1: string, 2: string, 3: string}> nome, zona, lat, lng
     */
    public static function regions(): array
    {
        return [
            ['Centro', 'CENTRO', '-22.214000', '-49.950500'],
            ['Fragata', 'SUL', '-22.223000', '-49.940500'],
            ['Alto Cafezal', 'NORTE', '-22.209200', '-49.956200'],
            ['Palmital', 'NORTE', '-22.209800', '-49.951200'],
            ['Somenzari', 'NORTE', '-22.207000', '-49.954100'],
            ['Banzato', 'LESTE', '-22.207300', '-49.940900'],
            ['Jardim Maria Izabel', 'LESTE', '-22.212900', '-49.930100'],
            ['Jardim Jequitibá', 'LESTE', '-22.201300', '-49.931200'],
            ['Jardim Tropical', 'LESTE', '-22.226300', '-49.925700'],
            ['Lácio', 'LESTE', '-22.222400', '-49.905000'],
            ['Jardim Esplanada', 'SUL', '-22.242200', '-49.918100'],
            ['Parque São Jorge', 'SUL', '-22.235000', '-49.929500'],
            ['Nova Marília', 'SUL', '-22.248500', '-49.929900'],
            ['Jardim Teotônio Vilela', 'SUL', '-22.250900', '-49.942400'],
            ['Jardim Portal do Sol', 'SUL', '-22.225500', '-49.946500'],
            ['Jardim Polyana', 'OESTE', '-22.223700', '-49.958300'],
            ['Jardim Cavallari', 'OESTE', '-22.231600', '-49.973600'],
            ['Jardim Universitário', 'OESTE', '-22.233500', '-49.979300'],
            ['Jardim Bandeirantes', 'OESTE', '-22.202300', '-49.966200'],
            ['Lorenzetti', 'NORTE', '-22.193400', '-49.958200'],
            ['Vila Santa Antonieta', 'NORTE', '-22.176500', '-49.969000'],
            ['Vila Romana', 'NORTE', '-22.175400', '-49.916600'],
        ];
    }

    /**
     * Equipamentos. Campos: type, maker, model, serial (null = desconhecida), agency, street, ref, cep, region,
     * lat, lng, final (situação ao fim do histórico; null = deixar o histórico decidir), interval (dias de preventiva),
     * fragility, installed (dias desde a instalação), specs.
     *
     * @return list<array<string, mixed>>
     */
    public static function equipment(string $profile = self::COMPACT): array
    {
        $all = self::allEquipment();

        return $profile === self::FULL ? $all : array_values(array_filter($all, static fn(array $e): bool => in_array($e['serial'], self::COMPACT_SERIALS, true)));
    }

    /** @return list<array<string, mixed>> */
    private static function allEquipment(): array
    {
        $radar = static fn(array $functions, ?int $limit, int $lanes, string $direction, string $modality = 'FIXO') => array_filter(
            ['functions' => $functions, 'modality' => $modality, 'speed_limit_kmh' => $limit, 'lanes' => $lanes, 'direction' => $direction],
            static fn($v) => $v !== null,
        );
        $camera = static fn(string $res, bool $ir, bool $color, bool $ptz, int $fov) => ['resolution' => $res, 'night_vision' => $ir, 'color_image' => $color, 'ptz' => $ptz, 'field_of_view_deg' => $fov];
        $e = static fn(string $type, string $maker, string $model, ?string $serial, string $agency, string $street, ?string $ref, ?string $cep, string $region, string $lat, string $lng, ?string $final, ?int $interval, float $fragility, int $installed, array $specs) => compact(
            'type',
            'maker',
            'model',
            'serial',
            'agency',
            'street',
            'ref',
            'cep',
            'region',
            'lat',
            'lng',
            'final',
            'interval',
            'fragility',
            'installed',
            'specs',
        );

        return [
            // Radares — avenidas de maior fluxo.
            $e('RADAR', 'Vigília Sistemas', 'VS-Rad 300', 'VS3-24-00117', 'DMT', 'Avenida Sampaio Vidal', 'Quadra central, sentido bairro', '17509-051', 'Centro', '-22.211200', '-49.951500', null, 90, 0.6, 900, $radar(['VELOCIDADE'], 50, 2, 'Centro → bairro')),
            $e('RADAR', 'Vigília Sistemas', 'VS-Rad 500 Multi', 'VS5-24-00201', 'DMT', 'Avenida Sampaio Vidal', 'Cruzamento semaforizado', '17500-200', 'Palmital', '-22.209700', '-49.952600', null, 90, 1.0, 900, $radar(['VELOCIDADE', 'AVANCO_SEMAFORO', 'PARADA_FAIXA'], 50, 3, 'Ambos os sentidos')),
            $e('RADAR', 'Ponto Certo Tecnologia', 'PC-Lombada E', 'PCL-0042', 'DMT', 'Avenida Rio Branco', 'Lombada eletrônica próxima à escola', '17500-060', 'Fragata', '-22.221200', '-49.951000', null, 60, 2.6, 1200, $radar(['VELOCIDADE'], 40, 2, 'Bairro → centro')),
            $e('RADAR', 'Vigília Sistemas', 'VS-Rad 300', 'VS3-23-00088', 'DMT', 'Avenida Rio Branco', 'Trecho do Jardim Polyana', '17502-000', 'Jardim Polyana', '-22.223700', '-49.958300', null, 90, 1.2, 1300, $radar(['VELOCIDADE'], 60, 2, 'Oeste → centro', 'PORTATIL')),
            $e('RADAR', 'Vigília Sistemas', 'VS-Rad 500 Multi', 'VS5-23-00150', 'DMT', 'Avenida República', 'Cruzamento com semáforo', '17509-200', 'Fragata', '-22.216900', '-49.941900', null, 90, 2.2, 1100, $radar(['AVANCO_SEMAFORO', 'PARADA_FAIXA'], null, 2, 'Sul → norte')),
            $e('RADAR', 'Ponto Certo Tecnologia', 'PC-Lombada E', 'PCL-0057', 'DMT', 'Avenida República', 'Próximo à praça', '17506-030', 'Somenzari', '-22.197800', '-49.956000', null, 60, 0.8, 700, $radar(['VELOCIDADE'], 40, 1, 'Norte → centro')),
            $e('RADAR', 'Vigília Sistemas', 'VS-Rad 300', null, 'DMT', 'Avenida das Esmeraldas', 'Antes da rotatória', '17521-460', 'Jardim Tropical', '-22.228700', '-49.919500', null, 90, 1.5, 1000, $radar(['VELOCIDADE'], 60, 2, 'Leste → oeste')),
            $e('RADAR', 'Vigília Sistemas', 'VS-Rad 500 Multi', 'VS5-24-00233', 'DMT', 'Avenida Tiradentes', 'Faixa exclusiva', '17501-330', 'Fragata', '-22.223100', '-49.936800', null, 90, 1.9, 600, $radar(['VELOCIDADE', 'FAIXA_EXCLUSIVA'], 50, 3, 'Ambos os sentidos')),
            $e('RADAR', 'Vigília Sistemas', 'VS-Rad 300', 'VS3-25-00301', 'DMT', 'Avenida Brigadeiro Eduardo Gomes', 'Trecho em declive', '17515-430', 'Jardim Maria Izabel', '-22.208400', '-49.935700', null, 90, 1.1, 800, $radar(['VELOCIDADE', 'CONVERSAO_PROIBIDA'], 60, 2, 'Norte → sul')),
            $e('RADAR', 'Ponto Certo Tecnologia', 'PC-Lombada E', 'PCL-0063', 'DMT', 'Rodovia Comandante João Ribeiro de Barros', 'Acesso ao Parque São Jorge', null, 'Parque São Jorge', '-22.231600', '-49.921700', 'FALHA', 60, 2.9, 1400, $radar(['VELOCIDADE'], 80, 2, 'Sentido capital')),
            $e('RADAR', 'Vigília Sistemas', 'VS-Rad 500 Multi', 'VS5-26-00012', 'DMT', 'Avenida João Ramalho', 'Ponto planejado para nova fiscalização', '17522-390', 'Nova Marília', '-22.243500', '-49.927400', 'NAO_INSTALADO', 90, 1.0, 0, $radar(['VELOCIDADE', 'AVANCO_SEMAFORO'], 50, 2, 'Sul → centro')),
            $e('RADAR', 'Vigília Sistemas', 'VS-Rad 300', 'VS3-21-00009', 'DMT', 'Avenida Pedro de Toledo', 'Ponto antigo, aguardando remoção', '17509-020', 'Palmital', '-22.207100', '-49.951500', 'DESATIVADO', null, 1.0, 1800, $radar(['VELOCIDADE'], 40, 1, 'Centro → norte')),

            // Câmeras — centro comercial, cruzamentos e terminais.
            $e('CAMERA', 'Óptica Planalto', 'OP-Dome 4K', 'OPD-4K-1101', 'GCD', 'Rua São Luiz', 'Calçadão', '17500-012', 'Centro', '-22.215900', '-49.951000', null, 120, 0.7, 800, $camera('4K', true, true, true, 360)),
            $e('CAMERA', 'Óptica Planalto', 'OP-Bullet 1080', 'OPB-1080-2210', 'GCD', 'Avenida Sampaio Vidal', 'Esquina comercial', '17506-000', 'Somenzari', '-22.208600', '-49.953800', null, 120, 1.3, 900, $camera('1080P', true, false, false, 90)),
            $e('CAMERA', 'Lumina Vision', 'LV-PTZ 30x', 'LV30-7781', 'GCD', 'Avenida Nelson Spielmann', 'Praça', '17509-020', 'Centro', '-22.212400', '-49.948500', null, 120, 1.0, 650, $camera('4MP', true, true, true, 360)),
            $e('CAMERA', 'Óptica Planalto', 'OP-Dome 4K', 'OPD-4K-1102', 'GCD', 'Avenida Nelson Spielmann', 'Cruzamento', '17509-130', 'Fragata', '-22.216200', '-49.945400', 'FALHA', 120, 2.4, 1000, $camera('4K', true, true, false, 110)),
            $e('CAMERA', 'Óptica Planalto', 'OP-Bullet 1080', null, 'GCD', 'Avenida Santo Antônio', 'Frente a ponto de ônibus', '17500-200', 'Centro', '-22.215800', '-49.954100', null, 120, 0.9, 500, $camera('1080P', false, true, false, 90)),
            $e('CAMERA', 'Lumina Vision', 'LV-PTZ 30x', 'LV30-7795', 'GCD', 'Avenida Santo Antônio', 'Viaduto', '17500-070', 'Fragata', '-22.220600', '-49.952200', null, 120, 1.7, 1100, $camera('4MP', true, true, true, 360)),
            $e('CAMERA', 'Óptica Planalto', 'OP-Bullet 1080', 'OPB-1080-2244', 'GCD', 'Avenida Carlos Gomes', 'Escola', '17509-130', 'Fragata', '-22.217400', '-49.947500', null, 120, 1.1, 700, $camera('1080P', true, false, false, 80)),
            $e('CAMERA', 'Óptica Planalto', 'OP-Dome 4K', 'OPD-4K-1140', 'GCD', 'Avenida Carlos Gomes', 'Trecho residencial', '17501-441', 'Jardim Portal do Sol', '-22.226100', '-49.945600', 'FURTADO', 120, 1.0, 400, $camera('4K', true, true, false, 110)),
            $e('CAMERA', 'Lumina Vision', 'LV-PTZ 30x', 'LV30-7802', 'GCD', 'Avenida Vicente Ferreira', 'Rotatória', '17509-200', 'Fragata', '-22.217100', '-49.939700', null, 120, 2.0, 1300, $camera('4MP', true, true, true, 360)),
            $e('CAMERA', 'Óptica Planalto', 'OP-Bullet 1080', 'OPB-1080-2290', 'GCD', 'Avenida Castro Alves', 'Terminal', '17507-000', 'Lorenzetti', '-22.192200', '-49.959300', null, 120, 0.8, 600, $camera('1080P', true, true, false, 100)),
            $e('CAMERA', 'Óptica Planalto', 'OP-Dome 4K', 'OPD-4K-1188', 'GCD', 'Avenida Cascata', 'Parque', '17515-490', 'Jardim Maria Izabel', '-22.213200', '-49.925000', null, 120, 1.4, 750, $camera('4K', true, true, false, 120)),
            $e('CAMERA', 'Lumina Vision', 'LV-PTZ 30x', 'LV30-7900', 'GCD', 'Avenida das Esmeraldas', 'Ponto planejado', '17516-280', 'Jardim Esplanada', '-22.242200', '-49.918100', 'NAO_INSTALADO', 120, 1.0, 0, $camera('4MP', true, true, true, 360)),
            $e('CAMERA', 'Óptica Planalto', 'OP-Bullet 1080', 'OPB-1080-2301', 'GCD', 'Avenida Tiradentes', 'Comércio', '17519-020', 'Fragata', '-22.226100', '-49.934200', null, 120, 1.2, 300, $camera('1080P', true, false, false, 90)),
            $e('CAMERA', 'Óptica Planalto', 'OP-Dome 4K', 'OPD-4K-1199', 'GCD', 'Avenida República', 'Saída norte', '17512-043', 'Vila Santa Antonieta', '-22.178500', '-49.967000', null, 120, 1.0, 45, $camera('4K', true, true, false, 110)),

            // Balanças.
            $e('BALANCA', 'Pesagem Paulista', 'PP-WIM 2', 'PPW-0007', 'DMT', 'Avenida das Esmeraldas', 'Acesso de carga leste', '17516-760', 'Lácio', '-22.223900', '-49.896900', null, 60, 1.8, 1500, ['weighing_mode' => 'DINAMICA', 'max_load_t' => 74, 'lanes' => 2]),
            $e('BALANCA', 'Pesagem Paulista', 'PP-Estática 80', 'PPE-0019', 'DMT', 'Avenida Brigadeiro Eduardo Gomes', 'Pátio de pesagem norte', '17514-820', 'Vila Romana', '-22.175400', '-49.916600', null, 60, 1.0, 1700, ['weighing_mode' => 'ESTATICA', 'max_load_t' => 80, 'lanes' => 1]),

            // Cancelas.
            $e('CANCELA', 'Barreira Viva', 'BV-Fast 4', 'BVF-3301', 'STU', 'Avenida Castro Alves', 'Garagem do transporte', '17506-030', 'Somenzari', '-22.200300', '-49.957200', null, 90, 1.6, 900, ['access_control' => 'TAG', 'arm_length_m' => 4, 'anti_crush' => true]),
            $e('CANCELA', 'Barreira Viva', 'BV-Fast 4', 'BVF-3302', 'DMT', 'Avenida República', 'Pátio de veículos removidos', '17512-043', 'Vila Santa Antonieta', '-22.175400', '-49.970300', null, 90, 2.1, 1200, ['access_control' => 'REMOTO', 'arm_length_m' => 6, 'anti_crush' => true]),
            $e('CANCELA', 'Barreira Viva', 'BV-Fast 4', 'BVF-3315', 'GCD', 'Avenida Santo Antônio', 'Base da guarda', '17511-349', 'Lorenzetti', '-22.185000', '-49.963400', null, 90, 0.7, 500, ['access_control' => 'MANUAL', 'arm_length_m' => 3, 'anti_crush' => false]),
            $e('CANCELA', 'Barreira Viva', 'BV-Fast 4', null, 'STU', 'Avenida Rio Branco', 'Terminal leste', '17516-760', 'Lácio', '-22.222400', '-49.888700', null, 90, 1.2, 800, ['access_control' => 'TICKET', 'arm_length_m' => 4, 'anti_crush' => true]),

            // Totens.
            $e('TOTEM', 'Totemix', 'TX-Cidadão 32', 'TXC-0501', 'GCD', 'Avenida Rio Branco', 'Praça central', '17500-000', 'Fragata', '-22.220900', '-49.949900', null, 180, 0.9, 600, ['services' => ['INFORMACAO', 'BOTAO_AJUDA', 'WIFI'], 'screen_in' => 32, 'accessible_height' => true]),
            $e('TOTEM', 'Totemix', 'TX-Cidadão 32', 'TXC-0502', 'GCD', 'Avenida Sampaio Vidal', 'Calçada sul', '17501-441', 'Jardim Portal do Sol', '-22.224000', '-49.947200', null, 180, 1.5, 600, ['services' => ['INFORMACAO', 'BOTAO_AJUDA'], 'screen_in' => 32, 'accessible_height' => true]),
            $e('TOTEM', 'Totemix', 'TX-Cidadão 43', 'TXC-0610', 'GCD', 'Avenida João Ramalho', 'Centro comercial do bairro', '17522-390', 'Nova Marília', '-22.246200', '-49.929400', null, 180, 1.1, 400, ['services' => ['INFORMACAO', 'BOTAO_AJUDA', 'RECARGA'], 'screen_in' => 43, 'accessible_height' => true]),
            $e('TOTEM', 'Totemix', 'TX-Cidadão 32', 'TXC-0533', 'GCD', 'Avenida das Esmeraldas', 'Parque', '17516-000', 'Jardim Maria Izabel', '-22.224800', '-49.932700', null, 180, 0.8, 500, ['services' => ['INFORMACAO', 'WIFI'], 'screen_in' => 32, 'accessible_height' => false]),
            $e('TOTEM', 'Totemix', 'TX-Cidadão 43', 'TXC-0640', 'GCD', 'Avenida Vicente Ferreira', 'Ponto planejado', '17515-410', 'Banzato', '-22.206400', '-49.938300', 'NAO_INSTALADO', 180, 1.0, 0, ['services' => ['INFORMACAO', 'BOTAO_AJUDA', 'WIFI', 'RECARGA'], 'screen_in' => 43, 'accessible_height' => true]),
            $e('TOTEM', 'Totemix', 'TX-Cidadão 32', 'TXC-0555', 'GCD', 'Avenida Cascata', 'Ponto desativado por obra', '17515-490', 'Lácio', '-22.217400', '-49.919100', 'DESATIVADO', null, 1.0, 900, ['services' => ['INFORMACAO'], 'screen_in' => 32, 'accessible_height' => true]),

            // Veículos (recursos móveis; endereço = base).
            $e('VEICULO', 'Aurora Veículos', 'Aurora Patrulha', 'AUR-P-0301', 'DMT', 'Rua São Luiz', 'Base Centro', '17500-012', 'Centro', '-22.216500', '-49.949000', null, 30, 1.0, 700, ['vehicle_role' => 'FISCALIZACAO', 'call_sign' => 'VF-01', 'seats' => 5, 'fuel' => 'FLEX']),
            $e('VEICULO', 'Aurora Veículos', 'Aurora Patrulha', 'AUR-P-0302', 'DMT', 'Avenida Cascata', 'Base Leste', '17515-430', 'Jardim Maria Izabel', '-22.213100', '-49.932400', null, 30, 1.4, 700, ['vehicle_role' => 'FISCALIZACAO', 'call_sign' => 'VF-02', 'seats' => 5, 'fuel' => 'FLEX']),
            $e('VEICULO', 'Aurora Veículos', 'Aurora EV Utilitário', 'AUR-E-0110', 'GCD', 'Avenida João Ramalho', 'Base Sul', '17522-390', 'Nova Marília', '-22.243500', '-49.927400', null, 45, 0.9, 300, ['vehicle_role' => 'EQUIPE', 'call_sign' => 'GC-07', 'seats' => 4, 'fuel' => 'ELETRICO']),
            $e('VEICULO', 'Aurora Veículos', 'Aurora Van 12', 'AUR-V-0920', 'STU', 'Avenida Rio Branco', 'Base Oeste', '17502-000', 'Jardim Polyana', '-22.223700', '-49.958300', null, 30, 1.6, 900, ['vehicle_role' => 'TRANSPORTE', 'call_sign' => 'TU-12', 'seats' => 12, 'fuel' => 'DIESEL']),
            $e('VEICULO', 'Aurora Veículos', 'Aurora Patrulha', 'AUR-P-0333', 'PDS', 'Avenida Pedro de Toledo', 'Base Norte', '17509-002', 'Lorenzetti', '-22.193400', '-49.958200', null, 30, 1.0, 500, ['vehicle_role' => 'FISCALIZACAO', 'call_sign' => 'PD-03', 'seats' => 5, 'fuel' => 'HIBRIDO']),
            $e('VEICULO', 'Aurora Veículos', 'Aurora Patrulha', 'AUR-P-0340', 'SMV', 'Avenida Vicente Ferreira', 'Oficina de manutenção', '17515-120', 'Jardim Maria Izabel', '-22.210100', '-49.938900', 'MANUTENCAO', 30, 2.3, 1100, ['vehicle_role' => 'EQUIPE', 'call_sign' => 'MV-04', 'seats' => 3, 'fuel' => 'DIESEL']),

            // Patinetes elétricos das equipes.
            $e('PATINETE', 'Mobilis', 'M-Patrol 2', 'MOB-2-0041', 'GCD', 'Rua São Luiz', 'Base Centro', '17500-012', 'Centro', '-22.216500', '-49.949000', null, 30, 1.0, 250, ['range_km' => 40, 'max_speed_kmh' => 25]),
            $e('PATINETE', 'Mobilis', 'M-Patrol 2', 'MOB-2-0042', 'GCD', 'Rua São Luiz', 'Base Centro', '17500-012', 'Centro', '-22.216500', '-49.949000', null, 30, 1.3, 250, ['range_km' => 40, 'max_speed_kmh' => 25]),
            $e('PATINETE', 'Mobilis', 'M-Patrol 2', 'MOB-2-0043', 'DMT', 'Avenida Cascata', 'Base Leste', '17515-430', 'Jardim Maria Izabel', '-22.213100', '-49.932400', 'FURTADO', 30, 1.0, 200, ['range_km' => 40, 'max_speed_kmh' => 25]),
            $e('PATINETE', 'Mobilis', 'M-Patrol 2', 'MOB-2-0050', 'GCD', 'Avenida Rio Branco', 'Base Oeste', '17502-000', 'Jardim Polyana', '-22.223700', '-49.958300', null, 30, 0.8, 120, ['range_km' => 35, 'max_speed_kmh' => 20]),
        ];
    }

    /**
     * Bases fictícias de trabalho dos agentes.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string}> chave => nome, rua, cep, bairro, lat, lng
     */
    public static function bases(): array
    {
        return [
            'C' => ['Base Centro', 'Rua São Luiz', '17500-012', 'Centro', '-22.216500', '-49.949000'],
            'L' => ['Base Leste', 'Avenida Cascata', '17515-430', 'Jardim Maria Izabel', '-22.213100', '-49.932400'],
            'S' => ['Base Sul', 'Avenida João Ramalho', '17522-390', 'Nova Marília', '-22.243500', '-49.927400'],
            'O' => ['Base Oeste', 'Avenida Rio Branco', '17502-000', 'Jardim Polyana', '-22.223700', '-49.958300'],
            'N' => ['Base Norte', 'Avenida Pedro de Toledo', '17509-002', 'Lorenzetti', '-22.193400', '-49.958200'],
        ];
    }

    /**
     * Agentes fictícios, com prefixos de rádio inspirados no mito de Prometeu (nenhum nome ou matrícula real).
     * Campos: indicativo, função, órgão, base, situação, posição recente? (false = em turno sem posição recente).
     *
     * @return list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: bool}>
     */
    public static function agents(string $profile = self::COMPACT): array
    {
        $all = self::allAgents();

        return $profile === self::FULL ? $all : array_values(array_filter($all, static fn(array $a): bool => in_array($a[0], self::COMPACT_AGENTS, true)));
    }

    /** @return list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: bool}> */
    private static function allAgents(): array
    {
        return [
            ['Hermes 01', 'TRANSITO', 'DMT', 'C', 'EM_ATIVIDADE', true],
            ['Hermes 02', 'TRANSITO', 'DMT', 'C', 'EM_ATIVIDADE', true],
            ['Hermes 03', 'TRANSITO', 'DMT', 'L', 'EM_ATIVIDADE', true],
            ['Hermes 04', 'TRANSITO', 'DMT', 'S', 'EM_PAUSA', true],
            ['Hermes 05', 'TRANSITO', 'DMT', 'O', 'EM_ATIVIDADE', true],
            ['Hermes 06', 'TRANSITO', 'DMT', 'N', 'EM_ATIVIDADE', false],
            ['Hermes 07', 'TRANSITO', 'DMT', 'C', 'FORA_DE_SERVICO', true],
            ['Hermes 08', 'TRANSITO', 'DMT', 'L', 'INDISPONIVEL', true],
            ['Atena 01', 'GUARDA', 'GCD', 'C', 'EM_ATIVIDADE', true],
            ['Atena 02', 'GUARDA', 'GCD', 'C', 'EM_ATIVIDADE', true],
            ['Atena 03', 'GUARDA', 'GCD', 'L', 'EM_ATIVIDADE', true],
            ['Atena 04', 'GUARDA', 'GCD', 'S', 'EM_ATIVIDADE', true],
            ['Atena 05', 'GUARDA', 'GCD', 'O', 'EM_PAUSA', true],
            ['Atena 06', 'GUARDA', 'GCD', 'N', 'FORA_DE_SERVICO', true],
            ['Atena 07', 'GUARDA', 'GCD', 'S', 'EM_ATIVIDADE', true],
            ['Têmis 01', 'POLICIA', 'PDS', 'N', 'EM_ATIVIDADE', true],
            ['Têmis 02', 'POLICIA', 'PDS', 'N', 'EM_ATIVIDADE', true],
            ['Têmis 03', 'POLICIA', 'PDS', 'C', 'EM_ATIVIDADE', true],
            ['Têmis 04', 'POLICIA', 'PDS', 'O', 'INDISPONIVEL', true],
            ['Têmis 05', 'POLICIA', 'PDS', 'S', 'EM_ATIVIDADE', true],
            ['Têmis 06', 'POLICIA', 'PDS', 'L', 'FORA_DE_SERVICO', true],
        ];
    }

    /** Propensão latente a falhas por série (1.0 para equipamentos cadastrados fora do cenário). */
    public static function fragility(?string $serial, string $code): float
    {
        foreach (self::allEquipment() as $item) {
            if ($item['serial'] !== null && $item['serial'] === $serial) {
                return $item['fragility'];
            }
        }

        return 1.0 + (crc32($code) % 50) / 100;
    }
}
