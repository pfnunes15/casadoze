-- ---------------------------------------------------------------------------
-- A montra, como está na maquete.
--
-- Três coisas que faltavam aos produtos para o cartão poder ser desenhado:
--
--   **O desenho.** A maquete não tem fotografias: cada produto traz uma
--   ilustração pintada num canvas — a vela acesa, o frasco da Lua Negra, o cacho
--   de ametista. Quem as pinta é public/assets/js/arte-produtos.js, que é a
--   mesma biblioteca que pinta a maquete; o que falta aqui é dizer qual delas
--   leva cada produto.
--
--   **A fase da lua.** Na maquete é um selo por cima da ilustração, e não parte
--   da frase. Estava escrita dentro do `summary` — «Prosperidade e caminhos
--   abertos · Lua crescente» —, e por isso aparecia no sítio errado e não
--   aparecia no certo.
--
--   **A existência em armazém.** Estava a zero nos oito, e uma loja inteira a
--   dizer «Esgotado» não é o que a maquete mostra.
--
-- Guardadas como características do produto, que é o mecanismo do pacote para
-- isto — ver product_attributes. Uma coluna nova em `products` era uma coluna
-- que os outros seis sites passavam a ter sem precisar dela.
-- ---------------------------------------------------------------------------

INSERT INTO product_attributes (locale, name, sort_order) VALUES
    ('pt', 'Desenho', 1),
    ('pt', 'Lua',     2);

-- O desenho de cada produto. Os nomes são as chaves de DRAW em arte-produtos.js;
-- uma chave que não exista lá deixa o cartão sem ilustração, e não rebenta nada.
INSERT INTO product_attribute_values (product_id, attribute_id, value, sort_order)
SELECT p.id, a.id, v.valor, 1
  FROM products p
  JOIN product_attributes a ON a.name = 'Desenho' AND a.locale = p.locale
  JOIN (
        SELECT 'vela-de-intencao'   AS slug, 'candle'   AS valor
  UNION SELECT 'oleo-lua-negra',             'oil'
  UNION SELECT 'ametista-bruta',             'crystal'
  UNION SELECT 'amuleto-crescente',          'amulet'
  UNION SELECT 'kit-lua-cheia',              'moon'
  UNION SELECT 'molho-de-salvia',            'sage'
  UNION SELECT 'tarot-da-casa',              'tarot'
  UNION SELECT 'grimorio-em-branco',         'grimoire'
       ) v ON v.slug = p.slug;

-- A fase da lua, escrita por extenso porque é o que o selo mostra.
INSERT INTO product_attribute_values (product_id, attribute_id, value, sort_order)
SELECT p.id, a.id, v.valor, 2
  FROM products p
  JOIN product_attributes a ON a.name = 'Lua' AND a.locale = p.locale
  JOIN (
        SELECT 'vela-de-intencao'   AS slug, 'Lua crescente'  AS valor
  UNION SELECT 'oleo-lua-negra',             'Lua minguante'
  UNION SELECT 'ametista-bruta',             'Lua cheia'
  UNION SELECT 'amuleto-crescente',          'Lua nova'
  UNION SELECT 'kit-lua-cheia',              'Lua cheia'
  UNION SELECT 'molho-de-salvia',            'Lua minguante'
  UNION SELECT 'tarot-da-casa',              'Lua nova'
  UNION SELECT 'grimorio-em-branco',         'Lua nova'
       ) v ON v.slug = p.slug;

-- A frase fica só com a frase: a lua saiu dali para o selo. Travado ao texto
-- exacto que o 0002 semeou, para não cortar o que a Casa tenha reescrito.
UPDATE products SET summary = 'Prosperidade e caminhos abertos' WHERE slug = 'vela-de-intencao'   AND summary = 'Prosperidade e caminhos abertos · Lua crescente';
UPDATE products SET summary = 'Proteção e corte de laços'       WHERE slug = 'oleo-lua-negra'     AND summary = 'Proteção e corte de laços · Lua minguante';
UPDATE products SET summary = 'Intuição e sono tranquilo'       WHERE slug = 'ametista-bruta'     AND summary = 'Intuição e sono tranquilo · Lua cheia';
UPDATE products SET summary = 'Proteção em viagem'              WHERE slug = 'amuleto-crescente'  AND summary = 'Proteção em viagem · Lua nova';
UPDATE products SET summary = 'Limpeza e gratidão'              WHERE slug = 'kit-lua-cheia'      AND summary = 'Limpeza e gratidão · Lua cheia';
UPDATE products SET summary = 'Limpeza de espaços'              WHERE slug = 'molho-de-salvia'    AND summary = 'Limpeza de espaços · Lua minguante';
UPDATE products SET summary = 'Adivinhação e clareza'           WHERE slug = 'tarot-da-casa'      AND summary = 'Adivinhação e clareza · Lua nova';
UPDATE products SET summary = 'Registo de feitiços'             WHERE slug = 'grimorio-em-branco' AND summary = 'Registo de feitiços · Lua nova';

-- ---------------------------------------------------------------------------
-- A existência em armazém.
--
-- **Doze é um número inventado, e tem de ser corrigido antes de abrir.** Está
-- aqui porque a alternativa era pior: com zero, os oito cartões diziam
-- «Esgotado» e a loja da maquete não se via. Um zero também é uma afirmação — a
-- de que não há nada —, e essa estava a ser feita sem ninguém a ter dito.
--
-- Só mexe no que ainda está a zero: quem já tiver contado o que tem no Covil
-- não o vê apagado por esta linha.
-- ---------------------------------------------------------------------------
UPDATE products SET stock = 12 WHERE stock = 0;
