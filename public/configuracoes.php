<?php
$pageTitle = 'Configuracoes de busca';
require_once __DIR__ . '/../includes/header.php';

$fontesDisponiveis = [
    'Remotive' => 'Vagas remotas internacionais, sem chave.',
    'Arbeitnow' => 'Vagas globais/tech, sem chave.',
    'RemoteOK' => 'Vagas remotas, sem chave.',
    'Adzuna' => 'Opcional, melhor para Brasil quando configurar chave gratis.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fontes = $_POST['fontes'] ?? [];
    $fontes = array_values(array_intersect(array_keys($fontesDisponiveis), $fontes));
    if (!$fontes) {
        $fontes = ['Remotive', 'Arbeitnow', 'RemoteOK'];
    }

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
        json_encode($fontes, JSON_UNESCAPED_UNICODE),
    ]);
    header('Location: configuracoes.php?salvo=1');
    exit;
}
$buscas = $pdo->query('SELECT * FROM buscas ORDER BY id DESC')->fetchAll();
?>
<?php if (isset($_GET['salvo'])): ?><div class="alert alert-success">Busca salva.</div><?php endif; ?>
<div class="alert alert-info border-0">
    <strong>Dica:</strong> para Brasil/Sao Paulo, use termos em portugues e em ingles. Exemplo: <code>designer grafico</code>, <code>web designer</code>, <code>assistente administrativo</code>. Site de vaga adora esconder coisa obvia, porque aparentemente clareza ofende RH.
</div>
<div class="row g-4">
    <div class="col-lg-5">
        <form class="card" method="post"><div class="card-body">
            <h2 class="h5 mb-3">Nova busca</h2>
            <label class="form-label">Nome</label><input name="nome" class="form-control mb-3" required>
            <label class="form-label">Termos de busca, um por linha</label><textarea name="termos" rows="6" class="form-control mb-3" required placeholder="web designer&#10;designer grafico&#10;atendimento ao cliente"></textarea>
            <label class="form-label">Localizacao</label><input name="localizacao" class="form-control mb-3" value="Brasil" placeholder="Brasil, Sao Paulo, Guarulhos, Remote...">
            <label class="form-label">Tipo</label><select name="remoto" class="form-select mb-3"><option value="1">Aceita remoto ou localidade informada</option><option value="0">Somente localidade informada</option></select>
            <label class="form-label">Salario minimo</label><input name="salario_minimo" type="number" step="0.01" class="form-control mb-3">
            <label class="form-label">Palavras obrigatorias</label><textarea name="palavras_obrigatorias" rows="3" class="form-control mb-3" placeholder="Uma por linha. Ex: Figma, Excel, WordPress"></textarea>
            <label class="form-label">Palavras proibidas</label><textarea name="palavras_proibidas" rows="3" class="form-control mb-3" placeholder="porta a porta&#10;comissao apenas&#10;vendedor externo"></textarea>
            <div class="mb-3">
                <label class="form-label d-block">Fontes gratis</label>
                <?php foreach ($fontesDisponiveis as $fonte => $descricao): ?>
                    <label class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="fontes[]" value="<?= e($fonte) ?>" <?= $fonte !== 'Adzuna' ? 'checked' : '' ?>>
                        <span class="form-check-label"><strong><?= e($fonte) ?></strong><br><small class="text-muted"><?= e($descricao) ?></small></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-accent" type="submit"><i class="bi bi-save"></i> Salvar busca</button>
        </div></form>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <h2 class="h5 mb-3">Buscas salvas</h2>
            <div class="table-responsive"><table class="table"><thead><tr><th>Nome</th><th>Local</th><th>Tipo</th><th>Termos</th><th>Fontes</th><th>Criada</th></tr></thead><tbody>
            <?php foreach ($buscas as $busca): ?><tr><td><?= e($busca['nome']) ?></td><td><?= e($busca['localizacao']) ?></td><td><?= (int)$busca['remoto'] ? 'Remoto/local' : 'Somente local' ?></td><td><?= nl2br(e($busca['termos'])) ?></td><td><?= e($busca['fontes']) ?></td><td><?= e($busca['created_at']) ?></td></tr><?php endforeach; ?>
            <?php if (!$buscas): ?><tr><td colspan="6" class="text-muted">Nenhuma busca configurada.</td></tr><?php endif; ?>
            </tbody></table></div>
        </div></div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
