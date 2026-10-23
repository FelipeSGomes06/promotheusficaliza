# ADR-0021 — Base do agente pelo mapa ou pelo endereço; rua pelo CEP (ViaCEP), ponto pela rua (Nominatim)

Data: 23/10/2026 · Situação: aceita · Relacionadas: [ADR-0008](0008-interface-para-iniciantes.md),
[ADR-0014](0014-acesso-somente-com-conta-cadastrada.md), [ADR-0016](0016-planejamento-preventivo-por-setores.md)

## Contexto

No "Cadastrar agente", o passo "Base de trabalho" tinha mapa, seis campos de endereço (nome da base, logradouro,
número ou referência, CEP, bairro, cidade) e, recolhidas em "Coordenadas da base", latitude e longitude obrigatórias.
Quem cadastra não sabia qual caminho usar, e o logradouro digitado à mão saía inconsistente. O autor pediu: o mapa é o
jeito de marcar e de mudar a base; o endereço digitado sai; a rua vem de uma consulta pelo CEP. No mesmo dia,
acrescentou: latitude e longitude **nunca** são digitadas; quem não consegue pelo mapa usa o CEP e a rua. São as
primeiras chamadas a serviços externos feitas durante uma requisição web.

## Decisões

- **Formulário**: nome da base; mapa com "Clique no mapa para marcar ou mudar a base"; latitude e longitude visíveis
  logo abaixo e **somente leitura** (também sem JavaScript); cidade fixa (Marília/SP); CEP opcional; rua. Saem como
  campo digitado: logradouro, número ou referência, bairro. O formulário de equipamentos não mudou.
- **Ponto pelo mapa**: clique marca ou muda; pelo teclado, Enter ou espaço no mapa marca o centro (que ganha uma mira
  enquanto o mapa tem foco). Sem JavaScript, ou se o Leaflet não carregar, mapa e instrução não aparecem e uma linha
  explica o caminho pelo endereço.
- **Rua pelo CEP**: com 8 dígitos, o navegador chama `GET /api/cep/{cep}` **no próprio servidor** (a CSP continua
  `connect-src 'self'`); o servidor valida o CEP antes de qualquer chamada e consulta o **ViaCEP**
  (`https://viacep.com.br/ws/{cep}/json/`, gratuito, sem chave, um único destino; a BrasilAPI v2 consulta vários
  provedores por trás e espalharia o CEP). A tela mostra "Rua: …" e grava em `BASE_STREET`. CEP não encontrado, geral
  da cidade, de outra cidade, provedor fora do ar ou sem rede: aparece o campo "Rua" para digitar, com o motivo.
- **Ponto pelo endereço** (quem não consegue pelo mapa): `GET /api/localizar?rua=&cep=` usa a rua informada ou, sem
  ela, a rua do CEP, e consulta o **Nominatim** (o buscador do OpenStreetMap, a mesma base do mapa) com busca
  estruturada "rua + Marília/SP", presa ao retângulo da demonstração. Com JavaScript, ao achar a rua pelo CEP sem ponto
  marcado, a base vai sozinha para o endereço; "Levar a base até este endereço" faz o mesmo sob pedido; um clique no
  mapa ajusta. Sem JavaScript, o servidor localiza ao salvar (só com o resto do formulário válido, fora da transação);
  se não achar, explica no campo e mantém o que foi digitado. O ponto do endereço é aproximado (meio da rua): não há
  número da casa.
- **Provedores atrás de interfaces pequenas** (`CepLookup` → `ViaCepClient`, `Geocoder` → `NominatimGeocoder`, juntos em
  `AddressLocator`), criados em `Services` só quando usados, com `curl` (o `allow_url_fopen` fica desligado), só https,
  sem redirecionamento, tempo limite de 3 s e URL base configurável (`CEP_LOOKUP_URL`, `GEOCODER_URL`). Os testes
  injetam falsos e nunca usam a rede. Política do Nominatim: uso eventual, no máximo 1 requisição por segundo (o
  servidor espaça as suas com uma trava de arquivo), User-Agent do projeto, atribuição visível (já presente: "mapa ©
  OpenStreetMap") e nada de autocompletar (só há consulta quando a pessoa pede, quando o CEP traz a rua sem ponto
  marcado ou ao salvar sem ponto).
- **Limite de dados**: sai do servidor só o CEP (8 dígitos) ou o nome da rua, mais o User-Agent do projeto; nada da
  conta, do agente ou do navegador. Grava-se só a rua, o CEP normalizado e o ponto; bairro, cidade e UF do ViaCEP
  servem apenas para a mensagem da tela. Sem cache; o log privado registra só o motivo de uma indisponibilidade. Rotas
  só com conta (401 sem login, como `/api/central`).
- **Bairro derivado**: `REGION_ID` deixa de ser escolhido. Vem do ponto, pelo centro de bairro mais próximo
  (`NearestRegion`), a mesma régua do "perto de …" do Planejar, agora compartilhada; os filtros por bairro continuam
  valendo. Na edição, é recalculado quando o ponto muda (ou se não havia bairro); fora isso, fica o gravado, inclusive
  o escolhido à mão em cadastros antigos.
- **Dados antigos**: logradouro, referência e bairro gravados continuam na ficha e na edição (a rua aparece como
  "Rua: …"). O antigo "número ou referência" descrevia o endereço digitado: sai quando o ponto ou a rua mudam. Nenhuma
  coluna mudou; sem migração. O histórico passa a listar rótulos ("Cadastro alterado: rua, ponto no mapa."), e a ficha
  mostra assim também as alterações antigas, que guardavam o nome técnico dos campos.

## Saída para a internet no contêiner publicado

As consultas saem do contêiner `app`. Em [`compose.yaml`](../../compose.yaml) ele está na rede `backend`, que **não** é
`internal: true`: o Docker dá saída pela NAT do host. [`compose.origem-dedicada.yaml`](../../compose.origem-dedicada.yaml)
só troca o site do Apache e o `APP_BASE_PATH`; não mexe em rede. A imagem `php:8.5.11-apache-trixie` traz `curl` e os
certificados do Debian, e o Compose não define proxy. Basta o host (ou o firewall do provedor) permitir HTTPS de saída
(porta 443) até `viacep.com.br` e `nominatim.openstreetmap.org`. Conferência depois de publicar:

```bash
docker compose exec app curl -sS -m 3 https://viacep.com.br/ws/17501000/json/
docker compose exec app curl -sS -m 3 -A 'PromotheusFiscaliza/1.0' 'https://nominatim.openstreetmap.org/search?street=Avenida%20Sampaio%20Vidal&city=Mar%C3%ADlia&state=S%C3%A3o%20Paulo&format=jsonv2&limit=1'
```

Se falharem, o mapa continua sendo o caminho do cadastro; pelo endereço, a tela diz que a consulta não respondeu, e o
log da aplicação mostra "consulta de CEP indisponível: …" ou "localização pelo endereço indisponível: …".

## Consequências

- Duas dependências de rede, ambas opcionais enquanto o mapa funciona: se o ViaCEP cair, a rua deixa de vir sozinha;
  se o Nominatim cair (ou bloquear o IP do servidor), o caminho sem mapa para. Sem mapa **e** sem provedores, não há
  como marcar a base: é a consequência de nunca digitar coordenadas.
- O endpoint de CEP pode servir depois ao formulário de equipamentos, que tem o mesmo bloco de rua/CEP (não alterado).
- Coberto por `CepLookupTest` e `AddressLocatorTest` (clientes com transporte falso: encontrado, inexistente, geral,
  outra cidade, fora da área, fora do ar, tempo esgotado), `AgentsHttpTest` (entradas inválidas → 422 sem chamada
  externa, sem login → 401, cada resposta, formulário, cadastro sem mapa, ficha e dados antigos), `AgentBaseTest`
  (cadastro só com nome e ponto, ponto pelo endereço, rua opcional, bairro derivado) e
  `scripts/verificacao-interface/agentes.cjs`. As chamadas reais ao ViaCEP e ao Nominatim **não** foram verificadas no
  ambiente de desenvolvimento (rede bloqueada): ver [evidência](../evidencias/2026-10-23-agente-base-pelo-mapa-e-cep.md).
