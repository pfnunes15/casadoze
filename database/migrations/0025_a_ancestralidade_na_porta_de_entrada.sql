-- ---------------------------------------------------------------------------
-- The homepage still introduced "o Zé".
--
-- 0020 rewrote the about page and left this block alone: it is on page 1, not
-- page 2, and it carried the mockup's person, photograph and two lists.
--
-- The client's text on ancestrality runs to seven paragraphs. On a homepage
-- section that is a wall, so it is cut to the three that carry it — the entity
-- that opened the way, the forças de cabeça, and what the gratitude amounts
-- to. Every name is kept exactly as written. The whole text belongs on the
-- Sala profile, where there is room for it.
--
-- The two lists go: "o que faço / o que não faço" was written for a tarot
-- reader's page and says nothing the Sobre nós does not say better.
-- ---------------------------------------------------------------------------
UPDATE page_sections
   SET eyebrow = 'A ancestralidade que me acompanha',
       heading = 'Débora D''Exu',
       image = '/assets/img/debora.jpg',
       image_alt = 'Débora D''Exu',
       cta_label = 'Conhecer a Sala',
       cta_url = '/sala/debora-dexu',
       body = CONCAT(
         '<p>José Pelintra do Cruzeiro das Almas é a minha principal entidade e o ancestral que me acompanha. Foi através da minha ligação a Seu Zé Pelintra das Almas que se abriu um novo caminho na minha vida — e é essa ligação que está na origem do trabalho que hoje desenvolvo.</p>',
         '<p>As minhas forças de cabeça são Exu Marabô das Sete Encruzilhadas e Dona Tata Mulambo. Honro a ligação a cada um deles, com respeito pelos seus fundamentos e pelo lugar que ocupam na minha caminhada.</p>',
         '<p>A todos agradeço o conhecimento que me trouxeram e o trabalho que me confiam. A minha gratidão expressa-se na dedicação, no sigilo e na seriedade com que procuro trabalhar todos os dias.</p>'
       )
 WHERE page_id = 1 AND type = 'ze';

DELETE FROM page_section_items
 WHERE section_id = (SELECT id FROM page_sections WHERE page_id = 1 AND type = 'ze');
