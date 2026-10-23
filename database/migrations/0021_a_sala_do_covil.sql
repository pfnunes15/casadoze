-- ---------------------------------------------------------------------------
-- The Sala: Débora D'Exu and Rogério de Exu.
--
-- The four rows seeded from the mockup were a stand-in — "Zé" plus three names
-- in brackets. The client has given two real people, with their own texts.
--
-- The rows are replaced and not added to: the old ids are referenced by
-- practitioner_consultations, and leaving them behind would show four people
-- in the Sala, two of whom do not exist.
--
-- Débora keeps id 1 so the link from the consultations survives; Rogério takes
-- id 2. The two placeholders go.
-- ---------------------------------------------------------------------------

DELETE FROM practitioners WHERE id IN (3, 4);

UPDATE practitioners
   SET slug  = 'debora-dexu',
       name  = 'Débora D''Exu',
       role  = 'Guardiã do Covil Ancestral',
       quote = 'A magia não tem cor — tem intenção. E a intenção exige responsabilidade.',
       deck  = 'Baralho Cigano, Jogo de Búzios e Oráculo Negro de Exu',
       sign  = '',
       years = 10,
       image = '',
       image_alt = '',
       specialties = 'Baralho Cigano\nJogo de Búzios\nOráculo Negro de Exu\nMagia de matriz africana\nPrática espírita\nAbertura de caminhos',
       formats   = 'Presencial\nOnline',
       languages = 'Português',
       sort_order = 1,
       is_published = 1,
       bio = CONCAT(
         '<p>Há dez anos que me dedico ao cuidado espiritual e à orientação de quem procura clareza, proteção e força perante os desafios da vida. O meu percurso reúne a consulta de oráculos, a prática espírita e a magia de matriz africana, numa ligação profunda a Exu e à ancestralidade, vivida com fé, respeito e responsabilidade.</p>',
         '<p>Trabalho com o Baralho Cigano, o Jogo de Búzios, o Oráculo Negro de Exu e outros oráculos, muitos deles criados e preparados por mim, inspirados na minha experiência espiritual e em saberes transmitidos entre gerações. Honro esses conhecimentos, preservando a sua memória e dando-lhes continuidade na minha prática.</p>',
         '<p>Cada oráculo possui uma linguagem própria. O Baralho Cigano permite refletir sobre afetos, família, trabalho e caminhos pessoais, ajudando a reconhecer padrões e possibilidades. O Jogo de Búzios e o Oráculo Negro de Exu integram a minha orientação espiritual, no contexto da minha fé e ligação à ancestralidade. Em todas as consultas, procuro escutar, esclarecer dúvidas e explicar as leituras de forma acessível, sem exigir conhecimentos prévios.</p>',
         '<p>O meu trabalho inclui oração, rituais, cuidados de limpeza, proteção, fortalecimento e abertura de caminhos, bem como a preparação e consagração de produtos espirituais. A abertura de caminhos é trabalhada com a intenção de cuidar do que é vivido como bloqueio e favorecer clareza e força para avançar, acompanhando as escolhas e ações de cada pessoa. Respeito as origens e a diversidade das tradições de matriz africana e explico a intenção de cada proposta, para permitir uma decisão livre e consciente.</p>',
         '<p>Respeito, discrição e sigilo são princípios essenciais do meu trabalho. Ofereço um acolhimento atento, sem julgamentos, com limites claros e respeito pela intimidade, pelas crenças e pelo tempo de cada pessoa. Reconheço os limites da minha atuação e preservo a autonomia de quem me procura.</p>',
         '<p>Não prometo milagres, resultados garantidos ou uma vida sem dificuldades. Comprometo-me com um cuidado espiritual sério, dedicado e verdadeiro, que proporcione espaço para refletir, fortalecer-se e caminhar com maior consciência.</p>'
       )
 WHERE id = 1;

UPDATE practitioners
   SET slug  = 'rogerio-de-exu',
       name  = 'Rogério de Exu',
       role  = 'Filho espiritual de Débora D''Exu',
       quote = 'A magia não tem cor — tem intenção e fundamento.',
       deck  = 'Oráculos',
       sign  = '',
       years = 0,
       image = '',
       image_alt = '',
       specialties = 'Consulta de oráculos\nPrática espírita\nLimpeza\nProteção\nFortalecimento\nAbertura de caminhos',
       formats   = 'Presencial\nOnline',
       languages = 'Português',
       sort_order = 2,
       is_published = 1,
       bio = CONCAT(
         '<p>Sou Rogério de Exu, filho espiritual de Débora D''Exu e parte da árvore ancestral do Covil. Foi com ela que aprendi os fundamentos que sustentam o meu trabalho: a orientação através dos oráculos, a prática espírita, os rituais e as diferentes práticas de magia que me transmitiu.</p>',
         '<p>A minha formação espiritual está diretamente ligada aos seus ensinamentos e à experiência partilhada. Dou continuidade a esse conhecimento com respeito pela ancestralidade, pela minha ligação a Exu e pela responsabilidade de acolher quem procura orientação.</p>',
         '<p>Aprendi com Débora D''Exu que a magia não tem cor — tem intenção e fundamento. Cada trabalho exige compreender o que se pretende, conhecer os fundamentos da prática e assumir a responsabilidade pelo que se faz. É com essa consciência que analiso os pedidos que me são apresentados e avalio os trabalhos que posso aceitar, de acordo com as necessidades de cada pessoa e com os conhecimentos que recebi.</p>',
         '<p>O meu trabalho abrange a consulta de oráculos e os cuidados espirituais com intenção de limpeza, proteção, fortalecimento e abertura de caminhos. Procuro explicar cada proposta com clareza, esclarecer dúvidas e respeitar sempre a liberdade de decisão de quem me procura.</p>',
         '<p>O respeito, o sigilo e a seriedade são compromissos essenciais. Acolho cada história sem julgamentos, com discrição e atenção, reconhecendo os limites da minha atuação. Não prometo milagres nem resultados garantidos: comprometo-me com dedicação, honestidade e responsabilidade.</p>',
         '<p>Ser filho espiritual de Débora D''Exu significa honrar o que me ensinou, continuar a aprender e dar continuidade, através do meu trabalho, às raízes do Covil Ancestral.</p>'
       )
 WHERE id = 2;

-- Both give every reading the Covil offers. The client can narrow this per
-- person in the backoffice; what is wrong is one of them silently giving none.
DELETE FROM practitioner_consultations;

INSERT INTO practitioner_consultations (practitioner_id, consultation_id, sort_order)
SELECT 1, id, sort_order FROM consultations WHERE locale = 'pt';

INSERT INTO practitioner_consultations (practitioner_id, consultation_id, sort_order)
SELECT 2, id, sort_order FROM consultations WHERE locale = 'pt';
