<?php
$pageTitle = 'Configuracoes de busca';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fontes = $_POST['fontes'] ?? [];
    $stmt = $pdo->prepare('INSERT INTO buscas (curriculo_id, nome, termos, localizacao, remoto, salario_minimo, palavras_obrigatorias, palavras_proibidas, fontes, ativa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
    $active = active_curriculo($pdo);
    $stmt->execute([
        $active['id'] ?? null,
        $_POST['nome'] ?: 'Busca sem nome',
        $_POST['termos'] ?? '',
        $_POST['localizacao'] ?? '',
        (int)($_POST['remoto'] ?? 1),
        $_POST['salario_minimo'] !== '' ? $_POST['salario_minimo'] : null,
        $_POST['palavras_obrigatorias'] ?? '',
        $_POST['palavras_proibidas'] ?? '',
        json_encode(array_values($fontes), JSON_UNESCAPED_UNICODE),
    ]);
    header('Location: configuracoes.php?salvo=1');
    exit;
}
$buscas = $pdo->query('SELECT * FROM buscas ORDER BY id DESC')->fetchAll();
?>
<?php if (isset($_GET['salvo'])): ?><div class="alert alert-success">Busca salva.</div><?php endif; ?>
<div class="row g-4">
    <div class="col-lg-5">
        <form class="card" method="post"><div class="card-body">
            <h2 class="h5 mb-3">Nova busca</h2>
            <label class="form-label">Nome</label><input name="nome" class="form-control mb-3" required>
            <label class="form-label">Termos de busca, um por linha</label><textarea name="termos" rows="5" class="form-control mb-3" required></textarea>
            <label class="form-label">Localizacao</label><input name="localizacao" class="form-control mb-3" value="Brasil">
            <label class="form-label">Remoto</label><select name="remoto" class="form-select mb-3"><option value="1">Sim</option><option value="0">Nao</option></select>
            <label class="form-label">Salario minimo</label><input name="salario_minimo" type="number" step="0.01" class="form-control mb-3">
            <label class="form-label">Palavras obrigatorias</label><textarea name="palavras_obrigatorias" rows="3" class="form-control mb-3"></textarea>
            <label class="form-label">Palavras proibidas</label><textarea name="palavras_proibidas" rows="3" class="form-control mb-3"></textarea>
            <div class="mb-3">
                <label class="form-label d-block">Fontes</label>
                <?php foreach (['Remotive', 'Arbeitnow', 'Adzuna'] as $fonte): ?>
                    <label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="fontes[]" value="<?= $fonte ?>" <?= $fonte !== 'Adzuna' ? 'checked' : '' ?>> <?= $fonte ?></label>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-accent" type="submit"><i class="bi bi-save"></i> Salvar busca</button>
        </div></form>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <h2 class="h5 mb-3">Buscas salvas</h2>
            <div class="table-responsive"><table class="table"><thead><tr><th>Nome</th><th>Termos</th><th>Fontes</th><th>Criada</th></tr></thead><tbody>
            <?php foreach ($buscas as $busca): ?><tr><td><?= e($busca['nome']) ?></td><td><?= nl2br(e($busca['termos'])) ?></td><td><?= e($busca['fontes']) ?></td><td><?= e($busca['created_at']) ?></td></tr><?php endforeach; ?>
            <?php if (!$buscas): ?><tr><td colspan="4" class="text-muted">Nenhuma busca configurada.</td></tr><?php endif; ?>
            </tbody></table></div>
        </div></div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
