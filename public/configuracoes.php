<?php
$pageTitle = 'Configuracoes de busca';
require_once __DIR__ . '/../includes/header.php';

$userId = current_user_id();
$fontesDisponiveis = [
    'Remotive' => 'Vagas remotas internacionais, sem chave.',
    'Arbeitnow' => 'Vagas globais/tech, sem chave.',
    'RemoteOK' => 'Vagas remotas, sem chave.',
    'Adzuna' => 'Opcional com chave gratis, melhor para Brasil quando configurada.',
    'Gupy' => 'Fonte brasileira ampla; usa token publico da Gupy quando configurado.',
    'Codante' => 'API brasileira gratuita para vagas de tecnologia.',
    'Himalayas' => 'API publica gratuita de vagas remotas.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'criar';
    $id = (int)($_POST['id'] ?? 0);

    if ($acao === 'excluir' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM buscas WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        header('Location: configuracoes.php?excluido=1');
        exit;
    }

    if ($acao === 'toggle' && $id > 0) {
        $stmt = $pdo->prepare('UPDATE buscas SET ativa = IF(ativa = 1, 0, 1) WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        header('Location: configuracoes.php?status=1');
        exit;
    }

    $fontes = array_keys($fontesDisponiveis);
    $active = active_curriculo($pdo, $userId);
    $payload = [
        $_POST['nome'] ?: 'Busca sem nome',
        $_POST['termos'] ?? '',
        $_POST['localizacao'] ?? '',
        (int)($_POST['remoto'] ?? 1),
        $_POST['salario_minimo'] !== '' ? $_POST['salario_minimo'] : null,
        $_POST['palavras_obrigatorias'] ?? '',
        $_POST['palavras_proibidas'] ?? '',
        json_encode($fontes, JSON_UNESCAPED_UNICODE),
    ];

    if ($acao === 'editar' && $id > 0) {
        $stmt = $pdo->prepare('UPDATE buscas SET nome = ?, termos = ?, localizacao = ?, remoto = ?, salario_minimo = ?, palavras_obrigatorias = ?, palavras_proibidas = ?, fontes = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([...$payload, $id, $userId]);
        header('Location: configuracoes.php?editado=1');
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO buscas (user_id, curriculo_id, nome, termos, localizacao, remoto, salario_minimo, palavras_obrigatorias, palavras_proibidas, fontes, ativa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
    $stmt->execute([
        $userId,
        $active['id'] ?? null,
        ...$payload,
    ]);
    header('Location: configuracoes.php?salvo=1');
    exit;
}

$editId = (int)($_GET['editar'] ?? 0);
$editBusca = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM buscas WHERE id = ? AND user_id = ?');
    $stmt->execute([$editId, $userId]);
    $editBusca = $stmt->fetch() ?: null;
}

$stmt = $pdo->prepare('SELECT * FROM buscas WHERE user_id = ? ORDER BY ativa DESC, id DESC');
$stmt->execute([$userId]);
$buscas = $stmt->fetchAll();
?>
<?php if (isset($_GET['salvo'])): ?><div class="alert alert-success">Busca salva.</div><?php endif; ?>
<?php if (isset($_GET['editado'])): ?><div class="alert alert-success">Busca atualizada.</div><?php endif; ?>
<?php if (isset($_GET['excluido'])): ?><div class="alert alert-warning">Busca excluida.</div><?php endif; ?>
<?php if (isset($_GET['status'])): ?><div class="alert alert-info">Status da busca alterado.</div><?php endif; ?>
<div class="alert alert-info border-0">
    <strong>Gerenciamento de buscas ativo:</strong> agora os botoes ficam na primeira coluna. Se nao aparecerem, o navegador esta vendo uma pagina velha, porque aparentemente ate tabela agora pratica ilusionismo.
</div>
<div class="alert alert-secondary border-0">
    <strong>Dica:</strong> para Brasil/Sao Paulo, use termos em portugues e em ingles. Exemplo: <code>designer grafico</code>, <code>web designer</code>, <code>assistente administrativo</code>. E cuidado com typo tipo <code>webdeisgner</code>, porque a API nao e mae.
</div>
<div class="row g-4">
    <div class="col-lg-5">
        <form class="card" method="post"><div class="card-body">
            <input type="hidden" name="acao" value="<?= $editBusca ? 'editar' : 'criar' ?>">
            <?php if ($editBusca): ?><input type="hidden" name="id" value="<?= (int)$editBusca['id'] ?>"><?php endif; ?>
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h5 mb-0"><?= $editBusca ? 'Editar busca' : 'Nova busca' ?></h2>
                <?php if ($editBusca): ?><a class="btn btn-sm btn-outline-light" href="configuracoes.php"><i class="bi bi-plus-circle"></i> Nova</a><?php endif; ?>
            </div>
            <label class="form-label">Nome</label><input name="nome" class="form-control mb-3" required value="<?= e($editBusca['nome'] ?? '') ?>">
            <label class="form-label">Termos de busca, um por linha</label><textarea name="termos" rows="6" class="form-control mb-3" required placeholder="web designer&#10;designer grafico&#10;atendimento ao cliente"><?= e($editBusca['termos'] ?? '') ?></textarea>
            <label class="form-label">Localizacao</label><input name="localizacao" class="form-control mb-3" value="<?= e($editBusca['localizacao'] ?? 'Brasil') ?>" placeholder="Brasil, Sao Paulo, Guarulhos, Remote...">
            <label class="form-label">Tipo</label><select name="remoto" class="form-select mb-3"><option value="1" <?= (int)($editBusca['remoto'] ?? 1) === 1 ? 'selected' : '' ?>>Aceita remoto ou localidade informada</option><option value="0" <?= isset($editBusca['remoto']) && (int)$editBusca['remoto'] === 0 ? 'selected' : '' ?>>Somente localidade informada</option></select>
            <label class="form-label">Salario minimo</label><input name="salario_minimo" type="number" step="0.01" class="form-control mb-3" value="<?= e((string)($editBusca['salario_minimo'] ?? '')) ?>">
            <label class="form-label">Palavras obrigatorias</label><textarea name="palavras_obrigatorias" rows="3" class="form-control mb-3" placeholder="Uma por linha. Ex: Figma, Excel, WordPress"><?= e($editBusca['palavras_obrigatorias'] ?? '') ?></textarea>
            <label class="form-label">Palavras proibidas</label><textarea name="palavras_proibidas" rows="3" class="form-control mb-3" placeholder="porta a porta&#10;comissao apenas&#10;vendedor externo"><?= e($editBusca['palavras_proibidas'] ?? '') ?></textarea>
            <div class="mb-3">
                <label class="form-label d-block">Fontes gratis usadas em todas as buscas</label>
                <?php foreach ($fontesDisponiveis as $fonte => $descricao): ?>
                    <div class="source-row mb-2"><strong><?= e($fonte) ?></strong><br><small class="text-muted"><?= e($descricao) ?></small></div>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-accent" type="submit"><i class="bi bi-save"></i> <?= $editBusca ? 'Salvar alteracoes' : 'Salvar busca' ?></button>
        </div></form>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <h2 class="h5 mb-3">Buscas salvas</h2>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Acoes</th><th>Status</th><th>Nome</th><th>Local</th><th>Tipo</th><th>Termos</th><th>Fontes</th></tr></thead><tbody>
            <?php foreach ($buscas as $busca): ?>
                <?php $fontesBusca = json_decode($busca['fontes'] ?: '[]', true); if (!is_array($fontesBusca)) { $fontesBusca = []; } ?>
                <tr class="<?= (int)$busca['ativa'] ? '' : 'opacity-50' ?>">
                    <td class="text-nowrap" style="min-width: 220px">
                        <a class="btn btn-sm btn-primary me-1" href="configuracoes.php?editar=<?= (int)$busca['id'] ?>" title="Editar"><i class="bi bi-pencil"></i> Editar</a>
                        <form method="post" class="d-inline"><input type="hidden" name="acao" value="toggle"><input type="hidden" name="id" value="<?= (int)$busca['id'] ?>"><button class="btn btn-sm btn-warning me-1" title="Ativar/pausar"><i class="bi bi-power"></i></button></form>
                        <form method="post" class="d-inline" onsubmit="return confirm('Excluir esta busca? As vagas ja salvas continuam no sistema.');"><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$busca['id'] ?>"><button class="btn btn-sm btn-danger" title="Excluir"><i class="bi bi-trash"></i></button></form>
                    </td>
                    <td><span class="badge <?= (int)$busca['ativa'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int)$busca['ativa'] ? 'Ativa' : 'Pausada' ?></span></td>
                    <td><?= e($busca['nome']) ?></td>
                    <td><?= e($busca['localizacao']) ?></td>
                    <td><?= (int)$busca['remoto'] ? 'Remoto/local' : 'Somente local' ?></td>
                    <td style="min-width:160px"><?= nl2br(e($busca['termos'])) ?></td>
                    <td><?= e(implode(', ', $fontesBusca)) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$buscas): ?><tr><td colspan="7" class="text-muted">Nenhuma busca configurada.</td></tr><?php endif; ?>
            </tbody></table></div>
        </div></div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
