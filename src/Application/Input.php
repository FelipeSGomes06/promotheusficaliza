<?php

declare(strict_types=1);

namespace Fiscaliza\Application;

use Fiscaliza\Domain\ValidationFailed;

/**
 * Validação de fronteira: lê somente as chaves pedidas, normaliza e acumula erros por campo.
 * Campos não lidos são ignorados — o cliente nunca define autoria, origem, versão interna ou papel.
 */
final class Input
{
    /** Retângulo amplo em torno de Marília/SP. Não prova que o ponto está dentro do limite municipal. */
    public const AREA = ['lat_min' => -22.40, 'lat_max' => -22.00, 'lng_min' => -50.20, 'lng_max' => -49.70];

    /** @var array<string, string> */
    private array $errors = [];

    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data) {}

    public function raw(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function text(string $key, int $max, bool $required = true): ?string
    {
        $value = $this->data[$key] ?? null;
        $value = is_scalar($value) ? trim((string) $value) : '';
        // Remove caracteres de controle (exceto quebra de linha) para não gravar lixo invisível.
        $value = (string) preg_replace('/[\x00-\x09\x0B-\x1F\x7F]/u', '', $value);
        if ($value === '') {
            if ($required) {
                $this->errors[$key] = 'Campo obrigatório.';
            }

            return null;
        }
        if (mb_strlen($value) > $max) {
            $this->errors[$key] = "Use no máximo {$max} caracteres.";

            return null;
        }

        return $value;
    }

    public function int(string $key, int $min, int $max, bool $required = true): ?int
    {
        $text = $this->text($key, 12, $required);
        if ($text === null) {
            return null;
        }
        $value = filter_var($text, FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max) {
            $this->errors[$key] = "Informe um número inteiro entre {$min} e {$max}.";

            return null;
        }

        return $value;
    }

    /** @param list<string> $allowed */
    public function choice(string $key, array $allowed, bool $required = true): ?string
    {
        $text = $this->text($key, 40, $required);
        if ($text !== null && !in_array($text, $allowed, true)) {
            $this->errors[$key] = 'Opção inválida.';

            return null;
        }

        return $text;
    }

    /** Coordenada WGS84 em graus decimais, devolvida com 6 casas (≈ 0,1 m) como texto para o DECIMAL(9,6). */
    public function coordinate(string $key, string $axis, bool $required = true): ?string
    {
        $text = $this->text($key, 20, $required);
        if ($text === null) {
            return null;
        }
        $text = str_replace(',', '.', $text);
        if (preg_match('/^-?\d{1,3}(\.\d+)?$/', $text) !== 1) {
            $this->errors[$key] = 'Use graus decimais, ex.: -22.2139.';

            return null;
        }
        $value = (float) $text;
        [$min, $max] = $axis === 'lat' ? [self::AREA['lat_min'], self::AREA['lat_max']] : [self::AREA['lng_min'], self::AREA['lng_max']];
        if ($value < $min || $value > $max) {
            $this->errors[$key] = 'Fora da área de Marília/SP atendida pela demonstração.';

            return null;
        }

        return number_format($value, 6, '.', '');
    }

    /** CEP brasileiro, aceito com ou sem hífen e gravado como 00000-000. */
    public function cep(string $key, bool $required = false): ?string
    {
        $text = $this->text($key, 12, $required);
        if ($text === null) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $text) ?? '';
        if (strlen($digits) !== 8 || preg_match('/^\d{5}-?\d{3}$/', $text) !== 1) {
            $this->errors[$key] = 'CEP no formato 00000-000.';

            return null;
        }

        return substr($digits, 0, 5) . '-' . substr($digits, 5);
    }

    /** Data local no formato AAAA-MM-DD. */
    public function date(string $key, bool $required = true): ?string
    {
        $text = $this->text($key, 10, $required);
        if ($text === null) {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $text);
        if ($date === false || $date->format('Y-m-d') !== $text) {
            $this->errors[$key] = 'Data inválida.';

            return null;
        }

        return $text;
    }

    /** Data e hora locais de Marília (AAAA-MM-DDTHH:MM, campo datetime-local), devolvidas como instante UTC do banco. */
    public function localDateTime(string $key, bool $required = true): ?string
    {
        $text = $this->text($key, 19, $required);
        if ($text === null) {
            return null;
        }
        $format = strlen($text) === 19 ? 'Y-m-d\TH:i:s' : 'Y-m-d\TH:i';
        $local = \DateTimeImmutable::createFromFormat('!' . $format, $text, \Fiscaliza\Domain\Time::local());
        if ($local === false || $local->format($format) !== $text) {
            $this->errors[$key] = 'Data e hora inválidas (use dia/mês/ano e hora:minuto).';

            return null;
        }

        return \Fiscaliza\Domain\Time::toDb($local);
    }

    /**
     * Valor em reais escrito à brasileira ("1.234,56", "293,47", "130") convertido para centavos sem passar por float.
     * Ponto só como separador de milhar em grupos de três; no máximo duas casas decimais.
     */
    public function cents(string $key, int $min, int $max, bool $required = false): ?int
    {
        $text = $this->text($key, 20, $required);
        if ($text === null) {
            return null;
        }
        $text = (string) preg_replace('/^R\$\s*/u', '', $text);
        if (preg_match('/^(\d{1,3}(?:\.\d{3})+|\d+)(?:,(\d{1,2}))?$/', $text, $m) !== 1) {
            $this->errors[$key] = 'Use o formato 1.234,56 (até duas casas decimais).';

            return null;
        }
        $digits = ltrim(str_replace('.', '', $m[1]) . str_pad($m[2] ?? '', 2, '0'), '0');
        if (strlen($digits) > 12 || (int) $digits < $min || (int) $digits > $max) {
            $this->errors[$key] = sprintf('Informe um valor entre R$ %s e R$ %s.', number_format($min / 100, 2, ',', '.'), number_format($max / 100, 2, ',', '.'));

            return null;
        }

        return (int) $digits;
    }

    public function flag(string $key): bool
    {
        return in_array($this->data[$key] ?? null, [true, 1, '1', 'on', 'true'], true);
    }

    public function fail(string $key, string $message): void
    {
        $this->errors[$key] = $message;
    }

    /** @param array<string, string> $errors */
    public function merge(array $errors): void
    {
        $this->errors = array_merge($this->errors, $errors);
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function assertValid(): void
    {
        if ($this->errors !== []) {
            throw new ValidationFailed($this->errors);
        }
    }
}
