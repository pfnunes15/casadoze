<?php
/**
 * As palavras deste site, em português.
 *
 * Só o que é da Casa de Zé. Tudo o que o CMS já diz — a loja, o cesto, os
 * e-mails de encomenda — vem do catálogo do pacote, e uma linha escrita aqui com
 * a mesma chave passa-lhe à frente. Ver Admedia\Core\Lang.
 *
 * **O que está aqui e o que está na base de dados.** Aqui fica a moldura: os
 * botões, as etiquetas dos campos, as mensagens de erro — o que é igual em todas
 * as páginas e não é escrito pela casa. O conteúdo — os títulos, os textos, os
 * nomes das consultas e dos produtos — está na base de dados, uma linha por
 * idioma, e edita-se no backoffice. Um nome de produto escrito aqui era um nome
 * de produto que a casa não podia mudar sem um programador.
 *
 * Uma frase que falte aparece no ecrã como a própria chave, `marcacao.pick`, que
 * se vê e se corrige — em vez de um botão sem texto que ninguém repara que está
 * vazio.
 */

return [

    // ---------------------------------------------------------------------
    // A moldura do site.
    // ---------------------------------------------------------------------
    'site.skip'       => 'Saltar para o conteúdo',
    'site.menu'       => 'Menu',
    'site.menu_main'  => 'Menu principal',
    'site.menu_open'  => 'Abrir o menu',
    'site.menu_close' => 'Fechar o menu',
    'site.book'       => 'Marcar consulta',
    'site.lang'       => 'Idioma',
    'site.cart'       => 'Cesto',
    'site.scroll'     => 'Desce',

    'footer.where'     => 'Onde',
    'footer.contact'   => 'Falar com a Casa',
    'footer.hours'     => 'Horário',
    'footer.follow'    => 'Segue a Casa',
    'footer.shop'      => 'A loja',
    'footer.readings'  => 'As consultas',
    'footer.privacy'   => 'Privacidade',
    'footer.terms'     => 'Termos e condições',
    'footer.cookies'   => 'Política de cookies',
    'footer.ral'       => 'Resolução alternativa de litígios',
    'footer.complaints' => 'Livro de Reclamações Eletrónico',
    'footer.rights'    => 'Todos os direitos reservados',

    // ---------------------------------------------------------------------
    // Quem está por trás.
    // ---------------------------------------------------------------------
    'about.does'   => 'O que faço',
    'about.doesnt' => 'O que não faço',

    // ---------------------------------------------------------------------
    // As consultas, do lado de fora.
    // ---------------------------------------------------------------------
    'consultas.title'       => 'Consultas',
    'consultas.on_request'  => 'Sob consulta',
    'consultas.book'        => 'Marcar',
    'consultas.talk'        => 'Falar com o Zé',
    'consultas.agenda'      => 'Ver agenda',
    'consultas.mode.ambos'      => 'Online ou presencial',
    'consultas.mode.presencial' => 'Presencial',
    'consultas.mode.online'     => 'Online',

    /* Dito em todos os sítios onde se marca, e não só numa página de termos. É
       uma exigência de quem vende serviços desta natureza, e é também o que
       separa a Casa de quem promete o que não pode prometer. */
    'consultas.disclaimer' => 'As leituras são orientação espiritual e não '
                           . 'substituem aconselhamento médico, psicológico, '
                           . 'jurídico ou financeiro.',

    'consultas.none'        => 'Não há horas marcáveis nos próximos dias. '
                             . 'Fale com a Casa e combinamos.',
    'consultas.pick_day'    => 'Escolhe o dia',
    'consultas.pick_time'   => 'Escolhe a hora',
    'consultas.free_one'    => '1 lugar',
    'consultas.free_many'   => ':count lugares',

    // ---------------------------------------------------------------------
    // O formulário de marcação.
    // ---------------------------------------------------------------------
    'marcacao.title'    => 'Marcar',
    'marcacao.name'     => 'Como te chamas',
    'marcacao.email'    => 'E-mail',
    'marcacao.phone'    => 'Telefone',
    'marcacao.people'   => 'Quantas pessoas',
    'marcacao.mode'     => 'Onde',
    'marcacao.note'     => 'O que te traz',
    'marcacao.note_help' => 'Não é obrigatório. Ajuda o Zé a preparar a mesa.',
    'marcacao.consent'  => 'Autorizo a Casa a guardar estes dados para tratar da marcação.',
    'marcacao.submit'   => 'Confirmar marcação',
    'marcacao.phone_optional' => 'opcional',

    'marcacao.thanks'   => 'Obrigado — a tua marcação ficou registada.',
    'marcacao.pick'     => 'Escolhe a consulta e a hora.',
    'marcacao.missing'  => 'Falta alguma coisa — vê os campos assinalados.',
    'marcacao.taken'    => 'Essa hora deixou de estar livre enquanto preenchias. '
                         . 'Escolhe outra — as que ainda têm lugar estão na lista.',
    'marcacao.busy'     => 'A agenda está ocupada neste instante. Tenta outra vez.',
    'marcacao.too_many_same_email' => 'Já há várias marcações com este e-mail nas '
                                    . 'últimas horas. Fala com a Casa para marcar mais.',
    'marcacao.slow_down' => 'Demasiados pedidos seguidos. Tenta daqui a :wait.',

    'marcacao.err.name'       => 'Diz-nos como te chamas.',
    'marcacao.err.name_long'  => 'Esse nome é comprido de mais.',
    'marcacao.err.email'      => 'Precisamos de um e-mail para te mandar a confirmação.',
    'marcacao.err.email_bad'  => 'Isso não parece um endereço de e-mail.',
    'marcacao.err.phone_long' => 'Esse número é comprido de mais.',
    'marcacao.err.mode'       => 'Diz se é presencial ou online.',
    'marcacao.err.people'     => 'Diz quantas pessoas vêm.',
    'marcacao.err.only_left'  => 'Nessa hora só resta(m) :count lugar(es).',
    'marcacao.err.call_us'    => 'Para mais de :max pessoas, fala com a Casa.',
    'marcacao.err.note_long'  => 'Esse texto é comprido de mais.',
    'marcacao.err.consent'    => 'Precisamos da tua autorização para guardar os dados.',

    // ---------------------------------------------------------------------
    // A marcação feita.
    // ---------------------------------------------------------------------
    'marcacao.done.title'   => 'Está marcado',
    'marcacao.done.code'    => 'O teu código',
    'marcacao.done.code_help' => 'Guarda-o. É o que dizes se telefonares.',
    'marcacao.done.when'    => 'Quando',
    'marcacao.done.what'    => 'O quê',
    'marcacao.done.where'   => 'Onde',
    'marcacao.done.cancel'  => 'Desmarcar',
    'marcacao.done.cancel_confirm' => 'Desmarcar esta consulta?',
    'marcacao.done.past'    => 'Esta consulta já aconteceu.',
    'marcacao.done.cancelled' => 'Esta marcação está desmarcada.',
    'marcacao.done.online_note' => 'A ligação para a sessão vai no e-mail, '
                                 . 'pouco antes da hora.',

    'marcacao.not_found'        => 'Essa marcação não existe.',
    'marcacao.already_happened' => 'Essa consulta já aconteceu.',
    'marcacao.cancelled'        => 'Marcação desmarcada. A hora voltou a ficar livre.',
    'marcacao.was_cancelled'    => 'Esta marcação já estava desmarcada.',
];
