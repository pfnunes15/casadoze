-- ---------------------------------------------------------------------------
-- Comprar por intenção.
--
-- A loja arruma-se por categorias — velas, cristais, ervas —, que é como se
-- arruma um armazém. Quem entra na Casa não procura uma vela: procura dormir
-- melhor, ou deixar de levar com o que não é seu. Esta migração abre a segunda
-- travessia do catálogo: cinco etiquetas, os produtos marcados com elas, e o
-- bloco de cartões na porta de entrada, a seguir à abertura.
--
-- As etiquetas já existiam como tabela — vêm do pacote — e estavam vazias. O
-- que falta ao pacote é um endereço público para elas, e esse é da Casa: ver
-- src/Controllers/IntentionController.php e a rota `/intencao/{slug}`.
--
-- **Quem leva que etiqueta** é o que a maquete diz, e a maquete di-lo pelo
-- resumo de cada produto: o Óleo Lua Negra é «proteção e corte de laços», o
-- Molho de Sálvia é «limpeza de espaços». O Tarot e o Grimório não levam
-- nenhuma — são ferramentas e não intenções, e marcá-los com uma qualquer era
-- encher uma lista para ela não parecer curta.
-- ---------------------------------------------------------------------------

INSERT INTO product_tags (locale, slug, name) VALUES
    ('pt', 'protecao',           'Proteção'),
    ('pt', 'amor',               'Amor'),
    ('pt', 'prosperidade',       'Prosperidade'),
    ('pt', 'limpeza-energetica', 'Limpeza energética'),
    ('pt', 'sono-e-sonhos',      'Sono e sonhos');

-- Por `slug` e não por número: o `id` de uma etiqueta depende de quantas linhas
-- a tabela já tinha, e uma migração que conte com ele parte-se na instalação
-- seguinte.
INSERT INTO product_tag_map (product_id, tag_id)
SELECT p.id, t.id
  FROM products p
  JOIN product_tags t ON t.locale = p.locale
 WHERE (p.slug = 'vela-de-intencao'  AND t.slug IN ('amor', 'prosperidade'))
    OR (p.slug = 'oleo-lua-negra'    AND t.slug = 'protecao')
    OR (p.slug = 'ametista-bruta'    AND t.slug = 'sono-e-sonhos')
    OR (p.slug = 'amuleto-crescente' AND t.slug = 'protecao')
    OR (p.slug = 'kit-lua-cheia'     AND t.slug = 'limpeza-energetica')
    OR (p.slug = 'molho-de-salvia'   AND t.slug = 'limpeza-energetica');

-- ---------------------------------------------------------------------------
-- O bloco na porta de entrada, entre a abertura e a montra.
--
-- Abrir espaço primeiro: tudo o que está depois da abertura desce um lugar. Em
-- descendente, ou o `UPDATE` passava por cima de uma ordem que ainda não tinha
-- lido — dois blocos com o mesmo número e uma página com a ordem baralhada.
-- ---------------------------------------------------------------------------
UPDATE page_sections
   SET sort_order = sort_order + 1
 WHERE page_id = 1 AND sort_order >= 2
 ORDER BY sort_order DESC;

INSERT INTO page_sections (page_id, locale, type, anchor, eyebrow, heading, heading_level, body, image, image_alt, cta_label, cta_url, options, sort_order, is_published)
VALUES (1, 'pt', 'intencoes', 'intencao', 'Comprar por intenção', 'O que procuras?', 2,
        '', '', '', '', '', '{}', 2, 1);

-- Os cinco cartões. A contagem de peças não está aqui: é lida da loja quando a
-- página se desenha, porque um número escrito à mão fica errado no dia em que
-- um produto mudar de etiqueta — e ninguém se lembra de o vir corrigir.
INSERT INTO page_section_items (section_id, title, subtitle, caption, sort_order, is_published)
SELECT s.id, v.title, v.subtitle, v.caption, v.sort_order, 1
  FROM page_sections s
  JOIN (
        SELECT 'Proteção'           AS title, 'protecao'           AS subtitle, 'olho' AS caption, 1 AS sort_order
  UNION SELECT 'Amor',                        'amor',                           'laco',           2
  UNION SELECT 'Prosperidade',                'prosperidade',                   'sol',            3
  UNION SELECT 'Limpeza energética',          'limpeza-energetica',             'fumo',           4
  UNION SELECT 'Sono e sonhos',               'sono-e-sonhos',                  'lua',            5
       ) v
 WHERE s.page_id = 1 AND s.type = 'intencoes';
