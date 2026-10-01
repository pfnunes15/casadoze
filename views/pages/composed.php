<?php
/**
 * Uma página composta: uma sequência de blocos guardada na base de dados.
 *
 * A porta de entrada e todas as outras páginas desenham por aqui — uma página é
 * sempre uma sequência de blocos, e uma página de prosa é simplesmente um bloco
 * 'texto'. Ver views/partials/sections.php, no pacote, para o que um parcial de
 * bloco pode contar.
 *
 * **Substitui o do pacote**, e por uma razão de folha de estilo e não de lógica:
 * o do pacote escreve as classes `page-view` e `page-shell`, que são do CSS do
 * CMS, e este site não as tem. Aqui a pergunta é a mesma — o primeiro bloco passa
 * por baixo do cabeçalho? — e a resposta é dita nas classes desta casa. Ver a
 * cadeia de vistas em App::viewPaths().
 *
 * @var array $page
 * @var array $sections
 * @var \Admedia\Core\App $app
 */
use Admedia\Cms\Blocks;

$pageTitle       = $page['title'];
$metaDescription = $page['meta_description'] ?? null;

/* O cabeçalho é fixo e transparente, o que só funciona por cima de uma abertura
   de ecrã inteiro com uma fotografia por trás. Qualquer outra página ficava com o
   primeiro título debaixo do cabeçalho, por isso essas levam espaço em cima — é
   o que a classe `com-cabeçalho` faz. */
$abreSobCabeçalho = Blocks::get((string)($sections[0]['type'] ?? ''))['underHeader'] ?? false;

$bodyClass = $abreSobCabeçalho ? '' : 'com-cabeçalho';

/* A imagem com que a página abre é o que quem chega está à espera de ver, e por
   isso é pedida no `<head>`. O endereço tem de ser o mesmo que o bloco desenha,
   ou o browser pede-a duas vezes. */
$aberturaImagem = trim((string)($sections[0]['image'] ?? ''));
$preloadImage   = $aberturaImagem !== '' ? media_url($aberturaImagem) : null;
?>
<?php if (!empty($isPreview)): ?>
    <div class="miolo"><p class="aviso">Pré-visualização — ainda não foi guardada.</p></div>
<?php endif; ?>
<?php
/* Por `require` e não por `partial()`, de propósito: o `partial()` corre num
   âmbito fechado, e o sections.php do pacote precisa de passar $section,
   $headingTag e companhia aos parciais dos blocos por partilha de âmbito. Ir
   buscá-lo pela cadeia porque ele vive no pacote e não aqui — um caminho relativo
   a partir deste ficheiro procurava-o em views/partials/ do site. */
$sectionsFile = $app->viewFile('partials/sections.php');
if ($sectionsFile !== null) {
    require $sectionsFile;
}
