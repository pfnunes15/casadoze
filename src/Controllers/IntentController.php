<?php
declare(strict_types=1);

namespace App\Controllers;

use Admedia\Core\Controller;
use Admedia\Core\Request;
use Admedia\Core\Response;
use Admedia\Shop\Models\Category;
use Admedia\Shop\Models\Product;
use Admedia\Shop\Models\Tag;
use Admedia\Shop\Services\Cart;

/**
 * A loja vista por uma intenção, e não por uma prateleira.
 *
 * A loja arruma-se por categorias, que é como se arruma um armazém — velas de
 * um lado, cristais do outro. Quem chega não procura uma vela: procura dormir
 * melhor. O bloco «Comprar por intenção» da porta de entrada abre o catálogo
 * por essa razão, e é aqui que ele chega.
 *
 * **Isto existe porque os cinco cartões já apontavam para cá.** O endereço
 * saiu com a redução a uma página só e os cartões ficaram, a apontar para um
 * 404 que ninguém via sem lhes carregar.
 *
 * Não é um controlador de loja paralelo: desenha a mesma vista `shop/index`
 * que a montra, com a mesma forma de dados, e o que muda é a lista e um
 * `$tag` que a vista usa para saber qual é o chip aceso. Uma segunda vista
 * acabaria a divergir da primeira — e a que divergisse seria esta, que é a
 * que menos gente abre.
 */
final class IntentController extends Controller
{
    public function show(Request $req, array $params): Response
    {
        $tag = Tag::findBySlug((string)($params['slug'] ?? ''));

        /* O mesmo 404 de um produto que não existe. Uma etiqueta apagada não
           deve ser distinguível de uma que nunca existiu. */
        if ($tag === null) {
            $this->notFound();
        }

        return $this->render('shop/index', [
            'products'   => Product::withTag((int)$tag['id']),
            'categories' => Category::tree(true),
            'category'   => null,
            'tag'        => $tag,
            'cart'       => Cart::contents(),
        ]);
    }
}
