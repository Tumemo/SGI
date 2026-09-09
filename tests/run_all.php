<?php
declare(strict_types=1);

/**
 * Runner Principal da Suite Completa de Testes Automatizados do SGI
 * Execute via terminal: php tests/run_all.php
 */

require_once __DIR__ . '/Support/TestClient.php';
require_once __DIR__ . '/Support/Assertions.php';
require_once __DIR__ . '/Support/TestDatabase.php';
require_once __DIR__ . '/Support/AuditFixtures.php';
require_once __DIR__ . '/Integration/AuditFixturesTest.php';
require_once __DIR__ . '/Integration/MesarioResourceScopeTest.php';
require_once __DIR__ . '/Integration/TemporaryResolutionScopeTest.php';
require_once __DIR__ . '/Integration/ExceptionEnvelopeTest.php';
require_once __DIR__ . '/Integration/CronometroPersistenceTest.php';
require_once __DIR__ . '/Integration/AuthAndRbacTest.php';
require_once __DIR__ . '/Integration/InterclasseLifecycleTest.php';
require_once __DIR__ . '/Integration/PontuacaoReconciliationTest.php';
require_once __DIR__ . '/Integration/ArrecadacaoConsistencyTest.php';
require_once __DIR__ . '/Integration/PodiumCreditTest.php';
require_once __DIR__ . '/Integration/IndividualSyncCreditTest.php';
require_once __DIR__ . '/Integration/HistoryRankingReconciliationTest.php';
require_once __DIR__ . '/Integration/ConcurrentInvariantsTest.php';
require_once __DIR__ . '/Integration/TurmasAndPdfImportTest.php';
require_once __DIR__ . '/Integration/ModalidadesAndEquipesTest.php';
require_once __DIR__ . '/Integration/InscricaoModalidadesTest.php';
require_once __DIR__ . '/Integration/JogosAndConflitosTest.php';
require_once __DIR__ . '/Integration/AgendamentoBlocoTest.php';
require_once __DIR__ . '/Integration/PlacarAndArtilhariaTest.php';
require_once __DIR__ . '/Integration/OcorrenciasAndRankingTest.php';
require_once __DIR__ . '/Integration/HistoricoTurmaAndClassificacaoTest.php';
require_once __DIR__ . '/Integration/FotoPerfilAndUsuariosTest.php';
require_once __DIR__ . '/Integration/AlunosPortalTest.php';
require_once __DIR__ . '/Integration/PublicBoundaryTest.php';
require_once __DIR__ . '/Integration/RefactorContractsTest.php';
require_once __DIR__ . '/Integration/MigrationsTest.php';
require_once __DIR__ . '/Integration/RecoveryRehearsalTest.php';
require_once __DIR__ . '/Integration/ConsistencyGuardsTest.php';
require_once __DIR__ . '/Integration/InitialAdminTest.php';
require_once __DIR__ . '/Integration/AtomicMutationTest.php';
require_once __DIR__ . '/Integration/MataMataEdgeCasesTest.php';
require_once __DIR__ . '/E2E/FullOfflineTournamentTest.php';

use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;
use SGITests\Integration\AuditFixturesTest;
use SGITests\Integration\MesarioResourceScopeTest;
use SGITests\Integration\TemporaryResolutionScopeTest;
use SGITests\Integration\ExceptionEnvelopeTest;
use SGITests\Integration\CronometroPersistenceTest;
use SGITests\Integration\AuthAndRbacTest;
use SGITests\Integration\InterclasseLifecycleTest;
use SGITests\Integration\PontuacaoReconciliationTest;
use SGITests\Integration\ArrecadacaoConsistencyTest;
use SGITests\Integration\PodiumCreditTest;
use SGITests\Integration\IndividualSyncCreditTest;
use SGITests\Integration\HistoryRankingReconciliationTest;
use SGITests\Integration\ConcurrentInvariantsTest;
use SGITests\Integration\TurmasAndPdfImportTest;
use SGITests\Integration\ModalidadesAndEquipesTest;
use SGITests\Integration\InscricaoModalidadesTest;
use SGITests\Integration\JogosAndConflitosTest;
use SGITests\Integration\AgendamentoBlocoTest;
use SGITests\Integration\PlacarAndArtilhariaTest;
use SGITests\Integration\OcorrenciasAndRankingTest;
use SGITests\Integration\HistoricoTurmaAndClassificacaoTest;
use SGITests\Integration\FotoPerfilAndUsuariosTest;
use SGITests\Integration\AlunosPortalTest;
use SGITests\Integration\PublicBoundaryTest;
use SGITests\Integration\MataMataEdgeCasesTest;
use SGITests\E2E\FullOfflineTournamentTest;

$inicio = microtime(true);
Assertions::reset();

$testBaseUrl = getenv('SGI_TEST_BASE_URL');
$testDatabase = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';

if ($testBaseUrl === false || trim($testBaseUrl) === '') {
    fwrite(STDERR, "SGI_TEST_BASE_URL é obrigatório. Inicie um servidor de teste separado antes da suíte.\n");
    exit(2);
}

try {
    TestDatabase::resetFromSchema($testDatabase);
} catch (Throwable $e) {
    fwrite(STDERR, "Falha ao preparar ambiente de teste: " . $e->getMessage() . "\n");
    exit(2);
}

echo "\033[1;36m====================================================================\033[0m\n";
echo "\033[1;36m       SGI — SUITE COMPLETA DE TESTES AUTOMATIZADOS E AUDITORIA       \033[0m\n";
echo "\033[1;36m====================================================================\033[0m\n";

$aborted = false;
try {
    // 0. Fixtures sintéticas das regressões da auditoria
    AuditFixturesTest::run();
    MesarioResourceScopeTest::run();
    TemporaryResolutionScopeTest::run();
    ExceptionEnvelopeTest::run();

    // 1. Autenticação e RBAC
    AuthAndRbacTest::run();

    // 2. Ciclo de vida da edição
    $idEdicao = InterclasseLifecycleTest::run();

    // 3. Turmas e importação de PDF
    $idTurma = TurmasAndPdfImportTest::run($idEdicao);
    PontuacaoReconciliationTest::run($idEdicao, $idTurma);
    ArrecadacaoConsistencyTest::run($idEdicao, $idTurma);

    // 4. Modalidades e Equipes
    $dadosMod = ModalidadesAndEquipesTest::run($idEdicao);
    $mod = $dadosMod['modalidade'];
    $equipes = $dadosMod['equipes'];
    $idModalidade = (int) $mod['id_modalidade'];

    // 5. Inscrição em Modalidades e Limites
    InscricaoModalidadesTest::run($idEdicao, $equipes);

    // 6. Agendamento e Conflitos
    $dadosJogos = JogosAndConflitosTest::run($idEdicao, $idModalidade, $equipes);
    $idJogo1 = $dadosJogos['id_jogo_1'];
    $idJogo2 = $dadosJogos['id_jogo_2'];
    $equipesIds = $dadosJogos['equipes_ids'];
    AgendamentoBlocoTest::run($idEdicao, $idModalidade, $dadosJogos);

    // 6.1 Persistência e replay do cronômetro
    CronometroPersistenceTest::run($idModalidade, $idJogo1);

    // 7. Placar e Artilharia
    PlacarAndArtilhariaTest::run($idJogo1, $idModalidade, $equipesIds);
    \SGITests\Integration\AtomicMutationTest::run($idJogo1);

    // 8. Ocorrências e Ranking
    OcorrenciasAndRankingTest::run($idEdicao, $idTurma);

    // 9. Histórico de Turma e Pódios
    HistoricoTurmaAndClassificacaoTest::run($idEdicao, $idTurma, $idModalidade);

    // 10. Gestão de Fotos e Perfil
    FotoPerfilAndUsuariosTest::run($idTurma, $idEdicao);

    // 11. Portal do Aluno
    AlunosPortalTest::run();

    // 12. Casos Limites do Motor de Chaveamento
    MataMataEdgeCasesTest::run($idEdicao, $idTurma, $idJogo1, $idModalidade, $equipesIds);

    // 13. Torneio Completo e Sincronização Offline
    FullOfflineTournamentTest::run($idEdicao, $idModalidade, $idJogo2, $equipesIds);
    PodiumCreditTest::run($idEdicao, $idModalidade, $equipesIds);
    IndividualSyncCreditTest::run($idEdicao, $idModalidade);
    HistoryRankingReconciliationTest::run($idEdicao, $idTurma);
    ConcurrentInvariantsTest::run($idEdicao, $idTurma);

    // 14. Fronteira pública e proteção de arquivos internos
    PublicBoundaryTest::run();
    \SGITests\Integration\RefactorContractsTest::run();
    \SGITests\Integration\MigrationsTest::run();
    \SGITests\Integration\RecoveryRehearsalTest::run();
    \SGITests\Integration\ConsistencyGuardsTest::run();
    \SGITests\Integration\InitialAdminTest::run();

} catch (Throwable $e) {
    $aborted = true;
    echo "\n\033[31m[ERRO CRÍTICO NA EXECUÇÃO DOS TESTES]\033[0m " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

$tempoTotal = round(microtime(true) - $inicio, 2);
$stats = Assertions::getStats();

echo "\n\033[1;36m====================================================================\033[0m\n";
echo "\033[1;36m                       RESULTADO DA EXECUÇÃO                         \033[0m\n";
echo "\033[1;36m====================================================================\033[0m\n";
echo " Total de Asserções: \033[1m{$stats['total']}\033[0m\n";
echo " Aprovadas:          \033[32m\033[1m{$stats['passed']}\033[0m\n";
echo " Falhas:             " . ($stats['failed'] > 0 ? "\033[31m\033[1m{$stats['failed']}\033[0m" : "0") . "\n";
echo " Tempo de Execução:  \033[33m{$tempoTotal}s\033[0m\n";
echo "\033[1;36m====================================================================\033[0m\n";

if ($stats['failed'] > 0 || $aborted) {
    echo "\n\033[31mAsserções que falharam:\033[0m\n";
    foreach ($stats['failures'] as $f) {
        echo " - $f\n";
    }
    exit(1);
} else {
    echo "\n\033[32m\033[1m>>> TODOS OS TESTES FORAM APROVADOS COM 100% DE SUCESSO! <<<\033[0m\n";
    echo "\033[32mCenários desta suíte aprovados. Execute também os testes unitários e de navegador.\033[0m\n\n";
    exit(0);
}
