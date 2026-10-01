-- ---------------------------------------------------------------------------
-- O site: a porta de entrada, os menus e as definições.
--
-- O conteúdo é o da maquete aprovada, e não «Lorem ipsum»: um site semeado com
-- texto falso é um site que ninguém consegue avaliar — não se vê se um título é
-- comprido de mais, nem se uma frase cabe no cartão. Não é conteúdo definitivo: é
-- o que a Casa vai editar em Páginas.
--
-- **Onde a maquete tinha um `[x]` fica um `[x]`.** Os prazos de envio, o NIF e os
-- preços das consultas estavam escritos assim na maquete porque ninguém os tinha
-- dito ainda. Semeá-los com números inventados era pior do que a falta: um prazo
-- de envio errado é uma promessa que a Casa não sabe que fez. Para saber o que
-- falta escrever, procure por `[`.
-- ---------------------------------------------------------------------------

-- ---------------------------------------------------------------------------
-- As definições da Casa.
--
-- **Todas no idioma de instalação**, o 'pt' do `app.locale`. Uma definição
-- «global» — ver o `'global' => true` em config/settings.php — não é guardada
-- sem língua nenhuma: é guardada uma vez, no idioma base, que é onde o
-- Setting::all() a vai ler sempre. Ver Setting::all e Locales::base.
--
-- A diferença entre uma global e as outras não está portanto na linha: está em
-- quem a pode mudar. Uma global muda-se uma vez e vale em toda a parte; uma
-- normal escreve-se por língua, e a que não estiver escrita numa língua mostra
-- a do idioma base através dela.
-- ---------------------------------------------------------------------------
INSERT INTO settings (`key`, locale, value) VALUES
    ('site.name',    'pt', 'Casa de Zé'),
    ('site.logo',    'pt', '/assets/img/logotipo.png'),

    -- As cinco línguas da maquete, e o português como a principal.
    ('i18n.locales', 'pt', 'pt,en,fr,de,es'),
    ('i18n.default', 'pt', 'pt'),

    ('contact.address', 'pt', 'O Covil\n[rua e número]\n[código postal] [localidade]'),
    ('contact.email',   'pt', '[email]@casadoze.pt'),
    ('contact.phone',   'pt', '[+351 000 000 000]'),

    -- O IVA do continente. A Casa muda-o em Definições se estiver nas ilhas.
    ('shop.vat_rate', 'pt', '23'),

    -- Quantas horas antes é que ainda se desmarca pelo site. Vinte e quatro,
    -- que é a mesma antecedência com que as consultas se marcam.
    ('readings.cancel_hours', 'pt', '24'),

    ('site.tagline',       'pt', 'Velas, óleos, cristais e ervas preparados à mão, e leituras de tarot com o Zé.'),
    ('contact.hours',      'pt', 'Consultas à noite, de terça a sexta\nSábados à tarde'),
    ('shipping.discreet',  'pt', 'Sem referência ao conteúdo no exterior'),
    ('readings.online_note', 'pt', 'A sessão é por videochamada. A ligação chega-te por e-mail pouco antes da hora — não precisas de instalar nada.'),
    -- O botão «Falar com o Zé», dos trabalhos espirituais. A âncora do rodapé
    -- enquanto não houver WhatsApp escrito nas Definições.
    ('readings.talk_url',  'pt', '#contacto');

-- ---------------------------------------------------------------------------
-- A porta de entrada.
--
-- O slug é 'home' e não vazio: é a constante HOME_SLUG do PageController, e é
-- por ela que a raiz do site é procurada. Uma página de slug vazio existiria e
-- não seria alcançável por endereço nenhum.
-- ---------------------------------------------------------------------------
-- O `content` fica vazio e não é esquecimento: uma página composta não tem texto
-- próprio — tem blocos. A coluna é da altura em que uma página era um campo de
-- HTML, e continua a existir para as páginas que o CMS cria por dentro.
INSERT INTO pages (id, locale, slug, menu, title, content, meta_description, is_published) VALUES
    (1, 'pt', 'home', '', 'Casa de Zé', '',
        'Velas, óleos, cristais e ervas preparados à mão no tempo da lua, e leituras de tarot com o Zé — online ou presencialmente.',
        1);

-- ---------------------------------------------------------------------------
-- Os blocos da porta de entrada, pela ordem da maquete.
--
-- A abertura, a montra da loja, as consultas, quem está por trás, os testemunhos
-- e as garantias. Os blocos da maquete que ainda não existem — o calendário
-- lunar, o tarot interactivo, a roda dos signos e o grimório dos vídeos — não
-- estão semeados porque não estão escritos: um bloco sem parcial não aparece
-- sequer no selector, e uma linha aqui para um bloco que não desenha era uma
-- página com um buraco e um aviso no registo. Ver Blocks::renderable.
-- ---------------------------------------------------------------------------
INSERT INTO page_sections (id, page_id, locale, type, anchor, eyebrow, heading, heading_level, body, image, image_alt, cta_label, cta_url, options, sort_order, is_published) VALUES

    (1, 1, 'pt', 'abertura', '', '', 'Casa de Zé', 1,
        'Velas, óleos, cristais e ervas preparados à mão no Covil, no tempo certo da lua.',
        '/assets/img/abertura.jpg', 'A mesa da Casa de Zé, com velas acesas',
        'Marcar consulta', '#consultas',
        '{"cta2_label":"Entrar na loja","cta2_url":"#loja","embers":70}',
        1, 1),

    (2, 1, 'pt', 'loja-montra', 'loja', 'Loja', 'Preparado à mão, no tempo da lua', 2,
        'Nada sai da Casa sem ser consagrado antes de seguir para ti.',
        '', '', 'Ver toda a loja', '',
        '{"limit":8}',
        2, 1),

    (3, 1, 'pt', 'consultas', 'consultas', 'Consultas', 'Senta-te à mesa da Casa', 2,
        '<p>Leituras e trabalhos feitos pelo Zé, presencialmente ou à distância. Cada consulta começa com uma conversa sobre o que te trouxe até aqui.</p>',
        '', '', '', '',
        '{"agenda":"inline"}',
        3, 1),

    (4, 1, 'pt', 'ze', 'ze', 'Quem está por trás', 'O Zé', 2,
        '<p>Leio cartas há [x] anos, e aprendi a fazê-lo onde se aprende: à mesa de alguém que já o fazia antes de mim. O Covil é onde preparo o que sai daqui — e é onde me sento, de frente para quem vem.</p>',
        '/assets/img/ze.png', 'O Zé, à mesa', 'Falar comigo', '#contacto',
        NULL, 4, 1),

    (5, 1, 'pt', 'testemunhos', 'testemunhos', 'Quem passou pela Casa', 'Palavras de quem voltou', 2,
        NULL, '', '', '', '', NULL, 5, 1),

    (6, 1, 'pt', 'garantias', '', '', '', 2,
        NULL, '', '', '', '', NULL, 6, 1);

-- ---------------------------------------------------------------------------
-- Os itens dos blocos que repetem.
--
-- `rating` no bloco do Zé é o que separa as duas listas: 1 é «o que faço», 0 é
-- «o que não faço». A segunda lista é a que faz a clareza de que uma casa destas
-- precisa, e é por isso que é conteúdo e não uma nota de rodapé.
-- ---------------------------------------------------------------------------
INSERT INTO page_section_items (section_id, title, subtitle, body, rating, sort_order, is_published) VALUES
    -- O que o Zé faz.
    (4, 'Leituras de tarot e Baralho Cigano',            '', NULL, 1, 1, 1),
    (4, 'Trabalhos de abertura de caminhos e proteção',  '', NULL, 1, 2, 1),
    (4, 'Limpezas de espaços e de pessoas',              '', NULL, 1, 3, 1),
    -- O que não faz. Ditas no negativo de propósito: é o que separa a Casa de
    -- quem promete o que não pode prometer.
    (4, 'Não prometo resultados nem prazos',             '', NULL, 0, 4, 1),
    (4, 'Não substituo médicos, advogados nem terapeutas', '', NULL, 0, 5, 1),
    (4, 'Não faço trabalhos contra ninguém',             '', NULL, 0, 6, 1),

    -- Os testemunhos. Iniciais e não nomes: quem conta uma coisa destas não tem
    -- de aparecer com o nome todo.
    (5, 'M.', 'Leitura completa', 'Saí de lá mais leve do que entrei. Não me disse o que eu queria ouvir — disse-me o que eu andava a evitar.', 0, 1, 1),
    (5, 'A. R.', 'Três cartas, online', 'Marquei sem grande fé e fiquei uma hora a pensar no que ele me disse em meia. Voltei no mês seguinte.', 0, 2, 1),
    (5, 'J.', 'Trabalho de proteção', 'O que me deu mais confiança foi ele ter-me dito logo o que não ia fazer.', 0, 3, 1),

    -- As garantias. Os prazos ficam em `[x]` porque a Casa ainda não os disse —
    -- e um prazo de envio inventado é uma promessa que ninguém fez.
    (6, 'Envios', '', 'Continente em [x] dias úteis · Madeira e Açores em [x] dias', 0, 1, 1),
    (6, 'Pagamento seguro', '', 'MB WAY, Multibanco e cartão, pelo Stripe', 0, 2, 1),
    (6, 'Devoluções', '', '14 dias para devolver, como a lei manda', 0, 3, 1),
    (6, 'Embalagem discreta', '', 'Sem referência ao conteúdo no exterior', 0, 4, 1);

-- ---------------------------------------------------------------------------
-- Os menus.
--
-- As âncoras levam a secções da porta de entrada; a loja leva a uma página a
-- sério. É o que a maquete fazia, e continua a valer enquanto a Casa tiver uma
-- página só — no dia em que as consultas tiverem página própria, muda-se o
-- endereço em Menus e mais nada.
-- ---------------------------------------------------------------------------
INSERT INTO menu_items (locale, location, label, url, sort_order, is_active) VALUES
    ('pt', 'header', 'Loja',      '/loja',      1, 1),
    ('pt', 'header', 'Consultas', '/#consultas', 2, 1),
    ('pt', 'header', 'A Casa',    '/#ze',        3, 1),
    ('pt', 'header', 'Contactos', '/#contacto',  4, 1),

    ('pt', 'footer', 'Loja',      '/loja',       1, 1),
    ('pt', 'footer', 'Consultas', '/#consultas', 2, 1),
    ('pt', 'footer', 'A Casa',    '/#ze',        3, 1);
