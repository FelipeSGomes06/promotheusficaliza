<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

use DateTimeImmutable;

/**
 * Acompanhamento de um alerta de agente entre a ciência e o encerramento, e a próxima ação mostrada na ficha e na
 * central. É a única política: Twig e JavaScript só apresentam o resultado. Ciência e providências não encerram a
 * causa; contato confirmado não atualiza a localização nem prova que a telemetria voltou.
 */
final class AlertFollowUp
{
    /** Providências registradas por pessoas: algo feito por outro meio (o sistema não envia mensagem). */
    public const ACTIONS = [
        'CONTATO_TENTADO' => 'Contato tentado, sem resposta',
        'CONTATO_CONFIRMADO' => 'Contato confirmado com a equipe',
        'COMUNICACAO_VERIFICADA' => 'Comunicação do rádio verificada',
        'OUTRA' => 'Outra providência',
    ];

    /** Motivo de encerramento (ALERT.RESOLVED_REASON); nulo nos alertas encerrados antes de 24/10/2026. */
    public const RESOLUTIONS = [
        'POSICAO_RECEBIDA' => 'Encerrado: posição recente recebida.',
        'FORA_DE_TURNO' => 'Deixou de se aplicar: o agente saiu de turno. Não indica que a comunicação voltou.',
        'ARQUIVADO' => 'Deixou de se aplicar: o agente foi arquivado.',
        'CAUSA_ENCERRADA' => 'Encerrado: a condição deixou de existir.',
    ];

    /** Próxima conferência: depois de agora e em até 7 dias. */
    public const MAX_CHECK_DAYS = 7;

    /**
     * @param array<string, mixed> $agent status, status_changed_at, archived_at, pos_observed_at
     * @param array<string, mixed>|null $alert alerta pendente do agente (state, next_check_at)
     * @param array<string, mixed>|null $last última providência registrada (action_code)
     * @return array{tone: 'urgent'|'normal'|'calm', what: string, detail: string}
     */
    public static function nextAction(array $agent, ?array $alert, ?array $last, DateTimeImmutable $now): array
    {
        if ($agent['archived_at'] !== null) {
            return self::say('calm', 'Nenhuma: agente arquivado.', 'Ele não aparece na visão geral. Alertas e histórico continuam consultáveis.');
        }
        $status = AgentStatus::from($agent['status']);
        if ($alert === null) {
            return match (true) {
                $status === AgentStatus::Indisponivel => self::say('normal', 'Registrar o retorno quando ele acontecer', 'Indisponível desde ' . Time::short(Time::fromDb($agent['status_changed_at']) ?? $now) . '. Quando o agente voltar, mude a situação para "Em atividade".'),
                $status->isOnShift() => self::say('calm', 'Nada pendente agora', 'Em turno e sem alertas pendentes.'),
                default => self::say('calm', 'Nada pendente agora', 'Fora de turno: a posição não é esperada.'),
            };
        }

        $observed = Time::fromDb($agent['pos_observed_at'] ?? null);
        $position = $observed === null ? 'Nenhuma posição recebida até agora.' : 'Última posição há ' . Time::elapsed($observed, $now) . ' (' . Time::short($observed) . ').';
        if ($alert['state'] === 'ABERTO') {
            return self::say('urgent', 'Registrar ciência e conferir a comunicação com a equipe', "Em turno sem posição recente. {$position} A falta de posição não confirma falha nem indisponibilidade do agente.");
        }
        $check = Time::fromDb($alert['next_check_at'] ?? null);
        $confirmed = ($last['action_code'] ?? null) === 'CONTATO_CONFIRMADO' ? 'Contato confirmado; ainda aguardando posição recente (contato não atualiza a localização). ' : '';

        return match (true) {
            $check !== null && $check <= $now => self::say('urgent', 'Conferência vencida desde ' . Time::short($check) . ': conferir de novo', "{$confirmed}{$position} Registre o resultado como nova providência."),
            $last === null => self::say('urgent', 'Conferir a comunicação com a equipe e registrar a providência', "{$position} A ciência não resolve a causa: o alerta encerra quando chegar posição recente ou o agente sair de turno."),
            $check !== null => self::say('normal', 'Aguardar a próxima conferência, ' . Time::short($check), "{$confirmed}{$position}"),
            default => self::say('normal', $confirmed !== '' ? 'Aguardar posição recente' : 'Conferir de novo e registrar o resultado', "{$confirmed}{$position} Sem próxima conferência definida."),
        };
    }

    /**
     * Última providência registrada (as atribuições não contam).
     *
     * @param list<array<string, mixed>> $followUps do mais antigo ao mais novo
     * @return array<string, mixed>|null
     */
    public static function lastAction(array $followUps): ?array
    {
        $actions = array_values(array_filter($followUps, static fn($f) => $f['kind'] === 'PROVIDENCIA'));

        return $actions === [] ? null : $actions[count($actions) - 1];
    }

    /** @return array{tone: 'urgent'|'normal'|'calm', what: string, detail: string} */
    private static function say(string $tone, string $what, string $detail): array
    {
        /** @var 'urgent'|'normal'|'calm' $tone */
        return ['tone' => $tone, 'what' => $what, 'detail' => $detail];
    }
}
