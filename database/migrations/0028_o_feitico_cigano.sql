-- ---------------------------------------------------------------------------
-- Feitiço Cigano — a vela das três intenções, agora com a fotografia.
--
-- **É o mesmo artigo que entrou na 0027 com o nome errado.** Preço, descrição e
-- fotografia são os mesmos; o que mudou foi o nome, que no selo da embalagem é
-- Feitiço Cigano e não Feitiço Ancestral. Em vez de o criar outra vez e deixar
-- dois artigos iguais na montra, renomeia-se o que já lá está.
--
-- O `slug` acompanha o nome, e quem tiver a morada antiga guardada vai dar a um
-- 404. É o preço de corrigir um nome, e o artigo esteve no ar menos de um dia.
--
-- «Feitiço Cigano» e não «Feitiço cigano»: os outros oito estão todos em
-- maiúscula inicial — «Vela de Intenção», «Kit Lua Cheia» —, e o nome aparece
-- a par deles na montra.
--
-- A fotografia: `/assets/img/feitico-cigano.jpg` não existe em disco, e é de
-- propósito. App\Services\Imagens tira a extensão e procura as variantes WebP e
-- AVIF ao lado — as mesmas cinco larguras do resto do site, feitas com o
-- bin/variantes-de-imagem.sh. O original fica fora do repositório.
-- ---------------------------------------------------------------------------
UPDATE products
   SET name      = 'Feitiço Cigano',
       slug      = 'feitico-cigano',
       image     = '/assets/img/feitico-cigano.jpg',
       image_alt = 'Três velas unidas — rosa, branca e azul — enroladas em ervas, na embalagem do Covil'
 WHERE slug = 'feitico-ancestral';

-- Em Velas & Óleos, que é a categoria das velas. Já lá estava desde a 0027;
-- isto é para a migração se bastar a si própria numa base de dados onde a
-- ligação se tenha perdido.
INSERT IGNORE INTO product_category_map (product_id, category_id)
SELECT p.id, c.id
  FROM products p
  JOIN product_categories c ON c.slug = 'velas-e-oleos'
 WHERE p.slug = 'feitico-cigano';
