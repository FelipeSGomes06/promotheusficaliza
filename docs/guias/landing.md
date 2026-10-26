# Landing pública

**Objetivo:** apresentar o Promotheus Fiscaliza a quem ainda não tem conta e levar ao acesso.
**Como acessar:** `/` do domínio, **fora** da aplicação; qualquer visitante. Sem banco, sessão nem cookies. O botão
**Acessar sistema** leva a `/promotheusfiscaliza/`, onde a aplicação decide o login
([acesso e contas](acesso-e-contas.md)).
Operação, configuração, vídeo e publicação: [`landing/README.md`](../../landing/README.md); código:
[referência da landing](../referencia/codigo/landing/landing.md).

## O que aparece na página

| Seção (âncora) | Conteúdo |
|---|---|
| Topo | marca, links das seções, **Reduzir movimento**, **Acessar sistema**; no celular, menu ☰ |
| Hero (`#inicio`) | "A cidade em movimento. A operação sob controle."; foto do terminal rodoviário de Marília (Pedro Monteiro, crédito no rodapé) e a **marca em 3D** que se monta uma vez |
| O sistema (`#sistema`) | captura real da Visão geral com legenda; enfeite 3D do mapa com pinos |
| Recursos (`#recursos`) | cenas de inventário, manutenção, multas e revisões com capturas reais (ampliáveis) e enfeites 3D (câmera, cone, placa **fictícia**, semáforo); entre elas, o **plano 3D noturno** de uma avenida com uma lombada eletrônica fictícia |
| Planejar reforço (`#planejamento`) | mapa de calor e as quatro prioridades |
| Dados e acesso (`#dados`) | "Saiba o que é real e o que é demonstração": operação fictícia; camada real = contagens agregadas do Infosiga-SP; perguntas: acesso sem conta (não), ligação com órgãos ou equipamentos reais (não), como acompanhar mudanças |
| Chamada final | **Acessar sistema** ("Acesso com conta fornecida pelo administrador.") |
| Para o avaliador (`#avaliador`) | vídeo de apresentação (`apresentacao.mp4` com capa); sem o arquivo, "Vídeo de apresentação em breve." |
| Rodapé | créditos e autoria |

## Comportamento

- **Sem JavaScript:** todo o texto, os botões e as capturas aparecem; a marca fica em SVG; os planos mostram o cartaz;
  os enfeites não aparecem; o link da captura abre o arquivo.
- **Sem WebGL ou se o 3D falhar:** SVG e cartazes ficam; nada quebra.
- **3D sob demanda:** os módulos (three.js) só são baixados quando a parte chega perto da tela e o navegador está
  ocioso; a marca, só com a hero visível ≥ 35% por 300 ms. Abaixo de 900 px os enfeites não aparecem.
- **Movimento reduzido** (botão do topo ou preferência do aparelho; mesma chave `pf-motion` da aplicação): marca pronta,
  quadro parado nos planos e enfeites prontos e parados, sem entradas de rolagem.
- **Captura ampliada:** clique abre um `<dialog>`; `Esc`, Fechar ou clique fora fecham e o foco volta ao link.
- **Celular:** menu em gaveta, imagens com recorte de celular, cenas ativas também abaixo de 900 px (regressão A11 da
  auditoria de 25/10).

## Publicação

No contêiner do projeto, o Apache serve `landing/public` na raiz e a aplicação em `/promotheusfiscaliza`
([implantação](../referencia/implantacao-e-ci.md#imagem-php-dockerphp)). A CSS e o JS são gerados por
`npm run build:landing` e **versionados** (a produção não precisa de Node); a CI confere que o build bate com
`landing/src/`. A página antiga ("Portal simulado") continua na imagem para reverter. O README da landing registra, em
19–20/10/2026, onde o subdomínio é servido.

## Verificação e limites

Evidências: [20/10](../evidencias/2026-10-20-landing-publica.md), [22/10](../evidencias/2026-10-22-landing-atualizada.md),
[planos 3D](../evidencias/2026-10-23-planos-3d.md), [enfeites 3D](../evidencias/2026-10-23-enfeites-3d.md);
`scripts/verificacao-interface/auditoria.cjs` (A11). Não há testes PHPUnit da landing. Decisões: ADR-0015, 0018, 0019,
0020 ([índice](../adr/README.md)).

## Relacionados

[Acesso e contas](acesso-e-contas.md) · [Vídeo e automações](video-e-automacoes.md) · [Sobre e interface](sobre-e-interface.md)
