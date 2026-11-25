<?php

declare(strict_types=1);

$escape = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$number = static fn (mixed $value, int $decimals = 0): string => number_format((float) $value, $decimals, ',', '.');
$edicao = $report['edicao'];
$metricas = $report['metricas'];
$rankingGeral = $report['ranking_geral'];
$doacoes = $report['doacoes'];
$maxScore = max(1, ...array_map(static fn (array $entry): int => max(0, (int) $entry['pontuacao_liquida']), $rankingGeral ?: [['pontuacao_liquida' => 0]]));
$maxDonation = max(1.0, ...array_map(static fn (array $entry): float => max(0.0, (float) $entry['quantidade_kg']), $doacoes['por_turma'] ?: [['quantidade_kg' => 0.0]]));
$podiumsByClass = [];
foreach ($report['modalidades'] as $modality) {
    foreach ($modality['podios'] as $podium) {
        $podiumsByClass[(int) $podium['id_turma']][] = $modality['nome_modalidade'] . ' — ' . $podium['posicao'] . 'º lugar';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= $escape($edicao['nome_interclasse']) ?> — Relatório Final</title>
    <?php foreach ($stylesheetUris as $stylesheetUri): ?>
    <link rel="stylesheet" href="<?= $escape($stylesheetUri) ?>">
    <?php endforeach; ?>
</head>
<body class="sgi-relatorio-pdf">
<main class="sgi-relatorio-final sgi-relatorio-pdf-page">
    <header class="sgi-relatorio-cabecalho">
        <p class="sgi-relatorio-selo">Sistema de Gestão de Interclasses</p>
        <h1>Relatório Final do Interclasse</h1>
        <p class="sgi-relatorio-edicao"><?= $escape($edicao['nome_interclasse']) ?> · <?= $escape($edicao['ano']) ?></p>
        <p class="sgi-relatorio-meta">Gerado em <?= $escape(date('d/m/Y H:i')) ?></p>
    </header>

    <section class="sgi-relatorio-indicadores" aria-label="Indicadores gerais">
        <article class="sgi-relatorio-indicador"><span>Turmas participantes</span><strong><?= $number($metricas['turmas_total']) ?></strong><small><?= $number($metricas['categorias_total']) ?> categorias</small></article>
        <article class="sgi-relatorio-indicador"><span>Doações solidárias</span><strong><?= $number($metricas['doacoes_total_kg'], 2) ?> kg</strong><small><?= $escape($metricas['lider_doacoes']['nome_turma'] ?? 'Sem registro') ?> lidera</small></article>
        <article class="sgi-relatorio-indicador"><span>Modalidades</span><strong><?= $number($metricas['modalidades_total']) ?></strong><small>no interclasse selecionado</small></article>
        <article class="sgi-relatorio-indicador"><span>Líder absoluta</span><strong><?= $escape($metricas['lider_absoluta']['nome_turma'] ?? 'Sem classificação') ?></strong><small><?= $number($metricas['lider_absoluta']['pontuacao_liquida'] ?? 0) ?> pontos</small></article>
    </section>

    <section class="sgi-relatorio-secao">
        <h2>Pontuação Geral das Turmas</h2>
        <p class="sgi-relatorio-legenda">Classificação por pontos líquidos registrados, após os descontos de penalidades ativas.</p>
        <div class="sgi-relatorio-grafico">
            <?php foreach ($rankingGeral as $entry): ?>
            <?php $scoreBand = (int) (round(max(0, (int) $entry['pontuacao_liquida']) / $maxScore * 10) * 10); ?>
            <div class="sgi-relatorio-barra-linha">
                <span><?= $number($entry['posicao']) ?>º · <?= $escape($entry['nome_turma']) ?></span>
                <span class="sgi-relatorio-barra-trilho"><span class="sgi-relatorio-barra sgi-relatorio-barra--pontos sgi-relatorio-barra--width-<?= $number($scoreBand) ?>"></span></span>
                <strong><?= $number($entry['pontuacao_liquida']) ?> pts</strong>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="sgi-relatorio-secao">
        <h2>Doações Solidárias</h2>
        <p class="sgi-relatorio-legenda">Total registrado: <strong><?= $number($doacoes['total_kg'], 2) ?> kg</strong></p>
        <div class="sgi-relatorio-grafico">
            <?php foreach ($doacoes['por_turma'] as $entry): ?>
            <?php $donationBand = (int) (round(max(0.0, (float) $entry['quantidade_kg']) / $maxDonation * 10) * 10); ?>
            <div class="sgi-relatorio-barra-linha">
                <span><?= $escape($entry['nome_turma']) ?></span>
                <span class="sgi-relatorio-barra-trilho"><span class="sgi-relatorio-barra sgi-relatorio-barra--doacoes sgi-relatorio-barra--width-<?= $number($donationBand) ?>"></span></span>
                <strong><?= $number($entry['quantidade_kg'], 2) ?> kg</strong>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php foreach ($report['categorias'] as $category): ?>
    <section class="sgi-relatorio-secao sgi-relatorio-categoria">
        <h2><?= $escape($category['nome_categoria']) ?> — resumo e classificação final</h2>
        <p class="sgi-relatorio-legenda"><?= $number($category['total_turmas']) ?> turmas · <?= $number($category['pontuacao_total']) ?> pontos líquidos somados</p>
        <div class="sgi-relatorio-tabela-wrap">
            <table class="sgi-relatorio-tabela">
                <thead><tr><th>Pos.</th><th>Turma</th><th>Doações</th><th>Penalidades identificadas</th><th>Descontos</th><th>Total líquido</th><th>Destaques esportivos</th></tr></thead>
                <tbody>
                <?php foreach ($category['ranking'] as $entry): ?>
                    <tr>
                        <td><?= $number($entry['posicao']) ?>º</td>
                        <td><strong><?= $escape($entry['nome_turma']) ?></strong><small><?= $escape($entry['nome_fantasia_turma']) ?></small></td>
                        <td><?= $number($entry['doacoes_kg'], 2) ?> kg</td>
                        <td>
                            <?php if ($entry['penalidades'] === []): ?>Sem penalidades registradas<?php else: ?>
                                <?php foreach ($entry['penalidades'] as $penalty): ?>
                                    <span class="sgi-relatorio-detalhe-penalidade"><?= $escape($penalty['titulo']) ?><?= $penalty['aluno'] !== null ? ' · ' . $escape($penalty['aluno']) : '' ?><?= $penalty['descricao'] !== '' ? ' — ' . $escape($penalty['descricao']) : '' ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td><?= $number($entry['penalidades_pontos']) ?> pts</td>
                        <td><strong><?= $number($entry['pontuacao_liquida']) ?> pts</strong></td>
                        <td><?= $escape(implode(', ', $podiumsByClass[(int) $entry['id_turma']] ?? [])) ?: '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endforeach; ?>

    <section class="sgi-relatorio-secao sgi-relatorio-observacoes">
        <h2>Observações da organização</h2>
        <p><?= $escape($report['observacao']) ?></p>
        <p>A pontuação de doações considera <?= $number($edicao['valor_item_arrecadacao']) ?> ponto(s) por kg conforme configuração da edição.</p>
    </section>

    <section class="sgi-relatorio-secao">
        <h2>Central de Modalidades e Pódios</h2>
        <?php foreach ($report['modalidades'] as $modality): ?>
        <article class="sgi-relatorio-modalidade">
            <h3><?= $escape($modality['nome_modalidade']) ?> <small><?= $escape($modality['nome_categoria']) ?> · <?= $escape($modality['nome_tipo_modalidade']) ?> · <?= $escape($modality['genero_modalidade']) ?></small></h3>
            <?php if ($modality['podios'] === []): ?>
                <p>Sem pódios registrados.</p>
            <?php else: ?>
                <table class="sgi-relatorio-tabela sgi-relatorio-tabela--compacta">
                    <thead><tr><th>Colocação</th><th>Turma</th><th>Destaque</th><th>Pontos</th></tr></thead>
                    <tbody><?php foreach ($modality['podios'] as $podium): ?><tr><td><?= $number($podium['posicao']) ?>º lugar</td><td><?= $escape($podium['nome_turma']) ?></td><td><?= $escape($podium['destaque']) ?></td><td><?= $number($podium['pontos']) ?> pts</td></tr><?php endforeach; ?></tbody>
                </table>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </section>

    <?php if (array_filter($report['modalidades'], static fn (array $modality): bool => $modality['contabiliza_artilharia']) !== []): ?>
    <section class="sgi-relatorio-secao">
        <h2>Ranking de Artilheiros</h2>
        <?php foreach ($report['modalidades'] as $modality): ?>
            <?php if (!$modality['contabiliza_artilharia']) { continue; } ?>
            <h3><?= $escape($modality['nome_categoria']) ?> · <?= $escape($modality['nome_modalidade']) ?></h3>
            <?php if ($modality['artilheiros'] === []): ?><p>Sem registros de artilharia nesta modalidade.</p><?php else: ?>
            <table class="sgi-relatorio-tabela sgi-relatorio-tabela--compacta"><thead><tr><th>Pos.</th><th>Competidor</th><th>Turma</th><th>Gols</th></tr></thead><tbody><?php foreach ($modality['artilheiros'] as $scorer): ?><tr><td><?= $number($scorer['posicao']) ?>º</td><td><?= $escape($scorer['nome_usuario']) ?></td><td><?= $escape($scorer['nome_turma']) ?></td><td><?= $number($scorer['gols']) ?></td></tr><?php endforeach; ?></tbody></table>
            <?php endif; ?>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <section class="sgi-relatorio-secao">
        <h2>Relatório da Gincana Solidária de Doações</h2>
        <p class="sgi-relatorio-total-doacoes">Total arrecadado: <strong><?= $number($doacoes['total_kg'], 2) ?> kg</strong></p>
        <table class="sgi-relatorio-tabela"><thead><tr><th>Pos.</th><th>Turma</th><th>Categoria</th><th>Quantidade</th><th>Pontos gerados</th></tr></thead>
            <tbody><?php foreach ($doacoes['por_turma'] as $index => $entry): ?><tr><td><?= $number($index + 1) ?>º</td><td><?= $escape($entry['nome_turma']) ?></td><td><?= $escape($entry['nome_categoria']) ?></td><td><?= $number($entry['quantidade_kg'], 2) ?> kg</td><td><?= $number($entry['pontuacao']) ?> pts</td></tr><?php endforeach; ?></tbody>
        </table>
    </section>

    <footer class="sgi-relatorio-rodape">Relatório consolidado do interclasse selecionado · <?= $escape($edicao['nome_interclasse']) ?> · <?= $escape($edicao['ano']) ?></footer>
</main>
</body>
</html>
