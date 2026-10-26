# Decisões de arquitetura (ADRs)

Cada ADR registra contexto, decisão e consequências **na data em que foi tomada**. ADRs não são reescritas: quando uma
decisão muda, uma ADR nova (ou um adendo datado) a atualiza, e a antiga continua como histórico. Para o estado atual do
sistema, leia o [manual](../MANUAL-DO-PROJETO.md) e os [guias](../guias/README.md).

| ADR | Data | Decisão | Situação |
|---|---|---|---|
| [0001](0001-php-firebird-em-conteiner.md) | 18/10 | PHP 8.5 + Firebird 5 em contêineres, com prova antes do código | aceita |
| [0002](0002-pdo-direto-sem-orm.md) | 18/10 | Slim 4 + Twig + PDO direto, sem ORM | aceita |
| [0003](0003-caracteristicas-por-tipo-em-json-validado.md) | 18/10 | características por tipo em JSON validado | aceita |
| [0004](0004-atualizacao-por-consulta-periodica.md) | 18/10 | Visão geral por consulta periódica a um instantâneo | aceita |
| [0005](0005-estado-atual-versus-historico.md) | 18/10 | estado atual separado do histórico de eventos | aceita |
| [0006](0006-publicacao-em-origem-dedicada.md) | 18/10 | recomendar origem dedicada na publicação | aceita (proposta) |
| [0007](0007-analise-preventiva-heuristica.md) | 18/10 | análise preventiva heurística com separação temporal | aceita |
| [0008](0008-interface-para-iniciantes.md) | 18/10 | interface para quem nunca usou o sistema | substituída em parte pela 0012 |
| [0009](0009-uma-intervencao-por-vez-e-arquivamento-explicito.md) | 18/10 | uma intervenção por vez; arquivamento sem ordens órfãs | aceita |
| [0010](0010-versao-do-cadastro-separada-da-telemetria.md) | 18/10 | versão do cadastro separada de posição e situação | aceita |
| [0011](0011-cota-de-login-atomica.md) | 18/10 | cota de login reservada antes do hash | aceita |
| [0012](0012-identidade-motion-e-build-de-assets.md) | 18/10 | identidade de trânsito, movimento coordenado, build de assets (adendo 19/10: cartão de autoria) | aceita |
| [0013](0013-perfil-compacto-de-demonstracao.md) | 18/10 | perfil compacto de demonstração (título cita 23 recursos; desde 24/10 são 17 equipamentos e 7 agentes, como diz o próprio texto) | aceita |
| [0014](0014-acesso-somente-com-conta-cadastrada.md) | 19/10 | acesso somente com conta cadastrada pelo administrador | aceita; substitui a leitura pública |
| [0015](0015-landing-publica-e-marca-3d.md) | 20/10 | landing pública na raiz e marca 3D | aceita; atualizada pela 0018 |
| [0016](0016-planejamento-preventivo-por-setores.md) | 20/10 | planejamento semanal por setores com histórico real agregado | aceita |
| [0017](0017-aba-multas.md) | 21/10 | aba Multas demonstrativa | aceita |
| [0018](0018-landing-seis-pilares-e-foto-de-marilia.md) | 22/10 | landing: seis pilares, foto de Marília, 3D sob demanda | aceita |
| [0019](0019-landing-planos-3d-cinematograficos.md) | 23/10 | landing: planos 3D cinematográficos | aceita; atualizada pela 0020 |
| [0020](0020-landing-enfeites-3d-junto-das-capturas.md) | 23/10 | landing: enfeites 3D junto das capturas, um plano só | aceita |
| [0021](0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md) | 23/10 | base do agente pelo mapa ou endereço; ViaCEP e Nominatim | aceita |
| [0022](0022-acompanhamento-dos-alertas-de-agentes.md) | 24/10 | acompanhamento dos alertas de agentes | aceita |
| [0023](0023-revisao-de-logica-e-fluxos.md) | 24/10 | revisão de lógica e fluxos (R01–R08) | aceita |

Todas as datas são de 2026. Nenhuma ADR nova foi criada pela documentação de 26/10/2026: ela descreve o sistema e não
toma decisões de arquitetura.
