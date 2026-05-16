<?php
$pageTitle = 'Perfil profissional';
require_once __DIR__ . '/../includes/header.php';

$userId = current_user_id();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM curriculos WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    $curriculo = $stmt->fetch();
} else {
    $curriculo = active_curriculo($pdo, $userId);
}
$profile = $curriculo && $curriculo['perfil_json'] ? json_decode($curriculo['perfil_json'], true) : [];
function lines(array $data, string $key): string { return implode("\n", array_map('strval', $data[$key] ?? [])); }
?>
<?php if (!$curriculo): ?>
    <div class="alert alert-warning">Envie um curriculo primeiro.</div>
<?php else: ?>
<form class="card" method="post" action="<?= url('api/salvar_perfil.php') ?>">
    <div class="card-body">
        <input type="hidden" name="curriculo_id" value="<?= (int)$curriculo['id'] ?>">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Nome</label><input name="nome" class="form-control" value="<?= e($profile['nome'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Senioridade</label><input name="senioridade" class="form-control" value="<?= e($profile['senioridade'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Localizacao</label><input name="localizacao" class="form-control" value="<?= e($profile['localizacao'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Aceita remoto</label><select name="aceita_remoto" class="form-select"><option value="1" <?= !empty($profile['aceita_remoto']) ? 'selected' : '' ?>>Sim</option><option value="0" <?= empty($profile['aceita_remoto']) ? 'selected' : '' ?>>Nao</option></select></div>
            <div class="col-md-6"><label class="form-label">Cargo alvo</label><textarea name="cargo_alvo" class="form-control" rows="4"><?= e(lines($profile, 'cargo_alvo')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Habilidades tecnicas</label><textarea name="habilidades" class="form-control" rows="4"><?= e(lines($profile, 'habilidades')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Ferramentas</label><textarea name="ferramentas" class="form-control" rows="4"><?= e(lines($profile, 'ferramentas')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Experiencias principais</label><textarea name="experiencias" class="form-control" rows="4"><?= e(lines($profile, 'experiencias')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Palavras-chave</label><textarea name="palavras_chave" class="form-control" rows="4"><?= e(lines($profile, 'palavras_chave')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Palavras proibidas</label><textarea name="palavras_proibidas" class="form-control" rows="4"><?= e(lines($profile, 'palavras_proibidas')) ?></textarea></div>
        </div>
        <button class="btn btn-accent mt-4" type="submit"><i class="bi bi-save"></i> Salvar perfil</button>
    </div>
</form>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
