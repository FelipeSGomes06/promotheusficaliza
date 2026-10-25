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
                ], 'tip' => 'O que o radar fiscaliza. Pode marcar mais de uma função.', 'optionTips' => [
                    'VELOCIDADE' => 'quem passa do limite da via (informe o limite abaixo).', 'AVANCO_SEMAFORO' => 'quem cruza com o sinal vermelho.',
                    'PARADA_FAIXA' => 'quem para em cima da faixa de pedestres.', 'CONVERSAO_PROIBIDA' => 'quem vira onde a placa proíbe.',
                    'FAIXA_EXCLUSIVA' => 'carro comum andando na faixa só de ônibus.',
                ]],
                ['name' => 'modality', 'label' => 'Modalidade', 'kind' => 'enum', 'options' => [
                    'FIXO' => 'Fixo (instalado no local)', 'PORTATIL' => 'Portátil (operado por agente)',
                ], 'help' => 'Portátil: o local cadastrado é o ponto de referência da operação.', 'tip' => 'Como o radar trabalha. Deixe em branco se não souber.', 'optionTips' => [
                    'FIXO' => 'fica preso num poste ou pórtico, sempre no mesmo lugar.', 'PORTATIL' => 'um agente leva o aparelho e mede em cada operação.',
                ]],
                ['name' => 'speed_limit_kmh', 'label' => 'Limite de velocidade', 'kind' => 'int', 'min' => 20, 'max' => 120, 'unit' => 'km/h',
                    'requiredIf' => ['functions' => 'VELOCIDADE'], 'help' => 'Obrigatório quando fiscaliza velocidade.',
                    'tip' => 'A velocidade máxima permitida na via onde o radar mede, como está na placa. Ex.: 40, 50 ou 60 km/h.'],
                ['name' => 'lanes', 'label' => 'Faixas monitoradas', 'kind' => 'int', 'min' => 1, 'max' => 8, 'unit' => 'faixa(s)', 'tip' => 'Quantas faixas da via o radar vigia ao mesmo tempo.'],
                ['name' => 'direction', 'label' => 'Sentido', 'kind' => 'text', 'max' => 80, 'help' => 'Ex.: bairro → centro.', 'tip' => 'Para onde vão os carros que o radar vigia.'],
            ]),
            new EquipmentType('CAMERA', 'Câmera de monitoramento', 'CAM', false, 'Videomonitoramento de vias e espaços públicos.', [
                ['name' => 'resolution', 'label' => 'Resolução', 'kind' => 'enum', 'required' => true, 'options' => [
                    '720P' => 'HD 720p', '1080P' => 'Full HD 1080p', '4MP' => '4 MP', '4K' => '4K UHD',
                ], 'tip' => 'A nitidez da imagem gravada. Quanto maior, mais detalhe se vê de longe, como uma placa de carro.', 'optionTips' => [
                    '720P' => 'básica; boa para ver o movimento.', '1080P' => 'a mais comum; dá para ler placas de perto.',
                    '4MP' => 'um pouco mais nítida que Full HD.', '4K' => 'a mais nítida; ajuda a ler detalhes de longe.',
                ]],
                ['name' => 'night_vision', 'label' => 'Visão noturna (IR)', 'kind' => 'bool', 'tip' => 'Marque se a câmera enxerga no escuro com luz infravermelha (IR). À noite a imagem costuma sair em preto e branco.'],
                ['name' => 'color_image', 'label' => 'Imagem colorida', 'kind' => 'bool', 'tip' => 'Marque se a imagem é colorida. Sem marcar, a câmera grava em preto e branco.'],
                ['name' => 'ptz', 'label' => 'Movimento PTZ', 'kind' => 'bool', 'tip' => 'PTZ: a câmera gira para os lados, para cima e para baixo e dá zoom por comando a distância. Sem marcar, ela é fixa.'],
                ['name' => 'field_of_view_deg', 'label' => 'Campo de visão', 'kind' => 'int', 'min' => 30, 'max' => 360, 'unit' => '°', 'tip' => 'A largura da área que a câmera vê, em graus. 90° cobre um canto de cruzamento; 360° é a volta toda.'],
            ]),
            new EquipmentType('BALANCA', 'Balança de pesagem', 'BAL', false, 'Pesagem de veículos de carga (estática ou em movimento).', [
                ['name' => 'weighing_mode', 'label' => 'Modo de pesagem', 'kind' => 'enum', 'required' => true, 'options' => [
                    'ESTATICA' => 'Estática', 'DINAMICA' => 'Em movimento (WIM)',
                ], 'tip' => 'Como o caminhão é pesado.', 'optionTips' => [
                    'ESTATICA' => 'o caminhão para em cima da balança.', 'DINAMICA' => 'pesa enquanto ele passa devagar, sem parar.',
                ]],
                ['name' => 'max_load_t', 'label' => 'Capacidade', 'kind' => 'int', 'min' => 1, 'max' => 200, 'unit' => 't', 'required' => true, 'tip' => 'O maior peso que a balança consegue medir, em toneladas.'],
                ['name' => 'lanes', 'label' => 'Faixas', 'kind' => 'int', 'min' => 1, 'max' => 4, 'unit' => 'faixa(s)', 'tip' => 'Quantas faixas passam sobre a balança.'],
            ]),
            new EquipmentType('CANCELA', 'Cancela automática', 'CAN', false, 'Controle de acesso a pátios, terminais e áreas restritas.', [
                ['name' => 'access_control', 'label' => 'Controle de acesso', 'kind' => 'enum', 'required' => true, 'options' => [
                    'TAG' => 'TAG veicular', 'TICKET' => 'Ticket', 'REMOTO' => 'Acionamento remoto', 'MANUAL' => 'Manual',
                ], 'tip' => 'O que faz a cancela abrir.', 'optionTips' => [
                    'TAG' => 'um adesivo no para-brisa é lido na chegada.', 'TICKET' => 'o motorista pega ou devolve um bilhete.',
                    'REMOTO' => 'alguém abre a distância, pela central.', 'MANUAL' => 'uma pessoa no local abre.',
                ]],
                ['name' => 'arm_length_m', 'label' => 'Comprimento da haste', 'kind' => 'int', 'min' => 2, 'max' => 8, 'unit' => 'm', 'tip' => 'O tamanho do braço que fecha a passagem.'],
                ['name' => 'anti_crush', 'label' => 'Sensor antiesmagamento', 'kind' => 'bool', 'tip' => 'Marque se a cancela para de descer quando há um carro ou uma pessoa embaixo.'],
            ]),
            new EquipmentType('TOTEM', 'Totem de atendimento', 'TOT', false, 'Informação ao cidadão e acionamento de ajuda.', [
                ['name' => 'services', 'label' => 'Serviços', 'kind' => 'multi', 'required' => true, 'options' => [
                    'INFORMACAO' => 'Informação', 'BOTAO_AJUDA' => 'Botão de ajuda', 'WIFI' => 'Wi-Fi público', 'RECARGA' => 'Recarga USB',
                ], 'tip' => 'O que o cidadão consegue fazer no totem. Pode marcar mais de um.'],
                ['name' => 'screen_in', 'label' => 'Tela', 'kind' => 'int', 'min' => 7, 'max' => 86, 'unit' => 'pol.', 'tip' => 'O tamanho da tela, medido na diagonal.'],
                ['name' => 'accessible_height', 'label' => 'Altura acessível', 'kind' => 'bool', 'tip' => 'Marque se a tela e os botões ficam ao alcance de quem usa cadeira de rodas.'],
            ]),
            new EquipmentType('VEICULO', 'Veículo', 'VEI', true, 'Viaturas de fiscalização, transporte e apoio às equipes (recurso móvel).', [
                ['name' => 'vehicle_role', 'label' => 'Uso', 'kind' => 'enum', 'required' => true, 'options' => [
                    'FISCALIZACAO' => 'Fiscalização', 'TRANSPORTE' => 'Transporte', 'EQUIPE' => 'Apoio à equipe',
                ], 'tip' => 'Para que a viatura é usada no dia a dia.'],
                ['name' => 'call_sign', 'label' => 'Prefixo (fictício)', 'kind' => 'text', 'max' => 20, 'required' => true, 'help' => 'Ex.: VF-03. Não use placas reais.', 'tip' => 'O código curto pintado na viatura, pelo qual a equipe a chama no rádio.'],
                ['name' => 'seats', 'label' => 'Lugares', 'kind' => 'int', 'min' => 1, 'max' => 60, 'tip' => 'Quantas pessoas cabem, contando o motorista.'],
                ['name' => 'fuel', 'label' => 'Energia', 'kind' => 'enum', 'options' => [
                    'FLEX' => 'Flex', 'DIESEL' => 'Diesel', 'ELETRICO' => 'Elétrico', 'HIBRIDO' => 'Híbrido',
                ], 'tip' => 'O que move o veículo. Flex: álcool ou gasolina.'],
            ]),
            new EquipmentType('PATINETE', 'Patinete elétrico', 'PAT', true, 'Patinete de patrulhamento das equipes (recurso móvel).', [
                ['name' => 'range_km', 'label' => 'Autonomia', 'kind' => 'int', 'min' => 5, 'max' => 120, 'unit' => 'km', 'required' => true, 'tip' => 'Quantos quilômetros o patinete anda com a bateria cheia.'],
                ['name' => 'max_speed_kmh', 'label' => 'Velocidade máxima', 'kind' => 'int', 'min' => 6, 'max' => 32, 'unit' => 'km/h', 'tip' => 'A velocidade mais alta que o patinete alcança.'],
            ]),
        ];
        $indexed = [];
        foreach ($types as $type) {
            $indexed[$type->code] = $type;
        }

        return $indexed;
    }
}
