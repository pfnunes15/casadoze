-- ---------------------------------------------------------------------------
-- As ligações legais do rodapé.
--
-- Estavam por preencher porque não havia para onde apontar, e o rodapé não
-- desenha uma ligação para um endereço por escrever — ver views/partials/
-- footer.php. A página `legal` já existe, com uma âncora por documento.
--
-- Ficheiro à parte e não acrescentado ao 0015 porque esse já tinha corrido:
-- uma migração aplicada não volta a correr, e editá-la é escrever uma coisa
-- que só acontece em instalações novas.
-- ---------------------------------------------------------------------------
INSERT INTO settings (`key`, locale, value) VALUES
    ('legal.terms_url',   'pt', '/legal#termos'),
    ('legal.privacy_url', 'pt', '/legal#privacidade')
ON DUPLICATE KEY UPDATE value = IF(value = '', VALUES(value), value);
