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
