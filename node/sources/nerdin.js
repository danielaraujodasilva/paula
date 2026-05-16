const axios = require('axios');
const cheerio = require('cheerio');
const { containsAny } = require('../lib/matcher');

function compact(value) {
  return String(value || '').replace(/\s+/g, ' ').trim();
}

function salaryText(salary) {
  const value = salary?.value || {};
  const min = Number(value.minValue || 0);
  const max = Number(value.maxValue || 0);
  if (!min && !max) return '';
  return `R$ ${[min || '', max || ''].filter(Boolean).join(' - ')}`;
}

function parsePosting(html, url) {
  const $ = cheerio.load(html);
  let posting = null;
  $('script[type="application/ld+json"]').each((_, script) => {
    if (posting) return;
    try {
      const data = JSON.parse($(script).contents().text());
      if (data['@type'] === 'JobPosting') posting = data;
    } catch {}
  });
  if (!posting) return null;
  const address = posting.jobLocation?.address || {};
  return {
    titulo: posting.title || '',
    empresa: posting.hiringOrganization?.name || '',
    localizacao: compact([address.addressLocality, address.addressRegion].filter(Boolean).join(' - ')),
    salario: salaryText(posting.baseSalary),
    fonte: 'Nerdin Experimental',
    url,
    descricao: posting.description || compact($('body').text()).slice(0, 5000),
    data_publicacao: posting.datePosted || '',
    raw_json: { ...posting, experimental: true }
  };
}

async function searchNerdin(term) {
  const response = await axios.get('https://www.nerdin.com.br/vagas.php', {
    timeout: 25000,
    params: { busca: term },
    headers: { 'User-Agent': 'Projeto Paula Job Search Bot' }
  });
  const $ = cheerio.load(response.data);
  const urls = [...new Set($('a[href*="vaga_emprego/"]').map((_, element) => $(element).attr('href')).get())]
    .filter((href) => /vaga_emprego\/vaga-/.test(href))
    .slice(0, 12)
    .map((href) => new URL(href, 'https://www.nerdin.com.br/').href);

  const jobs = [];
  for (const url of urls) {
    const detail = await axios.get(url, { timeout: 20000, headers: { 'User-Agent': 'Projeto Paula Job Search Bot' } });
    const job = parsePosting(detail.data, url);
    if (job && containsAny(`${job.titulo} ${job.empresa} ${job.localizacao} ${job.descricao}`, [term])) {
      jobs.push(job);
    }
  }
  return jobs;
}

module.exports = { searchNerdin };
