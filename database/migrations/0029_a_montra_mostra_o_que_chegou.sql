-- ---------------------------------------------------------------------------
-- A montra da porta de entrada passa a abrir pelo que chegou agora.
--
-- A Loja devolve os produtos pela ordem da casa — o `sort_order` — e a montra
-- cortava aos primeiros oito. Uma peça nova entra com a ordem no fim, por isso
-- **nunca chegava à porta de entrada**: o Feitiço Cigano, que é o único artigo
-- com fotografia a sério, era o nono de nove.
--
-- A ordem é uma escolha do bloco e não uma regra escrita no código: a ordem da
-- casa continua a ser a certa para uma montra pensada peça a peça, e é por isso
-- que o campo tem as duas. O que muda aqui é só o que esta secção desta página
-- faz — ver `order` em config/blocks.php.
--
-- JSON_SET e não um valor escrito por cima: a secção já tem o `limit` lá dentro
-- e reescrever o campo inteiro era perdê-lo.
-- ---------------------------------------------------------------------------
UPDATE page_sections
   SET options = JSON_SET(COALESCE(NULLIF(options, ''), '{}'), '$.order', 'recentes')
 WHERE type = 'loja-montra';
