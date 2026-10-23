-- ---------------------------------------------------------------------------
-- The house is called Covil Ancestral, and Débora is the one at the table.
--
-- This is not a rename: it is a change of who the site is about. "Casa de Zé"
-- and "o Zé" were placeholders from the mockup, down to the photograph. The
-- client has now given the real name, the real text and the real people.
--
-- Written as a migration and not as an edit to 0003 and 0015: those have run.
-- A migration already applied never runs again, so editing one only changes
-- what a fresh install gets — and the two would drift apart.
--
-- The site name is also in config/shop.php (the order prefix) and in the
-- language catalogues, which are files and not rows.
-- ---------------------------------------------------------------------------

UPDATE settings SET value = 'Covil Ancestral' WHERE `key` = 'site.name';

UPDATE settings
   SET value = 'Produtos preparados à mão e consagrados, e consultas de oráculos com Débora D''Exu — presencialmente ou à distância.'
 WHERE `key` = 'site.tagline';

UPDATE settings
   SET value = 'Leituras e trabalhos espirituais do Covil Ancestral, presencialmente ou à distância. Cada consulta começa com uma conversa sobre o que te trouxe até aqui.'
 WHERE `key` = 'readings.intro';

UPDATE settings SET value = '[email]@covilancestral.pt' WHERE `key` = 'contact.email';

UPDATE pages SET title = 'Covil Ancestral' WHERE slug = 'home';

-- ---------------------------------------------------------------------------
-- Sobre nós. The client's own text, kept as it was written.
--
-- The page was "O Zé" — one person's biography. It is now the house's: the
-- people moved to the Sala, which is where people belong.
-- ---------------------------------------------------------------------------
UPDATE pages
   SET title = 'Sobre nós',
       menu  = 'Sobre nós',
       meta_description = 'Há dez anos a cuidar do caminho espiritual, com acolhimento, sigilo e um trabalho dedicado. Covil Ancestral.'
 WHERE slug = 'a-casa';

UPDATE page_sections
   SET type = 'texto',
       eyebrow = 'Há dez anos a cuidar do caminho espiritual',
       heading = 'Covil Ancestral',
       image = '',
       image_alt = '',
       cta_label = 'Marcar consulta',
       cta_url = '/consultas',
       body = CONCAT(
         '<p>O Covil Ancestral nasceu da dedicação à espiritualidade e do respeito pelos saberes que atravessam gerações. Há dez anos que acolhemos quem procura orientação, proteção e força para enfrentar os desafios da vida, com um cuidado próximo, sério e responsável.</p>',
         '<p>Aqui, cada pessoa é recebida com respeito pela sua história, pelas suas dificuldades e pelo seu tempo. Acreditamos que cuidar da espiritualidade é também criar espaço para escutar, encontrar clareza e reconhecer o que precisa de mudar.</p>',
         '<p>O nosso trabalho procura trazer a força ancestral para o presente: cuidar da nossa energia, abrir e proteger os nossos caminhos e orientar os passos que damos. Fazemo-lo com fé, intenção e consciência, respeitando sempre a liberdade e as decisões de cada pessoa.</p>',
         '<p>O Covil não vive de promessas. Vive de compromisso com o cuidado espiritual. Não prometemos resultados garantidos nem uma vida sem obstáculos. Oferecemos acolhimento, sigilo e um trabalho dedicado, que acompanha a caminhada de cada pessoa sem substituir a sua vontade, as suas escolhas ou a sua responsabilidade.</p>',
         '<p>Acreditamos que a magia não tem cor — tem intenção. E a nossa intenção é clara: honrar a ancestralidade e cuidar de quem nos procura com verdade, respeito e firmeza.</p>',
         '<p><em>Covil Ancestral. Raízes que nos sustentam. Força que nos acompanha.</em></p>',
         '<p><strong>Guardiã do Covil Ancestral</strong></p>'
       )
 WHERE id = 20;

-- The "o que faço / o que não faço" lists belonged to the `ze` block, which is
-- now a text block and does not draw them.
DELETE FROM page_section_items WHERE section_id = 20;

-- The percurso and the four principles were written about a person who does not
-- exist. What replaces them is in the text above and in the Sala.
DELETE FROM page_section_items WHERE section_id IN (21, 22);
DELETE FROM page_sections WHERE id IN (21, 22, 23);

UPDATE page_sections SET eyebrow = 'Quem passou pelo Covil' WHERE id = 24;
UPDATE page_sections SET heading = 'O que costumam perguntar' WHERE id = 25;

UPDATE page_section_items
   SET body = REPLACE(body, 'O Zé', 'A Débora')
 WHERE section_id = 25;

UPDATE page_section_items
   SET body = REPLACE(body, 'entre ti e o Zé', 'entre ti e quem te atende')
 WHERE section_id = 25;

UPDATE page_section_items
   SET body = REPLACE(body, 'a loja física abre', 'a loja física abre'),
       body = REPLACE(body, 'com o Zé', 'no Covil')
 WHERE section_id = 25;

-- ---------------------------------------------------------------------------
-- The rest of the site: the name, wherever the old one was written into text.
-- ---------------------------------------------------------------------------
UPDATE page_sections SET body    = REPLACE(body, 'Casa de Zé', 'Covil Ancestral') WHERE body IS NOT NULL;
UPDATE page_sections SET heading = REPLACE(heading, 'Casa de Zé', 'Covil Ancestral');
UPDATE page_sections SET eyebrow = REPLACE(eyebrow, 'Casa de Zé', 'Covil Ancestral');
UPDATE page_section_items SET body = REPLACE(body, 'Casa de Zé', 'Covil Ancestral') WHERE body IS NOT NULL;
UPDATE consultations SET body = REPLACE(body, 'Casa de Zé', 'Covil Ancestral') WHERE body IS NOT NULL;
UPDATE consultations SET body = REPLACE(body, 'pelo Zé', 'no Covil') WHERE body IS NOT NULL;
UPDATE consultations SET body = REPLACE(body, 'com o Zé', 'com quem te atende') WHERE body IS NOT NULL;

-- The hero photograph. The old one was a stand-in; this is the client's.
UPDATE page_sections
   SET image = '/assets/img/abertura.jpg',
       image_alt = 'Covil Ancestral'
 WHERE type = 'abertura';
