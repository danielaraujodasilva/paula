const axios = require('axios');
const { containsAny } = require('../lib/matcher');

async function searchArbeitnow(term) {
  const response = await axios.get('https://www.arbeitnow.com/api/job-board-api', { timeout: 20000 });
  const jobs = response.data.data || [];
  return jobs
    .filter((job) => containsAny(`${job.title || ''} ${job.description || ''} ${(job.tags || []).join(' ')}`, [term]))
    .map((job) => ({
      titulo: job.title || '',
      empresa: job.company_name || '',
      localizacao: job.location || '',
      salario: '',
      fonte: 'Arbeitnow',
      url: job.url || '',
      descricao: job.description || '',
      data_publicacao: job.created_at ? new Date(job.created_at * 1000).toISOString() : '',
      raw_json: job
    }));
}

module.exports = { searchArbeitnow };
