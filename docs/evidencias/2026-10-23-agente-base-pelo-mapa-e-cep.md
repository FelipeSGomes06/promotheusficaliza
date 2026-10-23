# Evidência — Cadastrar agente: base pelo mapa ou pelo endereço, rua pelo CEP (23/10/2026)

Pedidos do autor: (1) "Clique no mapa para alterar"; (2) "retirar os campos de baixo e só informar latitude e
longitude"; (3) "remoção do logradouro e fazer por uma API para CEP e adicionar rua"; depois, no mesmo dia, (4) "nem
possa mexer nos números de longitude e latitude" e (5) "se não conseguir pelo mapa, seja pelo endereço e CEP, não
digitando latitude e longitude". Decisões em [ADR-0021](../adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md).

## O que mudou na tela (passo 2, "Base de trabalho")

| Antes | Depois |
|---|---|
| Mapa + 6 campos (nome da base, logradouro, número ou referência, CEP, bairro, cidade) | Nome da base; mapa com "Clique no mapa para marcar ou mudar a base"; CEP; rua; número (opcional, ajuda a localizar); cidade como texto fixo |
| Latitude e longitude recolhidas em "Coordenadas da base", digitáveis | Sem campos: o ponto aparece só em "Base marcada em …" abaixo do mapa; muda pelo clique, por Enter no mapa (teclado) ou pelo endereço |
| Endereço em blocos de alturas diferentes | Duas colunas iguais: CEP \| Cidade e Rua \| Número; cidade fixa (campo travado, mais escuro); o CEP preenche a rua, editável |
| "Nenhum local marcado ainda." | "Clique no mapa para marcar a base." / "Base marcada em … Para mudar, clique em outro ponto." |
| Logradouro digitado | CEP → "Rua: Avenida Sampaio Vidal"; sem rua na resposta, campo "Rua" com o motivo |
| — | Sem ponto marcado, o CEP com rua leva a base ao endereço; "Levar a base até este endereço" faz o mesmo |
| Bairro escolhido numa lista | Bairro derivado do ponto (centro mais próximo), mostrado como texto |
| Sem JavaScript: mapa vazio e coordenadas digitadas | Sem JavaScript: sem mapa vazio; CEP e rua visíveis; o servidor põe a base no endereço ao salvar |

Ficha: "Rua: … · Bairro: … · CEP … · Marília/SP" e "Ponto fixo marcado no mapa"; dados antigos (logradouro,
referência, bairro escolhido) continuam aparecendo. Histórico: "Cadastro alterado: rua, ponto no mapa." em vez de
"base_street, base_lat", também nas alterações antigas.

Capturas em [`capturas-2026-10-23-agente-base/`](capturas-2026-10-23-agente-base/) (1440 px e 390 px; sem fundo de
mapa porque os tiles estavam bloqueados pela rede do ambiente; respostas de CEP e localização simuladas no navegador,
exceto `05` e `08`, que mostram o servidor real sem saída para a internet).

## Verificação

Ambiente: contêiner de nuvem do Claude Code (Linux), Docker local; PHP 8.5.11 (`php:8.5.11-apache-trixie` com
PDO_FIREBIRD compilado com o cliente da imagem do Firebird 5.0.4, porque o espelho do Debian estava bloqueado) e
`firebirdsql/firebird:5.0.4-trixie`; Node 22. Nada foi publicado.

| O quê | Resultado |
|---|---|
| `vendor/bin/phpunit` | **242 testes**: todos passam, exceto 4 do `UrbanUpdaterTest` que precisam da extensão `zip` (não instalável nesta imagem local); os mesmos 4 falham em `main` sem esta mudança (214 testes nessa linha de base) |
| Testes novos | `CepLookupTest`, `AddressLocatorTest` (clientes do ViaCEP e do Nominatim com transporte falso), `AgentsHttpTest` (7), `AgentBaseTest` (6), `DomainRulesTest::testNearestRegionIsTheClosestCenter` |
| `php-cs-fixer fix --dry-run` · PHPStan 2.3.1 nível 6 | 0 de 126 arquivos · sem erros (PHPStan pelo PHAR oficial da versão travada: o pacote do Composer não pôde ser baixado daqui) |
| `npm run build` | sem erros; com o código de `main`, o build reproduziu exatamente os arquivos versionados |
| `scripts/verificacao-interface/agentes.cjs` (Chromium, 1440/390/360 px) | **32 verificações OK**, 0 erros de console, 0 violações de CSP: clique marca e muda; coordenadas não aceitam digitação; Enter marca o centro; CEP → rua; CEP sem rua → campo com motivo; base pelo endereço sem clicar; botão leva de volta ao endereço; rua não achada explica; cadastro só com nome, ponto e CEP; edição mostra a rua sem nova consulta e refaz ao mudar o CEP; histórico com rótulos; agente da demonstração abre e salva; sem JavaScript sem mapa vazio e coordenadas travadas; movimento reduzido; sem rolagem lateral; formulário de equipamentos inalterado |

**Não verificado:** as chamadas reais ao ViaCEP e ao Nominatim — `viacep.com.br`, `brasilapi.com.br` e o espelho do
Debian foram recusados pela política de rede deste ambiente, e o contêiner local não resolve nomes externos (o servidor
respondeu `INDISPONIVEL`, curl 6, com a mensagem amigável: capturas `05` e `08`). Os formatos de resposta usados nos
falsos seguem a documentação dos serviços, não uma resposta observada aqui. Também não verificado: leitores de tela
reais e a conferência de saída no contêiner publicado (comandos na ADR).
