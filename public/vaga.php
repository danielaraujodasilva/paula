<?php
$pageTitle = 'Detalhe da vaga';
require_once __DIR__ . '/../includes/header.php';

$userId = current_user_id();
$stmt = $pdo->prepare('SELECT * FROM vagas WHERE id = ? AND user_id = ?');
$stmt->execute([(int)($_GET['id'] ?? 0), $userId]);
$vaga = $stmt->fetch();
$curriculo = active_curriculo($pdo, $userId);
$profile = $curriculo && $curriculo['perfil_json'] ? json_decode($curriculo['perfil_json'], true) : [];
$skills = array_slice($profile['habilidades'] ?? [], 0, 4);
$message = 'Ola, tudo bem? Me interessei pela vaga de ' . ($vaga['titulo'] ?? '') . '. Tenho experiencia com ' . implode(', ', $skills) . ' e acredito que meu perfil combina com a oportunidade. Segue meu curriculo para avaliacao. Obrigado.';
?>
<?php if (!$vaga): ?>
    <div class="alert alert-warning">Vaga nao encontrada.</div>
<?php else: ?>
<?php render_monetization_block('vaga'); ?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between gap-3 align-items-start mb-3">
                <div><h2 class="h4"><?= e($vaga['titulo']) ?></h2><div class="text-muted"><?= e($vaga['empresa']) ?> · <?= e($vaga['localizacao']) ?></div></div>
                <span class="score-pill"><?= (int)$vaga['nota_compatibilidade'] ?></span>
            </div>
            <p><span class="badge <?= badge_class($vaga['status']) ?>"><?= e($vaga['status']) ?></span> <span class="badge text-bg-dark border"><?= e($vaga['fonte']) ?></span> <span class="text-muted"><?= e($vaga['salario']) ?></span></p>
            <p><a class="btn btn-accent" target="_blank" rel="noopener" href="<?= e($vaga['url']) ?>"><i class="bi bi-box-arrow-up-right"></i> Abrir vaga original</a></p>
            <h3 class="h6 mt-4">Descricao completa</h3>
            <div class="description-box"><?= nl2br(e(strip_tags((string)$vaga['descricao']))) ?></div>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <h3 class="h6">Compatibilidade</h3>
            <p class="text-muted"><?= nl2br(e($vaga['resumo_compatibilidade'])) ?></p>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach (status_options() as $s): ?><button class="btn btn-sm btn-outline-light" data-status-id="<?= (int)$vaga['id'] ?>" data-status="<?= $s ?>"><?= $s ?></button><?php endforeach; ?>
            </div>
        </div></div>
        <div class="card"><div class="card-body">
            <h3 class="h6">Mensagem de candidatura sugerida</h3>
            <textarea class="form-control" rows="8"><?= e($message) ?></textarea>
        </div></div>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
