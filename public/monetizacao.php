<?php
$pageTitle = 'Monetizacao';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_once __DIR__ . '/../includes/header.php';

$items = [
    'E-mail administrador' => ADMIN_EMAIL,
    'E-mail de contato publico' => SITE_CONTACT_EMAIL,
    'URL publica' => SITE_PUBLIC_URL,
    'Google Analytics' => GOOGLE_ANALYTICS_ID,
    'AdSense client' => GOOGLE_ADSENSE_CLIENT,
    'AdSense slot principal' => GOOGLE_ADSENSE_SLOT_MAIN,
    'Link de apoio' => DONATION_URL,
    'Chave Pix' => DONATION_PIX_KEY,
];
?>
<div class="card mb-4">
    <div class="card-body">
        <span class="eyebrow">Configuracao restrita</span>
        <h2 class="h5 mt-2">Monetizacao e medicao</h2>
        <p class="text-muted mb-0">Esta tela mostra o que esta ativo. Por seguranca, os valores reais devem ser alterados no arquivo privado <code>config/local.php</code> do servidor.</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Item</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($items as $label => $value): ?>
                    <tr>
                        <td><?= e($label) ?></td>
                        <td>
                            <?php if (trim((string)$value) !== ''): ?>
                                <span class="badge text-bg-success">Configurado</span>
                                <code class="ms-2"><?= e((string)$value) ?></code>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Nao configurado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
