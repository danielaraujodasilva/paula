<?php $current = basename($_SERVER['SCRIPT_NAME']); ?>
<aside class="sidebar">
    <a class="brand" href="<?= url('index.php') ?>">
        <span class="brand-mark">P</span>
        <span>Paula</span>
    </a>
    <nav class="nav flex-column gap-1">
        <a class="nav-link <?= $current === 'index.php' ? 'active' : '' ?>" href="<?= url('index.php') ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link <?= $current === 'curriculos.php' ? 'active' : '' ?>" href="<?= url('curriculos.php') ?>"><i class="bi bi-file-earmark-person"></i> Curriculos</a>
        <a class="nav-link <?= $current === 'perfil.php' ? 'active' : '' ?>" href="<?= url('perfil.php') ?>"><i class="bi bi-person-vcard"></i> Perfil</a>
        <a class="nav-link <?= $current === 'configuracoes.php' ? 'active' : '' ?>" href="<?= url('configuracoes.php') ?>"><i class="bi bi-sliders"></i> Buscas</a>
        <a class="nav-link <?= in_array($current, ['vagas.php', 'vaga.php'], true) ? 'active' : '' ?>" href="<?= url('vagas.php') ?>"><i class="bi bi-briefcase"></i> Vagas</a>
    </nav>
    <div class="sidebar-user">
        <div class="small text-muted">Logado como</div>
        <div class="fw-semibold"><?= e(current_user()['nome'] ?? 'Usuario') ?></div>
        <a class="btn btn-sm btn-outline-light w-100 mt-2" href="<?= url('logout.php') ?>"><i class="bi bi-box-arrow-right"></i> Sair</a>
    </div>
</aside>
