<?php
/**
 * A moldura do site.
 *
 * Uma página é sempre a mesma coisa: cabeçalho, conteúdo, rodapé. O que muda é
 * se o cabeçalho flutua por cima do que a página abre — o que só funciona quando
 * o primeiro bloco é uma fotografia de ecrã inteiro — ou se assenta num fundo
 * próprio. Este ficheiro não é o que decide: é a página, em
 * views/pages/composed.php, que olha para o primeiro bloco e escreve $bodyClass.
 *
 * @var string $content
 * @var \Admedia\Core\App $app
 * @var array<string,string[]> $flashes
 */
$nomeDoSite = setting('site.name', (string)$app->config('app.name', 'Casa de Zé'));

// A língua em que esta página está a ser servida, e onde vivem as outras.
$lingua = \Admedia\Core\Locales::get(\Admedia\Core\Locales::current()) ?? ['html' => 'pt', 'dir' => 'ltr'];
$outras = \Admedia\Core\Locales::many() ? \Admedia\Core\Locales::alternates() : [];
?>
<!DOCTYPE html>
<html lang="<?= e($lingua['html']) ?>"<?= ($lingua['dir'] ?? 'ltr') === 'rtl' ? ' dir="rtl"' : '' ?>>
<head>
<meta charset="UTF-8">
<?php /* Sem isto um telefone finge ter 980px de largura e encolhe a página toda
         — e o site, escrito a partir do telemóvel, chegava lá ilegível. É a
         linha mais importante do ficheiro. */ ?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? $nomeDoSite) ?></title>
<?php if (!empty($metaDescription)): ?>
<meta name="description" content="<?= e($metaDescription) ?>">
<?php endif; ?>

<?php /* O fundo do browser por trás da página, antes de a folha de estilo
         chegar. Sem isto há um relâmpago branco em cada carregamento, que num
         site desta cor se vê sempre. */ ?>
<meta name="theme-color" content="#09080a">
<meta name="color-scheme" content="dark">

<?php /* A mesma página nas outras línguas, para um motor de busca saber que são
         a mesma e não versões a competir. O x-default é para quem chega sem
         preferência: a língua principal. Com uma língua só, isto não desenha
         nada. */ ?>
<?php foreach ($outras as $outra): ?>
<link rel="alternate" hreflang="<?= e(\Admedia\Core\Locales::get($outra['code'])['html'] ?? $outra['code']) ?>" href="<?= e(url($outra['url'])) ?>">
<?php endforeach; ?>
<?php if ($outras !== []): ?>
<link rel="alternate" hreflang="x-default" href="<?= e(url(\Admedia\Core\Locales::url(\Admedia\Core\Locales::path(), \Admedia\Core\Locales::default()))) ?>">
<?php endif; ?>

<link rel="stylesheet" href="<?= asset('css/main.css') ?>">

<?php /* A imagem com que a página abre. Escrita por views/pages/composed.php; o
         endereço tem de ser o mesmo que o bloco desenha, ou isto pede-a duas
         vezes. */ ?>
<?php if (!empty($preloadImage)): ?>
<link rel="preload" href="<?= e($preloadImage) ?>" as="image" fetchpriority="high">
<?php endif; ?>
</head>
<?php
/* Quem decide é a página e não a moldura. Quando o primeiro bloco passa por
   baixo do cabeçalho, o corpo não leva classe nenhuma e o cabeçalho fica
   transparente, com a fotografia por trás para se ler contra ela. As outras
   páginas levam 'folha-só' ou nada, conforme o que têm para mostrar. */
$classesDoCorpo = trim((string)($bodyClass ?? ''));
?>
<body<?= $classesDoCorpo !== '' ? ' class="' . e($classesDoCorpo) . '"' : '' ?>>

<?php /* Saltar para o conteúdo. O primeiro elemento que ganha foco na página, e
         invisível até alguém lá chegar com o teclado: sem ele, quem navega
         assim atravessa o menu inteiro em cada página antes de chegar ao
         texto. */ ?>
<a class="saltar" href="#conteudo"><?= e(__('site.skip')) ?></a>

<?php require __DIR__ . '/../partials/header.php'; ?>

<main id="conteudo">
    <?php
    /* As mensagens de página, e só quando existem.

       O parcial do pacote só desenha quatro espécies — success, error, warning,
       info —, e o que é de um formulário é desenhado onde o formulário está. Por
       isso a pergunta tem de ser feita aqui e não lá dentro: um `<div class="miolo">`
       desenhado sempre, mesmo vazio, tem a goteira do miolo e empurra a página
       para baixo — e numa abertura que passa por baixo do cabeçalho transparente,
       empurrava a fotografia para fora do sítio e deixava uma banda escura por
       cima dela. */
    $deEcrã = array_intersect_key($flashes, array_flip(['success', 'error', 'warning', 'info']));
    ?>
    <?php if ($deEcrã !== []): ?>
        <?php /* Pela cadeia das vistas: este parcial vive no pacote e não aqui. O
                 $flashes vai explícito porque o `partial()` corre num âmbito
                 fechado — só vê o que lhe passam —, e sem isto nenhuma mensagem
                 de página aparecia em sítio nenhum. */ ?>
        <div class="miolo" style="padding-top: 90px"><?php partial('partials/flash', ['flashes' => $deEcrã]); ?></div>
    <?php endif; ?>
    <?= $content ?>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>

<script src="<?= asset('js/site.js') ?>" defer></script>
<?php clear_old(); ?>
</body>
</html>
