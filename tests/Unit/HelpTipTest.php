<?php

declare(strict_types=1);

namespace Fiscaliza\Tests\Unit;

use Fiscaliza\Config;
use Fiscaliza\Domain\EquipmentCatalog;
use Fiscaliza\Http\View;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;

/** Ajuda contextual (ui.help e opts.help_tip): acessível, gerada do domínio e sem mudar os campos que não a usam. */
final class HelpTipTest extends TestCase
{
    private static function render(string $source): string
    {
        $view = new View(new Config(['APP_ENV' => 'test', 'APP_BASE_PATH' => '/pf']), dirname(__DIR__, 2) . '/templates', dirname(__DIR__, 2) . '/public', null);
        /** @var Environment $twig */
        $twig = (new \ReflectionProperty($view, 'twig'))->getValue($view);
        $twig->setLoader(new ChainLoader([new ArrayLoader(['t' => $source]), $twig->getLoader()]));

        return $twig->render('t', ['types' => EquipmentCatalog::all()]);
    }

    public function testFieldsWithoutHelpTipKeepTheSharedStructure(): void
    {
        // Saída de ui.input/ui.select sem "?": mesmo cabeçalho (rótulo e marcador) e corpo (controle e erro) dos campos com ajuda.
        $html = self::render(<<<'TWIG'
            {% import '_ui.twig' as ui %}
            {{ ui.input('address_ref', 'Número ou referência', {address_ref: '181'}, {}, {max: 160, placeholder: 'Ex.: 1200'}) }}
            {{ ui.input('cep', 'CEP', {}, {cep: 'CEP inválido.'}, {required: true, inputmode: 'numeric'}) }}
            {{ ui.input('notes', 'Observações', {}, {}, {type: 'textarea', max: 500, class: 'span-2'}) }}
            {{ ui.select('role', 'Função', {A: 'Um', B: 'Dois'}, 'B', {}, {required: true}) }}
            {{ ui.select('kind', 'Tipo', {A: 'Um'}, '', {kind: 'Obrigatório.'}, {empty: 'Escolha…'}) }}

            TWIG);
        self::assertSame(file_get_contents(__DIR__ . '/fixtures/ui-campos-sem-ajuda.html'), $html);
    }

    public function testHelpTipIsAnAccessibleButtonNextToTheLabel(): void
    {
        $html = self::render(<<<'TWIG'
            {% import '_ui.twig' as ui %}{% import '_ajuda.twig' as ajuda %}
            {{ ui.input('address_ref', 'Número ou referência', {}, {cep: 'x'}, {max: 160, help_tip: ajuda.address_ref()}) }}
            {{ ui.select('role', 'Função', {A: 'Um'}, 'A', {}, {help_tip: ajuda.agent_role()}) }}
            TWIG);
        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8"?>' . $html);
        $xp = new \DOMXPath($doc);

        // O "?" fica no cabeçalho do campo, logo depois do rótulo.
        $button = $xp->query('//div[@class="field__head"][label[@for="f-address_ref"]]/span[@class="help"]/details[@data-help]/summary[@aria-describedby="f-address_ref-tip"]')->item(0);
        self::assertInstanceOf(\DOMElement::class, $button);
        self::assertSame('Ajuda sobre Número ou referência', $button->getAttribute('aria-label'));

        $box = $doc->getElementById('f-address_ref-tip');
        self::assertSame('tooltip', $box?->getAttribute('role'));
        foreach (['181', 'oposto ao número 181', 'KM 203', 'S/N', 'em frente ao Mercado do Tio'] as $example) {
            self::assertStringContainsString($example, (string) $box?->textContent);
        }
        // O campo é descrito pela ajuda (leitor de tela); nenhuma explicação fica entre o rótulo e o controle.
        self::assertSame(0, $xp->query('//*[contains(@class, "hint")]')->length);
        self::assertSame('f-address_ref-tip', $doc->getElementById('f-address_ref')?->getAttribute('aria-describedby'));
        self::assertSame('f-role-tip', $doc->getElementById('f-role')?->getAttribute('aria-describedby'));
        self::assertStringContainsString('Guarda municipal', (string) $doc->getElementById('f-role-tip')?->textContent);
    }

    public function testStatusAndSpecHelpComeFromTheDomain(): void
    {
        $html = self::render(<<<'TWIG'
            {% import '_ajuda.twig' as ajuda %}
            <div id="status">{{ ajuda.equipment_status() }}</div>
            <div id="change">{{ ajuda.status_change(enum('Fiscaliza\\Domain\\EquipmentStatus').Furtado) }}</div>
            {% for f in types.RADAR.fields|merge(types.CAMERA.fields) %}<div id="{{ f.name }}">{{ ajuda.spec(f) }}</div>{% endfor %}
            TWIG);
        $text = static fn(string $id): string => (string) preg_replace('/\s+/', ' ', strip_tags((string) preg_replace('#^.*?<div id="' . $id . '">(.*?)</div>.*$#s', '$1', $html)));

        foreach (['Não instalado', 'Operacional', 'Mau funcionamento', 'Em manutenção', 'Desativado', 'Furtado'] as $label) {
            self::assertStringContainsString($label, $text('status'));
        }
        // Do furtado só se vai a não instalado (repor) ou desativado (baixa), como em EquipmentStatus::allowedTransitions().
        self::assertStringContainsString('Não instalado: prepara a reposição', $text('change'));
        self::assertStringContainsString('Desativado:', $text('change'));
        self::assertStringNotContainsString('Operacional:', $text('change'));

        self::assertStringContainsString('Conversão proibida: quem vira onde a placa proíbe', $text('functions'));
        self::assertStringContainsString('Aceita de 20 a 120 km/h', $text('speed_limit_kmh'));
        // A orientação do catálogo (help) também vai para o "?", em vez de ficar embaixo do campo.
        self::assertStringContainsString('Obrigatório quando fiscaliza velocidade', $text('speed_limit_kmh'));
        self::assertStringContainsString('bairro → centro', $text('direction'));
        self::assertStringContainsString('infravermelha', $text('night_vision'));
        self::assertStringContainsString('preto e branco', $text('color_image'));
    }

    public function testEveryCatalogFieldHasHelp(): void
    {
        foreach (EquipmentCatalog::all() as $type) {
            foreach ($type->fields as $field) {
                self::assertNotSame('', $field['tip'] ?? '', "{$type->code}.{$field['name']} sem ajuda");
                foreach (array_keys($field['optionTips'] ?? []) as $option) {
                    self::assertArrayHasKey($option, $field['options'] ?? [], "{$type->code}.{$field['name']}: ajuda de opção que não existe");
                }
            }
        }
    }
}
