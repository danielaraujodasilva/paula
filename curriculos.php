<?php
$pageTitle = 'Curriculos';
require_once __DIR__ . '/includes/header.php';

if (isset($_GET['ativar'])) {
    $id = (int)$_GET['ativar'];
    $pdo->beginTransaction();
    $pdo->exec('UPDATE curriculos SET ativo = 0');
    $stmt = $pdo->prepare('UPDATE curriculos SET ativo = 1, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$id]);
    $pdo->commit();
    header('Location: curriculos.php');
    exit;
}

$curriculos = $pdo->query('SELECT * FROM curriculos ORDER BY id DESC')->fetchAll();
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card"><div class="card-body">
            <h2 class="h5 mb-3">Enviar curriculo</h2>
            <form id="uploadResumeForm" action="api/upload_curriculo.php" method="post" enctype="multipart/form-data">
                <input class="form-control mb-3" type="file" name="curriculo" accept=".pdf,.docx,.txt" required>
                <button class="btn btn-accent w-100" type="submit"><i class="bi bi-upload"></i> Enviar e extrair</button>
            </form>
            <p class="text-muted small mt-3 mb-0">Formatos aceitos: PDF, DOCX e TXT.</p>
        </div></div>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <h2 class="h5 mb-3">Arquivos enviados</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Arquivo</th><th>Tipo</th><th>Ativo</th><th>Perfil</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($curriculos as $c): ?>
                        <tr>
                            <td><?= e($c['nome_arquivo']) ?></td>
                            <td><?= e($c['tipo_arquivo']) ?></td>
                            <td><?= $c['ativo'] ? '<span class="badge text-bg-success">Sim</span>' : '<span class="badge text-bg-secondary">Nao</span>' ?></td>
                            <td><a href="<?= url('perfil.php?id=' . (int)$c['id']) ?>">Abrir</a></td>
                            <td><?php if (!$c['ativo']): ?><a class="btn btn-sm btn-outline-light" href="?ativar=<?= (int)$c['id'] ?>">Ativar</a><?php endif; ?></td>
                        </tr>
                        <tr><td colspan="5"><details><summary class="text-muted">Texto extraido</summary><pre class="description-box mt-2 mb-0"><?= e(mb_substr((string)$c['texto_extraido'], 0, 5000)) ?></pre></details></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$curriculos): ?><tr><td colspan="5" class="text-muted">Nenhum curriculo enviado.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
