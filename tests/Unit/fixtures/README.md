# Fixtures dos testes de unidade

| Arquivo | Usado por | Conteúdo |
|---|---|---|
| `ui-campos-sem-ajuda.html` | `HelpTipTest::testFieldsWithoutHelpTipKeepTheSharedStructure` | HTML que as macros `ui.input`/`ui.select` geravam **antes** da ajuda contextual: o teste prova que um campo sem `help_tip` continua idêntico. |

Atualize o arquivo só quando a estrutura comum dos campos mudar de propósito, e registre a mudança na evidência
correspondente ([`docs/evidencias/`](../../../docs/evidencias/README.md)).
