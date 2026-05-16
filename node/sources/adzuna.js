const axios = require('axios');
require('dotenv').config();

function hasAdzunaKeys() {
  return Boolean(process.env.ADZUNA_APP_ID && process.env.ADZUNA_APP_KEY);
}

async function searchAdzuna(term, where = '') {
  if (!hasAdzunaKeys()) {
    console.log('Adzuna ignorada: ADZUNA_APP_ID ou ADZUNA_APP_KEY nao configurados no .env.');
    return [];
  }

  const country = process.env.ADZUNA_COUNTRY || 'br';
  const endpoint = `https://api.adzuna.com/v1/api/jobs/${country}/search/1`;
  const response = await axios.get(endpoint, {
    timeout: 20000,
    params: {
      app_id: process.env.ADZUNA_APP_ID,
      app_key: process.env.ADZUNA_APP_KEY,
      what: term,
      where
    }
  });

  const results = response.data.results || [];
  return results.map((job) => ({
    titulo: job.title || '',
    empresa: job.company?.display_name || '',
    localizacao: job.location?.display_name || '',
    salario: [job.salary_min, job.salary_max].filter(Boolean).join(' - '),
    fonte: 'Adzuna',
    url: job.redirect_url || '',
    descricao: job.description || '',
    data_publicacao: job.created || '',
    raw_json: job
  }));
}

module.exports = { searchAdzuna, hasAdzunaKeys };
