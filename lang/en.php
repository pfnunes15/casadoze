<?php
/**
 * This site's words, in English.
 *
 * Also the net under the other languages: when a sentence is missing from
 * French, German or Spanish, Admedia\Core\Lang falls back to the language the
 * site was installed in and then to English — never to a blank button. So a key
 * written here is a key that is never shown raw on screen, in any language.
 */

return [

    'site.skip'       => 'Skip to content',
    'site.menu'       => 'Menu',
    'site.menu_main'  => 'Main menu',
    'site.menu_open'  => 'Open the menu',
    'site.menu_close' => 'Close the menu',
    'site.book'       => 'Book a reading',
    'site.lang'       => 'Language',
    'site.cart'       => 'Basket',
    'site.scroll'     => 'Scroll',

    'footer.where'      => 'Where',
    'footer.contact'    => 'Talk to the House',
    'footer.hours'      => 'Opening hours',
    'footer.follow'     => 'Follow the House',
    'footer.shop'       => 'The shop',
    'footer.readings'   => 'The readings',
    'footer.privacy'    => 'Privacy',
    'footer.terms'      => 'Terms and conditions',
    'footer.cookies'    => 'Cookie policy',
    'footer.ral'        => 'Alternative dispute resolution',
    'footer.complaints' => 'Electronic Complaints Book',
    'footer.rights'     => 'All rights reserved',

    // ---------------------------------------------------------------------
    // The moon letter, in the footer.
    // ---------------------------------------------------------------------
    'footer.nl_title' => 'This month\'s moon in your inbox',
    'footer.nl_text'  => 'At the start of each month we send the lunar calendar, the '
                       . 'suggested rituals and what is new in the shop.',
    'footer.nl_place' => 'Your email',
    'footer.nl_send'  => 'Subscribe',
    'footer.nl_ok'    => 'Thank you. Until the next moon.',
    'footer.nl_gift'  => 'Subscribe and get the Full Moon Ritual Guide as a PDF.',
    'footer.house'    => 'The House',
    'footer.help'     => 'Help',

    // ---------------------------------------------------------------------
    // Now at the House: the next sabbat and the lunar calendar.
    // ---------------------------------------------------------------------
    'agora.next'   => 'Next sabbat',
    'agora.left'   => ':n days to go',
    'agora.left_1' => '1 day to go',
    'agora.today'  => 'It is today',
    'agora.kit'    => ':sabbat kit',
    'agora.lunar'  => 'Lunar calendar',
    'agora.now'    => 'Today',

    // The bridge between the shop and the readings.
    'ponte.left'  => 'Shop · things that ship',
    'ponte.right' => 'Readings · booked in advance',

    // The Grimoire: the free sessions.
    'grimorio.related' => 'Used in this session',
    'grimorio.thumb'   => '[Video thumbnail]',

    // ---------------------------------------------------------------------
    // A mesa de tarot.
    //
    // Os vinte e dois Arcanos Maiores, e o que cada um diz ao direito e ao
    // contrário. Numa linha só por campo, separados por barra: são três
    // frases a traduzir em vez de sessenta e seis.
    // ---------------------------------------------------------------------
    'tarot.title'   => 'Three-card spread',
    'tarot.start'   => 'Shuffle and draw',
    'tarot.skip'    => 'Skip animation',
    'tarot.focus'   => 'Focus on your question…',
    'tarot.cut'     => 'Cutting the deck…',
    'tarot.pick'    => 'Choose 3 cards',
    'tarot.reveal'  => 'Tap the cards to reveal them',
    'tarot.again'   => 'New spread',
    'tarot.full'    => 'Book a full reading',
    'tarot.inv'     => 'Reversed',
    'tarot.note'    => 'A free, symbolic spread with the 22 Major Arcana.',
    'tarot.pos'     => 'Past|Present|Future',
    'tarot.names'   => 'The Fool|The Magician|The High Priestess|The Empress|The Emperor|The Hierophant|The Lovers|The Chariot|Strength|The Hermit|Wheel of Fortune|Justice|The Hanged Man|Death|Temperance|The Devil|The Tower|The Star|The Moon|The Sun|Judgement|The World',
    'tarot.up'      => 'New beginnings, spontaneity|Willpower, initiative|Intuition, mystery|Abundance, creativity|Structure, stability|Tradition, teaching|Union, choices of the heart|Determination, progress|Courage, compassion|Introspection, inner search|Change, cycles|Balance, truth|Pause, new perspective|Transformation, end of a cycle|Harmony, patience|Desire, attachments|Upheaval, sudden revelation|Hope, renewal|Illusion, deep intuition|Joy, success|Awakening, calling|Fulfilment, completion',
    'tarot.rev'     => 'Recklessness, hesitation|Manipulation, scattered energy|Secrets, blocked intuition|Creative block, dependence|Rigidity, excessive control|Rebellion, questioning rules|Imbalance, indecision|Lack of direction|Insecurity, impatience|Isolation, loneliness|Resisting change, delays|Unfairness, avoiding responsibility|Stagnation, needless sacrifice|Fear of change, attachment|Excess, imbalance|Release, breaking chains|Avoiding change, delayed crisis|Discouragement, lack of faith|Clarity emerging, fears fading|Muted enthusiasm, delays|Doubt, self-judgement|Unfinished cycle',

    // ---------------------------------------------------------------------
    // A previsão dos signos.
    // ---------------------------------------------------------------------
    'signos.eyebrow'   => 'Star forecast',
    'signos.title'     => 'Your week in the stars',
    'signos.note'      => 'Updated every week.',
    'signos.from'      => 'From',
    'signos.to'        => 'to',
    'signos.element'   => 'Element',
    'signos.ruler'     => 'Ruler',
    'signos.share'     => 'Share my sign',
    'signos.names'     => 'Aries|Taurus|Gemini|Cancer|Leo|Virgo|Libra|Scorpio|Sagittarius|Capricorn|Aquarius|Pisces',
    'signos.planets'   => 'Mars|Venus|Mercury|Moon|Sun|Mercury|Venus|Pluto|Jupiter|Saturn|Uranus|Neptune',
    'signos.elements'  => 'Fire|Earth|Air|Water',
    'signos.colors'    => 'Gold|Moss green|Lilac|Deep blue',
    'signos.meters'    => 'Love|Work|Energy',
    'signos.lucky'     => 'Number|Best day|Colour',
    'lua.0' => 'New moon',
    'lua.1' => 'Waxing crescent',
    'lua.2' => 'First quarter',
    'lua.3' => 'Waxing gibbous',
    'lua.4' => 'Full moon',
    'lua.5' => 'Waning gibbous',
    'lua.6' => 'Last quarter',
    'lua.7' => 'Waning crescent',


    // ---------------------------------------------------------------------
    // Shopping by intention.
    // ---------------------------------------------------------------------
    'intencao.piece'  => ':n piece',
    'intencao.pieces' => ':n pieces',
    'intencao.title'  => 'For :intencao',

    // A loja: duas frases do pacote que a Casa diz mais curtas.
    // O botão da montra é «+ Adicionar» e não «Adicionar ao carrinho» —
    // ao lado do sinal de mais, o resto da frase é ruído.
    'shop.product.add'      => 'Add',
    'shop.product.sold_out' => 'Sold out',
    'about.does'   => 'What I do',
    'about.doesnt' => 'What I do not do',

    // ---------------------------------------------------------------------
    // The readings, from outside.
    // ---------------------------------------------------------------------
    'consultas.title'      => 'Readings',
    'consultas.on_request' => 'On request',
    'consultas.book'       => 'Book',
    'consultas.talk'       => 'Talk to Zé',
    'consultas.agenda'     => 'See the diary',
    'consultas.mode.ambos'      => 'Online or in person',
    'consultas.mode.presencial' => 'In person',
    'consultas.mode.online'     => 'Online',
    'consultas.disclaimer' => 'Readings are spiritual guidance and are not a '
                           . 'substitute for medical, psychological, legal or '
                           . 'financial advice.',
    'consultas.none'      => 'There are no bookable times in the coming days. '
                           . 'Talk to the House and we will arrange one.',
    'consultas.pick_day'  => 'Pick a day',
    'consultas.pick_time' => 'Pick a time',
    'consultas.free_one'  => '1 place',
    'consultas.free_many' => ':count places',

    'marcacao.title'          => 'Book',
    'marcacao.name'           => 'Your name',
    'marcacao.email'          => 'E-mail',
    'marcacao.phone'          => 'Phone',
    'marcacao.people'         => 'How many people',
    'marcacao.mode'           => 'Where',
    'marcacao.note'           => 'What brings you',
    'marcacao.note_help'      => 'Not required. It helps Zé prepare the table.',
    'marcacao.consent'        => 'I allow the House to keep these details in order to handle the booking.',
    'marcacao.submit'         => 'Confirm booking',
    'marcacao.phone_optional' => 'optional',

    'marcacao.thanks'  => 'Thank you — your booking has been recorded.',
    'marcacao.pick'    => 'Choose the reading and the time.',
    'marcacao.missing' => 'Something is missing — see the fields marked below.',
    'marcacao.taken'   => 'That time stopped being free while you were filling the form. '
                        . 'Pick another — the ones still open are in the list.',
    'marcacao.busy'    => 'The diary is busy right now. Please try again.',
    'marcacao.too_many_same_email' => 'There are already several bookings with this e-mail in '
                                    . 'the last few hours. Talk to the House to book more.',
    'marcacao.slow_down' => 'Too many attempts in a row. Try again in :wait.',

    'marcacao.err.name'       => 'Tell us your name.',
    'marcacao.err.name_long'  => 'That name is too long.',
    'marcacao.err.email'      => 'We need an e-mail to send you the confirmation.',
    'marcacao.err.email_bad'  => 'That does not look like an e-mail address.',
    'marcacao.err.phone_long' => 'That number is too long.',
    'marcacao.err.mode'       => 'Say whether it is in person or online.',
    'marcacao.err.people'     => 'Say how many people are coming.',
    'marcacao.err.only_left'  => 'Only :count place(s) left at that time.',
    'marcacao.err.call_us'    => 'For more than :max people, talk to the House.',
    'marcacao.err.note_long'  => 'That text is too long.',
    'marcacao.err.consent'    => 'We need your permission to keep the details.',

    'marcacao.done.title'     => 'It is booked',
    'marcacao.done.code'      => 'Your code',
    'marcacao.done.code_help' => 'Keep it. It is what you say if you call.',
    'marcacao.done.when'      => 'When',
    'marcacao.done.what'      => 'What',
    'marcacao.done.where'     => 'Where',
    'marcacao.done.cancel'    => 'Cancel',
    'marcacao.done.cancel_confirm' => 'Cancel this reading?',
    'marcacao.done.past'      => 'This reading has already happened.',
    'marcacao.done.cancelled' => 'This booking is cancelled.',
    'marcacao.done.online_note' => 'The link for the session goes out by e-mail, shortly before the time.',

    'marcacao.not_found'        => 'That booking does not exist.',
    'marcacao.already_happened' => 'That reading has already happened.',
    'marcacao.cancelled'        => 'Booking cancelled. The time is free again.',
    'marcacao.was_cancelled'    => 'This booking was already cancelled.',
];
