# Pedido para o Claude que mantém o Promotheus Fiscaliza

Implemente no projeto uma extensão da parte Cidade Preditiva: **planejamento preventivo semanal por setores de Marília**, usando histórico agregado real do Infosiga-SP e os agentes fictícios já cadastrados. Quero demonstrar previsão, explicação, recomendação de reforço, confirmação do gestor e retrospecto.

O Codex preparou este pacote em `C:/Users/FelipedosSantosGomes/.codex/entregas-para-claude/ml-marilia-2026-10-20`. Se você recebeu um ZIP, use a pasta extraída como referência. Os arquivos desse pacote são a entrada para sua implementação; mantenha uma cópia imutável da execução de referência.

## Execute nesta ordem

1. Leia `02-ESPECIFICACAO-PARA-CLAUDE.md` e `03-CONTRATO-DOS-ARQUIVOS.md`. Confira o HEAD atual e as instruções do projeto. A inspeção de referência usou o GitHub `main` no commit `132dc7f42ec77d4c2c72bc2297a009f38a51967d`; a antiga pasta local estava atrasada. Preserve trabalho mais recente e confirme a base antes de integrar.
2. Inspecione a análise preventiva atual, mapa, autenticação, ingestão, migrações e regras de agentes. Registre um ADR curto sobre esta extensão e sobre os dados reais misturados à demonstração sintética.
3. Implemente importação CLI validada dos JSON de uma execução completa e do GeoJSON. Persista execuções e evidências no Firebird, com idempotência e transação. Mantenha o treinamento fora da requisição web.
4. Implemente a tela **Planejar reforço**, com mapa dos setores, período, referência dos dados, prioridade, estimativa, explicação e comparação. Reaproveite a identidade visual e componentes existentes. Preserve a análise de manutenção atual como um caso separado de antecipação.
5. Implemente sugestão de agente de trânsito disponível, confirmação do gestor e ciclo simples de missão de demonstração. Conflitos entre gestores e dupla reserva do mesmo agente devem ser recusados de forma atômica. O simulador não deve apagar a reserva.
6. Implemente retrospecto por semana: mostre primeiro a previsão congelada; revele os registros observados ao acionar o controle de comparação. Os dados históricos e a previsão atual devem ter identificação visual própria.
7. Leia `04-METODO-E-LIMITES.md` antes de alterar qualquer regra de treino, seleção ou alegação de eficácia. A referência foi escolhida no ajuste; o Poisson é um candidato experimental. O teste de 2025 já foi avaliado: tratá-lo como teste conhecido em novos experimentos.
8. Execute os critérios de aceite da especificação, os testes pertinentes no Firebird, análise estática, estilo, builds e verificação visual desktop/celular. Atualize README, guia, matriz de requisitos e autoria. Entregue comandos reais de importação e avaliação, arquivos alterados, capturas e limitações.

## Condições do pedido

- A integração dentro da pasta que você mantém cabe a você. O Codex não alterou esse projeto.
- Recursos e missões são fictícios; contagens históricas agregadas são reais e devem receber atribuição ao Infosiga-SP/Detran-SP. Atualize os avisos globais para refletir as duas origens corretamente.
- Uma previsão de contagem é diferente de porcentagem de chance de acidente. A priorização não comprova que deslocar agentes evitará sinistros.
- As sugestões e confirmações ficam dentro da aplicação de demonstração. Não envie mensagens a pessoas, não acione agentes reais e não publique/deploye automaticamente.
- Use a autenticação e o CSRF existentes. Não altere a visibilidade do GitHub nem as regras de preenchimento do inventário por causa desta extensão.
- O treino já é executável por `TREINAR.cmd`. Integre o contrato e adapte organização/nomes ao projeto; não há necessidade de criar um serviço Python permanente ou comprar APIs.

Conclua com uma demonstração reproduzível: importar execução, abrir Planejar reforço, selecionar setor/método, explicar a estimativa, confirmar uma missão fictícia e abrir o retrospecto de uma semana de teste. Apresente os resultados honestos da comparação com a referência.
