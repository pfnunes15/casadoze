-- ---------------------------------------------------------------------------
-- O Grimório, entre a roda dos signos e as garantias.
--
-- As sessões gratuitas do canal: três cartões com a miniatura, a duração e o
-- produto usado em cada uma. É a secção que liga o que se vê de graça ao que se
-- vende.
--
-- **Os três vídeos ficam por preencher**, como os outros `[x]` do projecto: não
-- há canal nem sessões gravadas, e inventar títulos era encher a página de
-- coisas que não existem. Sem endereço, o cartão desenha a moldura tracejada com
-- o botão de ver ao meio — que é o que a maquete mostra.
--
-- O endereço do canal também não está aqui: vem de Definições > Redes > YouTube,
-- que é o mesmo que o rodapé usa. Enquanto estiver vazio, o botão do canal não
-- se desenha.
-- ---------------------------------------------------------------------------

UPDATE page_sections
   SET sort_order = sort_order + 1
 WHERE page_id = 1 AND sort_order >= 10
 ORDER BY sort_order DESC;

INSERT INTO page_sections (page_id, locale, type, anchor, eyebrow, heading, heading_level, body, image, image_alt, cta_label, cta_url, options, sort_order, is_published)
VALUES (1, 'pt', 'grimorio', 'grimorio', 'Grimório', 'Sessões gratuitas no YouTube', 2,
        'Rituais, ervas e leituras explicados pelo Zé, para quem está a começar e para quem já pratica.',
        '', '', 'Ver o canal', '', '{}', 10, 1);

INSERT INTO page_section_items (section_id, title, subtitle, caption, url, sort_order, is_published)
SELECT s.id, v.title, v.subtitle, v.caption, '', v.sort_order, 1
  FROM page_sections s
  JOIN (
        SELECT '[Título da sessão]' AS title, '[Produto]' AS subtitle, '[mm:ss]' AS caption, 1 AS sort_order
  UNION SELECT '[Título da sessão]',          '[Produto]',             '[mm:ss]',             2
  UNION SELECT '[Título da sessão]',          '[Produto]',             '[mm:ss]',             3
       ) v
 WHERE s.page_id = 1 AND s.type = 'grimorio';
