<?php
declare(strict_types=1);

// Copie este arquivo para config/local.php no servidor.
// O arquivo local.php fica fora do Git e pode guardar segredos.

defined('DEPLOY_WEBHOOK_SECRET') || define('DEPLOY_WEBHOOK_SECRET', 'troque-este-segredo-no-servidor');
defined('DEPLOY_BRANCH') || define('DEPLOY_BRANCH', 'main');
defined('DEPLOY_REPO_PATH') || define('DEPLOY_REPO_PATH', ROOT_PATH);
defined('GIT_PATH') || define('GIT_PATH', 'git');
defined('BASE_URL') || define('BASE_URL', '/paula');

// Monetizacao e medicao, todos opcionais.
// Use IDs reais apenas no servidor.
defined('ADMIN_EMAIL') || define('ADMIN_EMAIL', 'danielaraujodasilva@gmail.com');
defined('SITE_CONTACT_EMAIL') || define('SITE_CONTACT_EMAIL', 'contato@seudominio.com');
defined('SITE_PUBLIC_URL') || define('SITE_PUBLIC_URL', 'https://seudominio.com/paula');
defined('GOOGLE_ANALYTICS_ID') || define('GOOGLE_ANALYTICS_ID', '');
defined('GOOGLE_ADSENSE_CLIENT') || define('GOOGLE_ADSENSE_CLIENT', '');
defined('GOOGLE_ADSENSE_SLOT_MAIN') || define('GOOGLE_ADSENSE_SLOT_MAIN', '');
defined('DONATION_URL') || define('DONATION_URL', '');
defined('DONATION_PIX_KEY') || define('DONATION_PIX_KEY', '');
