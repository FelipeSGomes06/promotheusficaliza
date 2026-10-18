<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

/**
 * Catálogo dos tipos de equipamento. Para incluir um tipo novo: acrescente-o aqui, inclua o código no CHECK
 * CK_EQUIPMENT_TYPE por uma nova migração e desenhe o ícone/miniatura em public/assets/img (ver docs/dominio.md).
 */
final class EquipmentCatalog
{
    /** @var array<string, EquipmentType>|null */
    private static ?array $types = null;

    /** @return array<string, EquipmentType> */
    public static function all(): array
    {
        return self::$types ??= self::build();
    }

    public static function get(string $code): ?EquipmentType
    {
        return self::all()[$code] ?? null;
    }

    /** @return array<string, EquipmentType> */
    private static function build(): array
    {
        $types = [
            new EquipmentType('RADAR', 'Radar de fiscalização', 'RAD', false, 'Fiscaliza velocidade, avanço de sinal e manobras; um aparelho pode acumular funções.', [
                ['name' => 'functions', 'label' => 'Funções fiscalizadas', 'kind' => 'multi', 'required' => true, 'options' => [
                    'VELOCIDADE' => 'Velocidade', 'AVANCO_SEMAFORO' => 'Avanço de semáforo', 'PARADA_FAIXA' => 'Parada sobre a faixa',
                    'CONVERSAO_PROIBIDA' => 'Conversão proibida', 'FAIXA_EXCLUSIVA' => 'Faixa exclusiva de ônibus',
                ]],
                ['name' => 'speed_limit_kmh', 'label' => 'Limite de velocidade', 'kind' => 'int', 'min' => 20, 'max' => 120, 'unit' => 'km/h',
                    'requiredIf' => ['functions' => 'VELOCIDADE'], 'help' => 'Obrigatório quando fiscaliza velocidade.'],
                ['name' => 'lanes', 'label' => 'Faixas monitoradas', 'kind' => 'int', 'min' => 1, 'max' => 8, 'unit' => 'faixa(s)'],
                ['name' => 'direction', 'label' => 'Sentido', 'kind' => 'text', 'max' => 80, 'help' => 'Ex.: bairro → centro.'],
            ]),
            new EquipmentType('CAMERA', 'Câmera de monitoramento', 'CAM', false, 'Videomonitoramento de vias e espaços públicos.', [
                ['name' => 'resolution', 'label' => 'Resolução', 'kind' => 'enum', 'required' => true, 'options' => [
                    '720P' => 'HD 720p', '1080P' => 'Full HD 1080p', '4MP' => '4 MP', '4K' => '4K UHD',
                ]],
                ['name' => 'night_vision', 'label' => 'Visão noturna (IR)', 'kind' => 'bool'],
                ['name' => 'color_image', 'label' => 'Imagem colorida', 'kind' => 'bool'],
                ['name' => 'ptz', 'label' => 'Movimento PTZ', 'kind' => 'bool'],
                ['name' => 'field_of_view_deg', 'label' => 'Campo de visão', 'kind' => 'int', 'min' => 30, 'max' => 360, 'unit' => '°'],
            ]),
            new EquipmentType('BALANCA', 'Balança de pesagem', 'BAL', false, 'Pesagem de veículos de carga (estática ou em movimento).', [
                ['name' => 'weighing_mode', 'label' => 'Modo de pesagem', 'kind' => 'enum', 'required' => true, 'options' => [
                    'ESTATICA' => 'Estática', 'DINAMICA' => 'Em movimento (WIM)',
                ]],
                ['name' => 'max_load_t', 'label' => 'Capacidade', 'kind' => 'int', 'min' => 1, 'max' => 200, 'unit' => 't', 'required' => true],
                ['name' => 'lanes', 'label' => 'Faixas', 'kind' => 'int', 'min' => 1, 'max' => 4, 'unit' => 'faixa(s)'],
            ]),
            new EquipmentType('CANCELA', 'Cancela automática', 'CAN', false, 'Controle de acesso a pátios, terminais e áreas restritas.', [
                ['name' => 'access_control', 'label' => 'Controle de acesso', 'kind' => 'enum', 'required' => true, 'options' => [
                    'TAG' => 'TAG veicular', 'TICKET' => 'Ticket', 'REMOTO' => 'Acionamento remoto', 'MANUAL' => 'Manual',
                ]],
                ['name' => 'arm_length_m', 'label' => 'Comprimento da haste', 'kind' => 'int', 'min' => 2, 'max' => 8, 'unit' => 'm'],
                ['name' => 'anti_crush', 'label' => 'Sensor antiesmagamento', 'kind' => 'bool'],
            ]),
            new EquipmentType('TOTEM', 'Totem de atendimento', 'TOT', false, 'Informação ao cidadão e acionamento de ajuda.', [
                ['name' => 'services', 'label' => 'Serviços', 'kind' => 'multi', 'required' => true, 'options' => [
                    'INFORMACAO' => 'Informação', 'BOTAO_AJUDA' => 'Botão de ajuda', 'WIFI' => 'Wi-Fi público', 'RECARGA' => 'Recarga USB',
                ]],
                ['name' => 'screen_in', 'label' => 'Tela', 'kind' => 'int', 'min' => 7, 'max' => 86, 'unit' => 'pol.'],
                ['name' => 'accessible_height', 'label' => 'Altura acessível', 'kind' => 'bool'],
            ]),
            new EquipmentType('VEICULO', 'Veículo', 'VEI', true, 'Viaturas de fiscalização, transporte e apoio às equipes (recurso móvel).', [
                ['name' => 'vehicle_role', 'label' => 'Uso', 'kind' => 'enum', 'required' => true, 'options' => [
                    'FISCALIZACAO' => 'Fiscalização', 'TRANSPORTE' => 'Transporte', 'EQUIPE' => 'Apoio à equipe',
                ]],
                ['name' => 'call_sign', 'label' => 'Prefixo (fictício)', 'kind' => 'text', 'max' => 20, 'required' => true, 'help' => 'Ex.: VF-03. Não use placas reais.'],
                ['name' => 'seats', 'label' => 'Lugares', 'kind' => 'int', 'min' => 1, 'max' => 60],
                ['name' => 'fuel', 'label' => 'Energia', 'kind' => 'enum', 'options' => [
                    'FLEX' => 'Flex', 'DIESEL' => 'Diesel', 'ELETRICO' => 'Elétrico', 'HIBRIDO' => 'Híbrido',
                ]],
            ]),
            new EquipmentType('PATINETE', 'Patinete elétrico', 'PAT', true, 'Patinete de patrulhamento das equipes (recurso móvel).', [
                ['name' => 'range_km', 'label' => 'Autonomia', 'kind' => 'int', 'min' => 5, 'max' => 120, 'unit' => 'km', 'required' => true],
                ['name' => 'max_speed_kmh', 'label' => 'Velocidade máxima', 'kind' => 'int', 'min' => 6, 'max' => 32, 'unit' => 'km/h'],
            ]),
        ];
        $indexed = [];
        foreach ($types as $type) {
            $indexed[$type->code] = $type;
        }

        return $indexed;
    }
}
