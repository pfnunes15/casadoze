-- ---------------------------------------------------------------------------
-- Portes grátis acima de 40 €.
--
-- **Este número o desenho diz.** A barra do topo da loja escreve «Portes
-- grátis acima de 40 € no Continente» e o cesto conta quanto falta para lá
-- chegar — ver `docs/maquete/Loja.dc.html`. É política da Casa, como os 23% de
-- IVA que já estão semeados, e não um valor inventado por este ficheiro.
--
-- **As zonas e os escalões continuam vazios, de propósito.** Quanto custa
-- enviar para o Continente, para as ilhas e para fora o desenho escreve `[x]`,
-- porque ninguém o disse ainda. Semear um preço de portes era prometer um
-- preço que a Casa não sabe que prometeu — o mesmo erro do stock a 12.
--
-- Enquanto não houver uma zona com escalões a loja mostra-se e não deixa
-- finalizar a compra, e diz porquê. Ver Shipping::anyConfigured.
-- ---------------------------------------------------------------------------
INSERT INTO settings (`key`, locale, value) VALUES
    ('shipping.free_from', 'pt', '40,00')
ON DUPLICATE KEY UPDATE value = value;
