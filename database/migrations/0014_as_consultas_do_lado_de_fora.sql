-- ---------------------------------------------------------------------------
-- As consultas voltam a ter porta.
--
-- A marcação pública saiu quando o site foi reduzido a uma página, e a razão
-- escrita na altura era que a maquete não tinha agenda na porta de entrada. O
-- desenho novo tem — `docs/maquete/Consultas.dc.html`, com calendário, vagas e
-- remarcação.
--
-- Aqui fica a frase de entrada da página, que é texto da Casa e não da
-- interface. A da maquete, para a página não abrir com um parágrafo vazio; a
-- Casa muda-a em Definições > Consultas.
--
-- E o `readings.talk_url`, que apontava para a âncora `#contacto` da porta de
-- entrada porque não havia para onde apontar. Passa a apontar para a lista das
-- consultas, que é onde se marca. Só muda se ainda estiver no valor semeado:
-- uma Casa que já lá tenha escrito o WhatsApp não o vê trocado por esta linha.
-- ---------------------------------------------------------------------------
INSERT INTO settings (`key`, locale, value) VALUES
    ('readings.intro', 'pt',
     'Leituras e trabalhos feitos pelo Zé, presencialmente ou à distância. Cada consulta começa com uma conversa sobre o que te trouxe até aqui.')
ON DUPLICATE KEY UPDATE value = value;

UPDATE settings
   SET value = '/consultas'
 WHERE `key` = 'readings.talk_url'
   AND value = '#contacto';
