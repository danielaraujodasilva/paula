const axios = require('axios');
require('dotenv').config();

function hasGupyToken() {
  return Boolean(process.env.GUPY_TOKEN || process.env.GUPY_BEARER_TOKEN);
}

async function searchGupy(term, where = '') {
  if (!hasGupyToken()) {
    console.log('Gupy ignorada: configure GUPY_TOKEN no node/.env para usar a API oficial da Gupy.');
    return [];
  }

  const token = process.env.GUPY_TOKEN || process.env.GUPY_BEARER_TOKEN;
  const response = await axios.get('https://api.gupy.io/api/v1/jobs', {
    timeout: 25000,
    headers: { Authorization: `Bearer ${token}` },
    params: {
      name: term,
      status: 'published',
      publicationType: 'external',
      addressCountry: 'Brasil',
      addressCity: where && where.toLowerCase() !== 'brasil' ? where : undefined
    }
  });

  const rows = Array.isArray(response.data?.data) ? response.data.data : (Array.isArray(response.data) ? response.data : []);
  return rows.map((job) => {
    const city = job.addressCity || job.city || '';
    const state = job.addressState || job.state || '';
    const workplace = job.workplaceType || job.remoteWorking || '';
    return {
      titulo: job.name || job.title || '',
      empresa: job.companyName || job.careerPageName || job.company?.name || '',
      localizacao: [city, state, workplace].filter(Boolean).join(' - '),
      salario: job.salary || job.salaryRange || '',
      fonte: 'Gupy',
      url: job.jobUrl || job.url || job.publicUrl || (job.id ? `https://portal.gupy.io/job-search/job/${job.id}` : ''),
      descricao: job.description || job.responsibilities || '',
      data_publicacao: job.publishedDate || job.createdAt || job.publicationDate || '',
      raw_json: job
    };
  });
}

module.exports = { searchGupy, hasGupyToken };
