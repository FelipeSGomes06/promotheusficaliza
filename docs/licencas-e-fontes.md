# Licenças e fontes

| Item | Origem | Licença / termos | Uso |
|---|---|---|---|
| Código do projeto | Este repositório | MIT ([LICENSE](../LICENSE)) | Todo `src/`, `templates/`, `public/assets/js`, `public/assets/css`, testes e scripts. |
| Ícones, miniaturas e favicon | Criados para o projeto | MIT | `public/assets/img/`. Ilustram a categoria; não representam modelos comerciais. |
| Leaflet 1.9.4 | npm `leaflet@1.9.4` | BSD-2-Clause ([cópia](../public/assets/vendor/leaflet/LICENSE)) | Mapa, servido localmente. |
| Space Grotesk | npm `@fontsource-variable/space-grotesk@5.2.10` | SIL OFL 1.1 ([cópia](../public/assets/fonts/OFL-space-grotesk.txt)) | Títulos e números. O texto usa a fonte do sistema do visitante. |
| Capturas de tela em `docs/evidencias/capturas-2026-10-18/` | Geradas localmente com dados fictícios | MIT (as telas); o mapa de fundo nelas é © colaboradores do OpenStreetMap, com a atribuição visível | Evidência antes/depois do redesenho. |
| Tiles do mapa | `tile.openstreetmap.org` (configurável) | [Política de tiles da OSMF](https://operations.osmfoundation.org/policies/tiles/); dados © colaboradores do OpenStreetMap (ODbL) | Imagens carregadas pelo navegador do visitante, com atribuição visível. Sem download em massa, sem cache próprio. Para tráfego relevante, trocar de provedor via `MAP_TILE_URL`. |
| Coordenadas de bairros e avenidas | Consulta pontual ao Nominatim em 18/10/2026 (≈40 requisições, 1/s, com identificação) | © colaboradores do OpenStreetMap, ODbL | Pontos ilustrativos do cenário. Nominatim não é usado em tempo de execução nem como autocompletar. |
| Slim, slim/psr7, Twig e dependências | Packagist (via `composer.lock`) | MIT / BSD-3-Clause | Execução. |
| PHPUnit, PHPStan, PHP-CS-Fixer | Packagist (dev) | BSD-3-Clause / MIT | Qualidade. |
| Imagens Docker | `php:8.5.11-apache-trixie`, `firebirdsql/firebird:5.0.4-trixie`, `composer:2.10` | Licenças dos projetos (PHP License, IDPL/IPL do Firebird, MIT) | Ambiente de execução. |
| Enunciado do desafio | DSIN Coder Challenge 2026 | Da organizadora | Apenas referenciado; não reproduzido. |

Nomes de bairros, logradouros e CEPs de logradouro de Marília são referências geográficas públicas. Órgãos, marcas,
modelos, séries e agentes são fictícios; qualquer semelhança com produtos ou pessoas reais é coincidência.
