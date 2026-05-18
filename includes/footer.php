    </main>
</div>
<?php if (is_public_page()): ?>
<nav class="public-footer" aria-label="Links institucionais">
    <a href="<?= url('login.php') ?>">Entrar</a>
    <a href="<?= url('apoie.php') ?>">Apoie</a>
    <a href="<?= url('privacidade.php') ?>">Privacidade</a>
    <a href="<?= url('contato.php') ?>">Contato</a>
</nav>
<?php endif; ?>
<div class="position-fixed bottom-0 start-0 m-2 px-2 py-1 rounded bg-dark border border-secondary text-secondary small" style="z-index: 1030; opacity: .8">
    Paula build: <strong>2026-05-15-localidade-buscas</strong>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="appToast" class="toast text-bg-dark border border-secondary" role="alert">
        <div class="toast-body"></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.PAULA_BASE_URL = <?= json_encode(rtrim(BASE_URL, '/') . '/') ?>;</script>
<script src="<?= url('assets/js/app.js') ?>?v=20260515-localidade-buscas"></script>
</body>
</html>
