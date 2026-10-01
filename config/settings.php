<?php
declare(strict_types=1);

/**
 * Tudo o que é do site e não é uma página.
 *
 * O logótipo, a morada, o telefone, as redes, o rodapé, as chaves do Stripe — as
 * coisas que um visitante encontra em todas as páginas e que todos os projectos
 * têm, cada um com valores diferentes. Antes disto estavam escritas dentro de
 * views/partials/header.php e footer.php, o que queria dizer que um segundo
 * projecto não podia reaproveitar nenhum dos dois sem os editar.
 *
 * Declaradas como o config/blocks.php e desenhadas pelos mesmos campos, por isso
 * uma definição ganha um selector de imagem ou uma linha de ajuda por o dizer
 * aqui.
 *
 * ---------------------------------------------------------------------------
 *   'label'   o título do grupo no backoffice
 *   'help'    uma linha por baixo dele
 *   'fields'  chave => campo, pela ordem em que o formulário os mostra
 *
 * A chave é o que guarda o valor, e o que uma vista pede: `setting('site.logo')`.
 * Com prefixo por área, para a lista continuar legível à medida que cresce.
 *
 * **`'global' => true` quer dizer «igual em todas as línguas».** Uma chave do
 * Stripe não se traduz, e uma morada não muda por se estar a ler em francês;
 * já a frase do rodapé muda. Sem isto, mudar a chave do Stripe em português
 * deixava a loja inglesa sem chave nenhuma.
 */

/**
 * As línguas que o CMS conhece, prontas a aparecer como opções. O catálogo está
 * em config/locales.php do pacote — acrescentar uma lá faz com que apareça aqui.
 */
$idiomas = [];
foreach (\Admedia\Core\Locales::catalogue() as $code => $lang) {
    $idiomas[$code] = $lang['label'] . ' (' . $lang['short'] . ')';
}

return [

    'identity' => [
        'label'  => 'Identidade',
        'help'   => 'O nome e a marca da Casa. Usados no cabeçalho, no rodapé e no backoffice.',
        'fields' => [
            'site.name' => [
                'type'  => 'text',
                'label' => 'Nome do site',
                'help'  => 'Usado quando não há logótipo, e como texto alternativo dele.',
                'placeholder' => 'Casa de Zé',
            ],
            'site.tagline' => [
                'type'  => 'text',
                'label' => 'Descrição curta',
                'help'  => 'Uma linha a dizer o que a Casa é. Aparece nos resultados de pesquisa '
                         . 'das páginas que não têm descrição própria.',
                'placeholder' => 'Velas, óleos, cristais e ervas preparados à mão, e leituras de tarot',
            ],
            'site.logo' => [
                'type'   => 'image',
                'alt'    => false,   // o que ele diz é o nome do site, acima
                'global' => true,
                'label'  => 'Logótipo',
                'help'   => 'Aparece no cabeçalho do site e no topo do backoffice. '
                          . 'PNG ou SVG com fundo transparente funciona melhor — '
                          . 'o cabeçalho é escuro e passa por cima da fotografia.',
            ],
        ],
    ],

    'languages' => [
        'label'  => 'Idiomas',
        'help'   => 'Em que línguas o site é servido. Cada língua tem as suas páginas, os seus '
                  . 'menus e os seus textos — escritos em Páginas, depois de a língua estar '
                  . 'ligada aqui.',
        'fields' => [
            'i18n.locales' => [
                'type'    => 'checkboxes',
                'global'  => true,
                'choices' => $idiomas,
                'label'   => 'Idiomas do site',
                'help'    => 'Com mais do que um, aparece no cabeçalho um selector de língua, e '
                           . 'cada uma passa a ter o seu endereço: o idioma principal fica em '
                           . '/loja e os outros em /en/loja.',
            ],
            'i18n.default' => [
                'type'    => 'select',
                'global'  => true,
                'choices' => $idiomas,
                'label'   => 'Idioma principal',
                'help'    => 'O que é servido a quem chega sem escolher — e o único sem prefixo '
                           . 'no endereço. É também a língua de onde os textos ainda não '
                           . 'traduzidos são aproveitados.',
            ],
        ],
    ],

    'contact' => [
        'label'  => 'Contactos',
        'help'   => 'Onde é a Casa e como se fala com ela. Aparece no rodapé de todas as páginas '
                  . 'e nos e-mails de marcação e de encomenda.',
        'fields' => [
            'contact.address' => [
                'type'   => 'textarea',
                'rows'   => 4,
                'global' => true,
                'label'  => 'Morada',
                'help'   => 'Uma linha por cada linha da morada. Enter parte-a onde quiser — '
                          . 'é por onde ela parte no rodapé e no e-mail de quem marca '
                          . 'presencialmente.',
            ],
            'contact.email' => [
                'type'   => 'text',
                'global' => true,
                'label'  => 'E-mail',
                'help'   => 'O endereço que aparece no rodapé, e para onde vão os pedidos de '
                          . 'contacto quando não houver outro escrito na Loja.',
            ],
            'contact.phone' => [
                'type'   => 'text',
                'global' => true,
                'label'  => 'Telefone',
                'help'   => 'Escrito como se lê, com espaços. O site tira os espaços sozinho '
                          . 'para o número poder ser marcado de um telefone.',
            ],
            'contact.hours' => [
                'type'  => 'textarea',
                'rows'  => 3,
                'label' => 'Horário',
                /* Não é `global`: «Terças e quintas, 21h–23h» tem de poder ser
                   lido em inglês por quem está a ler o site em inglês. */
                'help'  => 'Quando é que a Casa atende. Uma linha por bloco de dias. '
                         . 'Não é o horário das consultas — esse sai das vagas, em '
                         . 'Consultas > Horário.',
            ],
        ],
    ],

    'social' => [
        'label'  => 'Redes',
        'help'   => 'Os endereços dos perfis da Casa. Uma rede sem endereço não aparece no '
                  . 'rodapé — é assim que se tira uma de lá, sem precisar de ninguém.',
        'fields' => [
            'social.instagram' => ['type' => 'url', 'global' => true, 'label' => 'Instagram'],
            'social.facebook'  => ['type' => 'url', 'global' => true, 'label' => 'Facebook'],
            'social.youtube'   => [
                'type'   => 'url',
                'global' => true,
                'label'  => 'YouTube',
                'help'   => 'O canal do Grimório. É também o endereço do botão da secção das '
                          . 'sessões gratuitas.',
            ],
            'social.whatsapp'  => [
                'type'   => 'url',
                'global' => true,
                'label'  => 'WhatsApp',
                'help'   => 'O endereço completo, começado por https://wa.me/ e o número. '
                          . 'Não o número sozinho.',
            ],
        ],
    ],

    // -----------------------------------------------------------------------
    // A loja.
    //
    // Só as chaves e as taxas. Os produtos, as categorias e os portes são
    // tabelas e vivem em Loja, no backoffice — não são campos.
    // -----------------------------------------------------------------------
    'shop' => [
        'label'  => 'Loja',
        'help'   => 'As chaves do Stripe e o IVA. Enquanto as chaves estiverem vazias a loja '
                  . 'mostra os produtos e não deixa finalizar a compra — que é o que se quer '
                  . 'enquanto ela está a ser preparada.',
        'fields' => [
            'shop.stripe_publishable' => [
                'type'   => 'text',
                'global' => true,
                'label'  => 'Chave publicável',
                'help'   => 'Começa por pk_. É a que o navegador vê, e não é segredo. '
                          . 'Está no Stripe em Programadores → Chaves de API.',
                'placeholder' => 'pk_live_...',
            ],
            'shop.stripe_secret' => [
                'type'   => 'text',
                'global' => true,
                'label'  => 'Chave secreta',
                'help'   => 'Começa por sk_. Esta nunca sai do servidor — não a envie por '
                          . 'e-mail nem a cole em sítio nenhum além deste campo.',
                'placeholder' => 'sk_live_...',
            ],
            'shop.stripe_webhook_secret' => [
                'type'   => 'text',
                'global' => true,
                'label'  => 'Segredo do webhook',
                'help'   => 'Começa por whsec_. No Stripe, em Programadores → Webhooks, crie um '
                          . 'destino para o endereço do site seguido de /pagamento/stripe/aviso '
                          . 'e escolha o evento checkout.session.completed. **Sem isto as '
                          . 'encomendas nunca passam a pagas**, porque é o que prova que o '
                          . 'pagamento é real — quem volta ao site pode ter fechado a janela '
                          . 'antes de pagar.',
                'placeholder' => 'whsec_...',
            ],
            'shop.vat_rate' => [
                'type'   => 'text',
                'global' => true,
                'label'  => 'IVA por omissão',
                'help'   => 'A taxa que se aplica a quase tudo o que vende, em percentagem: 23 '
                          . 'no continente, 22 na Madeira, 16 nos Açores. Um produto com taxa '
                          . 'diferente escreve a sua na ficha dele. **Os preços que escreve nos '
                          . 'produtos já incluem IVA** — é o que o comprador paga —, e esta taxa '
                          . 'serve para o decompor na encomenda e na factura. Vazio não cobra '
                          . 'IVA nenhum.',
                'placeholder' => '23',
            ],
            'shop.email' => [
                'type'   => 'text',
                'global' => true,
                'label'  => 'E-mail para avisos de encomenda',
                'help'   => 'Para onde vai o aviso de cada encomenda paga. Vazio usa o e-mail '
                          . 'de contacto da Casa.',
            ],
        ],
    ],

    'shipping' => [
        'label'  => 'Portes de envio',
        'help'   => 'As zonas e os escalões de preço estão em Loja > Portes, porque são uma '
                  . 'tabela e não um campo. Aqui fica só o que vale para a loja inteira.',
        'fields' => [
            'shipping.free_from' => [
                'type'   => 'text',
                'global' => true,
                'label'  => 'Envio grátis a partir de',
                'help'   => 'O valor da encomenda, sem portes, a partir do qual o envio não é '
                          . 'cobrado. Deixe vazio para cobrar sempre.',
                'placeholder' => '50,00',
            ],
            'shipping.discreet' => [
                'type'  => 'text',
                'label' => 'A promessa da embalagem',
                'help'  => 'A linha que a loja mostra sobre como a encomenda vai embalada. '
                         . 'Numa casa destas é o que mais se pergunta.',
                'placeholder' => 'Sem referência ao conteúdo no exterior',
            ],
        ],
    ],

    // -----------------------------------------------------------------------
    // As consultas.
    //
    // O que é da Casa e não de cada consulta: onde se vai estar quando é online,
    // e com que antecedência se pode desmarcar. O que é de cada consulta — a
    // duração, o preço, o horário — está em Consultas.
    // -----------------------------------------------------------------------
    'readings' => [
        'label'  => 'Consultas',
        'help'   => 'O que vale para todas as consultas. A duração, o preço e o horário de cada '
                  . 'uma estão em Consultas.',
        'fields' => [
            'readings.online_note' => [
                'type'  => 'textarea',
                'rows'  => 3,
                'label' => 'O que dizer de uma consulta online',
                'help'  => 'Vai no e-mail de quem marcou online. Diga como a sessão acontece e '
                         . 'quando é que a ligação chega — não cole aqui a ligação, que é '
                         . 'diferente em cada sessão.',
            ],
            'readings.cancel_hours' => [
                'type'   => 'number',
                'min'    => 0,
                'max'    => 168,
                'global' => true,
                'label'  => 'Horas antes para poder desmarcar',
                'help'   => 'Com quanta antecedência é que quem marcou ainda pode desmarcar pelo '
                          . 'site. Passado esse prazo, o botão deixa de aparecer e fica o '
                          . 'telefone — que é o que a Casa quer quando a mesa já está preparada.',
            ],
            'readings.talk_url' => [
                'type'  => 'url',
                'label' => 'Onde levar o «Falar com o Zé»',
                'help'  => 'O botão das consultas que não têm agenda — os trabalhos espirituais. '
                         . 'Costuma ser a âncora do formulário de contacto, #contacto, ou o '
                         . 'WhatsApp. Vazio não desenha botão nenhum nessas consultas.',
            ],
        ],
    ],

    'legal' => [
        'label'  => 'Legal',
        'help'   => 'As páginas que a lei pede. Escrevem-se em Páginas como qualquer outra; o que '
                  . 'fica aqui é onde o rodapé as vai buscar.',
        'fields' => [
            'legal.privacy_url' => [
                'type'   => 'url',
                'label'  => 'Política de privacidade',
                'help'   => 'Vazio não põe a ligação no rodapé — e um site que guarda nomes, '
                          . 'telefones e o que traz cada pessoa a uma consulta precisa de a ter.',
            ],
            'legal.terms_url' => [
                'type'  => 'url',
                'label' => 'Termos e condições',
            ],
            'legal.company' => [
                'type'   => 'text',
                'global' => true,
                'label'  => 'Nome fiscal e NIF',
                'help'   => 'Como a empresa se chama nas facturas. Sai nos e-mails de encomenda.',
            ],
        ],
    ],
];
