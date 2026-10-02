-- ---------------------------------------------------------------------------
-- «Agora na Casa», entre a loja e as consultas.
--
-- O próximo sabbat, a contagem dos dias e a fita com as fases da lua do mês. É
-- a secção que a maquete tem a seguir à loja e que nunca tinha sido escrita.
--
-- Quase não leva conteúdo: o nome do sabbat, a data, os dias que faltam e as
-- trinta e uma luas são calculados em App\Services\Lua. O que fica escrito é o
-- kit — o que é, quanto custa e para onde leva o botão.
--
-- **O preço fica por dizer.** Como os outros `[x]` do 0003: ninguém disse quanto
-- custa o kit, e inventar um número era fazer uma promessa que a Casa não sabe
-- que fez. Vazio não desenha preço nenhum.
-- ---------------------------------------------------------------------------

UPDATE page_sections
   SET sort_order = sort_order + 1
 WHERE page_id = 1 AND sort_order >= 4
 ORDER BY sort_order DESC;

INSERT INTO page_sections (page_id, locale, type, anchor, eyebrow, heading, heading_level, body, image, image_alt, cta_label, cta_url, options, sort_order, is_published)
VALUES (1, 'pt', 'agora', 'agora', 'Agora na Casa', '', 2,
        'Velas, ervas e o ritual escrito à mão para celebrar a data. Preparado em pequenas quantidades.',
        '', '', 'Ver o kit', '#loja',
        '{"price":"[PREÇO] €"}',
        4, 1);
