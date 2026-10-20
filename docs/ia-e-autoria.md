# IA e autoria

O regulamento pede a declaração das ferramentas de IA usadas. Registro do que de fato aconteceu:

| Etapa | Ferramenta | Contribuição |
|---|---|---|
| Planejamento | **Codex (OpenAI)** | Pacote de planejamento sem código: requisitos, arquitetura, segurança, experiência e critérios de aceite ([cópia](planejamento-codex-2026-10-18/00-LEIA-PRIMEIRO.md)). |
| Implementação | **Claude (Anthropic), modelo Opus 5.5, no Claude Code** (aplicativo desktop, 18/10/2026) | Prova PHP/Firebird, código, migração, testes, ícones e miniaturas SVG, documentação e verificações registradas em [verificacao.md](verificacao.md). |
| Auditoria (18/10/2026) | **Codex (OpenAI)**, com o plugin Codex Security | Revisão de código, documentação e interface sobre uma cópia do projeto: cinco defeitos funcionais (OP-01 a OP-05), um achado de segurança de baixa gravidade (SEC-01) e uma avaliação da interface. Não alterou o projeto. |
| Correções e redesenho (18/10/2026) | **Claude (Anthropic), Opus 5.5, no Claude Code** | Correção dos achados com testes de regressão (inclusive concorrência real), migração 0002, redesenho da interface, novas ilustrações, guia de uso, ADRs 0008–0011 e evidências ([correções](evidencias/2026-10-18-correcoes-da-auditoria.md)). |
| Pesquisa da nova direção (18/10/2026) | **Codex (OpenAI)** | Pedido de identidade, GSAP e reatividade, seleção de skills e pesquisa técnica (documentos fora do repositório; não estavam acessíveis no ambiente de nuvem da implementação, que seguiu o texto do pedido). |
| Identidade, motion e perfil compacto (18/10/2026) | **Claude (Anthropic), Opus 5.5, no Claude Code (nuvem)** | Marca (F em forma de vias + radar), sistema visual em Tailwind 4, Alpine CSP, GSAP/Flip, build Vite, central reativa, avisos, novas miniaturas, perfil compacto de 23 recursos, testes, ADRs 0012–0013, capturas e vídeos ([evidência](evidencias/2026-10-18-identidade-e-motion.md)). Skills consultadas (texto integral lido): `emil-design-eng` e `review-animations` (Emil Kowalski), `motion-design` (LottieFiles), `frontend-design` (Anthropic), `gsap-core`, `gsap-timeline`, `gsap-performance` e `gsap-plugins` (GreenSock). `ui-ux-pro-max` não foi usada. |
| Dados e treino do planejamento por setores (20/10/2026) | **Codex (OpenAI)** | Preparação do snapshot do Infosiga-SP (agregação por setor/dia, sem dados de vítimas ou veículos), treino Python separado, protocolo temporal, execução completa, especificação e contrato de importação. Não alterou o projeto; o pacote está copiado sem mudanças em `ml/marilia-setores/`. |
| Integração do planejamento por setores (20/10/2026) | **Claude (Anthropic), Opus 5.5, no Claude Code** | Migração 0003, importador validado (CLI), tela Planejar reforço, sugestão de agente, missão fictícia com reserva atômica, retrospecto, testes (unidade, Firebird, HTTP, concorrência), ADR-0016, documentação e capturas ([evidência](evidencias/2026-10-20-planejar-reforco.md)). Não mudou regra de treino, seleção nem métrica do pacote. |
| Decisões e responsabilidade | Autor do projeto | Escolha do desafio, preferência por PHP/Firebird, Marília como cenário, publicação e revisão final. |

Ferramentas auxiliares sem IA: Docker, Composer, PHPUnit, PHPStan, PHP-CS-Fixer, navegador embutido do Claude Code
para testes manuais e uma consulta pontual ao Nominatim (OpenStreetMap) para coordenadas ilustrativas.

**O que a IA não fez:** não publicou nada, não fez push, não submeteu o desafio e não acessou dados privados do
portal além de leitura local de documentação de operação (registrada fora do repositório).

**Compreensão do código.** O [guia de arquitetura](arquitetura.md) descreve cada módulo, entradas e saídas, e os ADRs
explicam as escolhas. Antes de apresentar, o autor deve conseguir explicar: por que PDO direto, como a central evita
divergência entre mapa e indicadores, como duplicatas e atrasos são tratados, e por que o índice preventivo não é
probabilidade.
