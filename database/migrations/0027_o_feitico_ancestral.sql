-- ---------------------------------------------------------------------------
-- Feitiço Ancestral — o nono artigo da loja.
--
-- Nome, preço e descrição são os que o cliente deu. **O resumo não é**: a
-- montra da loja desenha uma linha curta por baixo do nome, e esta foi tirada
-- da própria descrição — a intenção a que a vela se dedica — e não inventada.
--
-- **Sem fotografia.** Veio uma, e não chegou como ficheiro; o `image` fica
-- vazio, que é o que a ficha sabe desenhar sem fingir. Quando a imagem
-- aparecer, entra pelo backoffice ou por `/assets/img/feitico-ancestral.jpg`
-- com as variantes ao lado — ver App\Services\Imagens.
--
-- O `stock` em 12 é o mesmo dos outros oito, que veio do povoamento inicial e
-- não de ninguém ter contado o que está na prateleira. Vale para mostrar;
-- antes de vender a sério, a casa tem de dizer quantos são.
--
-- O `vat_rate` fica NULL como em todos os outros: a tabela das taxas está
-- vazia e quem não diz nada apanha a taxa por omissão da loja.
-- ---------------------------------------------------------------------------
INSERT INTO products
    (locale, slug, name, summary, description, price_cents, vat_rate, stock, weight_g, sort_order, is_published)
SELECT
    'pt',
    'feitico-ancestral',
    'Feitiço Ancestral',
    'Amor, união e harmonia afetiva',
    '<p>Três velas unidas com ervas, num ritual dedicado à intenção de atrair o amor, fortalecer laços e favorecer a união e a harmonia afetiva.</p>',
    1000,
    NULL,
    12,
    0,
    9,
    1
WHERE NOT EXISTS (SELECT 1 FROM (SELECT slug FROM products) p WHERE p.slug = 'feitico-ancestral');

-- Na prateleira das velas, com as outras. Pelos slugs e não pelos ids: os ids
-- de um povoamento não são os mesmos em todas as bases de dados.
INSERT IGNORE INTO product_category_map (product_id, category_id)
SELECT p.id, c.id
  FROM products p
  JOIN product_categories c ON c.slug = 'velas-e-oleos'
 WHERE p.slug = 'feitico-ancestral';
