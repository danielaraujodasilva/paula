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

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function statusBadgeClass(status) {
    return {
        nova: 'text-bg-secondary',
        interessante: 'text-bg-info',
        candidatado: 'text-bg-primary',
        entrevista: 'text-bg-success',
        rejeitada: 'text-bg-danger',
        arquivada: 'text-bg-secondary'
    }[status] || 'text-bg-warning';
}

function jobRow(job) {
    const id = Number(job.id || 0);
    const score = Math.max(0, Math.min(100, Number(job.nota_compatibilidade || 0)));
    return `<tr>
        <td><span class="score-pill" title="${score}% compatível">${score}%</span></td>
        <td>${escapeHtml(job.titulo)}</td>
        <td>${escapeHtml(job.empresa)}</td>
        <td>${escapeHtml(job.localizacao)}</td>
        <td>${escapeHtml(job.fonte)}</td>
        <td>${escapeHtml(job.salario)}</td>
        <td><span class="badge ${statusBadgeClass(job.status)}">${escapeHtml(job.status)}</span></td>
        <td>${escapeHtml(job.data_publicacao)}</td>
        <td class="text-nowrap">
            <a class="btn btn-sm btn-outline-light small-action" href="${appUrl(`vaga.php?id=${id}`)}"><i class="bi bi-eye"></i></a>
            <button class="btn btn-sm btn-outline-info small-action" data-status-id="${id}" data-status="interessante"><i class="bi bi-star"></i></button>
            <button class="btn btn-sm btn-outline-primary small-action" data-status-id="${id}" data-status="candidatado"><i class="bi bi-send"></i></button>
            <button class="btn btn-sm btn-outline-secondary small-action" data-status-id="${id}" data-status="arquivada"><i class="bi bi-archive"></i></button>
        </td>
    </tr>`;
}

function initJobsTable() {
    const table = document.querySelector('[data-jobs-table]');
    if (!table) return;

    const tbody = document.querySelector('[data-jobs-body]');
    const emptyRow = document.querySelector('[data-jobs-empty]');
    const loadedEl = document.querySelector('[data-jobs-loaded]');
    const totalEl = document.querySelector('[data-jobs-total]');
    const loadingEl = document.querySelector('[data-jobs-loading]');
    const endEl = document.querySelector('[data-jobs-end]');
    const loadMoreBtn = document.querySelector('[data-load-more-jobs]');
    const sentinel = document.querySelector('[data-jobs-sentinel]');
    const limit = 50;
    let offset = 0;
    let loading = false;
    let hasMore = true;

    async function loadMore() {
        if (loading || !hasMore) return;
        loading = true;
        loadingEl?.classList.remove('d-none');
        loadMoreBtn?.classList.add('d-none');
        const params = new URLSearchParams(table.dataset.query || window.location.search);
        params.set('limit', String(limit));
        params.set('offset', String(offset));

        try {
            const response = await fetch(appUrl(`api/listar_vagas.php?${params.toString()}`));
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.error || 'Falha ao carregar vagas');
            emptyRow?.remove();
            tbody.insertAdjacentHTML('beforeend', data.data.map(jobRow).join(''));
            offset += data.data.length;
            hasMore = Boolean(data.has_more);
            if (loadedEl) loadedEl.textContent = String(offset);
            if (totalEl) totalEl.textContent = String(data.total);
            if (!offset) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-muted">Nenhuma vaga encontrada.</td></tr>';
            }
            endEl?.classList.toggle('d-none', hasMore || !offset);
            loadMoreBtn?.classList.toggle('d-none', !hasMore);
        } catch (error) {
            showToast(error.message);
            loadMoreBtn?.classList.remove('d-none');
        } finally {
            loading = false;
            loadingEl?.classList.add('d-none');
        }
    }

    loadMoreBtn?.addEventListener('click', loadMore);
    if ('IntersectionObserver' in window && sentinel) {
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) loadMore();
        }, { rootMargin: '500px 0px' });
        observer.observe(sentinel);
    }
    loadMore();
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

initJobsTable();
