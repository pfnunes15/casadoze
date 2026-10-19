<?php
declare(strict_types=1);

namespace App\Controllers;

use Admedia\Core\Controller;
use Admedia\Core\Request;
use Admedia\Core\Response;
use Admedia\Shop\Models\Attribute;
use Admedia\Shop\Services\Cart;
use Admedia\Shop\Shipping;

/**
 * O que está no cesto, como pedaço de página.
 *
 * Serve a gaveta lateral que a maquete abre quando se junta uma peça sem sair
 * da página — ver `docs/maquete/Landing Page.dc.html`. Devolve HTML e não JSON
 * de propósito: quem desenha o cesto é a mesma vista em PHP que desenha tudo o
 * resto, e um JSON obrigava a escrever a gaveta uma segunda vez em JavaScript,
 * com os preços formatados outra vez e a divergir da primeira.
 *
 * **Sem layout.** O que sai daqui entra dentro de um `<aside>` que já está no
 * documento; mandar um `<html>` inteiro para lá era pôr uma página dentro de
 * outra.
 *
 * Sem JavaScript isto nunca é pedido, e o formulário de juntar leva a pessoa ao
 * cesto a sério — que é a resposta certa e não um consolo.
 */
final class GavetaController extends Controller
{
    public function show(Request $req): Response
    {
        $cart = Cart::contents();

        /* O desenho de cada peça, para a moldura não ficar vazia. */
        $desenhos = [];
        foreach ($cart['lines'] as $linha) {
            $pid = (int)$linha['product']['id'];
            foreach (Attribute::forProduct($pid) as $c) {
                if (mb_strtolower((string)$c['name']) === 'desenho') {
                    $desenhos[$pid] = (string)$c['value'];
                }
            }
        }

        /* Pelo `view` e não pelo `render()` do controlador: o segundo tem o
           layout público por omissão e não aceita «nenhum». */
        return Response::html($this->view->render('shop/gaveta', [
            'cart'            => $cart,
            'gratisAPartirDe' => Shipping::freeFrom() ?? 0,
            'desenhos'        => $desenhos,
        ]));
    }
}
