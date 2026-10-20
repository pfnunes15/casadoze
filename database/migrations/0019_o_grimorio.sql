-- ---------------------------------------------------------------------------
-- O Grimório: as sessões gratuitas do Zé.
--
-- Até aqui as sessões eram itens de um bloco — três linhas numa página, todas
-- com `[Título da sessão]` e `[mm:ss]` lá dentro, porque nunca foi escrita
-- nenhuma a sério. O desenho novo dá-lhes uma página própria, uma página por
-- sessão, temas e busca, e isso é um tipo de conteúdo e não um bloco.
--
-- **Os itens do bloco desaparecem, e não se perde nada**: eram três lugares
-- marcados. O bloco fica — é ele que põe o Grimório na porta de entrada — mas
-- passa a ler as três sessões mais recentes desta tabela em vez de as ter
-- escritas dentro de si. Uma sessão escrita em dois sítios é uma que fica
-- desactualizada num deles.
--
-- **Sem contagem de visualizações.** A maquete põe «4 820 visualizações» em
-- cada cartão. Isso vem da API do YouTube, que não está ligada, e um número
-- escrito à mão hoje é um número errado amanhã.
--
-- Os capítulos são texto, uma linha por capítulo — «02:15 Preparar o altar».
-- Uma tabela de capítulos para uma lista que se escreve de uma vez e se lê de
-- uma vez era um esquema a mais.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS videos (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    locale        CHAR(5)      NOT NULL DEFAULT 'pt',
    slug          VARCHAR(190) NOT NULL,
    title         VARCHAR(190) NOT NULL,
    theme         VARCHAR(120) NOT NULL DEFAULT '',
    -- O endereço como o editor o copia da barra do browser. Quem o lê é
    -- Admedia\Cms\Video::youtube.
    url           VARCHAR(500) NOT NULL DEFAULT '',
    -- «18:42». Escrita e não lida do YouTube, porque a API não está ligada.
    duration      VARCHAR(12)  NOT NULL DEFAULT '',
    summary       VARCHAR(500) NOT NULL DEFAULT '',
    body          MEDIUMTEXT   NULL,
    -- Uma linha por capítulo: «02:15 Preparar o altar».
    chapters      TEXT NULL,
    -- Qual das oito ilustrações leva, enquanto não houver miniatura.
    drawing       VARCHAR(60)  NOT NULL DEFAULT '',
    published_at  DATE         NULL,
    is_published  TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order    INT          NOT NULL DEFAULT 0,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY videos_slug (locale, slug),
    KEY videos_order (locale, is_published, published_at, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- As peças usadas numa sessão. É a ligação que faz o Grimório vender: quem vê
-- um ritual com sálvia encontra o molho de sálvia a um clique.
CREATE TABLE IF NOT EXISTS video_products (
    video_id   INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (video_id, product_id),
    KEY vp_product (product_id),
    CONSTRAINT vp_video FOREIGN KEY (video_id) REFERENCES videos (id) ON DELETE CASCADE,
    CONSTRAINT vp_product_fk FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- As sessões da maquete.
--
-- Os títulos, as durações e as descrições são as do designer. **O endereço de
-- cada vídeo fica vazio**, porque nenhum foi dado: um vídeo sem endereço
-- desenha a moldura com o botão de ver ao meio e diz que ali vai estar uma
-- sessão, sem fingir que já está.
-- ---------------------------------------------------------------------------
INSERT INTO videos (id, locale, slug, title, theme, duration, summary, chapters, drawing, published_at, sort_order) VALUES
    (1, 'pt', 'ritual-de-lua-cheia-para-gratidao', 'Ritual de lua cheia para gratidão', 'Rituais da lua', '18:42',
     'Um ritual simples para fechar o ciclo com gratidão: limpar o espaço, acender a vela e escrever o que queres agradecer.',
     '00:00 Abertura\n02:15 Preparar o altar\n06:40 Acender a vela\n11:05 A carta de gratidão\n16:30 Fechar o ritual',
     'moon', NULL, 1),

    (2, 'pt', 'como-limpar-a-casa-com-salvia', 'Como limpar a casa com sálvia', 'Ervas e defumação', '12:05',
     'Passo a passo para defumar a casa sem riscos: por onde começar, que divisões abrir e como apagar o molho.',
     '00:00 Abertura\n01:40 Antes de acender\n04:20 Divisão a divisão\n09:30 Apagar e guardar',
     'sage', NULL, 2),

    (3, 'pt', 'tiragem-de-samhain-ao-vivo', 'Tiragem de Samhain ao vivo', 'Sabbats', '54:10',
     '[Descrição da sessão.]',
     '00:00 Abertura',
     'tarot', NULL, 3);

-- As peças de cada sessão, pelo slug: os ids dos produtos podem mudar numa
-- instalação nova, os slugs não.
INSERT INTO video_products (video_id, product_id, sort_order)
SELECT 1, id, 0 FROM products WHERE locale = 'pt' AND slug = 'kit-lua-cheia';
INSERT INTO video_products (video_id, product_id, sort_order)
SELECT 1, id, 1 FROM products WHERE locale = 'pt' AND slug = 'vela-de-intencao';
INSERT INTO video_products (video_id, product_id, sort_order)
SELECT 2, id, 0 FROM products WHERE locale = 'pt' AND slug = 'molho-de-salvia';
INSERT INTO video_products (video_id, product_id, sort_order)
SELECT 3, id, 0 FROM products WHERE locale = 'pt' AND slug = 'tarot-da-casa';

-- Os três lugares marcados que estavam dentro do bloco. O bloco fica e passa a
-- ler a tabela; os itens não têm mais para onde ir.
DELETE i FROM page_section_items i
  JOIN page_sections s ON s.id = i.section_id
 WHERE s.type = 'grimorio';

-- O Grimório entra no menu, a seguir à Sala, que é onde o desenho o põe.
INSERT INTO menu_items (locale, location, label, url, target, style, sort_order, is_active)
SELECT 'pt', 'header', 'Grimório', '/grimorio', '_self', 'link', 27, 1
 WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE location = 'header' AND url = '/grimorio');

INSERT INTO menu_items (locale, location, label, url, target, style, sort_order, is_active)
SELECT 'pt', 'footer', 'Grimório', '/grimorio', '_self', 'link', 27, 1
 WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE location = 'footer' AND url = '/grimorio');
