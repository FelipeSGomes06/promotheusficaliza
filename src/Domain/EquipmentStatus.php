<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

/**
 * Situação operacional do equipamento. É independente da necessidade de manutenção (ordens) e da atualidade
 * do sinal (Freshness): um equipamento operacional pode ter preventiva vencida ou estar sem sinal recente.
 */
enum EquipmentStatus: string
{
    case NaoInstalado = 'NAO_INSTALADO';
    case Operacional = 'OPERACIONAL';
    case Falha = 'FALHA';
    case Manutencao = 'MANUTENCAO';
    case Desativado = 'DESATIVADO';
    case Furtado = 'FURTADO';

    public function label(): string
    {
        return match ($this) {
            self::NaoInstalado => 'Não instalado',
            self::Operacional => 'Operacional',
            self::Falha => 'Mau funcionamento',
            self::Manutencao => 'Em manutenção',
            self::Desativado => 'Desativado',
            self::Furtado => 'Furtado',
        };
    }

    /** Deveria estar prestando serviço: entra no denominador da disponibilidade. */
    public function isInService(): bool
    {
        return in_array($this, [self::Operacional, self::Falha, self::Manutencao, self::Furtado], true);
    }

    /** Em serviço, porém sem funcionar. */
    public function isUnavailable(): bool
    {
        return in_array($this, [self::Falha, self::Manutencao, self::Furtado], true);
    }

    /**
     * Estados definidos por pessoas (intervenção, desativação, furto, instalação pendente): a telemetria simulada
     * não os sobrescreve; seus eventos ficam apenas no histórico.
     */
    public function isAdministrative(): bool
    {
        return in_array($this, [self::NaoInstalado, self::Manutencao, self::Desativado, self::Furtado], true);
    }

    /** Recebe sinal (heartbeat) e por isso tem atualidade de observação. */
    public function expectsSignal(): bool
    {
        return in_array($this, [self::Operacional, self::Falha], true);
    }

    /** @return list<self> transições manuais permitidas a partir deste estado */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::NaoInstalado => [self::Operacional, self::Desativado],
            self::Operacional => [self::Falha, self::Manutencao, self::Desativado, self::Furtado],
            self::Falha => [self::Operacional, self::Manutencao, self::Desativado, self::Furtado],
            self::Manutencao => [self::Operacional, self::Falha, self::Desativado],
            self::Desativado => [self::Operacional, self::NaoInstalado],
            self::Furtado => [self::NaoInstalado, self::Desativado],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** Sinal textual curto, usado junto com a cor (cor nunca é o único indicador). */
    public function glyph(): string
    {
        return match ($this) {
            self::NaoInstalado => '◌',
            self::Operacional => '✓',
            self::Falha => '!',
            self::Manutencao => '⚙',
            self::Desativado => '–',
            self::Furtado => '✕',
        };
    }
}
