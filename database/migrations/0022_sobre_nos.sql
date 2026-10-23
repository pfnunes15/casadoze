-- ---------------------------------------------------------------------------
-- The about page moves from /a-casa to /sobre-nos.
--
-- The title changed to "Sobre nós" in 0020 and the address did not, which left
-- a page called one thing and reached by another. Safe to move because the site
-- is not public yet — once it is, a moved address needs a redirect from the old
-- one, and there is no redirect table here.
-- ---------------------------------------------------------------------------
UPDATE pages SET slug = 'sobre-nos' WHERE slug = 'a-casa';

UPDATE menu_items SET url = '/sobre-nos', label = 'Sobre nós' WHERE url = '/a-casa';
