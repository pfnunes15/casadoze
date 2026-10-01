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
     * O que vai à frente do número de uma encomenda: CZ-2026-0007.
     *
     * Escrito e não deduzido do nome do site: «casadoze» dava «CAS», e o
     * número de uma encomenda é o que o comprador tem no e-mail quando
     * telefona a perguntar por ela.
     */
    'reference_prefix' => 'CZ',

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
        'checkout'    => '/checkout',
        'thanks'      => '/encomenda/obrigado',
        'callback'    => '/pagamento/{gateway}/aviso',
    ],
];
