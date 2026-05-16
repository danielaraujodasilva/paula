<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';

$userId = current_user_id();
$stats = [
    'curriculos' => 0,
    'vagas' => 0,
    'interessantes' => 0,
    'media' => 0,
];
$stmt = $pdo->prepare('SELECT COUNT(*) FROM curriculos WHERE user_id = ?');
$stmt->execute([$userId]);
$stats['curriculos'] = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM vagas WHERE user_id = ?');
$stmt->execute([$userId]);
$stats['vagas'] = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM vagas WHERE user_id = ? AND status = 'interessante'");
$stmt->execute([$userId]);
$stats['interessantes'] = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COALESCE(AVG(nota_compatibilidade), 0) FROM vagas WHERE user_id = ?');
$stmt->execute([$userId]);
$stats['media'] = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT * FROM vagas WHERE user_id = ? ORDER BY id DESC LIMIT 8');
$stmt->execute([$userId]);
$ultimas = $stmt->fetchAll();
?>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Curriculos</div><div class="stat-value"><?= $stats['curriculos'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Vagas</div><div class="stat-value"><?= $stats['vagas'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Interessantes</div><div class="stat-value"><?= $stats['interessantes'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Media</div><div class="stat-value"><?= $stats['media'] ?>%</div></div></div></div>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    <a class="btn btn-accent" href="<?= url('curriculos.php') ?>"><i class="bi bi-upload"></i> Enviar curriculo</a>
    <a class="btn btn-outline-light" href="<?= url('configuracoes.php') ?>"><i class="bi bi-sliders"></i> Configurar busca</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Ultimas vagas encontradas</h2>
            <a href="<?= url('vagas.php') ?>" class="btn btn-sm btn-outline-light">Ver todas</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Nota</th><th>Titulo</th><th>Empresa</th><th>Fonte</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($ultimas as $vaga): ?>
                    <tr>
                        <td><span class="score-pill"><?= (int)$vaga['nota_compatibilidade'] ?></span></td>
                        <td><?= e($vaga['titulo']) ?></td>
                        <td><?= e($vaga['empresa']) ?></td>
                        <td><?= e($vaga['fonte']) ?></td>
                        <td><span class="badge <?= badge_class($vaga['status']) ?>"><?= e($vaga['status']) ?></span></td>
                        <td><a class="btn btn-sm btn-outline-light" href="<?= url('vaga.php?id=' . (int)$vaga['id']) ?>">Detalhes</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$ultimas): ?><tr><td colspan="6" class="text-muted">Nenhuma vaga ainda.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
