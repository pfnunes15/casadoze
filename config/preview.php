<?php
declare(strict_types=1);

/**
 * The stylesheets the backoffice loads when it draws a block.
 *
 * The same list as views/layouts/public.php, and it has to stay the same
 * list: the page editor is only worth having because what it shows is what
 * the site shows, and a stylesheet missing here is a block the editor draws
 * unstyled. Without this file the package would load its own default three —
 * brand.css, main.css and site.css — two of which this site does not have.
 */
return [
    'stylesheets' => [
        'css/main.css',
    ],
];
