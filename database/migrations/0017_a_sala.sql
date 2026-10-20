-- ---------------------------------------------------------------------------
-- A Sala: quem lê as cartas na Casa.
--
-- O desenho novo traz uma equipa — `docs/maquete/Sala.dc.html` —, e até aqui o
-- site inteiro assumia uma pessoa só. Isto é a tabela dessa equipa.
--
-- **Os nomes da maquete estão entre parênteses rectos, menos o do Zé.** O
-- designer escreveu `[Helena]`, `[Marisa]` e `[Inês]`, que é a maneira de dizer
-- que ainda não há nomes. Ficam assim, pela mesma regra que o resto do site:
-- um nome inventado é um nome que alguém tem de descobrir que é falso.
--
-- **As especialidades, os formatos e as línguas são texto e não tabelas.** São
-- listas curtas, uma por linha, como os assuntos de um pedido em `enquiries`.
-- Três tabelas de junção para guardar «Tarot» e «Português» era um esquema a
-- explicar-se a si próprio em vez de ao problema. Os filtros da página são
-- construídos do que lá estiver escrito.
--
-- **Sem estrelas nem contagem de avaliações.** A maquete põe «4,9 · 212
-- avaliações» em cada perfil. Não há tabela de avaliações, e inventar uma média
-- é pôr no site um número que ninguém disse — o mesmo erro do stock a 12.
--
-- **A agenda continua a ser da consulta e não da pessoa.** Marcar é escolher
-- uma consulta e uma hora, como antes. Dar agenda a cada taróloga é acrescentar
-- `practitioner_id` às vagas, ao gerador e ao backoffice das marcações, e isso
-- é mexer no motor que já trabalha — fica para quando a equipa existir a sério.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS practitioners (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    locale        CHAR(5)      NOT NULL DEFAULT 'pt',
    slug          VARCHAR(160) NOT NULL,
    name          VARCHAR(190) NOT NULL,
    role          VARCHAR(190) NOT NULL DEFAULT '',
    quote         VARCHAR(500) NOT NULL DEFAULT '',
    bio           MEDIUMTEXT   NULL,
    deck          VARCHAR(190) NOT NULL DEFAULT '',
    sign          VARCHAR(60)  NOT NULL DEFAULT '',
    -- Anos à mesa. Zero quer dizer «não dito», e a página não o mostra.
    years         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    image         VARCHAR(255) NOT NULL DEFAULT '',
    image_alt     VARCHAR(255) NOT NULL DEFAULT '',
    -- Uma por linha.
    specialties   TEXT NULL,
    formats       TEXT NULL,
    languages     TEXT NULL,
    is_published  TINYINT(1) NOT NULL DEFAULT 1,
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY practitioners_slug (locale, slug),
    KEY practitioners_order (locale, is_published, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quem dá que consulta. Uma pessoa dá várias e uma consulta é dada por várias,
-- por isso é uma tabela de junção a sério — ao contrário das listas acima, esta
-- aponta para linhas que existem noutro sítio.
CREATE TABLE IF NOT EXISTS practitioner_consultations (
    practitioner_id INT UNSIGNED NOT NULL,
    consultation_id INT UNSIGNED NOT NULL,
    sort_order      INT NOT NULL DEFAULT 0,
    PRIMARY KEY (practitioner_id, consultation_id),
    KEY pc_consultation (consultation_id),
    CONSTRAINT pc_practitioner FOREIGN KEY (practitioner_id)
        REFERENCES practitioners (id) ON DELETE CASCADE,
    CONSTRAINT pc_consultation_fk FOREIGN KEY (consultation_id)
        REFERENCES consultations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- A equipa da maquete. O Zé é real; os outros três são o lugar marcado.
-- ---------------------------------------------------------------------------
INSERT INTO practitioners (id, locale, slug, name, role, quote, bio, deck, sign, years, image, image_alt, specialties, formats, languages, sort_order) VALUES
    (1, 'pt', 'ze', 'Zé', 'Fundador da Casa',
     '[Frase do Zé sobre a forma como lê as cartas.]',
     '<p>Abriu a Casa e é quem prepara os trabalhos espirituais. Lê com calma e diz o que vê, sem rodeios.</p>',
     'Tarot da Casa e Baralho Cigano', 'Escorpião', 20,
     '/assets/img/ze.png', 'O Zé, à mesa',
     'Tarot\nBaralho Cigano\nEspiritualidade', 'Online\nPresencial', 'Português\nEspañol', 1),

    (2, 'pt', 'helena', '[Helena]', 'Taróloga',
     '[Frase da taróloga.]',
     '<p>[Percurso: há quantos anos lê, como chegou ao tarot, o que mais gosta de trabalhar.]</p>',
     'Rider-Waite-Smith', 'Peixes', 12, '', '',
     'Tarot\nAmor e relações', 'Online', 'Português\nEnglish', 2),

    (3, 'pt', 'marisa', '[Marisa]', 'Taróloga e médium',
     '[Frase da taróloga.]',
     '<p>[Percurso: formação, especialidade em mediunidade, como conduz a consulta.]</p>',
     'Tarot de Marselha', 'Caranguejo', 15, '', '',
     'Tarot\nMediunidade\nEspiritualidade', 'Online\nPresencial', 'Português', 3),

    (4, 'pt', 'ines', '[Inês]', 'Cartomante',
     '[Frase da cartomante.]',
     '<p>[Percurso: há quantos anos lê e o que mais gosta de trabalhar.]</p>',
     'Baralho Cigano', '', 8, '', '',
     'Baralho Cigano\nTrabalho e caminhos', 'Online', 'Português', 4);

-- Quem dá que consulta. O Zé dá as quatro, que é o que o site faz hoje; as
-- outras três ficam com as de tarot, que é o que os perfis da maquete dizem.
INSERT INTO practitioner_consultations (practitioner_id, consultation_id, sort_order)
SELECT 1, id, sort_order FROM consultations WHERE locale = 'pt';

INSERT INTO practitioner_consultations (practitioner_id, consultation_id, sort_order)
SELECT 2, id, sort_order FROM consultations WHERE locale = 'pt' AND slug LIKE 'tarot-%';

INSERT INTO practitioner_consultations (practitioner_id, consultation_id, sort_order)
SELECT 3, id, sort_order FROM consultations WHERE locale = 'pt' AND slug LIKE 'tarot-%';

INSERT INTO practitioner_consultations (practitioner_id, consultation_id, sort_order)
SELECT 4, id, sort_order FROM consultations WHERE locale = 'pt' AND slug = 'baralho-cigano';
