<?php

declare(strict_types=1);

namespace Fiscaliza\Http\Controller;

use Fiscaliza\Application\AddressLocator;
use Fiscaliza\Application\Input;
use Fiscaliza\Application\ProviderUnavailable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Endereço nos formulários (ADR-0021): rua pelo CEP e ponto pelo endereço. O navegador só fala com este servidor (CSP
 * connect-src 'self'); daqui sai só o CEP ou o nome da rua, validados antes de qualquer chamada. Toda resposta tem as
 * mesmas chaves e uma mensagem pronta para a tela; falha do provedor vira resposta amigável, sem detalhes.
 */
final class AddressController extends Controller
{
    /** @param array<string, string> $args */
    public function cep(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $cep = (new Input(['cep' => $args['cep'] ?? '']))->cep('cep', true);
        if ($cep === null) {
            return $this->answer($response, 422, '', 'INVALIDO', 'Digite os 8 números do CEP, como 17500-000.');
        }
        try {
            $address = $this->services->cepLookup()->find($cep);
        } catch (ProviderUnavailable $e) {
            // Log privado (sem o CEP): ajuda a ver se o servidor publicado consegue sair para a internet.
            error_log('[fiscaliza] consulta de CEP indisponível: ' . $e->getMessage());

            return $this->answer($response, 503, $cep, 'INDISPONIVEL', 'A consulta de CEP não respondeu agora. Digite a rua abaixo; o cadastro não depende dela.');
        }

        return match (true) {
            $address === null => $this->answer($response, 404, $cep, 'NAO_ENCONTRADO', 'CEP não encontrado. Confira o número ou digite a rua abaixo.'),
            !$address->isInMarilia() => $this->answer($response, 200, $cep, 'OUTRA_CIDADE', "Este CEP é de {$address->city}/{$address->uf}, não de Marília/SP. Confira o número ou digite a rua abaixo."),
            $address->street === '' => $this->answer($response, 200, $cep, 'SEM_RUA', 'Este é um CEP geral de Marília/SP, sem rua. Se souber, digite a rua abaixo.'),
            default => $this->answer($response, 200, $cep, 'ENCONTRADO', 'Rua encontrada pelo CEP.', $address->street, $address->district),
        };
    }

    /** Ponto da base pela rua (ou pela rua do CEP), para quem não consegue marcar no mapa. Query: rua, cep, numero. */
    public function locate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $input = new Input($request->getQueryParams());
        $street = $input->text('rua', 120, false);
        $cep = $input->cep('cep');
        $number = $input->text('numero', 20, false);
        if ($input->errors() !== [] || ($street === null && $cep === null)) {
            return $this->json($response, ['situacao' => 'INVALIDO', 'lat' => null, 'lng' => null, 'rua' => '', 'mensagem' => 'Informe a rua ou um CEP com 8 números.'], 422);
        }
        $found = $this->services->addressLocator()->locate($street, $cep, $number);
        $status = ['ENCONTRADO' => 200, 'SEM_RUA' => 200, 'NAO_ENCONTRADO' => 404, 'INDISPONIVEL' => 503][$found['situacao']];

        return $this->json($response, [
            'situacao' => $found['situacao'],
            'lat' => $found['lat'] ?? null,
            'lng' => $found['lng'] ?? null,
            'rua' => $found['rua'] ?? '',
            'mensagem' => AddressLocator::MESSAGES[$found['situacao']],
        ], $status);
    }

    private function answer(ResponseInterface $response, int $status, string $cep, string $situation, string $message, string $street = '', string $district = ''): ResponseInterface
    {
        return $this->json($response, ['cep' => $cep, 'situacao' => $situation, 'rua' => $street, 'bairro' => $district, 'mensagem' => $message], $status);
    }
}
