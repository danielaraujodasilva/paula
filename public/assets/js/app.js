function showToast(message) {
    const el = document.getElementById('appToast');
    if (!el) return alert(message);
    el.querySelector('.toast-body').textContent = message;
    bootstrap.Toast.getOrCreateInstance(el).show();
}

async function postJson(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload || {})
    });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.error || 'Falha na requisicao');
    return data;
}

function appUrl(path) {
    return `${window.PAULA_BASE_URL || ''}${path.replace(/^\/+/, '')}`;
}

function setOnboardingStep(step) {
    const root = document.querySelector('[data-onboarding]');
    if (!root) return;
    const activeStep = String(step || root.dataset.initialStep || '1');
    root.querySelector('[data-onboarding-flow]')?.classList.remove('d-none');
    root.querySelectorAll('[data-step]').forEach((slide) => {
        slide.classList.toggle('d-none', slide.dataset.step !== activeStep);
    });
    root.querySelectorAll('[data-step-jump]').forEach((button) => {
        button.classList.toggle('active', button.dataset.stepJump === activeStep);
    });
}

document.querySelector('[data-start-onboarding]')?.addEventListener('click', () => {
    const root = document.querySelector('[data-onboarding]');
    setOnboardingStep(root?.dataset.initialStep || 1);
});

document.addEventListener('click', (event) => {
    const stepButton = event.target.closest('[data-step-jump]');
    if (stepButton) setOnboardingStep(stepButton.dataset.stepJump);
});

document.addEventListener('click', async (event) => {
    const statusButton = event.target.closest('[data-status-id]');
    if (statusButton) {
        statusButton.disabled = true;
        try {
            await postJson(appUrl('api/atualizar_status_vaga.php'), {
                id: statusButton.dataset.statusId,
                status: statusButton.dataset.status
            });
            showToast('Status atualizado.');
            setTimeout(() => window.location.reload(), 500);
        } catch (error) {
            showToast(error.message);
            statusButton.disabled = false;
        }
    }
});

document.getElementById('runSearchBtn')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const original = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Buscando...';
    try {
        const data = await postJson(appUrl('api/rodar_busca.php'), {});
        showToast(data.message || 'Busca finalizada.');
        setTimeout(() => window.location.reload(), 900);
    } catch (error) {
        showToast(error.message);
    } finally {
        button.disabled = false;
        button.innerHTML = original;
    }
});

document.querySelector('[data-guided-run-search]')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const original = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Buscando vagas...';
    try {
        const data = await postJson(appUrl('api/rodar_busca.php'), {});
        showToast(data.message || 'Busca finalizada.');
        setOnboardingStep(3);
    } catch (error) {
        showToast(error.message);
    } finally {
        button.disabled = false;
        button.innerHTML = original;
    }
});

document.getElementById('uploadResumeForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const button = form.querySelector('button[type="submit"]');
    const original = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Extraindo...';
    try {
        const response = await fetch(form.action, { method: 'POST', body: new FormData(form) });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.error || 'Falha no upload');
        showToast(data.message || 'Curriculo enviado.');
        if (form.classList.contains('guided-upload')) {
            setOnboardingStep(2);
        } else {
            setTimeout(() => window.location.reload(), 900);
        }
    } catch (error) {
        showToast(error.message);
    } finally {
        button.disabled = false;
        button.innerHTML = original;
    }
});
