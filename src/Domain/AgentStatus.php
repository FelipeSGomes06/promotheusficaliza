<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

/** Situação do agente em campo. Agente é o recurso operacional fictício, não uma conta de acesso ao sistema. */
enum AgentStatus: string
{
    case EmAtividade = 'EM_ATIVIDADE';
    case EmPausa = 'EM_PAUSA';
    case Indisponivel = 'INDISPONIVEL';
    case ForaDeServico = 'FORA_DE_SERVICO';

    public function label(): string
    {
        return match ($this) {
            self::EmAtividade => 'Em atividade',
            self::EmPausa => 'Em pausa',
            self::Indisponivel => 'Indisponível',
            self::ForaDeServico => 'Fora de serviço',
        };
    }

    public function isActive(): bool
    {
        return $this === self::EmAtividade;
    }

    /** Em turno (atividade ou pausa): posição recebida é esperada. */
    public function isOnShift(): bool
    {
        return $this === self::EmAtividade || $this === self::EmPausa;
    }

    public function glyph(): string
    {
        return match ($this) {
            self::EmAtividade => '●',
            self::EmPausa => '❚❚',
            self::Indisponivel => '!',
            self::ForaDeServico => '–',
        };
    }
}
