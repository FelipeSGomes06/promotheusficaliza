# IA e autoria

O regulamento pede a declaração das ferramentas de IA usadas. Registro do que de fato aconteceu:

| Etapa | Ferramenta | Contribuição |
|---|---|---|
| Planejamento | **Codex (OpenAI)** | Pacote de planejamento sem código: requisitos, arquitetura, segurança, experiência e critérios de aceite ([cópia](planejamento-codex-2026-10-18/00-LEIA-PRIMEIRO.md)). |
| Implementação | **Claude (Anthropic), modelo Opus 5.5, no Claude Code** (aplicativo desktop, 18/10/2026) | Prova PHP/Firebird, código, migração, testes, ícones e miniaturas SVG, documentação e verificações registradas em [verificacao.md](verificacao.md). |
| Decisões e responsabilidade | Autor do projeto | Escolha do desafio, preferência por PHP/Firebird, Marília como cenário, publicação e revisão final. |

Ferramentas auxiliares sem IA: Docker, Composer, PHPUnit, PHPStan, PHP-CS-Fixer, navegador embutido do Claude Code
para testes manuais e uma consulta pontual ao Nominatim (OpenStreetMap) para coordenadas ilustrativas.

**O que a IA não fez:** não publicou nada, não fez push, não submeteu o desafio e não acessou dados privados do
portal além de leitura local de documentação de operação (registrada fora do repositório).

**Compreensão do código.** O [guia de arquitetura](arquitetura.md) descreve cada módulo, entradas e saídas, e os ADRs
explicam as escolhas. Antes de apresentar, o autor deve conseguir explicar: por que PDO direto, como a central evita
divergência entre mapa e indicadores, como duplicatas e atrasos são tratados, e por que o índice preventivo não é
probabilidade.
