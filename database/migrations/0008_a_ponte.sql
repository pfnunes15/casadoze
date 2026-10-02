-- ---------------------------------------------------------------------------
-- A ponte, entre «Agora na Casa» e as consultas.
--
-- Separa as duas metades do que a Casa faz: o que se leva para casa e o que se
-- vive à mesa. Sem ela, a lista de consultas vinha a seguir à grelha de
-- produtos como se fosse mais catálogo.
--
-- As duas filas de letras ocas que passam por trás não estão aqui: são textura
-- e não conteúdo, e lêem-se do catálogo de frases — ver views/sections/ponte.php.
-- ---------------------------------------------------------------------------

UPDATE page_sections
   SET sort_order = sort_order + 1
 WHERE page_id = 1 AND sort_order >= 5
 ORDER BY sort_order DESC;

INSERT INTO page_sections (page_id, locale, type, anchor, eyebrow, heading, heading_level, body, image, image_alt, cta_label, cta_url, options, sort_order, is_published)
VALUES (1, 'pt', 'ponte', '', 'Da loja à mesa',
        'Os objetos levam-se para casa. As consultas vivem-se à mesa.', 2,
        'A seguir, os serviços da Casa: leituras de tarot e trabalhos espirituais com o Zé, online ou presencialmente.',
        '', '', '', '', '{}', 5, 1);
