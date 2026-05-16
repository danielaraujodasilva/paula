const axios = require('axios');
const cheerio = require('cheerio');

function compact(value) {
  return String(value || '').replace(/\s+/g, ' ').trim();
}

function extractJobPosting(html) {
  const $ = cheerio.load(html);
  const raw = $('body').text().match(/\{"@context":"http:\\\/\\\/schema\.org"[\s\S]*?\}\s*$/)?.[0];
  if (raw) {
    try {
      return JSON.parse(raw);
    } catch {
      return null;
    }
  }
  let parsed = null;
  $('script[type="application/ld+json"]').each((_, script) => {
    if (parsed) return;
    try {
      const data = JSON.parse($(script).contents().text());
      if (data['@type'] === 'JobPosting') parsed = data;
    } catch {}
  });
  return parsed;
}

async function searchNetvagas(term, where = '') {
  const body = new URLSearchParams({ cargo_anuncio: term, cidade: where || '' }).toString();
  const response = await axios.post('https://www.netvagas.com.br/empresa/buscar/', body, {
    timeout: 25000,
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'User-Agent': 'Projeto Paula Job Search Bot'
    }
  });

  const $ = cheerio.load(response.data);
  const urls = [...new Set($('a[href*="/empresa/anuncios/a"]').map((_, element) => $(element).attr('href')).get())].slice(0, 12);
  const jobs = [];
  for (const url of urls) {
    const detail = await axios.get(url, { timeout: 20000, headers: { 'User-Agent': 'Projeto Paula Job Search Bot' } });
    const posting = extractJobPosting(detail.data);
    if (!posting) continue;
    const org = posting.hiringOrganization || {};
    const address = posting.jobLocation?.address || {};
    jobs.push({
      titulo: posting.title || '',
      empresa: org.name || '',
      localizacao: compact([address.addressLocality, address.addressRegion].filter(Boolean).join(' - ')),
      salario: '',
      fonte: 'Netvagas',
      url,
      descricao: posting.description || '',
      data_publicacao: posting.datePosted || '',
      raw_json: posting
    });
  }
  return jobs;
}

module.exports = { searchNetvagas };
