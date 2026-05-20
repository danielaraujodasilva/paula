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
$stmt = $pdo->prepare('SELECT * FROM curriculos WHERE user_id = ? AND ativo = 1 ORDER BY id DESC LIMIT 1');
$stmt->execute([$userId]);
$curriculoAtivo = $stmt->fetch();
$onboardingStep = $curriculoAtivo ? ($stats['vagas'] > 0 ? 3 : 2) : 1;
?>
<section class="start-panel mb-4" data-onboarding data-initial-step="<?= (int)$onboardingStep ?>">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
            <span class="eyebrow">Busca guiada</span>
            <h2 class="h3 mb-2">Comece por aqui</h2>
            <p class="text-muted mb-0">Envie um currículo e a Paula monta a busca inicial para trazer vagas compatíveis, sem precisar configurar tudo na mão.</p>
        </div>
        <button class="btn btn-accent btn-lg start-button" type="button" data-start-onboarding>
            <i class="bi bi-play-circle"></i> Comece aqui
        </button>
    </div>
    <div class="onboarding-flow mt-4 <?= $curriculoAtivo || isset($_GET['guia']) ? '' : 'd-none' ?>" data-onboarding-flow>
        <div class="step-dots mb-3">
            <button type="button" data-step-jump="1">1</button>
            <button type="button" data-step-jump="2">2</button>
            <button type="button" data-step-jump="3">3</button>
        </div>
        <div class="onboarding-slide" data-step="1">
            <div>
                <h3 class="h5">1. Envie o currículo</h3>
                <p class="text-muted">PDF, DOCX ou TXT. A Paula extrai o texto e cria um perfil profissional inicial.</p>
            </div>
            <form id="uploadResumeForm" class="guided-upload" action="<?= url('api/upload_curriculo.php') ?>" method="post" enctype="multipart/form-data">
                <input class="form-control" type="file" name="curriculo" accept=".pdf,.docx,.txt" required>
                <button class="btn btn-accent" type="submit"><i class="bi bi-upload"></i> Enviar currículo</button>
            </form>
            <?php if ($curriculoAtivo): ?><p class="text-success small mb-0 mt-3"><i class="bi bi-check-circle"></i> Currículo ativo: <?= e($curriculoAtivo['nome_arquivo']) ?></p><?php endif; ?>
        </div>
        <div class="onboarding-slide d-none" data-step="2">
            <div>
                <h3 class="h5">2. Busque vagas automaticamente</h3>
                <p class="text-muted">A busca usa o perfil extraído do currículo e consulta as fontes brasileiras, internacionais e as experimentais que estiverem ligadas nas buscas salvas.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-accent btn-lg" type="button" data-guided-run-search><i class="bi bi-lightning-charge"></i> Buscar vagas agora</button>
                <a class="btn btn-outline-light btn-lg" href="<?= url('perfil.php') ?>"><i class="bi bi-person-vcard"></i> Revisar perfil</a>
            </div>
        </div>
        <div class="onboarding-slide d-none" data-step="3">
            <div>
                <h3 class="h5">3. Veja e marque as melhores</h3>
                <p class="text-muted">Abra a lista, filtre por localidade/fonte/empresa e marque vagas interessantes para acompanhar.</p>
            </div>
            <a class="btn btn-accent btn-lg" href="<?= url('vagas.php') ?>"><i class="bi bi-briefcase"></i> Ver minhas vagas</a>
        </div>
    </div>
</section>

<?php render_monetization_block('dashboard'); ?>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Curriculos</div><div class="stat-value"><?= $stats['curriculos'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Vagas</div><div class="stat-value"><?= $stats['vagas'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Interessantes</div><div class="stat-value"><?= $stats['interessantes'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Media</div><div class="stat-value"><?= $stats['media'] ?>%</div></div></div></div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Ultimas vagas encontradas</h2>
            <a href="<?= url('vagas.php') ?>" class="btn btn-sm btn-outline-light">Ver todas</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Nota (%)</th><th>Titulo</th><th>Empresa</th><th>Fonte</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($ultimas as $vaga): ?>
                    <tr>
                        <td><span class="score-pill"><?= (int)$vaga['nota_compatibilidade'] ?>%</span></td>
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
