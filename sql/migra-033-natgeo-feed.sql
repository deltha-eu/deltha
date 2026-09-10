-- National Geographic España: el feed real es /feeds/rss (RSS 2.0 con dc:terms, dc:abstract y media:content).
UPDATE sources SET feed_portada = 'https://www.nationalgeographic.com.es/feeds/rss', last_error = NULL WHERE slug = 'natgeo';
