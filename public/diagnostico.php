<?php
$pageTitle = 'Diagnostico do Projeto Paula';
require_once __DIR__ . '/../includes/header.php';

$root = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$gitHeadFile = $root . DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR . 'HEAD';
$head = is_file($gitHeadFile) ? trim((string)file_get_contents($gitHeadFile)) : 'Arquivo .git/HEAD nao encontrado';
$commit = 'Nao identificado';

if (str_starts_with($head, 'ref:')) {
    $ref = trim(substr($head, 4));
    $refFile = $root . DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $ref);
    if (is_file($refFile)) {
        $commit = trim((string)file_get_contents($refFile));
    }
} elseif (preg_match('/^[a-f0-9]{40}$/i', $head)) {
    $commit = $head;
}

$checks = [
    'Carimbo esperado' => 'PAULA_BUILD_2026_05_15_LOCALIDADE_BUSCAS',
    'Commit atual aproximado' => $commit,
    'HEAD' => $head,
    'Pasta public' => __DIR__,
    'Pasta raiz detectada' => $root,
    'BASE_URL' => defined('BASE_URL') ? BASE_URL : 'BASE_URL nao definida',
    'NODE_SCRIPT_DIR' => defined('NODE_SCRIPT_DIR') ? NODE_SCRIPT_DIR : 'NODE_SCRIPT_DIR nao definida',
    'PHP_VERSION' => PHP_VERSION,
    'Data/hora servidor' => date('Y-m-d H:i:s'),
];
?>
<div class="alert alert-warning border-0">
    <strong>Diagnostico ativo:</strong> se esta pagina nao mostrar o carimbo <code>PAULA_BUILD_2026_05_15_LOCALIDADE_BUSCAS</code>, voce esta acessando outra pasta, outra rota ou cache miseravel.
</div>
<div class="card"><div class="card-body">
    <h2 class="h5 mb-3">Ambiente em execucao</h2>
    <div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>Item</th><th>Valor</th></tr></thead>
        <tbody>
        <?php foreach ($checks as $key => $value): ?>
            <tr><td><?= e($key) ?></td><td><code><?= e((string)$value) ?></code></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
