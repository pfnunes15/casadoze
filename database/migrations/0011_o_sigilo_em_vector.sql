-- ---------------------------------------------------------------------------
-- O sigilo da Casa passa a vector.
--
-- O desenho novo — `docs/maquete/` — usa `sigil.svg` nas treze páginas, onde o
-- antigo usava um PNG. **Não é só peso**, embora 95 KB passem a 8,6: os dois
-- ficheiros têm recortes diferentes. O PNG é 286 por 207 e o vector 96 por 92,
-- e com a mesma largura o vector é 30% mais alto. No separador da ponte isso
-- eram 21px de altura a faltar, e tudo o que vem abaixo dele subia.
--
-- No cabeçalho a caixa é fixa — 54 por 40, com `object-fit: contain` — por isso
-- aqui a diferença é só a de o sigilo encher a caixa em vez de ficar deitado.
--
-- Só mexe no que ainda é o valor semeado: uma Casa que já tenha carregado o seu
-- logótipo no backoffice não o vê trocado por esta linha.
-- ---------------------------------------------------------------------------
UPDATE settings
   SET value = '/assets/img/logotipo.svg'
 WHERE `key` = 'site.logo'
   AND value = '/assets/img/logotipo.png';
