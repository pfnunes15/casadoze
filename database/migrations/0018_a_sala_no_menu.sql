-- ---------------------------------------------------------------------------
-- «Sala» entra no menu, entre as Consultas e o Grimório.
--
-- É a posição que o desenho novo lhe dá no cabeçalho — Loja, Consultas, Sala,
-- Grimório, A Casa, Contactos. O Grimório ainda não existe; quando existir,
-- entra a seguir a esta.
--
-- Os números de ordem abrem de dois em dois para caber o que vier pelo meio sem
-- ter de renumerar tudo.
-- ---------------------------------------------------------------------------
UPDATE menu_items SET sort_order = sort_order * 10 WHERE location IN ('header', 'footer');

INSERT INTO menu_items (locale, location, label, url, target, style, sort_order, is_active)
SELECT 'pt', 'header', 'Sala', '/sala', '_self', 'link', 25, 1
 WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE location = 'header' AND url = '/sala');

INSERT INTO menu_items (locale, location, label, url, target, style, sort_order, is_active)
SELECT 'pt', 'footer', 'Sala', '/sala', '_self', 'link', 25, 1
 WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE location = 'footer' AND url = '/sala');
