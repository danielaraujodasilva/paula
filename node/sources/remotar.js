const axios = require('axios');

function salaryText(job) {
  const salary = job.jobSalary || {};
  if (!salary || salary.type === 'uninformed') return '';
  const currency = salary.currency || 'BRL';
  const from = salary.from || salary.min || '';
  const to = salary.to || salary.max || '';
  return [currency, [from, to].filter(Boolean).join(' - ')].filter(Boolean).join(' ');
}

async function searchRemotar(term) {
  const response = await axios.get('https://api.remotar.com.br/jobs', {
    timeout: 20000,
    params: { search: term, active: true, page: 1 }
  });

  const rows = Array.isArray(response.data?.data) ? response.data.data : [];
  return rows.map((job) => ({
    titulo: job.title || '',
    empresa: job.company?.name || job.companyDisplayName || job.author?.name || '',
    localizacao: [job.city, job.state].filter(Boolean).join(' - ') || 'Remoto',
    salario: salaryText(job),
    fonte: 'Remotar',
    url: job.externalLink || `https://remotar.com.br/jobs/${job.id}`,
    descricao: [job.subtitle, job.description, job.moreInfos].filter(Boolean).join('\n\n'),
    data_publicacao: job.createdAt || '',
    raw_json: job
  }));
}

module.exports = { searchRemotar };
