-- ---------------------------------------------------------------------------
-- A abertura, como está na maquete.
--
-- A primeira versão pôs uma coluna de texto ao lado do retrato: o nome da Casa
-- outra vez escrito, e uma frase por baixo. A maquete não tem nada disso — o
-- nome está desenhado dentro da fotografia, e ao lado dela só há os dois botões,
-- encostados à margem. O título continua guardado porque é o `h1` da página,
-- mas deixa de aparecer no ecrã.
--
-- Esta migração só arruma a linha semeada. Quem já editou a abertura no
-- backoffice fica com o que escreveu: o `UPDATE` trava em cada campo que já não
-- é o que o 0003 lá pôs. Uma frase apagada a quem a escreveu de propósito era
-- esta migração a decidir por ela.
-- ---------------------------------------------------------------------------

-- A frase, que passou a ser repetição da meta-descrição da página.
UPDATE page_sections
   SET body = ''
 WHERE page_id = 1
   AND type = 'abertura'
   AND body = 'Velas, óleos, cristais e ervas preparados à mão no Covil, no tempo certo da lua.';

-- Os botões trocam de lugar: o cheio passa a ser o da loja. Quem chega sem ler
-- nada e carrega num botão quer ver o que a Casa vende; a consulta marca-se
-- depois de se saber o que a Casa é.
UPDATE page_sections
   SET cta_label = 'Entrar na loja',
       cta_url   = '#loja',
       options   = JSON_SET(
                       COALESCE(NULLIF(options, ''), '{}'),
                       '$.cta2_label', 'Marcar consulta',
                       '$.cta2_url',   '#consultas'
                   )
 WHERE page_id = 1
   AND type = 'abertura'
   AND cta_label = 'Marcar consulta'
   AND cta_url   = '#consultas';
