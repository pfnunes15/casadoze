<?php
declare(strict_types=1);

/**
 * O catálogo de blocos deste site.
 *
 * Uma página é uma sequência de blocos. Este ficheiro diz que blocos existem,
 * como se chamam no backoffice e que campos cada um oferece. Os formulários do
 * backoffice são gerados daqui — não há um formulário por bloco escrito à mão —,
 * por isso um bloco novo é uma entrada aqui mais um ficheiro em views/sections/
 * com o nome da chave.
 *
 * Este ficheiro é do site de propósito. O pacote lê-o através de
 * Admedia\Cms\Blocks e não sabe nada de tarot nem de velas; outro site é outro
 * blocks.php e outros parciais, sem uma linha mudar no CMS.
 *
 * ---------------------------------------------------------------------------
 * Escrever uma entrada
 * ---------------------------------------------------------------------------
 *
 *   'label'   O nome que o editor vê. Em linguagem corrente e não em jargão:
 *             quem preenche isto não sabe o que é um «hero».
 *   'summary' Uma frase a dizer o que o visitante acaba por ver. Aparece debaixo
 *             do nome, no momento de escolher um bloco.
 *   'fields'  Os campos do próprio bloco, pela ordem em que o formulário os
 *             mostra.
 *   'items'   Declarado só quando o bloco repete alguma coisa (cartões,
 *             fotografias, tópicos). Deixado de fora quando não repete.
 *   'sample'  Conteúdo de exemplo para a pré-visualização de quem escolhe.
 *
 * Um campo é `coluna => especificação`, onde a chave é a coluna da base de dados
 * onde o valor vai cair — ver page_sections e page_section_items — e a
 * especificação é:
 *
 *   'type'      Que campo desenhar. Ver Admedia\Cms\FieldSpec::TYPES.
 *   'label'     Como se chama.
 *   'help'      Uma linha por baixo. Diga o que escrever, não o que o campo é.
 *   'advanced'  true esconde-o atrás de «Opções avançadas». Para o que um editor
 *               nunca precisa de tocar para publicar uma página.
 *   'store'     'options' guarda o valor no JSON da secção em vez de numa coluna
 *               própria. Só para os extras de que um bloco precisa.
 *
 * ---------------------------------------------------------------------------
 * O que **não** é um bloco
 * ---------------------------------------------------------------------------
 *
 * As consultas e os produtos não se escrevem dentro de um bloco: são registos,
 * em Consultas e em Loja. O bloco é onde o editor decide que a lista aparece —
 * e a lista vem da base de dados. Na maquete estavam escritos à mão dentro da
 * página, e isso queria dizer duas coisas: que a mesma consulta em duas páginas
 * eram duas consultas diferentes para quem contasse horas, e que mudar um preço
 * era mexer em todas as páginas onde ele aparecia. Ver App\Models\Consultation.
 */

return [

    // -----------------------------------------------------------------------
    // A abertura.
    //
    // Uma fotografia de ecrã inteiro, com o retrato esbatido nas bordas, brasas
    // a subir por cima e duas chamadas por baixo. Passa por baixo do cabeçalho,
    // que é transparente — é o que o 'under_header' diz.
    // -----------------------------------------------------------------------
    'abertura' => [
        'label'   => 'Abertura',
        'summary' => 'A fotografia de ecrã inteiro com que a Casa se apresenta, com dois '
                   . 'botões encostados à margem — um para a loja, outro para as consultas.',
        'under_header'  => true,
        'heading_level' => 1,
        'sample' => [
            'heading'   => 'Casa de Zé',
            'cta_label' => 'Entrar na loja',
        ],
        'fields' => [
            'image' => [
                'type'  => 'image',
                'label' => 'Fotografia',
                'help'  => 'Ocupa o ecrã inteiro e é esbatida nas bordas, em redondo. Não leva '
                         . 'texto por cima, e por isso é ela que diz o nome da Casa: o assunto '
                         . 'e o nome devem estar ao centro, que é o que fica à vista.',
            ],
            'heading' => [
                'type'  => 'heading',
                'level' => 1,
                'label' => 'Título',
                'help'  => 'O nome da Casa, ou o que a página é. Não aparece no ecrã — o nome '
                         . 'está desenhado dentro da fotografia —, mas é lido por quem ouve a '
                         . 'página e por um motor de busca, e por isso há um só por página e '
                         . 'não se deixa vazio.',
            ],
            'cta_label' => [
                'type'  => 'text',
                'label' => 'Texto do primeiro botão',
                'help'  => 'Vazio não desenha botão nenhum.',
            ],
            'cta_url' => [
                'type'  => 'url',
                'label' => 'Endereço do primeiro botão',
                'help'  => 'Sem endereço não há botão, mesmo com o texto escrito.',
            ],
            'cta2_label' => [
                'type'  => 'text',
                'store' => 'options',
                'label' => 'Texto do segundo botão',
            ],
            'cta2_url' => [
                'type'  => 'url',
                'store' => 'options',
                'label' => 'Endereço do segundo botão',
            ],
            'embers' => [
                'type'     => 'number',
                'store'    => 'options',
                'min'      => 0,
                'max'      => 200,
                'label'    => 'Quantas brasas',
                'help'     => 'Os pontos de luz a subir por todo o ecrã. Zero desliga-as. '
                            . 'Acima de 120 nota-se num telefone antigo. A quem pediu menos '
                            . 'movimento no sistema não aparecem, seja o número que for.',
                'advanced' => true,
            ],
            'anchor' => ['type' => 'anchor', 'advanced' => true],
        ],
    ],

    // -----------------------------------------------------------------------
    // Comprar por intenção.
    //
    // A loja arruma-se por categorias — velas, cristais, ervas —, que é como se
    // arruma um armazém. Quem chega não procura uma vela: procura dormir melhor.
    // Esta é a outra entrada para o mesmo catálogo, pela razão e não pela
    // prateleira, e cada cartão abre a lista dos produtos com aquela etiqueta.
    // -----------------------------------------------------------------------
    'intencoes' => [
        'label'   => 'Comprar por intenção',
        'summary' => 'A fila de cartões com que se entra na loja pela razão — proteção, amor, '
                   . 'sono — em vez de pela prateleira.',
        'sample' => [
            'eyebrow' => 'Comprar por intenção',
            'heading' => 'O que procuras?',
            'items'   => [
                ['title' => 'Proteção', 'subtitle' => 'protecao'],
            ],
        ],
        'fields' => [
            'eyebrow' => ['type' => 'eyebrow', 'label' => 'Sobrescrita'],
            'heading' => ['type' => 'heading', 'label' => 'Título'],
            'anchor'  => ['type' => 'anchor', 'advanced' => true],
        ],
        'items' => [
            'label'    => 'Intenções',
            'singular' => 'intenção',
            'fields'   => [
                'title' => [
                    'type'  => 'text',
                    'label' => 'Intenção',
                    'help'  => 'Uma ou duas palavras. É o que o cartão diz em grande.',
                ],
                'subtitle' => [
                    'type'  => 'text',
                    'label' => 'Etiqueta',
                    'help'  => 'A etiqueta da loja que este cartão abre — o endereço dela, em '
                             . 'minúsculas e sem acentos, como aparece em Loja > Etiquetas. É '
                             . 'ela que decide que produtos aparecem e quantos o cartão conta. '
                             . 'Uma etiqueta que não exista desenha o cartão sem contagem.',
                ],
                'caption' => [
                    'type'    => 'select',
                    'label'   => 'Símbolo',
                    'choices' => [
                        'olho' => 'Olho — proteção',
                        'laco' => 'Dois laços — amor',
                        'sol'  => 'Sol — prosperidade',
                        'fumo' => 'Fumo — limpeza',
                        'lua'  => 'Lua — sono e sonhos',
                    ],
                    'help'    => 'O desenho a traço no cimo do cartão. Sem símbolo escolhido o '
                               . 'cartão desenha-se à mesma, sem ele.',
                ],
            ],
        ],
    ],

    // -----------------------------------------------------------------------
    // A montra da loja.
    //
    // Mostra produtos que estão no catálogo — não os guarda. O editor escolhe
    // quantos e de que categoria, e a lista vem da Loja.
    // -----------------------------------------------------------------------
    'loja-montra' => [
        'label'   => 'Montra da loja',
        'summary' => 'Uma grelha com os produtos da loja e um botão para a loja inteira. '
                   . 'Os produtos vêm da Loja — aqui só se escolhe quantos e de onde.',
        'sample' => [
            'eyebrow' => 'Loja',
            'heading' => 'Preparado à mão, no tempo da lua',
            'body'    => 'Nada sai da Casa sem ser consagrado antes de seguir para ti.',
        ],
        'fields' => [
            'eyebrow' => ['type' => 'eyebrow', 'label' => 'Sobrescrita'],
            'heading' => ['type' => 'heading', 'label' => 'Título'],
            'body'    => [
                'type'  => 'textarea',
                'rows'  => 2,
                'label' => 'Texto',
                'help'  => 'Uma linha debaixo do título. Vazio não deixa buraco.',
            ],
            'category' => [
                'type'  => 'text',
                'store' => 'options',
                'label' => 'Só uma categoria',
                'help'  => 'O endereço da categoria, como está escrito em Loja > Categorias — '
                         . 'por exemplo «velas-e-oleos». Vazio mostra de todas.',
            ],
            'limit' => [
                'type'  => 'number',
                'store' => 'options',
                'min'   => 2,
                'max'   => 24,
                'label' => 'Quantos produtos',
                'help'  => 'Quantos cabem na grelha antes do botão. Oito é o que a maquete '
                         . 'mostra; mais do que doze faz a página crescer sem ninguém chegar '
                         . 'ao fim.',
            ],
            'cta_label' => ['type' => 'text', 'label' => 'Texto do botão'],
            'cta_url'   => [
                'type'  => 'url',
                'label' => 'Endereço do botão',
                'help'  => 'A loja inteira. Vazio usa o endereço da loja deste site.',
            ],
            'anchor' => ['type' => 'anchor', 'advanced' => true],
        ],
    ],

    // -----------------------------------------------------------------------
    // Agora na Casa.
    //
    // O próximo sabbat e o mês da lua. As duas coisas calculam-se — ver
    // App\Services\Lua —, e por isso este bloco quase não tem campos: o que se
    // escreve é o kit, e o nome dele é o do sabbat.
    // -----------------------------------------------------------------------
    'agora' => [
        'label'   => 'Agora na Casa',
        'summary' => 'O próximo sabbat com a contagem dos dias, o kit da data, e a fita com '
                   . 'as fases da lua do mês. As datas e as luas são calculadas; não se escrevem.',
        'sample' => [
            'eyebrow'   => 'Agora na Casa',
            'body'      => 'Velas, ervas e o ritual escrito à mão para celebrar a data.',
            'cta_label' => 'Ver o kit',
        ],
        'fields' => [
            'eyebrow' => ['type' => 'eyebrow', 'label' => 'Sobrescrita'],
            'body' => [
                'type'  => 'textarea',
                'rows'  => 3,
                'label' => 'O que é o kit',
                'help'  => 'Duas linhas a dizer o que vai dentro. O nome não se escreve: é o do '
                         . 'sabbat que vier a seguir, e muda sozinho.',
            ],
            'price' => [
                'type'  => 'text',
                'store' => 'options',
                'label' => 'Preço do kit',
                'help'  => 'Escrito como aparece — «38,00 €». Vazio não mostra preço nenhum, '
                         . 'que é o que deve acontecer enquanto não houver um.',
            ],
            'cta_label' => ['type' => 'text', 'label' => 'Texto do botão'],
            'cta_url'   => ['type' => 'url',  'label' => 'Endereço do botão'],
            'anchor'    => ['type' => 'anchor', 'advanced' => true],
        ],
    ],

    // -----------------------------------------------------------------------
    // A ponte.
    //
    // Separa o que se leva para casa do que se vive à mesa. Por trás passam duas
    // filas de letras ocas — «Loja» e «Consultas» —, que são textura e não
    // texto, e por isso não se escrevem aqui.
    // -----------------------------------------------------------------------
    'ponte' => [
        'label'   => 'Ponte entre a loja e as consultas',
        'summary' => 'A faixa que separa os produtos dos serviços, com as duas palavras em '
                   . 'letras ocas a passar por trás.',
        'sample' => [
            'eyebrow' => 'Da loja à mesa',
            'heading' => 'Os objetos levam-se para casa. As consultas vivem-se à mesa.',
        ],
        'fields' => [
            'eyebrow' => ['type' => 'eyebrow', 'label' => 'Sobrescrita'],
            'heading' => ['type' => 'heading', 'label' => 'Título'],
            'body'    => ['type' => 'textarea', 'rows' => 3, 'label' => 'Frase'],
            'cta_label' => ['type' => 'text', 'label' => 'O que fica acima', 'help' => 'Vazio diz «Loja · produtos com envio».'],
            'cta_url'   => ['type' => 'url',  'label' => 'Para onde leva', 'help' => 'Vazio leva à loja desta página.'],
            'cta2_label' => ['type' => 'text', 'store' => 'options', 'label' => 'O que fica abaixo'],
            'cta2_url'   => ['type' => 'url',  'store' => 'options', 'label' => 'Para onde leva'],
            'anchor'     => ['type' => 'anchor', 'advanced' => true],
        ],
    ],

    // -----------------------------------------------------------------------
    // As consultas.
    //
    // Os cartões das consultas publicadas, cada um com a sua agenda. O bloco não
    // guarda consultas nenhumas: elas estão em Consultas, com o horário e o
    // preço, e é de lá que a lista vem.
    // -----------------------------------------------------------------------
    'consultas' => [
        'label'   => 'Consultas',
        'summary' => 'Os cartões das consultas da Casa, cada um com as horas livres e o '
                   . 'formulário de marcação. As consultas vêm de Consultas — aqui escreve-se '
                   . 'o que vai por cima delas.',
        'sample' => [
            'eyebrow' => 'Consultas',
            'heading' => 'Senta-te à mesa da Casa',
            'body'    => 'Leituras e trabalhos feitos pelo Zé, presencialmente ou à distância.',
        ],
        'fields' => [
            'eyebrow' => ['type' => 'eyebrow', 'label' => 'Sobrescrita'],
            'heading' => ['type' => 'heading', 'label' => 'Título'],
            'body'    => [
                'type'  => 'richtext',
                'label' => 'Texto de abertura',
                'help'  => 'O que se diz antes dos cartões. A ressalva de que as leituras não '
                         . 'substituem um médico não se escreve aqui — o bloco põe-na sozinho, '
                         . 'em todas as línguas, debaixo da marcação.',
            ],
            'agenda' => [
                'type'    => 'select',
                'store'   => 'options',
                'choices' => [
                    'inline' => 'Com a agenda em cada cartão',
                    'linked' => 'Só com um botão que leva à agenda',
                ],
                'label'   => 'Como se marca',
                'help'    => 'Com a agenda no cartão marca-se sem sair da página, que é o que '
                           . 'faz mais gente chegar ao fim. Só com o botão é mais leve, e é o '
                           . 'que faz sentido numa página onde as consultas são uma menção de '
                           . 'passagem.',
            ],
            'anchor' => ['type' => 'anchor', 'advanced' => true],
        ],
    ],

    // -----------------------------------------------------------------------
    // Quem está por trás.
    // -----------------------------------------------------------------------
    'ze' => [
        'label'   => 'Quem está por trás',
        'summary' => 'O retrato ao lado do texto, com duas listas: o que a Casa faz e o que '
                   . 'não faz.',
        'sample' => [
            'eyebrow' => 'Quem está por trás',
            'heading' => 'O Zé',
        ],
        'fields' => [
            'eyebrow'   => ['type' => 'eyebrow', 'label' => 'Sobrescrita'],
            'heading'   => ['type' => 'heading', 'label' => 'Título'],
            'image'     => ['type' => 'image', 'label' => 'Retrato'],
            'body'      => ['type' => 'richtext', 'label' => 'Texto'],
            'cta_label' => ['type' => 'text', 'label' => 'Texto do botão'],
            'cta_url'   => ['type' => 'url', 'label' => 'Endereço do botão'],
            'anchor'    => ['type' => 'anchor', 'advanced' => true],
        ],
        /* As duas listas são itens e não dois campos de texto, e é isso que faz
           «o que faço» poder ter cinco linhas numa página e três noutra sem
           ninguém mexer no código. O que as separa é o `rating`, que aqui vale
           1 para uma e 0 para a outra — ver o parcial. */
        'items' => [
            'label'    => 'Linhas',
            'singular' => 'linha',
            'fields'   => [
                'title'  => ['type' => 'text', 'label' => 'A linha'],
                'rating' => [
                    'type'    => 'select',
                    'choices' => [1 => 'O que faço', 0 => 'O que não faço'],
                    'label'   => 'De que lista',
                    'help'    => 'As duas listas saem lado a lado, na ordem em que as linhas '
                               . 'estão escritas.',
                ],
            ],
        ],
    ],

    // -----------------------------------------------------------------------
    // Os testemunhos.
    // -----------------------------------------------------------------------
    'testemunhos' => [
        'label'   => 'Testemunhos',
        'summary' => 'Palavras de quem passou pela Casa, em cartões lado a lado.',
        'sample' => [
            'eyebrow' => 'Quem passou pela Casa',
            'heading' => 'Palavras de quem voltou',
            'items'   => [
                ['body' => 'Saí de lá mais leve do que entrei.', 'title' => 'M.'],
            ],
        ],
        'fields' => [
            'eyebrow' => ['type' => 'eyebrow', 'label' => 'Sobrescrita'],
            'heading' => ['type' => 'heading', 'label' => 'Título'],
            'anchor'  => ['type' => 'anchor', 'advanced' => true],
        ],
        'items' => [
            'label'    => 'Testemunhos',
            'singular' => 'testemunho',
            'fields'   => [
                'body'  => [
                    'type'  => 'textarea',
                    'rows'  => 4,
                    'label' => 'O que disse',
                    'help'  => 'Sem aspas — o desenho põe-nas. Duas ou três linhas: um '
                             . 'testemunho comprido não se lê.',
                ],
                'title' => [
                    'type'  => 'text',
                    'label' => 'Quem',
                    'help'  => 'Uma inicial ou um primeiro nome basta, e é o que se deve usar: '
                             . 'quem conta uma coisa destas não tem de aparecer com o nome todo.',
                ],
                'subtitle' => ['type' => 'text', 'label' => 'O que marcou', 'help' => 'Por exemplo «Leitura completa, Março».'],
            ],
        ],
    ],

    // -----------------------------------------------------------------------
    // O Grimório: as sessões gratuitas.
    //
    // Liga o que se vê de graça ao que se vende: quem assiste a um ritual com
    // sálvia encontra o molho de sálvia a um clique. O endereço do canal vem das
    // Definições e não se escreve aqui.
    // -----------------------------------------------------------------------
    'grimorio' => [
        'label'   => 'Grimório',
        'summary' => 'As sessões gratuitas do canal, em três cartões, cada um a dizer que '
                   . 'produto foi usado.',
        'sample' => [
            'eyebrow' => 'Grimório',
            'heading' => 'Sessões gratuitas no YouTube',
            'items'   => [
                ['title' => '[Título da sessão]', 'caption' => '[mm:ss]'],
            ],
        ],
        'fields' => [
            'eyebrow'   => ['type' => 'eyebrow', 'label' => 'Sobrescrita'],
            'heading'   => ['type' => 'heading', 'label' => 'Título'],
            'body'      => ['type' => 'textarea', 'rows' => 3, 'label' => 'Frase'],
            'cta_label' => [
                'type'  => 'text',
                'label' => 'Texto do botão do canal',
                'help'  => 'O endereço não se escreve aqui: vem de Definições > Redes > '
                         . 'YouTube, que é o mesmo que o rodapé usa. Sem ele o botão não '
                         . 'aparece.',
            ],
            'anchor'    => ['type' => 'anchor', 'advanced' => true],
        ],
        'items' => [
            'label'    => 'Sessões',
            'singular' => 'sessão',
            'fields'   => [
                'title'    => ['type' => 'text', 'label' => 'Título da sessão'],
                'url'      => [
                    'type'  => 'url',
                    'label' => 'Endereço do vídeo',
                    'help'  => 'Vazio desenha a moldura à espera, como na maquete — diz que '
                             . 'ali vai estar um vídeo sem fingir que já está.',
                ],
                'caption'  => ['type' => 'text', 'label' => 'Duração', 'help' => 'Escrita como se lê: «12:40».'],
                'image'    => ['type' => 'image', 'label' => 'Miniatura'],
                'subtitle' => ['type' => 'text', 'label' => 'Produto usado'],
                'link_label' => ['type' => 'url', 'label' => 'Onde está esse produto', 'help' => 'Vazio leva à loja desta página.'],
            ],
        ],
    ],

    // -----------------------------------------------------------------------
    // A previsão dos signos.
    //
    // Quase não tem campos: os doze signos, as constelações, os períodos e a
    // semana são calculados — ver App\Services\Signos —, e as frases estão no
    // catálogo de línguas. O que o editor escreve é a sobrescrita e o título.
    // -----------------------------------------------------------------------
    'signos' => [
        'label'   => 'Previsão dos signos',
        'summary' => 'A roda dos doze signos com a constelação ao centro, e a carta da semana '
                   . 'do signo escolhido. Abre no signo em que o sol anda hoje.',
        'sample' => [
            'eyebrow' => 'Previsão dos signos',
            'heading' => 'A tua semana nos astros',
        ],
        'fields' => [
            'eyebrow' => ['type' => 'eyebrow', 'label' => 'Sobrescrita'],
            'heading' => ['type' => 'heading', 'label' => 'Título'],
            'anchor'  => ['type' => 'anchor', 'advanced' => true],
        ],
    ],

    // -----------------------------------------------------------------------
    // As garantias.
    // -----------------------------------------------------------------------
    'garantias' => [
        'label'   => 'Garantias',
        'summary' => 'A fila do que a Casa promete: envios, pagamento, devoluções, embalagem.',
        'sample' => [
            'items' => [
                ['title' => 'Embalagem discreta', 'body' => 'Sem referência ao conteúdo no exterior'],
            ],
        ],
        'fields' => [
            'anchor' => ['type' => 'anchor', 'advanced' => true],
        ],
        'items' => [
            'label'    => 'Garantias',
            'singular' => 'garantia',
            'fields'   => [
                'title' => ['type' => 'text', 'label' => 'O quê'],
                'body'  => ['type' => 'textarea', 'rows' => 2, 'label' => 'O detalhe'],
            ],
        ],
    ],

    // -----------------------------------------------------------------------
    // Prosa.
    //
    // O bloco que serve para o que não tem bloco. Está em último porque é o que
    // se escolhe depois de se olhar para os outros e nenhum servir.
    // -----------------------------------------------------------------------
    'texto' => [
        'label'   => 'Texto',
        'summary' => 'Um título e prosa, na largura do miolo. Para as páginas que são texto — '
                   . 'a privacidade, os termos, uma carta.',
        'sample' => [
            'heading' => 'Um título',
            'body'    => '<p>E o que há para dizer debaixo dele.</p>',
        ],
        'fields' => [
            'eyebrow' => ['type' => 'eyebrow', 'label' => 'Sobrescrita'],
            'heading' => ['type' => 'heading', 'label' => 'Título'],
            'body'    => ['type' => 'richtext', 'label' => 'Texto'],
            'anchor'  => ['type' => 'anchor', 'advanced' => true],
        ],
    ],
];
