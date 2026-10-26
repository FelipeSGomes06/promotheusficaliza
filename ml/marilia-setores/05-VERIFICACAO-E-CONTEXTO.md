# Verificação e contexto

Preparado em 20/10/2026.

## Entregue

- Contrato dos arquivos e descrição do método.
- Preparação reproduzível do snapshot oficial: verificação SHA-256, leitura só dos CSV de sinistros, agregação e períodos com zero, sem extração de pessoas/veículos.
- Dados agregados, setores GeoJSON e manifesto da fonte/licença.
- Treino Python separado da aplicação, busca pequena de regularização no ajuste, comparação com referência, teste temporal e emissão atual.
- Modelo em JSON, previsões, evidências e retrospecto de uma execução completa.
- Comando Windows para repetir o treino e verificação numérica local em PHP.

## Ambiente e verificações

Ambiente virtual criado somente nesta pasta. Python 3.14.6; versões fixadas em `requirements.txt`, instaladas do PyPI com preferência obrigatória por wheels. Nenhuma configuração global do Python foi alterada.

Comandos verificados:

```powershell
python preparar_dados.py --zip <caminho>/dados_infosiga.zip
& ./.venv/Scripts/python.exe -m unittest discover -s tests -v
& ./.venv/Scripts/python.exe treino.py --modo piloto --as-of 2026-10-20 --saida runs/piloto-verificado
& ./.venv/Scripts/python.exe treino.py --modo completo --as-of 2026-10-20 --saida runs/primeiro-treino-verificado
php verificar_paridade.php runs/primeiro-treino-verificado/vetores_paridade.json
```

- Preparação: **8.419 eventos elegíveis**, contagens diárias completas.
- **4 testes passaram:** corte de publicação atravessando ano; alteração arbitrária de eventos futuros sem mudar características; semanas/zeros e rótulos disponíveis antes do ajuste; atribuição determinística de borda dos setores.
- O primeiro teste encontrou falta de 52 semanas de histórico no início previsto de janeiro/2020, devido ao atraso de publicação. O primeiro corte foi corrigido para **02/03/2020**, antes do piloto válido e antes de consultar as métricas de teste. A exigência de histórico foi preservada.
- Piloto: quatro valores de alpha avaliados apenas na janela de ajuste; a referência venceu e a decisão foi gravada antes do teste.
- Execução completa terminou; resultados em `runs/primeiro-treino-verificado/RESULTADO.md`. Não houve mudança de algoritmo/variáveis/grade depois de examinar o teste de 2025.
- Paridade: **20 vetores PHP/Python**, erro absoluto máximo **2,6645352591003757e-15**, abaixo da tolerância.
- O indicador de status se chama `final_test_evaluated`: o piloto carrega o snapshot agregado para preparar a grade, mas não avalia as previsões no teste final. É só metadado e não altera modelo ou métricas.

## Decisões

1. Treinar contagens em quatro quadrantes amplos: simples de integrar e com todos os setores comparáveis. Não usar os limites como se fossem bairros oficiais.
2. Separar o caso urbano da manutenção existente; a previsão não usa eventos sintéticos do simulador como rótulos de validação.
3. Retirar dois meses completos de informação em cada corte, por falta de data de primeira publicação. Mostrar essa hipótese e a data de referência ao usuário.
4. Selecionar método/alpha no ajuste e preservar a seleção mesmo com resultado diferente no teste. A comparação pode ser oferecida, com identificação do método.
5. Exportar JSON e previsões em lote, evitando dependência Python em requisição PHP.
6. Usar regras de disponibilidade/proximidade para sugestões de agentes; não fingir que o conjunto contém dados de alocação ou efeito causal da fiscalização.
7. Exigir reserva atômica de agente e histórico de missão na aplicação. Nenhuma comunicação externa é disparada por este pacote.
8. Manter venv, caches e fonte bruta fora do repositório.

## Estado

O treino está concluído e não deixa processo em segundo plano. A repetição com mesmos dados/parâmetros serve para reproduzir; treinamento mais longo não acrescenta dados nem garante ganho.

O teste de 2025 agora é conhecido. Seus números não podem guiar novas tentativas e depois ser apresentados como avaliação independente. O ganho do candidato foi modesto, não uma garantia operacional.
