const axios = require('axios');
const cheerio = require('cheerio');
const { containsAny } = require('../lib/matcher');

function slug(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

function compact(value) {
  return String(value || '').replace(/\s+/g, ' ').trim();
}

function titleFromHref(href) {
  const slugPart = String(href || '').split('/').filter(Boolean).at(-2) || '';
  return slugPart
    .split('-')
    .filter(Boolean)
    .map((part) => part.length <= 3 ? part.toUpperCase() : part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
}

function locationFromHref(href) {
  const match = String(href || '').match(/vagas-de-emprego-em-([^/]+)/);
  if (!match) return '';
  const parts = match[1].split('-');
  const uf = (parts.pop() || '').toUpperCase();
  const city = parts.map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join(' ');
  return city && uf ? `${city}/${uf}` : city;
}

function parseText(text, href) {
  const title = titleFromHref(href) || compact((text.match(/^(?:\d+\s+)?Vagas?\s+de\s+(.+?)\s+(?:Home-Office|Presencial|Híbrido|Hibrido|[A-ZÁÉÍÓÚÂÊÔÃÕÇ][\wÀ-ÿ.' -]+\/[A-Z]{2})/) || [])[1] || text.slice(0, 100));
  const salary = compact((text.match(/R\$\s?[\d.,]+(?:,\d{2})?(?:\s+por mês)?/i) || [''])[0]);
  const mode = compact((text.match(/\b(Home-Office|Presencial|Híbrido|Hibrido)\b/i) || [''])[0]);
  const location = locationFromHref(href) || compact((text.match(/[A-ZÁÉÍÓÚÂÊÔÃÕÇ][\wÀ-ÿ.' -]+\/[A-Z]{2}/) || [''])[0]);
  const cleanText = compact(text.replace(/^(?:\d+\s+)?Vagas?\s+de\s+/i, '').replace(title, ''));
  const company = compact(cleanText
    .split(location || mode || salary)[0]
    .replace(/\b(Home-Office|Presencial|Híbrido|Hibrido)\b/gi, '')
    .replace(salary, ''));
  return { title, company, salary, location: [location, mode].filter(Boolean).join(' - ') };
}

async function searchTrabalhaBrasil(term, where = '') {
  const termSlug = slug(term);
  const whereSlug = where && !/^brasil$/i.test(where) ? `/${slug(where)}` : '';
  const url = `https://www.trabalhabrasil.com.br/vagas-de-emprego/${termSlug}${whereSlug}`;
  const response = await axios.get(url, {
    timeout: 25000,
    headers: { 'User-Agent': 'Projeto Paula Job Search Bot' }
  });

  const $ = cheerio.load(response.data);
  const seen = new Set();
  const jobs = [];
  $('a[href*="/vagas-de-emprego-em-"]').each((_, element) => {
    const href = $(element).attr('href');
    const text = compact($(element).text());
    if (!href || !text || seen.has(href)) return;
    if (!containsAny(text, [term])) return;
    seen.add(href);
    const parsed = parseText(text, href);
    jobs.push({
      titulo: parsed.title,
      empresa: parsed.company,
      localizacao: parsed.location,
      salario: parsed.salary,
      fonte: 'TrabalhaBrasil Experimental',
      url: new URL(href, 'https://www.trabalhabrasil.com.br').href,
      descricao: text,
      data_publicacao: '',
      raw_json: { href, text, experimental: true }
    });
  });
  return jobs.slice(0, 30);
}

module.exports = { searchTrabalhaBrasil };
