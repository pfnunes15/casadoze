-- ---------------------------------------------------------------------------
-- A previsão dos signos, entre os testemunhos e as garantias.
--
-- A roda dos doze com a constelação ao centro, e a carta da semana. É a secção
-- que a maquete tem depois dos testemunhos e que nunca tinha sido escrita.
--
-- Não leva conteúdo nenhum para além da sobrescrita e do título: os períodos, os
-- elementos, os regentes, as constelações e a semana saem de
-- App\Services\Signos, e as frases do catálogo de línguas. Era o que faltava
-- para o bloco poder existir.
-- ---------------------------------------------------------------------------

UPDATE page_sections
   SET sort_order = sort_order + 1
 WHERE page_id = 1 AND sort_order >= 9
 ORDER BY sort_order DESC;

INSERT INTO page_sections (page_id, locale, type, anchor, eyebrow, heading, heading_level, body, image, image_alt, cta_label, cta_url, options, sort_order, is_published)
VALUES (1, 'pt', 'signos', 'signos', 'Previsão dos signos', 'A tua semana nos astros', 2,
        '', '', '', '', '', '{}', 9, 1);
