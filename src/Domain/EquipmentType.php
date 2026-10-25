<?php

declare(strict_types=1);

namespace Fiscaliza\Domain;

/**
 * Tipo de equipamento e o esquema das suas características próprias (gravadas como JSON validado em EQUIPMENT.SPECS).
 *
 * Cada campo: name, label, kind (int|bool|enum|multi|text), required, min/max/unit (int), options (enum/multi),
 * max (text, em caracteres), requiredIf [campo => opção] para obrigatoriedade condicional, help (texto fixo abaixo do
 * rótulo), tip e optionTips [opção => texto] (só interface: a ajuda do "?").
 *
 * @phpstan-type SpecField array{name: string, label: string, kind: string, required?: bool, min?: int, max?: int,
 *     unit?: string, options?: array<string, string>, requiredIf?: array<string, string>, help?: string, tip?: string,
 *     optionTips?: array<string, string>}
 */
final readonly class EquipmentType
{
    /** @param list<SpecField> $fields */
    public function __construct(
        public string $code,
        public string $label,
        public string $prefix,
        public bool $mobile,
        public string $description,
        public array $fields,
    ) {}

    /** "Radar de fiscalização" → "Radar": o nome curto lidera os títulos; o completo aparece na ficha. */
    public function shortLabel(): string
    {
        return explode(' ', $this->label, 2)[0];
    }

    /**
     * Valida e normaliza as características enviadas. Campos desconhecidos são ignorados; o cliente não escolhe
     * o formato gravado.
     *
     * @param array<string, mixed> $input
     * @return array{0: array<string, int|bool|string|list<string>>, 1: array<string, string>} [specs, erros]
     */
    public function validateSpecs(array $input): array
    {
        $specs = [];
        $errors = [];
        foreach ($this->fields as $field) {
            $name = $field['name'];
            $raw = $input[$name] ?? null;
            $key = 'specs.' . $name;
            switch ($field['kind']) {
                case 'bool':
                    $specs[$name] = in_array($raw, [true, 1, '1', 'on', 'true', 'sim'], true);
                    continue 2;
                case 'multi':
                    $values = array_values(array_unique(array_filter(is_array($raw) ? $raw : [], 'is_string')));
                    $unknown = array_diff($values, array_keys($field['options'] ?? []));
                    if ($unknown !== []) {
                        $errors[$key] = 'Opção inválida.';
                    } elseif ($values === [] && ($field['required'] ?? false)) {
                        $errors[$key] = 'Escolha ao menos uma opção.';
                    } else {
                        $specs[$name] = $values;
                    }
                    continue 2;
            }
            $text = is_scalar($raw) ? trim((string) $raw) : '';
            if ($text === '') {
                if ($this->isRequired($field, $specs)) {
                    $errors[$key] = 'Obrigatório para este tipo.';
                }
                continue;
            }
            switch ($field['kind']) {
                case 'int':
                    $value = filter_var($text, FILTER_VALIDATE_INT);
                    if ($value === false || $value < ($field['min'] ?? PHP_INT_MIN) || $value > ($field['max'] ?? PHP_INT_MAX)) {
                        $errors[$key] = sprintf('Informe um inteiro entre %d e %d%s.', $field['min'] ?? 0, $field['max'] ?? 0, isset($field['unit']) ? ' ' . $field['unit'] : '');
                    } else {
                        $specs[$name] = $value;
                    }
                    break;
                case 'enum':
                    if (!array_key_exists($text, $field['options'] ?? [])) {
                        $errors[$key] = 'Opção inválida.';
                    } else {
                        $specs[$name] = $text;
                    }
                    break;
                default:
                    if (mb_strlen($text) > ($field['max'] ?? 120)) {
                        $errors[$key] = 'Texto longo demais.';
                    } else {
                        $specs[$name] = $text;
                    }
            }
        }

        return [$specs, $errors];
    }

    /**
     * Características legíveis para a ficha, na ordem do esquema.
     *
     * @param array<string, mixed> $specs
     * @return list<array{label: string, value: string}>
     */
    public function describe(array $specs): array
    {
        $lines = [];
        foreach ($this->fields as $field) {
            if (!array_key_exists($field['name'], $specs)) {
                continue;
            }
            $value = $specs[$field['name']];
            $text = match ($field['kind']) {
                'bool' => $value ? 'Sim' : 'Não',
                'multi' => implode(', ', array_map(static fn($v) => $field['options'][$v] ?? (string) $v, (array) $value)) ?: '—',
                'enum' => $field['options'][$value] ?? (string) $value,
                'int' => $value . (isset($field['unit']) ? ' ' . $field['unit'] : ''),
                default => (string) $value,
            };
            $lines[] = ['label' => $field['label'], 'value' => $text];
        }

        return $lines;
    }

    /**
     * @param SpecField $field
     * @param array<string, mixed> $specs
     */
    private function isRequired(array $field, array $specs): bool
    {
        if ($field['required'] ?? false) {
            return true;
        }
        foreach ($field['requiredIf'] ?? [] as $other => $option) {
            if (in_array($option, (array) ($specs[$other] ?? []), true)) {
                return true;
            }
        }

        return false;
    }
}
