-- ---------------------------------------------------------------------------
-- A loja volta a ter porta.
--
-- A montra, a ficha, o cesto e o checkout saíram quando o site foi reduzido à
-- porta de entrada. O desenho novo — `docs/maquete/Loja.dc.html` — traz-lhes a
-- vista, e as rotas voltaram em `config/routes.php`.
--
-- Aqui fica só a frase de entrada da montra, que é texto da Casa e não da
-- interface: quem a escreve é quem vende. A da maquete, para a loja não abrir
-- com um parágrafo vazio. A Casa muda-a em Definições > Loja.
-- ---------------------------------------------------------------------------
INSERT INTO settings (`key`, locale, value) VALUES
    ('shop.intro', 'pt',
     'Velas, óleos, cristais e ervas preparados à mão, no tempo certo da lua. Tudo é consagrado antes de seguir para ti.')
ON DUPLICATE KEY UPDATE value = value;
