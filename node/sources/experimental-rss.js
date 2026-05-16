const axios = require('axios');
const cheerio = require('cheerio');
const { containsAny } = require('../lib/matcher');

const DEFAULT_FEEDS = [
  'https://bnee.webnode.page/rss/vagas.xml',
  'https://bnee.webnode.page/rss/all.xml',
  'https://grhscachoeirinha.webnode.com.br/rss/confira-vagas-de-empregos.xml',
  'https://recrutandoengenheiros.webnode.page/rss/all.xml'
];

function feedUrls() {
  const extra = String(process.env.PAULA_EXPERIMENTAL_FEEDS || '')
    .split(/[\n,;|]+/)
    .map((item) => item.trim())
    .filter(Boolean);
  return [...new Set([...DEFAULT_FEEDS, ...extra])];
}

async function searchExperimentalRss(term) {
  const jobs = [];
  for (const feedUrl of feedUrls()) {
    const response = await axios.get(feedUrl, {
      timeout: 20000,
      headers: { 'User-Agent': 'Projeto Paula Job Search Bot' }
    });
    const $ = cheerio.load(response.data, { xmlMode: true });
    $('item').each((_, item) => {
      const title = $(item).find('title').text().trim();
      const description = $(item).find('description').text().trim();
      const text = `${title} ${description}`;
      if (!containsAny(text, [term])) return;
      jobs.push({
        titulo: title,
        empresa: '',
        localizacao: '',
        salario: '',
        fonte: 'RSS Experimental',
        url: $(item).find('link').text().trim(),
        descricao: description,
        data_publicacao: $(item).find('pubDate').text().trim(),
        raw_json: { feedUrl, title, description, experimental: true }
      });
    });
  }
  return jobs.slice(0, 30);
}

module.exports = { searchExperimentalRss };
