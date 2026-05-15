<?php
$pageTitle = 'Vagas';
require_once __DIR__ . '/includes/header.php';

function current_query(array $overrides = []): string
{
    $query = array_merge($_GET, $overrides);
    foreach ($query as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
        }
    }
    return http_build_query($query);
}

function sort_link(string $key, string $label): string
{
    $current = $_GET['ordem'] ?? 'nota';
    $dir = $_GET['dir'] ?? 'desc';
    $nextDir = ($current === $key && $dir === 'asc') ? 'desc' : 'asc';
    $icon = $current === $key ? ($dir === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up';
    return '<a class="table-sort" href="?' . e(current_query(['ordem' => $key, 'dir' => $nextDir])) . '">' . e($label) . ' <i class="bi ' . $icon . '"></i></a>';
}

$fontes = $pdo->query("SELECT DISTINCT fonte FROM vagas WHERE fonte IS NOT NULL AND fonte <> '' ORDER BY fonte")->fetchAll(PDO::FETCH_COLUMN);
$empresas = $pdo->query("SELECT DISTINCT empresa FROM vagas WHERE empresa IS NOT NULL AND empresa <> '' ORDER BY empresa LIMIT 200")->fetchAll(PDO::FETCH_COLUMN);

$params = [];
$where = [];

if (!empty($_GET['q'])) {
    $where[] = '(titulo LIKE ? OR empresa LIKE ? OR localizacao LIKE ? OR descricao LIKE ?)';
    $term = '%' . $_GET['q'] . '%';
    array_push($params, $term, $term, $term, $term);
}
if (!empty($_GET['fonte'])) {
    $where[] = 'fonte = ?';
    $params[] = $_GET['fonte'];
}
if (!empty($_GET['status'])) {
    $where[] = 'status = ?';
    $params[] = $_GET['status'];
}
if (!empty($_GET['empresa'])) {
    $where[] = 'empresa = ?';
    $params[] = $_GET['empresa'];
}
if (isset($_GET['nota_min']) && $_GET['nota_min'] !== '') {
    $where[] = 'nota_compatibilidade >= ?';
    $params[] = (int)$_GET['nota_min'];
}
if (isset($_GET['nota_max']) && $_GET['nota_max'] !== '') {
    $where[] = 'nota_compatibilidade <= ?';
    $params[] = (int)$_GET['nota_max'];
}
if (!empty($_GET['localizacao'])) {
    $where[] = 'localizacao LIKE ?';
    $params[] = '%' . $_GET['localizacao'] . '%';
}
if (!empty($_GET['remoto'])) {
    $where[] = "(localizacao LIKE '%remote%' OR localizacao LIKE '%remoto%' OR descricao LIKE '%remote%' OR descricao LIKE '%remoto%')";
}

$orderColumns = [
    'nota' => 'nota_compatibilidade',
    'recentes' => 'id',
    'titulo' => 'titulo',
    'empresa' => 'empresa',
    'fonte' => 'fonte',
    'status' => 'status',
    'data' => 'data_publicacao',
];
$orderKey = $_GET['ordem'] ?? 'nota';
$direction = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
$orderColumn = $orderColumns[$orderKey] ?? 'nota_compatibilidade';
$secondaryOrder = $orderColumn === 'id' ? '' : ', id DESC';

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM vagas' . $whereSql);
$countStmt->execute($params);
$totalFiltrado = (int)$countStmt->fetchColumn();
$totalGeral = (int)$pdo->query('SELECT COUNT(*) FROM vagas')->fetchColumn();

$sql = "SELECT * FROM vagas$whereSql ORDER BY $orderColumn $direction$secondaryOrder LIMIT 300";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vagas = $stmt->fetchAll();

$activeFilters = [];
foreach (['q' => 'Texto', 'fonte' => 'Fonte', 'status' => 'Status', 'empresa' => 'Empresa', 'localizacao' => 'Local', 'nota_min' => 'Nota min.', 'nota_max' => 'Nota max.'] as $key => $label) {
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        $activeFilters[] = $label . ': ' . $_GET[$key];
    }
}
if (!empty($_GET['remoto'])) {
    $activeFilters[] = 'Remoto';
}
?>

<form class="card filters-card mb-4" method="get">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Filtrar vagas</h2>
                <div class="text-muted small"><?= $totalFiltrado ?> de <?= $totalGeral ?> vagas exibidas</div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-light" href="<?= url('vagas.php') ?>"><i class="bi bi-x-circle"></i> Limpar</a>
                <button class="btn btn-accent" type="submit"><i class="bi bi-filter"></i> Aplicar</button>
            </div>
        </div>

        <div class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label">Texto livre</label>
                <input name="q" class="form-control" placeholder="Titulo, empresa, local ou tecnologia" value="<?= e($_GET['q'] ?? '') ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Fonte</label>
                <select name="fonte" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($fontes as $fonte): ?><option value="<?= e($fonte) ?>" <?= ($_GET['fonte'] ?? '') === $fonte ? 'selected' : '' ?>><?= e($fonte) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach (status_options() as $s): ?><option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Empresa</label>
                <select name="empresa" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($empresas as $empresa): ?><option value="<?= e($empresa) ?>" <?= ($_GET['empresa'] ?? '') === $empresa ? 'selected' : '' ?>><?= e($empresa) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Localizacao</label>
                <input name="localizacao" class="form-control" value="<?= e($_GET['localizacao'] ?? '') ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Nota minima</label>
                <input name="nota_min" type="number" min="0" max="100" class="form-control" value="<?= e($_GET['nota_min'] ?? '') ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Nota maxima</label>
                <input name="nota_max" type="number" min="0" max="100" class="form-control" value="<?= e($_GET['nota_max'] ?? '') ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Ordenar por</label>
                <select name="ordem" class="form-select">
                    <?php foreach (['nota' => 'Nota', 'recentes' => 'Mais recentes', 'titulo' => 'Titulo', 'empresa' => 'Empresa', 'fonte' => 'Fonte', 'status' => 'Status', 'data' => 'Data'] as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $orderKey === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Direcao</label>
                <select name="dir" class="form-select">
                    <option value="desc" <?= strtolower((string)($_GET['dir'] ?? 'desc')) === 'desc' ? 'selected' : '' ?>>Decrescente</option>
                    <option value="asc" <?= strtolower((string)($_GET['dir'] ?? '')) === 'asc' ? 'selected' : '' ?>>Crescente</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <div class="form-check switch-row">
                    <input name="remoto" value="1" class="form-check-input" type="checkbox" id="remotoFilter" <?= !empty($_GET['remoto']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="remotoFilter">Somente remoto</label>
                </div>
            </div>
        </div>

        <?php if ($activeFilters): ?>
            <div class="filter-chips mt-3">
                <?php foreach ($activeFilters as $filter): ?><span class="filter-chip"><?= e($filter) ?></span><?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</form>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle jobs-table">
                <thead>
                    <tr>
                        <th><?= sort_link('nota', 'Nota') ?></th>
                        <th><?= sort_link('titulo', 'Titulo') ?></th>
                        <th><?= sort_link('empresa', 'Empresa') ?></th>
                        <th>Local</th>
                        <th><?= sort_link('fonte', 'Fonte') ?></th>
                        <th>Salario</th>
                        <th><?= sort_link('status', 'Status') ?></th>
                        <th><?= sort_link('data', 'Data') ?></th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($vagas as $vaga): ?>
                    <tr>
                        <td><span class="score-pill"><?= (int)$vaga['nota_compatibilidade'] ?></span></td>
                        <td>
                            <a class="job-title" href="<?= url('vaga.php?id=' . (int)$vaga['id']) ?>"><?= e($vaga['titulo']) ?></a>
                            <div class="text-muted small d-lg-none"><?= e($vaga['empresa']) ?> - <?= e($vaga['fonte']) ?></div>
                        </td>
                        <td><?= e($vaga['empresa']) ?></td>
                        <td><?= e($vaga['localizacao']) ?></td>
                        <td><span class="badge text-bg-dark border"><?= e($vaga['fonte']) ?></span></td>
                        <td><?= e($vaga['salario'] ?: '-') ?></td>
                        <td><span class="badge <?= badge_class($vaga['status']) ?>"><?= e($vaga['status']) ?></span></td>
                        <td><?= e($vaga['data_publicacao'] ?: '-') ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-light small-action" title="Ver detalhes" href="<?= url('vaga.php?id=' . (int)$vaga['id']) ?>"><i class="bi bi-eye"></i></a>
                            <button class="btn btn-sm btn-outline-info small-action" title="Interessante" data-status-id="<?= (int)$vaga['id'] ?>" data-status="interessante"><i class="bi bi-star"></i></button>
                            <button class="btn btn-sm btn-outline-primary small-action" title="Candidatado" data-status-id="<?= (int)$vaga['id'] ?>" data-status="candidatado"><i class="bi bi-send"></i></button>
                            <button class="btn btn-sm btn-outline-secondary small-action" title="Arquivar" data-status-id="<?= (int)$vaga['id'] ?>" data-status="arquivada"><i class="bi bi-archive"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$vagas): ?><tr><td colspan="9" class="text-muted py-4">Nenhuma vaga encontrada com esses filtros.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalFiltrado > 300): ?>
            <div class="text-muted small mt-3">Mostrando as primeiras 300 vagas. Refine os filtros para ver um conjunto menor.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
