<?php
declare(strict_types=1);

namespace App\Controllers;

use Admedia\Core\Controller;
use Admedia\Core\Request;
use Admedia\Core\Response;
use Admedia\Shop\Discount;
use Admedia\Shop\Payment\Gateways;
use Admedia\Shop\Services\Cart;
use Admedia\Shop\Shipping;
use Admedia\Shop\Shop;
use Admedia\Shop\Vat;

/**
 * A página do checkout. Mostra-a; não cobra nada.
 *
 * O pacote tem `POST /checkout` e mais nada: no desenho dele o cesto já pede o
 * e-mail e a zona, e o botão de finalizar manda tudo de uma vez. O desenho
 * desta casa parte isso em dois — ver `docs/maquete/Loja.dc.html`, ecrã
 * «Checkout» —, e esta página é a metade que faltava.
 *
 * **Quem cobra continua a ser o pacote.** O formulário desta página submete
 * para o mesmo `POST /checkout` de sempre, com os mesmos campos que ele lê:
 * nada sobre dinheiro sai daqui.
 */
final class CheckoutPageController extends Controller
{
    public function show(Request $req): Response
    {
        $cart = Cart::contents();

        /* Um checkout de um cesto vazio não é uma página: é um engano. */
        if ($cart['lines'] === []) {
            return $this->redirect(Shop::to('cart'));
        }

        $discount = Discount::on($cart['subtotal_cents']);

        return $this->render('shop/checkout', [
            'cart'     => $cart,
            'discount' => $discount,
            'shipping' => Shipping::options(
                max(0, $cart['subtotal_cents'] - $discount['cents']),
                $cart['weight_g'],
                $cart['count'],
                $discount['free_shipping']
            ),
            'vat'       => Vat::ofLines($cart['lines']),
            'gateways'  => Gateways::available(),
            'canSell'   => Gateways::anyConfigured() && Shipping::anyConfigured(),
        ]);
    }
}
