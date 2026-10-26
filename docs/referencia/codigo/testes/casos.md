# Casos de teste

<!-- fontes: tests/Unit/AddressLocatorTest.php tests/Unit/AttentionModelTest.php tests/Unit/CepLookupTest.php tests/Unit/DatabaseSeparationTest.php tests/Unit/DomainRulesTest.php tests/Unit/FineRulesTest.php tests/Unit/HelpTipTest.php tests/Unit/InputAndSpecsTest.php tests/Unit/UrbanImportTest.php tests/Unit/ViewCacheTest.php tests/Unit/ViteAssetsTest.php tests/Unit/fixtures/ui-campos-sem-ajuda.html tests/Integration/AccountsTest.php tests/Integration/AgentBaseTest.php tests/Integration/AlertFollowUpTest.php tests/Integration/CentralAndAnalysisTest.php tests/Integration/DemoProfileTest.php tests/Integration/DriverContractTest.php tests/Integration/EquipmentLocationTest.php tests/Integration/FinesTest.php tests/Integration/InventoryTest.php tests/Integration/MaintenanceInvariantsTest.php tests/Integration/OperationsTest.php tests/Integration/PlanningTest.php tests/Integration/UrbanUpdaterTest.php tests/Http/AgentsHttpTest.php tests/Http/FinesHttpTest.php tests/Http/LoginQuotaTest.php tests/Http/PlanningHttpTest.php tests/Http/ReviewFlowsHttpTest.php tests/Http/WebSecurityTest.php -->

Cada caso, o comportamento que ele prova e onde o comportamento está documentado. Os nomes dos métodos já são frases
(em inglês); a coluna "Prova" é a tradução fiel, sem acrescentar o que o teste não verifica. A infraestrutura comum
(banco de testes, falsos, concorrência) está em [suporte](suporte.md).

> **Quantos testes e quando passaram.** Este catálogo descreve o código; ele não é um resultado de execução. As
> execuções registradas estão datadas em [verificação](../../../verificacao.md) — a mais recente informada lá é de
> 24/10/2026 (283 testes, 1914 asserções). O commit da auditoria de 25/10 (`35de711`) acrescentou casos depois
> disso — por exemplo `AccountsTest`, `Interleaving` e casos novos em `FinesTest`, `InventoryTest` e
> `UrbanUpdaterTest` — e não há registro datado da execução deles em `verificacao.md`. Esta documentação **não** rodou
> a suíte (o ambiente desta revisão não tinha Docker/Firebird).

## Suíte `unidade` (`tests/Unit`, sem banco)

### `AddressLocatorTest` — localização pelo endereço ([endereço](../application/endereco.md))

| Caso | Prova |
|---|---|
| `AddressLocatorTest::testStreetInMariliaBecomesAPointAndOnlyTheStreetGoesOut` | rua de Marília vira um ponto e só a rua sai para o provedor |
| `AddressLocatorTest::testStreetNotFoundOrOutsideTheAreaIsNotFound` | rua não achada ou fora da área = não encontrada |
| `AddressLocatorTest::testProviderDownTimeoutOrOddAnswersAreUnavailable` | provedor fora, tempo esgotado ou resposta estranha = indisponível |
| `AddressLocatorTest::testLocatorUsesTheTypedStreetOrTheStreetOfTheCep` | usa a rua digitada ou a rua do CEP |
| `AddressLocatorTest::testAddressAreaIsTheStreetStretchOrTheHouse` | a "área do endereço" é o trecho da rua ou a casa |
| `AddressLocatorTest::testGeocoderAddressComesFromConfigAndMustBeHttps` | o endereço do provedor vem da configuração e precisa ser HTTPS |

Auxiliar: `AddressLocatorTest::geocoder` (`NominatimGeocoder` com resposta simulada).

### `AttentionModelTest` — heurística de revisão ([atenção preventiva](../domain/atencao-preventiva.md))

| Caso | Prova |
|---|---|
| `AttentionModelTest::testQuietEquipmentIsLowAttention` | equipamento sem ocorrências = atenção baixa |
| `AttentionModelTest::testRecentFailuresAndOverduePreventiveRaiseAttentionWithExplanations` | falhas recentes e preventiva vencida elevam a atenção, com explicações |
| `AttentionModelTest::testShortHistoryIsInsufficientNotLowRisk` | histórico curto é "insuficiente", não "baixo risco" |
| `AttentionModelTest::testAlreadyFailingIsCorrectiveNotPrediction` | equipamento já em falha é caso corretivo, não previsão |
| `AttentionModelTest::testF1ComesFromCountsAndKeepsZeroDistinctFromUndefined` | F1 calculado das contagens; zero é diferente de indefinido |
| `AttentionModelTest::testMetricsHandleEmptyAndCountConfusionMatrix` | métricas tratam conjunto vazio e contam a matriz de confusão |

Auxiliar: `AttentionModelTest::features` (atributos de entrada com sobrescrita).

### `CepLookupTest` — consulta de CEP ([endereço](../application/endereco.md))

| Caso | Prova |
|---|---|
| `CepLookupTest::testValidCepReturnsStreetAndOnlyTheDigitsGoOutWithAShortTimeout` | CEP válido devolve a rua; só os dígitos saem, com timeout curto |
| `CepLookupTest::testCepThatDoesNotExistIsNotFound` | CEP inexistente = não encontrado |
| `CepLookupTest::testGeneralCityCepComesWithoutStreet` | CEP geral da cidade vem sem rua |
| `CepLookupTest::testCepOfAnotherCityIsRecognized` | CEP de outra cidade é reconhecido como tal |
| `CepLookupTest::testProviderDownOrOddAnswersAreUnavailable` | provedor fora ou resposta estranha = indisponível |
| `CepLookupTest::testTimeoutIsUnavailable` | tempo esgotado = indisponível |
| `CepLookupTest::testStreetIsCutToTheColumnSize` | a rua é cortada no tamanho da coluna |
| `CepLookupTest::testProviderAddressComesFromConfigAndMustBeHttps` | endereço do provedor vem da configuração e exige HTTPS |

Auxiliar: `CepLookupTest::client` (`ViaCepClient` com resposta simulada).

### `DatabaseSeparationTest` — proteção do banco principal ([suporte](suporte.md#databaseseparation))

| Caso | Prova |
|---|---|
| `DatabaseSeparationTest::testSameTargetIsRefusedEvenWithDifferentOptions` | mesmo alvo é recusado mesmo com opções diferentes no DSN |
| `DatabaseSeparationTest::testDifferentAliasesForTheSameFileAreRefused` | apelidos diferentes para o mesmo arquivo são recusados |
| `DatabaseSeparationTest::testSeparateDatabasesOrNoMainDatabaseAreAccepted` | bancos separados, ou sem banco principal, são aceitos |

### `DomainRulesTest` — regras de domínio ([situações](../domain/situacoes-e-atualidade.md), [indicadores e alertas](../domain/indicadores-e-alertas.md), [tempo](../domain/tempo.md))

| Caso | Prova |
|---|---|
| `DomainRulesTest::testNearestRegionIsTheClosestCenter` | bairro sugerido = centro mais próximo |
| `DomainRulesTest::testAvailabilityExcludesNotInstalledAndDeactivatedFromDenominator` | disponibilidade exclui não instalados e desativados do denominador |
| `DomainRulesTest::testZeroDenominatorIsNotApplicableInsteadOfZeroOrHundredPercent` | denominador zero = "não se aplica", nem 0% nem 100% |
| `DomainRulesTest::testAgentsAreCountedSeparately` | agentes são contados à parte dos equipamentos |
| `DomainRulesTest::testStatusTransitions` | tabela de transições de situação (dados: `DomainRulesTest::transitions`) |
| `DomainRulesTest::testStartBlockerFollowsTheTransitionTable` | o impedimento de iniciar manutenção segue a mesma tabela |
| `DomainRulesTest::testAdministrativeStatusesAreNotOverriddenByTelemetry` | situação administrativa não é sobrescrita pela telemetria |
| `DomainRulesTest::testMaintenanceUrgencyUsesLocalDates` | urgência da manutenção usa datas locais (dados: `DomainRulesTest::urgencies`) |
| `DomainRulesTest::testLocalDateCrossesMidnightInSaoPaulo` | a data local vira à meia-noite de São Paulo, não de UTC |
| `DomainRulesTest::testFreshnessThresholds` | limites de atualidade do sinal e da posição |
| `DomainRulesTest::testNextActionFollowsTheAlertFromOpenToFollowUp` | a próxima ação acompanha o alerta da abertura ao acompanhamento |
| `DomainRulesTest::testAlertConditionsFromCurrentState` | condições de alerta derivadas do estado atual |

### `FineRulesTest` — regras das multas ([multas](../domain/multas.md))

| Caso | Prova |
|---|---|
| `FineRulesTest::testPlateFormatOnlyAfterRemovingPresentationPunctuation` | formato da placa avaliado depois de tirar a pontuação de apresentação (dados: `FineRulesTest::plates`) |
| `FineRulesTest::testInternalCodeIsDerivedFromIdAndParsedBack` | o código interno vem do id e é lido de volta |
| `FineRulesTest::testAitKeyKeepsPunctuationAndIgnoresCaseAndEdges` | chave do AIT mantém a pontuação e ignora maiúsculas e espaços nas pontas |
| `FineRulesTest::testBrazilianMoneyBecomesCentsWithoutFloat` | valor em reais vira centavos sem ponto flutuante (dados: `FineRulesTest::money`) |
| `FineRulesTest::testArrayWhereTextIsExpectedIsTreatedAsEmpty` | lista onde se espera texto = vazio |
| `FineRulesTest::testLocalDateTimeIsStrictAndConvertedToUtc` | data e hora local é estrita e convertida para UTC |
| `FineRulesTest::testFilterPeriodIsInclusiveLocalAndHalfOpenInUtc` | período do filtro: inclusivo em data local, semiaberto em UTC |
| `FineRulesTest::testRegistrationNeedsMinimumFieldsButJustifiedAbsencesCount` | registrar exige mínimos, mas ausência justificada conta |
| `FineRulesTest::testSpeedCoherenceIsCheckedWithoutInventingTolerance` | coerência das velocidades sem inventar tolerância |
| `FineRulesTest::testConsideredSpeedFollowsTheContranMargin` | velocidade considerada segue a margem do Contran (dados: `FineRulesTest::speeds`) |
| `FineRulesTest::testExpiredMeterVerificationOnTheOccurrenceDayNeedsAttention` | aferição do medidor vencida no dia da ocorrência pede atenção |
| `FineRulesTest::testAttentionReasonsComeFromTheSameRuleAndCancelledHasNone` | motivos "A conferir" saem da mesma regra; cancelada não tem |
| `FineRulesTest::testPendingDocumentsStopNeedingAttentionOnlyWhenRegularityIsInformedLater` | pendência documental só deixa de pedir atenção com regularidade informada depois |
| `FineRulesTest::testReviewReminderAnswerBecomesADateCountedFromToday` | a resposta do lembrete vira uma data contada de hoje |
| `FineRulesTest::testUnusualSequencesAreOnlyWarnings` | sequências incomuns de datas são só avisos |
| `FineRulesTest::testDocumentSelectionUsesCheckInstantSubjectVersionAndScope` | a conferência escolhida usa instante, versão do sujeito e momento |

Auxiliar: `FineRulesTest::complete` (multa completa com sobrescrita).

### `HelpTipTest` — ajuda contextual "?" ([macros](../templates/macros-e-telas.md))

| Caso | Prova |
|---|---|
| `HelpTipTest::testFieldsWithoutHelpTipKeepTheSharedStructure` | campo sem ajuda mantém a estrutura comum (compara com `tests/Unit/fixtures/ui-campos-sem-ajuda.html`) |
| `HelpTipTest::testHelpTipIsAnAccessibleButtonNextToTheLabel` | a ajuda é um botão acessível ao lado do rótulo |
| `HelpTipTest::testStatusAndSpecHelpComeFromTheDomain` | as explicações de situação e especificação vêm do domínio |
| `HelpTipTest::testEveryCatalogFieldHasHelp` | todo campo do catálogo de equipamentos tem ajuda |

Auxiliar: `HelpTipTest::render` (renderiza um trecho Twig com as macros reais).

### `InputAndSpecsTest` — fronteira de entrada ([validação e filtros](../application/validacao-e-filtros.md), [catálogo](../domain/catalogo-de-equipamentos.md), [configuração](../raiz/configuracao-e-servicos.md))

| Caso | Prova |
|---|---|
| `InputAndSpecsTest::testRadarRequiresSpeedLimitOnlyWhenEnforcingSpeed` | radar só exige limite de velocidade quando fiscaliza velocidade |
| `InputAndSpecsTest::testCameraCapabilitiesAreStructured` | capacidades da câmera são estruturadas |
| `InputAndSpecsTest::testBoundaryInputNormalization` | normalização da entrada na fronteira |
| `InputAndSpecsTest::testFiltersDropUnknownValuesAndScopeResourceKinds` | filtros descartam valores desconhecidos e restringem os tipos de recurso |
| `InputAndSpecsTest::testDotEnvParsingAndBasePathValidation` | leitura do `.env` e validação do `APP_BASE_PATH` |
| `InputAndSpecsTest::testTileOriginForCsp` | origem dos ladrilhos do mapa para a CSP |
| `InputAndSpecsTest::testMigrationSplitterIgnoresCommentsAndSplitsAtLineEnd` | divisor de migrações ignora comentários e corta no `;` de fim de linha |

### `UrbanImportTest` — pacote do planejamento ([importação](../application/importacao-urbana.md), [setores](../domain/setores-e-regioes.md))

| Caso | Prova |
|---|---|
| `UrbanImportTest::testReferenceCopyIsByteIdenticalToTheCodexManifest` | os 28 arquivos de `ml/marilia-setores` batem byte a byte com o SHA-256 de `ENTREGA-MANIFESTO.json` |
| `UrbanImportTest::testReferenceRunIsValidAndKeepsEveryDigit` | a execução de referência é válida e mantém todos os dígitos |
| `UrbanImportTest::testReplayRowsReproduceTheDeclaredTestMetrics` | as linhas do retrospecto reproduzem as métricas declaradas |
| `UrbanImportTest::testGridCellsMatchTheRunAndRankTheCityCentreFirst` | os quadrados batem com a execução e o centro fica em primeiro |
| `UrbanImportTest::testGridOutsideTheContractIsRejected` | grade fora do contrato é recusada (dados: `UrbanImportTest::invalidGrids`) |
| `UrbanImportTest::testRankIsDescendingWithLexicalTieBreak` | ordem decrescente com desempate lexical |
| `UrbanImportTest::testDistanceIsZeroInsideAndStraightLineOutside` | distância zero dentro do quadrado, em linha reta fora |
| `UrbanImportTest::testFilesOutsideTheContractAreRejected` | arquivos fora do contrato são recusados (dados: `UrbanImportTest::invalidBundles`) |

Auxiliares: `UrbanImportTest::readDefault` (lê o pacote de referência), `UrbanImportTest::tearDown` (apaga o diretório temporário).

### `ViewCacheTest` e `ViteAssetsTest` — renderização e assets ([aplicação web](../http/aplicacao-web.md))

| Caso | Prova |
|---|---|
| `ViewCacheTest::testProductionCacheRecompilesATemplateChangedByAnUpdate` | o cache de produção recompila um template alterado por atualização |
| `ViteAssetsTest::testTagsPointToHashedFilesUnderTheConfiguredPrefix` | as tags apontam para arquivos com hash sob o prefixo configurado |
| `ViteAssetsTest::testRejectsUnexpectedFileNamesAndMissingManifest` | nomes inesperados e manifesto ausente são recusados |

Auxiliares: `ViewCacheTest::setUp`, `ViewCacheTest::tearDown`, `ViewCacheTest::request`; `ViteAssetsTest::setUp`,
`ViteAssetsTest::tearDown`, `ViteAssetsTest::assets` (cria diretório e manifesto temporários).

## Suíte `integracao-firebird` (`tests/Integration`)

Todos estendem [`FirebirdTestCase`](suporte.md#firebirdtestcase).

### `AccountsTest` — contas ([autenticação](../application/autenticacao.md))

| Caso | Prova |
|---|---|
| `AccountsTest::testTheLastActiveAdminCannotBeDeactivated` | o último administrador ativo não pode ser desativado |
| `AccountsTest::testCrossDeactivationAtTheSameTimeKeepsOneActiveAdmin` | dois administradores desativando um ao outro ao mesmo tempo deixam um ativo (`Interleaving`) |

Auxiliar: `AccountsTest::activeAdmins`.

### `AgentBaseTest` — base de trabalho do agente ([agentes](../application/agentes.md))

| Caso | Prova |
|---|---|
| `AgentBaseTest::testRegistersWithOnlyNameRoleAgencyStatusBaseNameAndPoint` | cadastra só com nome, função, órgão, situação, nome da base e ponto |
| `AgentBaseTest::testOnlyNameRoleAgencyBaseNameAndPointAreRequired` | só esses campos são obrigatórios |
| `AgentBaseTest::testStreetComesFromTheFormAndRegionOrReferenceSentByTheClientAreIgnored` | a rua vem do formulário; bairro ou referência enviados pelo cliente são ignorados |
| `AgentBaseTest::testWithoutThePointTheBaseGoesToTheAddress` | sem o ponto, a base vai para o endereço |
| `AgentBaseTest::testPointOutsideTheAddressGoesToTheAddressOnSave` | ponto fora do endereço vai para o endereço ao salvar |
| `AgentBaseTest::testLegacyAgentKeepsChosenRegionUntilThePointChanges` | agente antigo mantém o bairro escolhido até o ponto mudar |
| `AgentBaseTest::testLegacyAgentWithoutRegionGetsOneOnTheNextSave` | agente antigo sem bairro ganha um no próximo salvamento |

Auxiliares: `AgentBaseTest::setUp` (falsos de CEP/geocodificação com respostas), `AgentBaseTest::minimal`, `AgentBaseTest::userId`.

### `AlertFollowUpTest` — acompanhamento de alertas ([alertas](../application/alertas.md))

| Caso | Prova |
|---|---|
| `AlertFollowUpTest::testPauseKeepsTheSameAlertInsteadOfResolvingAndReopening` | pausa mantém o mesmo alerta em vez de resolver e reabrir |
| `AlertFollowUpTest::testAcknowledgeKeepsTheAlertPendingAndDoesNotAssign` | ciência mantém o alerta pendente e não atribui responsável |
| `AlertFollowUpTest::testFollowUpsComeAfterAcknowledgeAndAreAppendOnly` | providências vêm depois da ciência e só se acrescentam |
| `AlertFollowUpTest::testFollowUpValidationKeepsNothingAndExplainsEachField` | validação não grava nada e explica cada campo |
| `AlertFollowUpTest::testNewAccountIsAssignedAndReassignedWithHistory` | conta nova é atribuída e reatribuída com histórico |
| `AlertFollowUpTest::testDeactivatedAccountKeepsTheLinkButReceivesNoNewAssignment` | conta desativada mantém o vínculo mas não recebe atribuição nova |
| `AlertFollowUpTest::testFormReadBeforeAnotherPersonActedIsRefused` | formulário lido antes de outra pessoa agir é recusado |
| `AlertFollowUpTest::testResolutionReasonSeparatesNewPositionFromLeavingShift` | o motivo de encerramento separa "posição recebida" de "saiu do turno" |
| `AlertFollowUpTest::testOldPendingAlertIsListedEvenWithManyRecentClosedOnes` | alerta antigo pendente aparece mesmo com muitos encerrados recentes |
| `AlertFollowUpTest::testManualStatusChangeNeedsAReason` | mudança manual de situação exige motivo |

Auxiliares: `AlertFollowUpTest::account`, `AlertFollowUpTest::agentWithAlert`, `AlertFollowUpTest::version`, `AlertFollowUpTest::localIn`.

### `CentralAndAnalysisTest` — Visão geral e revisões ([central](../application/central.md), [análise](../application/analise-preventiva.md))

| Caso | Prova |
|---|---|
| `CentralAndAnalysisTest::testMapListAndIndicatorsComeFromTheSameFilteredSet` | mapa, lista e indicadores vêm do mesmo conjunto filtrado |
| `CentralAndAnalysisTest::testSearchAndAttentionFilterTheWholeSnapshot` | busca e filtro de atenção valem para o retrato inteiro |
| `CentralAndAnalysisTest::testNoEquipmentInServiceShowsNotApplicable` | sem equipamento em serviço = "não se aplica" |
| `CentralAndAnalysisTest::testResourceWithoutCoordinatesIsListedButNotPlaced` | recurso sem coordenadas aparece na lista, não no mapa |
| `CentralAndAnalysisTest::testLaterIntervalChangeDoesNotRewriteAPastCutoff` | mudança posterior do intervalo de manutenção não reescreve um corte passado |
| `CentralAndAnalysisTest::testEventReceivedAfterTheCutoffIsUnknownAtTheCutoff` | evento recebido depois do corte é desconhecido no corte |
| `CentralAndAnalysisTest::testEquipmentArchivedLaterStillExistedAtEarlierCutoffs` | equipamento arquivado depois ainda existia nos cortes anteriores |
| `CentralAndAnalysisTest::testDemoSeedAnalysisIsReproducibleAndTemporallySeparated` | a análise da carga de demonstração é reproduzível e separada no tempo |

Auxiliar: `CentralAndAnalysisTest::row`.

### `DemoProfileTest` — perfis da demonstração ([cenário](../demo/cenario-e-carga.md))

| Caso | Prova |
|---|---|
| `DemoProfileTest::testCompactProfileCoversEveryCategoryStatusAndMaintenanceExampleWithTrueCounts` | o perfil compacto cobre toda categoria, situação e exemplo de manutenção, com contagens verdadeiras |
| `DemoProfileTest::testFullProfileRemainsAvailableForVolumeAndMethodology` | o perfil completo continua disponível |
| `DemoProfileTest::testCompactSelectionIsASubsetAndUnknownProfilesAreRejected` | o compacto é subconjunto do completo; perfil desconhecido é recusado |

Auxiliar: `DemoProfileTest::seedProfile`.

### `DriverContractTest` — contrato com o PDO_FIREBIRD ([banco](../infrastructure/banco-e-migracoes.md))

| Caso | Prova |
|---|---|
| `DriverContractTest::testUtf8TextBlobDecimalAndBooleanRoundTrip` | UTF-8, BLOB de texto, decimal e booleano voltam iguais |
| `DriverContractTest::testInsertReturningAndOffsetFetchPagination` | `INSERT … RETURNING` e paginação `OFFSET/FETCH` |
| `DriverContractTest::testFailureInsideTransactionRollsBackEveryWrite` | falha dentro da transação desfaz todas as escritas |
| `DriverContractTest::testLockWaitIsBoundedAndBecomesConcurrencyConflict` | espera de lock tem limite e vira `ConcurrencyConflict` |
| `DriverContractTest::testApplicationIdentityCannotRewriteHistoryOrChangeSchema` | o usuário da aplicação não reescreve histórico nem muda esquema |

### `EquipmentLocationTest` — local do equipamento ([equipamentos](../application/equipamentos.md))

| Caso | Prova |
|---|---|
| `EquipmentLocationTest::testPlannedEquipmentNeedsALocation` | equipamento planejado precisa de local |
| `EquipmentLocationTest::testWithoutThePointTheLocationGoesToTheAddressAndTheRegionComesFromIt` | sem o ponto, o local vai para o endereço e o bairro sai dele |
| `EquipmentLocationTest::testPointOutsideTheAddressGoesToTheAddress` | ponto fora do endereço vai para o endereço |
| `EquipmentLocationTest::testLegacyEquipmentWithoutCoordinatesGetsALocationOnEdit` | equipamento antigo sem coordenadas ganha local ao editar |

Auxiliares: `EquipmentLocationTest::setUp`, `EquipmentLocationTest::planned`.

### `FinesTest` — multas ([serviço](../application/multas.md), [repositórios](../infrastructure/repositorios-multas.md), [carga](../demo/multas-de-demonstracao.md))

| Caso | Prova |
|---|---|
| `FinesTest::testDraftIsCompletedRegisteredAndKeepsInternalCodeApartFromAit` | rascunho é completado e registrado; código interno separado do AIT |
| `FinesTest::testSourceIsCheckedOnTheServerEvenWithManipulatedPost` | a origem é conferida no servidor mesmo com POST manipulado |
| `FinesTest::testRenavamKeepsLeadingZerosAndMayBeJustifiedlyMissing` | RENAVAM mantém zeros à esquerda e pode faltar com justificativa |
| `FinesTest::testSpeedsStayTranscribedAndIncoherentSpeedBlocksOnlyRegistration` | velocidades ficam como transcritas; incoerência só bloqueia o registro |
| `FinesTest::testDocumentChecksAreTemporalAndRetificationRequiresANewOne` | conferências são temporais; retificar a identidade exige nova conferência |
| `FinesTest::testPendingOnTheOccurrenceDayLeavesToReviewOnlyWhenRegularityIsInformedLater` | pendência no dia só sai de "A conferir" com regularidade informada depois |
| `FinesTest::testRadarWithoutTheInfractionAmongItsFunctionsNeedsAnExplanation` | radar sem a função da infração exige observação |
| `FinesTest::testNeverInstalledOrFixedRadarWithAnOperatorNeedsAnExplanation` | radar nunca instalado, ou fixo com operador, exige observação |
| `FinesTest::testReviewReminderAnswerIsStoredAsADateAndCannotPrecedeTheCheckedDay` | o lembrete é gravado como data e não antecede o dia conferido |
| `FinesTest::testLaterCheckReminderFollowsUpFromItsDayAndTheLastCheckDecides` | lembrete da conferência posterior conta do dia dela; vale a última |
| `FinesTest::testRetificationCannotPlaceTheOccurrenceAfterFactsAlreadyRecorded` | retificação não põe a ocorrência depois de fatos já registrados |
| `FinesTest::testProgressDimensionsAreIndependentAndCorrectionsNeedAReason` | etapa, pagamento e contestação são independentes; correções exigem motivo |
| `FinesTest::testCancellationIsInternalReadOnlyAndRepeatedCommandsDoNotDuplicate` | cancelamento é interno, deixa só leitura, e comandos repetidos não duplicam |
| `FinesTest::testSameCommandSentTwiceAtTheSameTimeIsAReplayNotAConflict` | o mesmo comando enviado duas vezes ao mesmo tempo é repetição, não conflito (`Interleaving`) |
| `FinesTest::testVersionAndCommandKeyProtectAgainstLostUpdatesAndReplays` | versão e chave do comando protegem contra perda de atualização e repetição |
| `FinesTest::testAitIsUniquePerAgencyAndAbsentAitsNeverCollide` | AIT único por órgão; AITs ausentes nunca colidem |
| `FinesTest::testConcurrentCreationsResolveAitAndCommandKeyInTheDatabase` | criações simultâneas: AIT e chave resolvidos pelo banco (`Parallel`) |
| `FinesTest::testFailureWritingHistoryRollsBackTheWholeOperation` | falha ao gravar o histórico desfaz a operação inteira |
| `FinesTest::testApplicationIdentityCannotRewriteFineHistory` | o usuário da aplicação não reescreve o histórico das multas |
| `FinesTest::testSnapshotSurvivesLaterArchivingAndArchivedRadarStillServesHistory` | o retrato da origem sobrevive ao arquivamento; radar arquivado segue no histórico |
| `FinesTest::testListTotalsAndSummariesShareTheSameFilterWithoutDuplicates` | lista, totais e resumos usam o mesmo filtro, sem duplicar |
| `FinesTest::testNonDestructiveSeedAddsTheScenarioOnceAndRefusesUnmarkedDatabases` | a carga não destrutiva acrescenta o cenário uma vez e recusa banco sem marcador |

Auxiliares: `FinesTest::setUp`, `FinesTest::radarFine`, `FinesTest::version`, `FinesTest::cmd` (comando com chave e versão), `FinesTest::rows`.

### `InventoryTest` — inventário ([equipamentos](../application/equipamentos.md), [agentes](../application/agentes.md))

| Caso | Prova |
|---|---|
| `InventoryTest::testCreatePersistsTypeSpecificSpecsAndHistory` | criar grava especificações do tipo e histórico |
| `InventoryTest::testCodesKeepCountingPastTheMinimumWidth` | códigos continuam contando além da largura mínima |
| `InventoryTest::testSerialIsUniquePerManufacturerAndUnknownSerialsDoNotCollide` | série única por fabricante; séries desconhecidas não colidem |
| `InventoryTest::testConcurrentDuplicateSerialIsResolvedByTheDatabase` | série duplicada simultânea é resolvida pelo banco |
| `InventoryTest::testStaleFormVersionIsRejectedWithoutLosingTheOtherEdit` | versão antiga do formulário é recusada sem perder a outra edição |
| `InventoryTest::testPositionAndTelemetryDoNotInvalidateAnOpenRegistrationForm` | posição e telemetria não invalidam um formulário de cadastro aberto |
| `InventoryTest::testAgentStatusChangeDoesNotInvalidateAnOpenAgentForm` | mudança de situação do agente não invalida o formulário aberto |
| `InventoryTest::testInstallationChangesTheRegistrationAndInvalidatesAnOlderForm` | instalação muda o cadastro e invalida formulário mais antigo |
| `InventoryTest::testPendingChangesCompareTheTypedVersionWithTheSavedOne` | o conflito compara o digitado com o salvo |
| `InventoryTest::testStatusTransitionsAreEnforcedAndRecorded` | transições de situação são impostas e registradas |
| `InventoryTest::testLocationKindFollowsTheStatusOnCreationAndInTransitions` | o tipo de local acompanha a situação na criação e nas transições |
| `InventoryTest::testArchivingRemovesFromOperationButKeepsHistory` | arquivar tira da operação e mantém o histórico |
| `InventoryTest::testMobileResourceKeepsBaseSeparateFromObservedPosition` | recurso móvel mantém a base separada da última posição |

Auxiliares: `InventoryTest::userId`, `InventoryTest::rawRow`.

### `MaintenanceInvariantsTest` — manutenção ([manutenção](../application/manutencao.md))

| Caso | Prova |
|---|---|
| `MaintenanceInvariantsTest::testSecondInterventionWaitsForTheFirstAndCompletionReleasesOnlyThen` | a segunda intervenção espera a primeira; só a conclusão libera |
| `MaintenanceInvariantsTest::testOrdersCanBeScheduledInAnyStatusButStartOnlyWhereTheTransitionExists` | agenda-se em qualquer situação; inicia-se só onde a transição existe |
| `MaintenanceInvariantsTest::testPendingOrdersAreAllReadEvenBehindMoreThanThirtyClosedOnes` | ordens pendentes aparecem mesmo atrás de mais de 30 encerradas |
| `MaintenanceInvariantsTest::testDatabaseRejectsTwoRunningInterventionsEvenOutsideTheService` | o banco recusa duas em execução mesmo fora do serviço |
| `MaintenanceInvariantsTest::testManualStatusChangeCannotReleaseARunningIntervention` | mudança manual de situação não libera intervenção em execução |
| `MaintenanceInvariantsTest::testArchiveIsRefusedWhileAnInterventionRunsAndTheOrderCanStillBeCompleted` | arquivar é recusado durante intervenção, que ainda pode ser concluída |
| `MaintenanceInvariantsTest::testScheduledOrdersAreCancelledOnlyWithExplicitConfirmationAndKeepAuthorship` | ordens agendadas só são canceladas com confirmação explícita, com autoria |
| `MaintenanceInvariantsTest::testConcurrentStartsOnTheSameEquipmentAdmitExactlyOne` | inícios simultâneos no mesmo equipamento: exatamente um (`Parallel`) |
| `MaintenanceInvariantsTest::testStartRacingArchiveNeverLeavesAnUnfinishableOrder` | início correndo com arquivamento nunca deixa ordem impossível de concluir (`Parallel`) |

Auxiliares: `MaintenanceInvariantsTest::openOrder`, `MaintenanceInvariantsTest::state`, `MaintenanceInvariantsTest::userId`.

### `OperationsTest` — ingestão, alertas e simulador ([ingestão](../application/ingestao.md), [alertas](../application/alertas.md), [console](../cli/console.md))

| Caso | Prova |
|---|---|
| `OperationsTest::testDuplicateEventIsIgnoredAndCounted` | evento duplicado é ignorado e contado |
| `OperationsTest::testLateEventGoesToHistoryWithoutRegressingCurrentState` | evento atrasado vai para o histórico sem regredir o estado atual |
| `OperationsTest::testAdministrativeStatusPrevailsOverTelemetry` | situação administrativa prevalece sobre a telemetria |
| `OperationsTest::testOlderPositionDoesNotReplaceNewerOne` | posição mais antiga não substitui a mais nova |
| `OperationsTest::testAlertLifecycleIsIdempotentAndAcknowledgeDoesNotResolve` | ciclo do alerta é idempotente e ciência não resolve |
| `OperationsTest::testConcurrentReconcileNeverDuplicatesAnOpenAlert` | reconciliações simultâneas nunca duplicam um alerta aberto |
| `OperationsTest::testMaintenanceLifecycle` | ciclo da manutenção |
| `OperationsTest::testUrgentSummaryCountsOnlyOrdersWaitingToStart` | o resumo de urgentes conta só ordens esperando início |
| `OperationsTest::testCompletionCanReportThatEquipmentStillFails` | a conclusão pode informar que o equipamento continua em falha |
| `OperationsTest::testSimulatorTickIsSkippedWhileAnotherTickHoldsTheLock` | `sim:tick` é pulado enquanto outro ciclo segura a trava |

Auxiliar: `OperationsTest::userId`.

### `PlanningTest` — planejamento ([planejamento](../application/planejamento.md), [importação](../application/importacao-urbana.md))

| Caso | Prova |
|---|---|
| `PlanningTest::testImportPersistsTheRunWithFullPrecisionAndIsIdempotent` | a importação grava com precisão total e é idempotente |
| `PlanningTest::testGridIsAddedToAnExistingRunOnceAndShownWithTheWeek` | a grade é acrescentada uma vez à execução existente e aparece com a semana |
| `PlanningTest::testInvalidRunLeavesNothingWritten` | execução inválida não grava nada |
| `PlanningTest::testImportedRunCannotBeRewrittenByTheApplicationUser` | o usuário da aplicação não reescreve a execução importada |
| `PlanningTest::testPoissonComparisonUsesItsOwnValuesWithoutChangingTheImportedRun` | a comparação Poisson usa seus próprios valores sem mudar a execução |
| `PlanningTest::testSuggestionOnlyListsFreeTrafficAgentsAndFlagsPositionsThatAreNotCurrent` | a sugestão lista só agentes de trânsito livres e marca posições não atuais |
| `PlanningTest::testConfirmationRechecksEligibilityAndRequiresJustificationForAManualChoice` | confirmar reconfere a elegibilidade; escolha manual exige justificativa |
| `PlanningTest::testTheAgentSuggestedOnScreenNeedsNoJustificationEvenIfTheOrderChangedBeforeSending` | o agente sugerido na tela dispensa justificativa mesmo se a ordem mudou antes do envio |
| `PlanningTest::testMissionForASquareMeasuresDistanceToItAndKeepsTheSquare` | missão por quadrado mede a distância até ele e guarda o quadrado |
| `PlanningTest::testClosingReleasesTheAgentAndKeepsTheHistory` | encerrar libera o agente e mantém o histórico |
| `PlanningTest::testTheDatabaseRefusesASecondActiveMissionForTheSameAgent` | o banco recusa segunda missão vigente para o mesmo agente |
| `PlanningTest::testTwoManagersConfirmingTheSameAgentAtOnceGetOneMissionAndOneConflict` | dois gestores ao mesmo tempo: uma missão e um conflito (`Parallel`) |
| `PlanningTest::testSimulatorTicksNeverTouchAConfirmedMission` | 40 ciclos do simulador não tocam a missão confirmada |
| `PlanningTest::testFinishedPeriodRefusesNewMissions` | período encerrado recusa missões novas |
| `PlanningTest::testReplayShowsFrozenPredictionsForEveryWeekAndRevealsObservedOnlyOnRequest` | o retrospecto mostra previsões congeladas e só revela o observado a pedido |

Auxiliares: `PlanningTest::import`, `PlanningTest::counts`, `PlanningTest::agentAt`, `PlanningTest::confirm`,
`PlanningTest::assertRefused`, `PlanningTest::userId`.

### `UrbanUpdaterTest` — atualização mensal/semanal ([atualização urbana](../application/atualizacao-urbana.md))

| Caso | Prova |
|---|---|
| `UrbanUpdaterTest::testWeeklyForecastFromTheMonthlyFileReproducesTheReferenceRun` | a previsão semanal a partir do arquivo mensal reproduz a execução de referência |
| `UrbanUpdaterTest::testSameFileAndSameWeekAreNotStoredTwice` | mesmo arquivo e mesma semana não são gravados duas vezes |
| `UrbanUpdaterTest::testIdenticalFileInTheNextMonthCountsAsThatMonthsCheck` | arquivo idêntico no mês seguinte conta como a checagem desse mês |
| `UrbanUpdaterTest::testFiltersKeepOnlyEligibleMariliaRecordsOfTheLastTwelveMonths` | filtros guardam só registros elegíveis de Marília dos últimos 12 meses |
| `UrbanUpdaterTest::testBrokenFilesAreRefusedWithoutWritingAndDownloadFailureKeepsTheForecastStep` | arquivo quebrado é recusado sem gravar; falha no download mantém o passo da previsão |
| `UrbanUpdaterTest::testPoissonFormulaMatchesThePythonParityVectors` | a fórmula Poisson em PHP bate com os vetores de paridade do Python |

Auxiliares: `UrbanUpdaterTest::setUp`, `UrbanUpdaterTest::tearDown`, `UrbanUpdaterTest::importReference`,
`UrbanUpdaterTest::zipFromGrid`, `UrbanUpdaterTest::zip`, `UrbanUpdaterTest::temp`, `UrbanUpdaterTest::assertRefusal`.
Os arquivos ZIP são montados no teste; **nenhum download real** acontece.

## Suíte `http` (`tests/Http`)

Requisições PSR-7 entregues a `WebApp::create(...)->handle()` com [`ArraySession`](suporte.md#falsos-e-sessão-em-memória),
contra o banco de testes — sem servidor web.

### `AgentsHttpTest` — agentes e endereço pela web ([controladores](../http/controladores.md))

| Caso | Prova |
|---|---|
| `AgentsHttpTest::testCepLookupNeedsAnAccountAndNeverReachesTheProviderWithoutOne` | consulta de CEP exige conta; sem conta o provedor nunca é chamado |
| `AgentsHttpTest::testInvalidCepIsRefusedBeforeAnyCallToTheProvider` | CEP inválido é recusado antes de chamar o provedor |
| `AgentsHttpTest::testCepLookupAnswersWithStreetOrAFriendlyReasonToTypeIt` | responde a rua ou um motivo amigável para digitá-la |
| `AgentsHttpTest::testLocatingByAddressGivesAPointWithoutTypingCoordinates` | localizar pelo endereço dá um ponto sem digitar coordenadas |
| `AgentsHttpTest::testWithoutTheMapTheBaseGoesToTheAddressOnSave` | sem o mapa, a base vai para o endereço ao salvar |
| `AgentsHttpTest::testNewAgentFormLeadsWithTheMapAndAsksOnlyForPointAndCep` | o formulário novo começa pelo mapa e pede só ponto e CEP |
| `AgentsHttpTest::testRegisteringThroughTheFormNeedsOnlyNameAndPoint` | cadastrar pelo formulário exige só nome e ponto |
| `AgentsHttpTest::testLegacyAgentOpensWithItsStreetAndSavesWithClearHistory` | agente antigo abre com sua rua e salva com histórico claro |
| `AgentsHttpTest::testFollowUpOnTheAgentPageKeepsWhatWasTypedOnEveryRefusal` | o acompanhamento na ficha mantém o digitado a cada recusa |

Auxiliares: `AgentsHttpTest::setUp`, `AgentsHttpTest::request`, `AgentsHttpTest::loginAs`, `AgentsHttpTest::json`.

### `FinesHttpTest` — multas pela web

| Caso | Prova |
|---|---|
| `FinesHttpTest::testEveryFineRouteRequiresAnAccountAndDeniedWritesStoreNothing` | toda rota de multas exige conta; escrita negada não grava nada |
| `FinesHttpTest::testDeactivatedAccountLosesAccessOnTheNextRequest` | conta desativada perde o acesso na requisição seguinte |
| `FinesHttpTest::testWholeFlowWorksWithPlainFormsForOperatorAndAdmin` | o fluxo inteiro funciona com formulários comuns, para operador e administrador |
| `FinesHttpTest::testErrorsKeepTypingAndStaleFormsShowTheCurrentVersion` | erros mantêm o digitado; formulário antigo mostra a versão atual |
| `FinesHttpTest::testUntrustedTextIsEscapedAndPagesHaveNoInlineCode` | texto não confiável é escapado; páginas sem código inline |
| `FinesHttpTest::testGetNeverWritesMissingIdIs404AndBackLinkStaysInside` | GET nunca grava; id inexistente = 404; link de volta fica dentro do sistema |
| `FinesHttpTest::testFieldsShownOnlyInSomeSituationsAppearExactlyWhenTheServerDemandsThem` | campos condicionais aparecem exatamente quando o servidor os exige |
| `FinesHttpTest::testRegistrationFormHidesOnlyOptionalOrAlternativeFieldsAndOpensThemWhenTheyMatter` | o formulário esconde só opcionais/alternativos e os abre quando importam |
| `FinesHttpTest::testModuleWorksWithAnEmptyBasePath` | o módulo funciona com `APP_BASE_PATH` vazio |

Auxiliares: `FinesHttpTest::setUp`, `FinesHttpTest::request`, `FinesHttpTest::loginAs`, `FinesHttpTest::formKeys`,
`FinesHttpTest::complete`, `FinesHttpTest::fines`, `FinesHttpTest::shownFor` (avalia `data-show-if` como o JavaScript),
`FinesHttpTest::dom`, `FinesHttpTest::errorFields`, `FinesHttpTest::registeredFine`.

### `LoginQuotaTest` — cota atômica de login ([autenticação](../application/autenticacao.md))

| Caso | Prova |
|---|---|
| `LoginQuotaTest::testVerificationInProgressCountsAgainstTheQuota` | verificação em andamento já conta na cota |
| `LoginQuotaTest::testSuccessfulLoginSettlesItsOwnReservation` | login correto quita a própria reserva |
| `LoginQuotaTest::testRepeatedSuccessfulLoginsAndOlderFailureRowsCoexist` | logins corretos repetidos convivem com falhas antigas |
| `LoginQuotaTest::testConcurrentAttemptsNeverExceedTheUserLimit` | tentativas simultâneas nunca passam do limite (dados: `LoginQuotaTest::accounts`; `Parallel`) |
| `LoginQuotaTest::testConcurrentHttpLoginsFromDistinctSessionsWithValidCsrfRespectTheLimit` | logins HTTP simultâneos, sessões distintas e CSRF válido, respeitam o limite |

Auxiliares: `LoginQuotaTest::priorFailures`, `LoginQuotaTest::key`.

### `PlanningHttpTest` — Planejar reforço pela web

| Caso | Prova |
|---|---|
| `PlanningHttpTest::testWithoutAnImportedRunThePageSaysSoAndShowsNoNumbers` | sem execução importada a página diz isso e não mostra números |
| `PlanningHttpTest::testPageShowsPeriodReferenceMethodOriginsAndOnlyTheJsonDataInline` | a página mostra período, referência, método e origens; só JSON de dados inline |
| `PlanningHttpTest::testHeatMapCarriesTheWholeGridWhileTheListKeepsOnlyThePriorities` | o mapa de calor leva a grade toda; a lista, só os prioritários |
| `PlanningHttpTest::testConfirmingAMissionNeedsCsrfAnAccountAndAnEligibleAgent` | confirmar missão exige CSRF, conta e agente elegível |
| `PlanningHttpTest::testAManualChoiceWithoutJustificationShowsTheErrorAtTheFieldAndKeepsWhatWasTyped` | escolha manual sem justificativa: erro no campo, digitação mantida |
| `PlanningHttpTest::testReplayKeepsObservedCountsHiddenUntilRevealed` | o retrospecto esconde o observado até revelar |

Auxiliares: `PlanningHttpTest::setUp`, `PlanningHttpTest::import`, `PlanningHttpTest::request`, `PlanningHttpTest::loginAs`.

### `ReviewFlowsHttpTest` — fluxos revisados em 24/10

| Caso | Prova |
|---|---|
| `ReviewFlowsHttpTest::testStolenEquipmentAsksForReplacementOrWriteOffInTheNextAction` | equipamento furtado pede reposição ou baixa na próxima ação |
| `ReviewFlowsHttpTest::testFailureNextActionUsesTheScheduledCorrectiveOrder` | a próxima ação da falha usa a corretiva agendada |
| `ReviewFlowsHttpTest::testMaintenanceStartIsOfferedOnlyWhenTheEquipmentCanEnterMaintenance` | "Iniciar" só aparece quando o equipamento pode entrar em manutenção |
| `ReviewFlowsHttpTest::testAnOldPendingOrderStaysReachableBehindThirtyClosedOnes` | ordem pendente antiga continua alcançável atrás de 30 encerradas |
| `ReviewFlowsHttpTest::testEveryConfirmedMissionKeepsItsCloseActionsAndTheAgentPageLinksToIt` | toda missão confirmada mantém as ações de encerrar; a ficha do agente aponta para ela |
| `ReviewFlowsHttpTest::testAdminResetsAPasswordOnTheSameAccountAndOpenSessionsEnd` | o administrador redefine a senha da mesma conta e as sessões abertas acabam |
| `ReviewFlowsHttpTest::testWorkingSummaryNamesTheRegisteredSituationAndTheRecentSignal` | o resumo nomeia a situação registrada e o sinal recente |
| `ReviewFlowsHttpTest::testFineRetificationConflictKeepsTypingAndPointsToTheProgress` | conflito na retificação mantém o digitado e aponta o andamento |

Auxiliares: `ReviewFlowsHttpTest::setUp`, `ReviewFlowsHttpTest::request`, `ReviewFlowsHttpTest::login`,
`ReviewFlowsHttpTest::loginAs`, `ReviewFlowsHttpTest::page`, `ReviewFlowsHttpTest::openOrder`.

### `WebSecurityTest` — segurança da aplicação web ([middlewares](../http/middlewares.md))

| Caso | Prova |
|---|---|
| `WebSecurityTest::testVisitorIsSentToLoginWithoutCookieAndWithSecurityHeaders` | visitante vai para o login sem cookie e com cabeçalhos de segurança |
| `WebSecurityTest::testEveryScreenAndTheApiRequireAnAccount` | toda tela e a API exigem conta |
| `WebSecurityTest::testOnlyAccountsRegisteredByTheAdminGetIn` | só entram contas cadastradas pelo administrador |
| `WebSecurityTest::testLogoutReturnsToTheLoginPage` | sair volta ao login |
| `WebSecurityTest::testSignedInUserSeesUnavailabilityWhenTheAccountCannotBeChecked` | logado vê indisponibilidade quando a conta não pode ser conferida |
| `WebSecurityTest::testWritesRequireCsrfAndAuthentication` | escritas exigem CSRF e autenticação |
| `WebSecurityTest::testCrossOriginPostIsRejectedEvenWithToken` | POST de outra origem é recusado mesmo com token |
| `WebSecurityTest::testOperatorCreatesEquipmentAndCannotManageAccounts` | operador cria equipamento e não gerencia contas |
| `WebSecurityTest::testUntrustedTextIsEscapedInHtmlAndJson` | texto não confiável é escapado em HTML e JSON |
| `WebSecurityTest::testLoginFailuresAreGenericThrottledAndRedirectStaysInternal` | falhas de login são genéricas, limitadas, e o redirecionamento fica interno |
| `WebSecurityTest::testMalformedQueryParametersDegradeSafely` | parâmetros malformados degradam com segurança |
| `WebSecurityTest::testEditConflictKeepsTypingShowsDifferencesAndCarriesTheCurrentVersion` | conflito de edição mantém o digitado, mostra diferenças e leva a versão atual |
| `WebSecurityTest::testEquipmentFormAsksForLocationByMapOrAddress` | o formulário de equipamento pede local pelo mapa ou endereço |
| `WebSecurityTest::testEditConflictTableNamesTheRightAgencyAndRegion` | a tabela do conflito nomeia o órgão e o bairro certos |
| `WebSecurityTest::testAcknowledgeAlertViaJsonUsesHeaderToken` | ciência por JSON usa o token no cabeçalho |
| `WebSecurityTest::testPagesUseOnlyLocalBuiltAssetsWithoutInlineCode` | páginas usam só assets locais gerados, sem código inline |
| `WebSecurityTest::testSimulateFailureViaJsonReportsTheServerOutcome` | simular falha por JSON informa o resultado do servidor |

Auxiliares: `WebSecurityTest::setUp`, `WebSecurityTest::request`, `WebSecurityTest::loginAs`, `WebSecurityTest::login`.

## Testes fora do PHPUnit

| Onde | O quê | Documentação |
|---|---|---|
| `ml/marilia-setores/tests/test_protocolo.py` | protocolo do treino em Python (`unittest`) | [ML](../../../../ml/README.md) |
| `ml/marilia-setores/verificar_paridade.php` | paridade PHP × Python da fórmula Poisson | [ML](../../../../ml/README.md) |
| `scripts/verificacao-interface/*.cjs` | verificação da interface no navegador (Playwright) — **cria dados** num banco de verificação | [verificação da interface](../../../../scripts/verificacao-interface/README.md) |
