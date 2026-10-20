-- ---------------------------------------------------------------------------
-- Três páginas que o desenho novo trouxe: A Casa, os Contactos e o Legal.
--
-- São páginas do CMS e não vistas escritas à mão, de propósito: o que lá está é
-- texto da Casa, e texto da Casa muda sem programador. Cada uma é feita dos
-- blocos que já existiam mais três novos — `perguntas`, `principios` e
-- `contacto`. Ver config/blocks.php.
--
-- **Onde a maquete tem um `[x]` fica um `[x]`.** O ano em que o Zé pegou no
-- primeiro baralho, o NIF, a morada, a entidade RAL — nada disso foi dito, e
-- semear um valor inventado é pior do que a falta. Para saber o que falta
-- escrever, procure por `[`.
--
-- **O texto legal foi adaptado ao que o site faz, e não copiado.** A maquete
-- escreve-o a descrever um site com MB WAY, Multibanco, área de cliente e
-- etiqueta pré-paga dos CTT — nada disso existe. Um texto legal que promete
-- meios de pagamento que a loja não tem é uma promessa que a Casa não sabe que
-- fez. As frases que descreviam essas coisas foram reescritas para o que há, e
-- o resto ficou como o designer o escreveu.
--
-- **Continua a ser texto por rever.** É o rascunho do designer, não é uma peça
-- jurídica: antes de abrir ao público tem de ser lido por quem responde por
-- ele.
-- ---------------------------------------------------------------------------

INSERT INTO pages (id, locale, slug, menu, title, content, meta_description, is_published) VALUES
    (2, 'pt', 'a-casa', 'A Casa', 'O Zé', '',
        'Quem lê as cartas na Casa de Zé: o percurso, como trabalha, e o que costumam perguntar-lhe.', 1),
    (3, 'pt', 'contactos', 'Contactos', 'Contactos', '',
        'Falar com a Casa de Zé: morada, horário e o formulário para escrever.', 1),
    (4, 'pt', 'legal', '', 'Legal', '',
        'Termos e condições, privacidade, cookies, trocas e devoluções e resolução de litígios.', 1);

-- ---------------------------------------------------------------------------
-- A Casa.
-- ---------------------------------------------------------------------------
INSERT INTO page_sections (id, page_id, locale, type, anchor, eyebrow, heading, heading_level, body, image, image_alt, cta_label, cta_url, options, sort_order, is_published) VALUES

    -- O bloco do Zé serve-o inteiro: o retrato, a prosa, e as duas listas do
    -- que faz e do que não faz. É o mesmo que está na porta de entrada, com
    -- outro texto — e é para isso que um bloco serve.
    (20, 2, 'pt', 'ze', '', 'Quem está à mesa', 'O Zé', 1,
        '<p>[Uma frase do Zé, na primeira pessoa, sobre porque faz este trabalho.]</p><p>Lê tarot e Baralho Cigano, prepara trabalhos espirituais e faz à mão as peças da loja. Recebe na Casa e à distância, para Portugal e para fora.</p>',
        '/assets/img/ze.png', 'O Zé, à mesa', 'Marcar consulta', '/consultas',
        NULL, 1, 1),

    (21, 2, 'pt', 'principios', 'percurso', 'Percurso', 'Como cheguei aqui', 2,
        NULL, '', '', '', '', NULL, 2, 1),

    (22, 2, 'pt', 'principios', '', 'Como trabalho', 'Quatro coisas que não mudam', 2,
        NULL, '', '', '', '', NULL, 3, 1),

    (23, 2, 'pt', 'texto', 'a-casa', 'A Casa', 'Uma mesa, uma vela e tempo para ti', 2,
        '<p>[Descrição do espaço: onde fica, como é a sala das consultas e a loja.]</p>',
        '', '', 'Como chegar', '/contactos', NULL, 4, 1),

    (24, 2, 'pt', 'testemunhos', '', 'Quem passou pela Casa', 'Palavras de quem voltou', 2,
        NULL, '', '', '', '', NULL, 5, 1),

    (25, 2, 'pt', 'perguntas', 'perguntas', 'Perguntas', 'O que costumam perguntar ao Zé', 2,
        NULL, '', '', '', '', NULL, 6, 1),

-- ---------------------------------------------------------------------------
-- Os contactos.
-- ---------------------------------------------------------------------------
    (30, 3, 'pt', 'contacto', 'contacto', 'Falar com a Casa', 'Contactos', 1,
        '<p>Escreve, e a Casa responde. Para marcar uma consulta há a <a href="/consultas">agenda</a>; para tudo o resto, é por aqui.</p>',
        '', '', '', '', NULL, 1, 1),

-- ---------------------------------------------------------------------------
-- O legal.
--
-- Um bloco de texto por documento, cada um com a sua âncora — são as âncoras
-- que o rodapé da porta de entrada já usa: #termos, #privacidade, #cookies,
-- #devolucoes, #litigios.
-- ---------------------------------------------------------------------------
    (40, 4, 'pt', 'texto', 'termos', '', 'Termos e condições', 1,
        '<h3>Quem somos</h3><p>[Nome da empresa ou empresário], com o NIF [000 000 000] e sede em [morada], explora a loja online e o serviço de consultas Casa de Zé.</p><h3>Encomendas</h3><p>A encomenda fica confirmada depois do pagamento. Enviamos a confirmação por e-mail com o resumo da encomenda.</p><h3>Preços e pagamento</h3><p>Os preços incluem IVA à taxa em vigor. O pagamento é feito na página segura de quem o recebe, fora deste site.</p><h3>Envios</h3><p>Enviamos para Portugal Continental, Madeira e Açores, em embalagem discreta. Os prazos e as tarifas estão no checkout.</p><h3>Consultas</h3><p>As consultas marcam-se online e pagam-se à chegada, ou combina-se na conversa. Podes desmarcar sem custo até [x] horas antes.</p><h3>Natureza dos serviços</h3><p>As consultas e os trabalhos espirituais têm natureza espiritual e não substituem aconselhamento médico, psicológico, jurídico ou financeiro. Não garantimos resultados. Os serviços destinam-se a maiores de 18 anos.</p>',
        '', '', '', '', NULL, 1, 1),

    (41, 4, 'pt', 'texto', 'privacidade', '', 'Política de privacidade', 2,
        '<h3>Responsável pelo tratamento</h3><p>[Nome da empresa], NIF [000 000 000]. Para qualquer pedido sobre os teus dados, escreve para [email].</p><h3>Que dados recolhemos</h3><p>Nome, e-mail, telemóvel, morada de entrega, NIF para fatura e o histórico de encomendas e consultas. A pergunta que escreves ao marcar uma consulta só é lida pelo Zé.</p><h3>Para quê</h3><p>Para tratar das encomendas e das marcações e para responder ao que escreves. Não vendemos os teus dados.</p><h3>Durante quanto tempo</h3><p>As faturas são guardadas durante o prazo exigido pela lei fiscal. O resto é apagado quando deixa de ser preciso.</p><h3>Os teus direitos</h3><p>Podes pedir acesso, correção, portabilidade ou apagamento dos teus dados, por e-mail. Podes também apresentar queixa à CNPD.</p>',
        '', '', '', '', NULL, 2, 1),

    (42, 4, 'pt', 'texto', 'cookies', '', 'Política de cookies', 2,
        '<h3>O que são</h3><p>Pequenos ficheiros guardados no teu navegador para o site funcionar.</p><h3>Essenciais</h3><p>Guardam o cesto, a sessão e a vaga reservada enquanto marcas. Estão sempre activos.</p><h3>E mais nada</h3><p>Este site não usa cookies de estatística nem de publicidade. Se um dia usar, esta página di-lo-á antes.</p>',
        '', '', '', '', NULL, 3, 1),

    (43, 4, 'pt', 'texto', 'devolucoes', '', 'Trocas e devoluções', 2,
        '<h3>Prazo</h3><p>Tens 14 dias a contar da entrega para devolver um produto, sem precisares de dar motivo.</p><h3>Condições</h3><p>O produto deve estar por abrir e na embalagem original. Por razões de higiene, ervas abertas, óleos e velas usadas não podem ser devolvidos.</p><h3>Como devolver</h3><p>Escreve-nos pelos <a href="/contactos">Contactos</a> com o número da encomenda, e dizemos-te como enviar.</p><h3>Reembolso</h3><p>Reembolsamos no método de pagamento original até 14 dias depois de recebermos o produto.</p><h3>Consultas</h3><p>Uma consulta desmarcada com a antecedência indicada nos Termos não tem custo. Depois de realizada, deixa de ser possível desistir.</p>',
        '', '', '', '', NULL, 4, 1),

    (44, 4, 'pt', 'texto', 'litigios', '', 'Resolução de litígios', 2,
        '<h3>Livro de Reclamações Eletrónico</h3><p>Podes apresentar reclamação em <a href="https://www.livroreclamacoes.pt" rel="noopener">livroreclamacoes.pt</a>.</p><h3>Resolução alternativa de litígios</h3><p>Em caso de litígio, podes recorrer a [Entidade RAL], [morada e sítio da entidade]. Mais informação no Portal do Consumidor, em consumidor.gov.pt.</p><h3>Plataforma europeia</h3><p>Para compras feitas a partir de outro país da União Europeia, podes usar a plataforma de resolução de litígios em linha da Comissão Europeia.</p>',
        '', '', '', '', NULL, 5, 1);

-- ---------------------------------------------------------------------------
-- Os itens dos blocos.
-- ---------------------------------------------------------------------------
INSERT INTO page_section_items (section_id, title, subtitle, body, rating, sort_order, is_published) VALUES
    -- O que o Zé faz, e o que não faz.
    (20, 'Leituras de tarot e Baralho Cigano',                     '', NULL, 1, 1, 1),
    (20, 'Trabalhos de proteção, limpeza, caminhos e amor',        '', NULL, 1, 2, 1),
    (20, 'Peças consagradas, feitas à mão em pequenas quantidades', '', NULL, 1, 3, 1),
    (20, 'Acompanhamento depois da consulta',                      '', NULL, 1, 4, 1),
    (20, 'Não prometo resultados nem prazos',                      '', NULL, 0, 5, 1),
    (20, 'Não substituo médico, psicólogo nem advogado',           '', NULL, 0, 6, 1),

    -- O percurso. Os anos são `[Ano]` porque ninguém os disse.
    (21, '[Ano] · O primeiro baralho',      '', 'Como o Zé chegou ao tarot e quem o ensinou.', 0, 1, 1),
    (21, '[Ano] · Os anos de aprendizagem', '', 'Onde estudou, com quem trabalhou e o que aprendeu sobre ervas, velas e rituais.', 0, 2, 1),
    (21, '[Ano] · A Casa abre a porta',     '', 'Quando e porquê nasceu a Casa de Zé.', 0, 3, 1),
    (21, 'Hoje · À mesa e no Grimório',     '', 'Consultas presenciais e online, a loja feita à mão e sessões gratuitas no YouTube para quem quer aprender.', 0, 4, 1),

    -- Quatro coisas que não mudam.
    (22, 'Escuta primeiro', '', 'Cada consulta começa com uma conversa sobre o que te trouxe. As cartas vêm depois.', 0, 1, 1),
    (22, 'Diz o que vê',    '', 'Sem rodeios e sem dramatizar. Se uma leitura não for clara, o Zé diz-to.', 0, 2, 1),
    (22, 'Tempo certo',     '', 'Os trabalhos e as peças da loja seguem o calendário lunar. Nada é feito à pressa.', 0, 3, 1),
    (22, 'Preço fechado',   '', 'Sabes quanto custa antes de começar. Nos trabalhos, recebes a proposta por escrito.', 0, 4, 1),

    -- Os testemunhos desta página. São outros que os da porta de entrada.
    (24, 'C. M.', 'Braga',   'Senti-me ouvida desde o primeiro minuto. A leitura foi clara e sem rodeios.', 0, 1, 1),
    (24, 'L. P.', 'Paris',   'Fiz online a partir de Paris e correu muito bem.', 0, 2, 1),
    (24, 'J. R.', 'Coimbra', 'Gostei da calma com que o Zé explica cada carta.', 0, 3, 1),

    -- O que costumam perguntar ao Zé.
    (25, 'Preciso de acreditar para a consulta funcionar?', '', 'Não. Vens com a tua pergunta e a abertura que tiveres. O Zé explica cada carta e o que ela pode significar para ti.', 0, 1, 1),
    (25, 'Os trabalhos espirituais têm garantia?',          '', 'Não há garantias de resultado. O Zé explica o que o trabalho faz, quanto tempo leva e o que depende de ti. Se não fizer sentido, diz-to.', 0, 2, 1),
    (25, 'O que acontece ao que digo na consulta?',         '', 'Fica entre ti e o Zé. As consultas online não são gravadas e as notas ficam guardadas só para dar continuidade, se voltares.', 0, 3, 1),
    (25, 'Posso visitar a Casa sem marcação?',              '', 'A loja física abre nos horários indicados em Contactos. As consultas presenciais são sempre com marcação.', 0, 4, 1),

    -- Os assuntos do formulário de contacto.
    (30, 'Uma consulta',          '', NULL, 0, 1, 1),
    (30, 'Uma encomenda',         '', NULL, 0, 2, 1),
    (30, 'Um trabalho espiritual', '', NULL, 0, 3, 1),
    (30, 'Outra coisa',           '', NULL, 0, 4, 1);

-- ---------------------------------------------------------------------------
-- Os menus.
--
-- «A Casa» e «Contactos» apontavam para âncoras da porta de entrada porque não
-- havia páginas. Agora há. O Legal não entra no menu: vive no rodapé, que já
-- lhe aponta por âncora.
-- ---------------------------------------------------------------------------
UPDATE menu_items SET url = '/a-casa'    WHERE url = '/#ze';
UPDATE menu_items SET url = '/contactos' WHERE url = '/#contacto';
UPDATE menu_items SET url = '/consultas' WHERE url = '/#consultas';
