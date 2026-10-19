<?php
declare(strict_types=1);

namespace App\Controllers;

use Admedia\Core\Controller;
use Admedia\Core\Request;
use Admedia\Core\Response;
use Admedia\Shop\Models\Product;
use Admedia\Shop\Models\Variant;
use Admedia\Shop\Services\Cart;
use Admedia\Shop\Shop;

/**
 * Juntar ao cesto o que a Casa usa com uma peça, de uma vez.
 *
 * O `POST /carrinho/adicionar` do pacote leva uma peça de cada vez, e está
 * certo: é o que um botão «Adicionar» faz. O bloco «O que a Casa usa com esta
 * peça» é outra coisa — três peças com uma caixa cada e um total que acompanha,
 * e um botão só no fim. Ver `docs/maquete/Loja.dc.html`.
 *
 * **Não é um segundo caminho para dentro do cesto.** Chama o mesmo
 * `Cart::add()` que o controlador do pacote, peça a peça, e com as mesmas
 * recusas: uma peça despublicada ou esgotada não entra, e uma que se venda por
 * tamanho não entra sem tamanho — aqui não há onde o escolher, por isso essa
 * fica de fora e é dito. Uma segunda via de escrita para o cesto acabaria a
 * divergir da primeira, e a que divergisse seria esta.
 */
final class KitController extends Controller
{
    /** Quantas peças um kit pode levar. Três na maquete; dez é folga. */
    private const LIMITE = 10;

    public function add(Request $req): Response
    {
        $pedidas = $req->input('product_id', []);
        if (!is_array($pedidas)) {
            return $this->back($req, Shop::to('index'));
        }

        $juntas  = [];
        $deFora  = [];

        foreach (array_slice($pedidas, 0, self::LIMITE) as $cru) {
            $id      = (int)$cru;
            $produto = $id > 0 ? Product::find($id) : null;

            if ($produto === null || !Product::isAvailable($produto)) {
                continue;
            }

            /* Uma peça que se vende por tamanho não se compra sem um, e aqui
               não há onde o escolher. Mandá-la para o cesto sem tamanho punha
               lá uma linha que nenhum armazém consegue aviar. */
            if (Variant::hasAny($id)) {
                $deFora[] = (string)$produto['name'];
                continue;
            }

            if (Cart::add($id, 1) > 0) {
                $juntas[] = (string)$produto['name'];
            }
        }

        if ($juntas === []) {
            $this->flash('error', __('shop.cart.unavailable'));
            return $this->back($req, Shop::to('index'));
        }

        $this->flash('success', __('loja.kit.added', ['n' => count($juntas)]));

        /* Dito e não escondido: quem carregou no botão a contar com três peças
           tem de saber que foram duas, e qual é a que ficou de fora. */
        if ($deFora !== []) {
            $this->flash('info', __('loja.kit.left_out', ['names' => implode(', ', $deFora)]));
        }

        return $this->redirect(Shop::to('cart'));
    }
}
