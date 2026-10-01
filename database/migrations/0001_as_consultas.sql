-- ---------------------------------------------------------------------------
-- As consultas da Casa, e as marcações delas.
--
-- Cinco tabelas, e a razão de serem cinco:
--
--   consultations            a consulta em si — o que é, quanto dura, quanto custa.
--   consultation_schedules   o horário: «terças e quintas às 21h».
--   consultation_closures    os dias em que não há — férias, luas de guarda.
--   consultation_slots       cada vaga concreta, com dia, hora e lugares tomados.
--   bookings                 quem vem.
--
-- Vem do enoturismo do Barbeito, onde esta forma já está provada, com três
-- diferenças que são deste sítio e estão anotadas onde aparecem: uma consulta é
-- de uma pessoa e não de um grupo, tem um preço que é um número e não uma
-- frase, e pode não se marcar por vaga nenhuma.
--
-- **Porquê uma tabela de vagas e não só o horário.** Um horário sabe dizer que
-- às terças há consulta; não sabe segurar um lugar. Para descontar lotação é
-- preciso uma linha que se possa trancar, e é essa a `consultation_slots`. O
-- horário gera-as para a frente — ver App\Services\GeradorDeVagas — e a partir
-- daí cada vaga vive por si: muda-se-lhe a lotação, fecha-se, abre-se uma
-- avulsa, sem mexer no horário nem perder o que já lá está marcado.
--
-- A geração é idempotente: `uk_slot` impede duas vagas para a mesma consulta à
-- mesma hora, e é isso que deixa correr o gerador as vezes que forem precisas.
-- ---------------------------------------------------------------------------

-- ---------------------------------------------------------------------------
-- A consulta.
--
-- Tem identidade própria, e não é um item de um bloco de conteúdo escrito à mão
-- em cada página: um item de bloco não pode ter vagas, porque não tem nada por
-- onde uma vaga lhe pegue, e duas páginas com a mesma consulta escrita eram
-- duas consultas diferentes para quem contasse lugares. O bloco da página passa
-- a mostrar isto em vez de o guardar.
-- ---------------------------------------------------------------------------
CREATE TABLE consultations (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    locale       VARCHAR(5)   NOT NULL DEFAULT 'pt',
    slug         VARCHAR(160) NOT NULL,
    name         VARCHAR(190) NOT NULL,
    summary      VARCHAR(255) NOT NULL DEFAULT '',
    body         MEDIUMTEXT   DEFAULT NULL,
    image        VARCHAR(255) NOT NULL DEFAULT '',
    image_alt    VARCHAR(255) NOT NULL DEFAULT '',

    -- Quanto dura, em minutos. Serve para dizer a que horas acaba, no e-mail e
    -- na lista do dia, e é o que o cartão mostra como «30 min».
    duration_min SMALLINT UNSIGNED NOT NULL DEFAULT 30,

    /* O preço em cêntimos, e NULL quando é sob consulta.
       Diferente do Barbeito, onde o preço é uma linha de texto escrita como se
       lê: aqui é um número porque a maquete mostra o preço ao lado da duração e
       porque uma consulta que se venha a pagar online tem de o pagar em
       cêntimos — ver a nota sobre dinheiro no topo das migrações da loja.
       NULL não é zero: zero é grátis e NULL é «falamos primeiro», que é o caso
       dos trabalhos espirituais. */
    price_cents  INT UNSIGNED DEFAULT NULL,

    -- Onde acontece. «ambos» é o caso das leituras, e é o que faz a marcação
    -- perguntar a quem marca — ver bookings.mode.
    mode         ENUM('presencial','online','ambos') NOT NULL DEFAULT 'ambos',

    /* Se se marca escolhendo uma hora, ou se começa com uma conversa.
       Os trabalhos espirituais não têm agenda: preparam-se depois de se falar, e
       o cartão deles mostra «Falar com o Zé» em vez de horas. Sem esta coluna a
       única saída era deixá-los sem horário e esperar que a página percebesse o
       vazio — e uma consulta sem vagas por engano ficava igual a uma que nunca
       as devia ter. */
    is_bookable  TINYINT(1)   NOT NULL DEFAULT 1,

    /* A lotação que uma vaga desta consulta leva quando o horário não disser
       outra. Um, porque uma leitura é de uma pessoa — e a coluna existe à mesma
       porque um círculo ou um workshop é a mesma mesa com mais cadeiras, e isso
       escreve-se mudando um número e não acrescentando uma tabela. */
    capacity     SMALLINT UNSIGNED NOT NULL DEFAULT 1,

    -- Quantos lugares uma marcação pode levar de uma vez.
    max_party    SMALLINT UNSIGNED NOT NULL DEFAULT 1,

    -- Com quanta antecedência se fecha. Uma leitura precisa de ser preparada;
    -- marcar para dentro de dez minutos é marcar para ninguém.
    notice_hours SMALLINT UNSIGNED NOT NULL DEFAULT 24,

    is_published TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order   INT          NOT NULL DEFAULT 0,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_consultation_slug (locale, slug),
    KEY idx_consultation_live (locale, is_published, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- O horário semanal.
--
-- Uma linha por dia da semana e hora. `weekday` é 1 (segunda) a 7 (domingo),
-- como o ISO-8601 e como o `N` do PHP — e não 0 a 6, que muda de significado
-- conforme a linguagem que o lê.
--
-- `starts_on` e `ends_on` são o que faz um horário de Verão poder existir sem
-- apagar o de Inverno: os dois ficam na tabela, cada um com as suas datas, e o
-- gerador escolhe o que vale para o dia que está a gerar.
-- ---------------------------------------------------------------------------
CREATE TABLE consultation_schedules (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT UNSIGNED NOT NULL,
    weekday         TINYINT UNSIGNED NOT NULL,
    start_time      TIME         NOT NULL,
    -- NULL = a da consulta.
    capacity        SMALLINT UNSIGNED DEFAULT NULL,
    starts_on       DATE         DEFAULT NULL,
    ends_on         DATE         DEFAULT NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_schedule_consultation FOREIGN KEY (consultation_id)
        REFERENCES consultations(id) ON DELETE CASCADE,
    KEY idx_schedule_consultation (consultation_id, weekday, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Os dias em que não há.
--
-- `consultation_id` a NULL fecha a Casa toda nesse dia — que é o caso do Natal
-- e do Ano Novo, e é o que se quer escrever uma vez e não uma vez por consulta.
--
-- `every_year` faz do dia uma regra em vez de uma data. O Natal não é uma data,
-- é 25 de Dezembro sempre; escrito como data, no dia 26 deixava de existir e
-- alguém tinha de se lembrar de o voltar a escrever em Novembro — que é a
-- espécie de coisa de que ninguém se lembra. Numa linha anual o ano guardado
-- quer dizer «desde quando é que isto vale», e o mês e o dia é que mandam.
-- ---------------------------------------------------------------------------
CREATE TABLE consultation_closures (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT UNSIGNED DEFAULT NULL,
    on_date         DATE         NOT NULL,
    every_year      TINYINT(1)   NOT NULL DEFAULT 0,
    note            VARCHAR(190) NOT NULL DEFAULT '',
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_closure_consultation FOREIGN KEY (consultation_id)
        REFERENCES consultations(id) ON DELETE CASCADE,
    KEY idx_closure_date (on_date, consultation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- A vaga.
--
-- `seats_taken` é a soma dos lugares das marcações vivas, e está aqui e não
-- calculado a cada leitura de propósito: é a coluna que a marcação tranca. O
-- lugar segura-se com
--
--     UPDATE consultation_slots SET seats_taken = seats_taken + :n
--      WHERE id = :id AND is_open = 1 AND seats_taken + :n <= capacity
--
-- e é o `WHERE` que faz o trabalho. Duas pessoas a marcar a mesma hora no mesmo
-- instante: a primeira actualiza uma linha, a segunda actualiza zero, e a
-- segunda é a que ouve que já não há lugar. Sem esta coluna era preciso contar
-- as marcações e depois inserir, e entre as duas coisas cabe uma marcação a
-- mais.
--
-- `source` diz de onde veio, e serve para o gerador saber o que pode substituir:
-- mexe no que gerou, nunca no que foi escrito à mão.
-- ---------------------------------------------------------------------------
CREATE TABLE consultation_slots (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT UNSIGNED NOT NULL,
    schedule_id     INT UNSIGNED DEFAULT NULL,
    starts_at       DATETIME     NOT NULL,
    capacity        SMALLINT UNSIGNED NOT NULL,
    seats_taken     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_open         TINYINT(1)   NOT NULL DEFAULT 1,
    source          ENUM('horario','avulsa') NOT NULL DEFAULT 'horario',
    note            VARCHAR(190) NOT NULL DEFAULT '',
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_slot_consultation FOREIGN KEY (consultation_id)
        REFERENCES consultations(id) ON DELETE CASCADE,
    CONSTRAINT fk_slot_schedule FOREIGN KEY (schedule_id)
        REFERENCES consultation_schedules(id) ON DELETE SET NULL,
    -- O que faz o gerador poder correr as vezes que forem precisas.
    UNIQUE KEY uk_slot (consultation_id, starts_at),
    KEY idx_slot_when (starts_at, is_open)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- A marcação.
--
-- `code` é o que a pessoa lê e diz ao telefone; `token` é o que abre a página de
-- cancelamento e nunca aparece escrito em lado nenhum senão na ligação do
-- e-mail. São dois porque servem coisas diferentes: um código curto para ser
-- lido em voz alta é curto de mais para ser um segredo.
--
-- Uma marcação cancelada fica. É o registo de que existiu, e os lugares são
-- devolvidos à vaga no mesmo instante em que se cancela.
--
-- Não há coluna de consentimento: não pode haver marcação sem a caixa ticada,
-- por isso o registo existir já é o registo do consentimento, com a data em
-- `created_at`. É a mesma decisão dos pedidos de contacto.
--
-- `note` é o que a pessoa escreve sobre o que a traz, e numa casa destas é a
-- coluna mais sensível da base de dados. É por causa dela que a página de uma
-- marcação se abre pelo `token` e não pelo `code`: um endereço adivinhável dava
-- a qualquer pessoa o nome, o telefone e a aflição de outra.
-- ---------------------------------------------------------------------------
CREATE TABLE bookings (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slot_id      INT UNSIGNED NOT NULL,
    code         VARCHAR(12)  NOT NULL,
    token        CHAR(64)     NOT NULL,
    name         VARCHAR(190) NOT NULL,
    email        VARCHAR(190) NOT NULL,
    phone        VARCHAR(60)  NOT NULL DEFAULT '',
    people       SMALLINT UNSIGNED NOT NULL DEFAULT 1,

    /* Presencial ou online, para as consultas que são as duas coisas.
       Guardado na marcação e não deduzido da consulta porque é uma escolha de
       quem marca, e porque é o que decide se o e-mail leva uma morada ou uma
       ligação. */
    mode         ENUM('presencial','online') NOT NULL DEFAULT 'presencial',

    note         TEXT         DEFAULT NULL,
    status       ENUM('confirmada','cancelada') NOT NULL DEFAULT 'confirmada',
    locale       VARCHAR(5)   NOT NULL DEFAULT 'pt',
    ip           VARCHAR(45)  NOT NULL DEFAULT '',
    user_agent   VARCHAR(255) NOT NULL DEFAULT '',
    mailed_at    TIMESTAMP    NULL DEFAULT NULL,
    cancelled_at TIMESTAMP    NULL DEFAULT NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_slot FOREIGN KEY (slot_id)
        REFERENCES consultation_slots(id) ON DELETE CASCADE,
    UNIQUE KEY uk_booking_code (code),
    UNIQUE KEY uk_booking_token (token),
    KEY idx_booking_slot (slot_id, status),
    KEY idx_booking_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- As quatro consultas que estavam escritas na maquete, agora como registos.
--
-- Os textos são os da maquete aprovada e não «Lorem ipsum»: um site semeado com
-- texto falso é um site que ninguém consegue avaliar. Os preços, esses, estavam
-- na maquete como `[PREÇO] €` — ficam a NULL, que é como a página diz «sob
-- consulta», e escrevem-se em Consultas quando a casa os disser. Deixá-los a
-- zero punha «0,00 €» num cartão, que é pior do que não dizer nada.
-- ---------------------------------------------------------------------------
INSERT INTO consultations
    (id, locale, slug, name, summary, body, duration_min, price_cents, mode, is_bookable, capacity, max_party, notice_hours, sort_order)
VALUES
    (1, 'pt', 'tarot-tres-cartas', 'Tarot · Três cartas',
        'Passado, presente e caminho. Para uma pergunta concreta que precisa de clareza.',
        '<p>Três cartas sobre a mesa: o que ficou atrás, o que está a acontecer agora e o caminho que se abre. É a leitura para quem traz uma pergunta e quer uma resposta que se possa levar para casa.</p>',
        30, NULL, 'ambos', 1, 1, 1, 24, 1),

    (2, 'pt', 'tarot-leitura-completa', 'Tarot · Leitura completa',
        'Cruz Celta com as cartas todas na mesa: amor, trabalho, saúde do espírito e o que está a travar o teu caminho.',
        '<p>A Cruz Celta, com tempo. Dez cartas que abrem o amor, o trabalho, a saúde do espírito e aquilo que está a travar o caminho — e uma conversa a seguir sobre o que fazer com o que apareceu.</p>',
        60, NULL, 'ambos', 1, 1, 1, 24, 2),

    /* Sem agenda: prepara-se depois de se falar. É o único caso de
       `is_bookable = 0`, e é para ele que a coluna existe. */
    (3, 'pt', 'trabalhos-espirituais', 'Trabalhos espirituais',
        'Abertura de caminhos, proteção, amor e limpeza. Cada trabalho é preparado para ti depois de uma primeira conversa.',
        '<p>Abertura de caminhos, proteção, amor e limpeza. Não há duas iguais, e por isso não há horas marcadas: começa com uma conversa, e é dela que sai o que se vai preparar e quanto tempo leva.</p>',
        0, NULL, 'ambos', 0, 1, 1, 0, 3),

    (4, 'pt', 'baralho-cigano', 'Baralho Cigano · Leitura',
        'As 36 cartas do Baralho Cigano para perguntas do dia a dia: amor, trabalho e os caminhos que se abrem.',
        '<p>As 36 cartas do Baralho Cigano, que respondem ao concreto: o que vai acontecer com aquele assunto, aquela pessoa, aquele trabalho. Directo, e sem rodeios.</p>',
        45, NULL, 'ambos', 1, 1, 1, 24, 4);

-- ---------------------------------------------------------------------------
-- O horário com que a Casa arranca.
--
-- À noite e em dias alternados, que é quando uma casa destas atende e é o que
-- deixa as leituras longas caber entre as curtas. É um ponto de partida para a
-- casa mexer em Consultas > Horário, e não uma decisão nossa.
-- ---------------------------------------------------------------------------
INSERT INTO consultation_schedules (consultation_id, weekday, start_time, capacity) VALUES
    -- Três cartas: terça, quinta e sábado.
    (1, 2, '21:00:00', NULL),
    (1, 2, '21:45:00', NULL),
    (1, 4, '21:00:00', NULL),
    (1, 4, '21:45:00', NULL),
    (1, 6, '17:00:00', NULL),
    (1, 6, '17:45:00', NULL),
    -- Leitura completa: uma por noite, quarta e sexta, que leva uma hora.
    (2, 3, '21:00:00', NULL),
    (2, 5, '21:00:00', NULL),
    -- Baralho Cigano: sábado à tarde.
    (4, 6, '15:00:00', NULL),
    (4, 6, '15:45:00', NULL);

-- Natal e Ano Novo, para a Casa toda e todos os anos.
INSERT INTO consultation_closures (consultation_id, on_date, every_year, note) VALUES
    (NULL, '2026-12-25', 1, 'Natal'),
    (NULL, '2027-01-01', 1, 'Ano Novo');
