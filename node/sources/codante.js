const axios = require('axios');

async function searchCodante(term) {
  const response = await axios.get('https://apis.codante.io/api/job-board/jobs', {
    timeout: 20000,
    params: { search: term }
  });

  const rows = Array.isArray(response.data?.data) ? response.data.data : [];
  return rows.map((job) => ({
    titulo: job.title || '',
    empresa: job.company || '',
    localizacao: job.city || '',
    salario: job.salary ? `R$ ${job.salary}` : '',
    fonte: 'Codante',
    url: job.company_website || '',
    descricao: [job.description, job.requirements].filter(Boolean).join('\n\n'),
    data_publicacao: job.created_at || '',
    raw_json: job
  }));
}

module.exports = { searchCodante };
