-- ---------------------------------------------------------------------------
-- A loja da Casa: as três categorias e os oito produtos da maquete.
--
-- Os nomes, as intenções e os preços são os que a maquete aprovada já tinha. Não
-- é conteúdo definitivo — é o que a casa vai editar em Loja —, mas é conteúdo a
-- sério e não «Lorem ipsum»: um site semeado com texto falso é um site que
-- ninguém consegue avaliar.
--
-- **O dinheiro está em cêntimos**, como em toda a loja: 1400 e não 14,00. Um
-- preço nunca é um número com vírgula — 0,1 + 0,2 não é 0,3 em binário, e uma
-- loja que arredonda uma fracção de cêntimo para o lado errado em cada linha
-- acaba a discordar do Stripe sobre o que foi cobrado. O Stripe recebe inteiros
-- pela mesma razão, o que também quer dizer que não há conversão nenhuma na
-- fronteira.
--
-- **O stock vai a zero de propósito.** Um produto sem stock mostra-se na montra e
-- diz «esgotado» em vez de se deixar comprar — e é isso que se quer enquanto a
-- casa não contar o que tem na prateleira. Pôr um número inventado aqui era
-- prometer o que ninguém verificou.
--
-- O que **não** está aqui são as fotografias dos produtos: entram pelo
-- backoffice, em Loja > Produtos, porque não há nenhuma na maquete — os cartões
-- dela desenhavam cada produto num canvas, à mão, em vez de o fotografarem.
-- Enquanto não houver fotografia, o cartão sai só com o nome e o preço, que é o
-- que a grelha sabe fazer.
-- ---------------------------------------------------------------------------

-- As três famílias, pela ordem da maquete.
INSERT INTO product_categories (id, locale, slug, name, description, sort_order, is_published) VALUES
    (1, 'pt', 'velas-e-oleos',      'Velas & Óleos',
        'Velas de intenção e óleos preparados à mão, cada um no seu tempo da lua.', 1, 1),
    (2, 'pt', 'cristais-e-amuletos', 'Cristais & Amuletos',
        'Pedras em bruto, amuletos e kits, limpos e consagrados antes de seguirem.', 2, 1),
    (3, 'pt', 'ervas-e-grimorios',  'Ervas & Grimórios',
        'Ervas para queimar, baralhos e cadernos em branco para registar o que se faz.', 3, 1);

-- ---------------------------------------------------------------------------
-- Os oito produtos.
--
-- A fase da lua em que cada um é preparado está no `summary`, à frente da
-- intenção, porque é isso que a maquete mostra no cartão e é o que distingue
-- estes produtos de uma loja de velas qualquer. Fica no texto e não numa coluna
-- própria: é uma frase que a casa escreve, não um valor que o site calcula.
-- ---------------------------------------------------------------------------
INSERT INTO products (id, locale, slug, name, summary, description, price_cents, stock, sort_order, is_published) VALUES
    (1, 'pt', 'vela-de-intencao', 'Vela de Intenção',
        'Prosperidade e caminhos abertos · Lua crescente',
        '<p>Vela preparada em lua crescente, para o que está a começar: um trabalho novo, uma mudança de casa, um caminho que se quer ver abrir. Vem com a intenção escrita à mão.</p>',
        1400, 0, 1, 1),

    (2, 'pt', 'oleo-lua-negra', 'Óleo Lua Negra',
        'Proteção e corte de laços · Lua minguante',
        '<p>Óleo de lua minguante, para o que se quer ver partir. Usa-se em pequena quantidade, nos pulsos e na nuca, antes de entrar onde não se quer levar nada para casa.</p>',
        1800, 0, 2, 1),

    (3, 'pt', 'ametista-bruta', 'Ametista Bruta',
        'Intuição e sono tranquilo · Lua cheia',
        '<p>Pedra em bruto, sem polimento, limpa em água e sal e deixada uma noite de lua cheia. Fica à cabeceira ou na mão, enquanto se lê.</p>',
        2600, 0, 3, 1),

    (4, 'pt', 'amuleto-crescente', 'Amuleto Crescente',
        'Proteção em viagem · Lua nova',
        '<p>Amuleto de lua nova, para levar ao colo ou no bolso de quem anda na estrada. Preparado para uma pessoa só — diga-nos para quem é.</p>',
        3200, 0, 4, 1),

    (5, 'pt', 'kit-lua-cheia', 'Kit Lua Cheia',
        'Limpeza e gratidão · Lua cheia',
        '<p>Vela, ervas, sal e o ritual escrito à mão para a noite de lua cheia. Preparado em pequenas quantidades, a cada ciclo.</p>',
        3800, 0, 5, 1),

    (6, 'pt', 'molho-de-salvia', 'Molho de Sálvia',
        'Limpeza de espaços · Lua minguante',
        '<p>Sálvia branca atada à mão, para fumigar uma casa nova ou uma casa onde ficou alguma coisa. Queima-se com a janela aberta.</p>',
        900, 0, 6, 1),

    (7, 'pt', 'tarot-da-casa', 'Tarot da Casa',
        'Adivinhação e clareza · Lua nova',
        '<p>O baralho da Casa, 78 cartas, com o livrinho das leituras de três cartas e da Cruz Celta. É o mesmo que está na mesa nas consultas.</p>',
        4200, 0, 7, 1),

    (8, 'pt', 'grimorio-em-branco', 'Grimório em Branco',
        'Registo de feitiços · Lua nova',
        '<p>Caderno cosido à mão, papel grosso, sem linhas. Para escrever o que se fez, quando, e o que aconteceu depois — que é a parte de que todos se esquecem.</p>',
        4800, 0, 8, 1);

-- Que produto está em que família.
INSERT INTO product_category_map (product_id, category_id) VALUES
    (1, 1), (2, 1),
    (3, 2), (4, 2), (5, 2),
    (6, 3), (7, 3), (8, 3);
