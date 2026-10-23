<?php
/**
 * A loja deste site.
 *
 * A existência deste ficheiro é o que liga a loja: sem ele, as migrações da
 * loja não correm e as tabelas dela não são criadas — ver App::hasShop. É por
 * isso que o pacote não traz nenhum, e é por isso que ligar a loja é criar o
 * ficheiro e desligá-la é apagá-lo.
 *
 * Os preços, os portes e as chaves do Stripe estão em Definições, que é onde a
 * casa lhes chega sem precisar de servidor. O que fica aqui é o que não é da
 * casa: os endereços, e a assinatura das encomendas.
 */

return [

    /*
     * What goes in front of an order number: CA-2026-0007.
     *
     * Written out and not derived from the site name, because the number is
     * what a buyer reads back over the phone. Changed from CZ when the house
     * was renamed; safe to change because no order had been placed yet. Once
     * one has, leave it alone — an order keeps the reference it was given.
     */
    'reference_prefix' => 'CA',

    /*
     * Onde a loja vive, neste site, em português.
     *
     * O pacote traz os mesmos endereços em inglês; declarar aqui os nossos é o
     * que faz o mesmo controlador servir /loja aqui e /shop noutro sítio.
     *
     * Os nomes são os que a maquete já usa nas âncoras do cabeçalho — #loja —,
     * para a página e a loja não falarem de si com duas palavras diferentes.
     */
    'paths' => [
        'index'       => '/loja',
        'category'    => '/loja/categoria/{slug}',
        'product'     => '/loja/{slug}',
        'cart'        => '/carrinho',
        'cart_add'    => '/carrinho/adicionar',
        'cart_update' => '/carrinho/actualizar',
        'cart_remove' => '/carrinho/remover',
        /* Os cupões. Estavam por declarar, e o que estava por declarar não
           ficava sem endereço: ficava com o do pacote, em inglês — um
           /carrinho que submete para /cart/coupon. */
        'cart_coupon'     => '/carrinho/cupao',
        'cart_coupon_off' => '/carrinho/cupao/remover',
        'checkout'    => '/checkout',
        'thanks'      => '/encomenda/obrigado',
        'callback'    => '/pagamento/{gateway}/aviso',
    ],
];
