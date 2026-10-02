/* ===========================================================================
   Casa de Zé — o guião do site.
   ===========================================================================

   **Nada aqui é necessário para a página se ler.** O cabeçalho fica
   transparente, o painel do menu fica fechado, a agenda mostra as horas todas
   numa lista — e o site funciona. Isto é o que o torna melhor quando há
   JavaScript, não o que o torna possível: ver o `<noscript>` da agenda e o
   `hidden` do painel, que é o estado certo e não um estado a corrigir.

   Escrito sem dependências e sem passo de compilação. Não é purismo: é o que
   faz um ficheiro deste caber no `.htaccess` de um alojamento partilhado sem
   nada no meio, e é o que faz quem vier depois poder mudar uma linha sem
   instalar nada.
   =========================================================================== */
(function () {
  'use strict';

  /* ---------------------------------------------------------------------
     O cabeçalho ganha fundo ao descer.

     Com um IntersectionObserver e não com um listener de `scroll`: um
     listener de scroll corre dezenas de vezes por segundo e obriga a medir a
     página em cada uma delas. Isto corre duas vezes — quando a sentinela
     entra e quando sai — e o browser faz a medição por nós, fora da linha
     principal.
     --------------------------------------------------------------------- */
  var cabeçalho = document.querySelector('[data-cabeçalho]');

  if (cabeçalho && 'IntersectionObserver' in window) {
    /* A sentinela: um pedaço de nada no topo do documento, da altura do
       cabeçalho. Enquanto estiver à vista, estamos no topo. Posta aqui e não
       no HTML porque não é conteúdo — é um instrumento de medida, e não tem
       nada a dizer a quem ouve a página. */
    var sentinela = document.createElement('div');
    sentinela.setAttribute('aria-hidden', 'true');
    sentinela.style.cssText = 'position:absolute;top:0;left:0;width:1px;height:80px;pointer-events:none';
    document.body.prepend(sentinela);

    new IntersectionObserver(function (entradas) {
      if (entradas[0].isIntersecting) {
        cabeçalho.removeAttribute('data-descido');
      } else {
        cabeçalho.setAttribute('data-descido', '');
      }
    }).observe(sentinela);
  }

  /* ---------------------------------------------------------------------
     Abrir e fechar coisas: o painel do menu e a lista de idiomas.

     Uma função para os dois porque a pergunta é a mesma — mostrar uma coisa,
     dizer ao botão que ela está aberta, e fechá-la com a tecla Escape ou com
     um toque fora dela. Escrita duas vezes, uma delas acabava sem o Escape.
     --------------------------------------------------------------------- */
  function arranjarGaveta(botão, gaveta, opções) {
    if (!botão || !gaveta) { return; }
    opções = opções || {};

    function mostrar(aberta) {
      gaveta.hidden = !aberta;
      botão.setAttribute('aria-expanded', aberta ? 'true' : 'false');
      if (opções.trancarPágina) {
        /* O corpo deixa de rolar enquanto o painel está aberto. Sem isto, o
           dedo que arrasta o painel arrasta a página por trás dele. */
        document.documentElement.style.overflow = aberta ? 'hidden' : '';
      }
      if (!aberta && opções.devolverFoco !== false) {
        botão.focus();
      }
    }

    botão.addEventListener('click', function () {
      mostrar(gaveta.hidden);
    });

    if (opções.fechar) {
      gaveta.querySelectorAll(opções.fechar).forEach(function (b) {
        b.addEventListener('click', function () { mostrar(false); });
      });
    }

    /* Escape fecha. É o que qualquer pessoa experimenta primeiro, e um painel
       que só se fecha com o rato é um painel que prende quem usa o teclado. */
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !gaveta.hidden) { mostrar(false); }
    });

    /* Um toque fora fecha. No painel, «fora» é o escuro por trás da folha —
       daí comparar com o próprio alvo e não usar `contains`. */
    gaveta.addEventListener('click', function (e) {
      if (e.target === gaveta) { mostrar(false); }
    });

    if (opções.foraFecha) {
      document.addEventListener('click', function (e) {
        if (gaveta.hidden) { return; }
        if (!gaveta.contains(e.target) && !botão.contains(e.target)) { mostrar(false); }
      });
    }
  }

  arranjarGaveta(
    document.querySelector('[data-abrir-painel]'),
    document.querySelector('[data-painel]'),
    { trancarPágina: true, fechar: '[data-fechar-painel]' }
  );

  arranjarGaveta(
    document.querySelector('[data-abrir-idiomas]'),
    document.querySelector('[data-lista-idiomas]'),
    { foraFecha: true }
  );

  /* ---------------------------------------------------------------------
     As brasas da abertura.

     Pontos de luz a subir devagar por todo o ecrã de abertura, com rolos de
     fumo roxo a passar atrás deles. Desenhadas num canvas e não com elementos:
     cem divs a mexer são cem coisas para o browser recompor em cada quadro, e
     num telefone isso vê-se.

     Cada brasa é uma estampa desenhada uma vez — um círculo que se apaga para
     fora — e depois copiada. Desenhar o gradiente em cada brasa e em cada
     quadro era pedir ao browser cem gradientes por quadro; assim é uma cópia de
     imagem, que é a coisa que ele faz mais depressa.

     Três em cada dez são roxas e não cor de brasa. Com uma cor só o ecrã lê-se
     como uma fogueira; é a mistura das duas que o faz ler como a Casa.

     **Não corre para quem pediu menos movimento.** A pergunta é feita uma vez
     e não é refeita: quem muda a preferência a meio recarrega a página, e um
     listener para isso era código a correr sempre para um caso que não
     acontece.
     --------------------------------------------------------------------- */
  var tela = document.querySelector('[data-brasas]');
  var queto = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (tela && tela.getContext && !queto) {
    var ctx = tela.getContext('2d');
    var brasas = [];
    var fumos = [];
    var quantas = parseInt(tela.getAttribute('data-brasas') || '70', 10);
    var larg = 0, alt = 0, dpr = 1;

    /* Uma estampa: um quadrado com um círculo que se apaga do centro para fora.
       As paragens são a cor — do branco quente ao laranja que desaparece, ou do
       branco lilás ao roxo. */
    function estampa(lado, paragens) {
      var c = document.createElement('canvas');
      c.width = c.height = lado;
      var g = c.getContext('2d');
      var grad = g.createRadialGradient(lado / 2, lado / 2, 0, lado / 2, lado / 2, lado / 2);
      for (var k = 0; k < paragens.length; k++) { grad.addColorStop(paragens[k][0], paragens[k][1]); }
      g.fillStyle = grad;
      g.fillRect(0, 0, lado, lado);
      return c;
    }

    var brasaEstampa = estampa(64, [
      [0, 'rgba(255, 236, 190, 1)'], [0.18, 'rgba(255, 170, 80, .9)'],
      [0.45, 'rgba(224, 110, 40, .25)'], [1, 'rgba(224, 110, 40, 0)']
    ]);
    var roxaEstampa = estampa(64, [
      [0, 'rgba(240, 220, 255, 1)'], [0.2, 'rgba(190, 140, 255, .8)'],
      [0.5, 'rgba(140, 80, 220, .2)'], [1, 'rgba(140, 80, 220, 0)']
    ]);
    var fumoEstampa = estampa(256, [
      [0, 'rgba(140, 90, 190, .5)'], [0.5, 'rgba(90, 64, 120, .2)'], [1, 'rgba(60, 40, 80, 0)']
    ]);

    function medir() {
      /* O canvas tem duas medidas: a que ocupa na página e a dos pixéis que
         tem lá dentro. Sem multiplicar pela densidade do ecrã, num telefone
         as brasas saem desfocadas.

         Escrever `width` limpa a tela e repõe o estado do contexto. Não faz
         diferença porque o quadro seguinte volta a pôr tudo o que usa, mas é a
         razão de isto não poder ser chamado a meio de um desenho. */
      var novaLarg = tela.clientWidth;
      var novaAlt  = tela.clientHeight;
      var novoDpr  = Math.min(window.devicePixelRatio || 1, 2);

      // Sem mudança, não mexer: escrever `width` com o mesmo valor limpa a tela
      // à mesma, e isso é um piscar de olhos a cada medição inútil.
      if (novaLarg === larg && novaAlt === alt && novoDpr === dpr) { return; }

      larg = novaLarg;
      alt = novaAlt;
      dpr = novoDpr;
      tela.width = Math.round(larg * dpr);
      tela.height = Math.round(alt * dpr);
    }

    /* As brasas vivem em coordenadas de 0 a 1 e não em pixéis: assim uma janela
       que muda de tamanho não as atira para fora nem obriga a recalculá-las. */
    function nascer(brasa, deBaixo) {
      brasa.x = Math.random();
      brasa.y = deBaixo ? 1.05 : Math.random();
      brasa.sobe = -(0.0007 + Math.random() * 0.0016);
      brasa.lado = (Math.random() - 0.45) * 0.0004;
      brasa.r = 0.6 + Math.random() * 2.2;
      brasa.fase = Math.random() * Math.PI * 2;
      brasa.roxa = Math.random() < 0.3;
    }

    medir();
    for (var i = 0; i < quantas; i++) { brasas.push({}); nascer(brasas[i], false); }
    for (var f = 0; f < 7; f++) {
      fumos.push({
        x: Math.random(), y: 0.3 + Math.random() * 0.7,
        tamanho: 0.4 + Math.random() * 0.6,
        anda: (Math.random() - 0.5) * 0.00018,
        opacidade: 0.05 + Math.random() * 0.06
      });
    }

    var último = 0;
    var pedido = 0;

    function quadro(agora) {
      /* O tempo que passou desde o quadro anterior, e não um passo fixo: num
         ecrã de 120 Hz um passo fixo fazia as brasas subirem ao dobro da
         velocidade.

         **O primeiro quadro conta como um.** Com `último` a zero, o primeiro
         `agora - último` são os milissegundos desde que a página abriu — e o
         campo de brasas inteiro dava um salto à vista no instante em que
         aparecia. O mesmo acontecia ao voltar de um separador em segundo
         plano, onde o relógio anda e o desenho não.

         Daí o limite de dois quadros e não de cinquenta milissegundos: uma
         pausa, seja de que tamanho for, retoma onde ficou em vez de adiantar o
         que não se viu. */
      var dt = último === 0 ? 1 : Math.min((agora - último) / 16.67, 2);
      último = agora;

      var W = tela.width, H = tela.height;
      ctx.clearRect(0, 0, W, H);

      /* Primeiro o fumo, por baixo, somado ao fundo como tinta normal. */
      ctx.globalCompositeOperation = 'source-over';
      for (var m = 0; m < fumos.length; m++) {
        var fu = fumos[m];
        fu.x += fu.anda * 16 * dt;
        if (fu.x < -0.4) { fu.x = 1.4; }
        if (fu.x > 1.4) { fu.x = -0.4; }
        var sz = fu.tamanho * W;
        ctx.globalAlpha = fu.opacidade * (0.8 + 0.2 * Math.sin(agora * 0.0003 + fu.y * 9));
        ctx.drawImage(
          fumoEstampa,
          fu.x * W - sz / 2,
          fu.y * H - sz * 0.3 + Math.sin(agora * 0.0002 + fu.x * 5) * 20 * dpr,
          sz, sz * 0.6
        );
      }

      /* Depois as brasas, somadas à luz do que está por baixo — 'lighter' — que
         é o que faz duas brasas sobrepostas brilharem mais em vez de uma tapar
         a outra. */
      ctx.globalCompositeOperation = 'lighter';
      for (var j = 0; j < brasas.length; j++) {
        var b = brasas[j];
        b.x += (b.lado + Math.sin(agora * 0.0012 + b.fase) * 0.0003) * dt;
        b.y += b.sobe * dt;

        if (b.y < -0.05 || b.x < -0.05 || b.x > 1.05) { nascer(b, true); }

        /* Apaga-se ao nascer em baixo e ao chegar acima, para nenhuma aparecer
           nem desaparecer de repente no meio do ecrã. */
        var desvanece = Math.min(1, b.y / 0.4) * Math.min(1, (1.05 - b.y) / 0.15);
        ctx.globalAlpha = Math.max(0, desvanece * (0.55 + 0.45 * Math.sin(agora * 0.006 + b.fase * 4)));

        var lado = b.r * 7 * dpr;
        ctx.drawImage(
          b.roxa ? roxaEstampa : brasaEstampa,
          b.x * W - lado / 2,
          b.y * H - lado / 2,
          lado, lado
        );
      }

      ctx.globalAlpha = 1;
      pedido = requestAnimationFrame(quadro);
    }

    function arrancar() {
      if (pedido) { return; }
      // Repor o relógio: retomar com o tempo que passou enquanto não se
      // desenhava era adiantar as brasas o tanto que esteve fora de vista.
      último = 0;
      pedido = requestAnimationFrame(quadro);
    }

    function parar() {
      if (!pedido) { return; }
      cancelAnimationFrame(pedido);
      pedido = 0;
    }

    /* Desenhar só enquanto a abertura está à vista.

       Cem brasas e sete rolos de fumo por quadro não são de graça, e a abertura
       passa a ficar acima do ecrã logo ao fim do primeiro desenrolar. Continuar
       a pintá-la é tirar quadros ao que está mesmo à frente de quem lê — e é
       isso que se sente como estremecimento mais abaixo na página. */
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entradas) {
        if (entradas[0].isIntersecting) { arrancar(); } else { parar(); }
      }, { rootMargin: '120px' }).observe(tela);
    } else {
      arrancar();
    }

    /* Voltar a medir quando a tela muda de tamanho — e é a tela que se observa e
       não a janela, porque é ela que tem de bater certo. Entre o instante em que
       a janela muda e aquele em que se remede, o browser estica o desenho antigo
       para o tamanho novo; com um atraso de um décimo de segundo isso vê-se como
       um esticão. O ResizeObserver chega antes do quadro seguinte. */
    if ('ResizeObserver' in window) {
      new ResizeObserver(medir).observe(tela);
    } else {
      window.addEventListener('resize', medir);
    }
  }

  /* ---------------------------------------------------------------------
     O retrato da abertura fica para trás ao descer.

     Desce a um terço da velocidade da página, o que faz a abertura parecer ter
     fundura em vez de ser uma folha a deslizar. Pára de contar a uma altura e
     meia de ecrã: abaixo disso o retrato já saiu de vista e continuar a
     escrever-lhe a posição era trabalho para ninguém ver.

     Só num ecrã largo. Num telefone o retrato ocupa a largura toda e o mesmo
     deslocamento deixava-o a descobrir fundo em baixo.

     Um `scroll` com `passive` e a escrita adiada para o quadro seguinte: o
     listener só aponta que há trabalho, e quem o faz é o browser quando vai
     desenhar. Escrever o `transform` dentro do listener é obrigá-lo a recompor
     a página a meio do desenrolar.
     --------------------------------------------------------------------- */
  var retrato = document.querySelector('.abertura__retrato');

  if (retrato && !queto) {
    var marcado = false;

    function colocar() {
      marcado = false;
      if (window.innerWidth < 1100) { retrato.style.transform = ''; return; }
      var y = window.pageYOffset || document.documentElement.scrollTop || 0;
      if (y > window.innerHeight * 1.5) { return; }
      retrato.style.transform = 'translate3d(0, ' + (y * 0.35).toFixed(1) + 'px, 0)';
    }

    function marcar() {
      if (marcado) { return; }
      marcado = true;
      requestAnimationFrame(colocar);
    }

    window.addEventListener('scroll', marcar, { passive: true });
    window.addEventListener('resize', marcar);
    colocar();
  }

  /* ---------------------------------------------------------------------
     A luz que segue o rato.

     Duas listas a usam — os cartões das intenções e as linhas das consultas —
     e é a mesma resposta nas duas: escrever onde está o ponteiro e deixar a
     folha de estilo desenhar o resto. É ela que sabe o tamanho e a cor da luz;
     aqui só se diz o sítio.

     Com `pointermove` e não `mousemove`: num ecrã táctil o dedo também é um
     ponteiro, e quem toca vê a luz acender onde tocou.

     Um listener por lista e não um por cartão. Cinco listeners para cinco
     cartões é trabalho a mais para a mesma resposta, e com um só os cartões que
     o editor acrescentar depois já vêm servidos.
     --------------------------------------------------------------------- */
  var listasComLuz = [
    ['.intenções', '.intenção'],
    ['.consultas__lista', '.consulta'],
    ['.montra', '.produto__moldura']
  ];

  for (var n = 0; n < listasComLuz.length; n++) {
    (function (par) {
      var listas = document.querySelectorAll(par[0]);

      for (var k = 0; k < listas.length; k++) {
        (function (lista) {
          lista.addEventListener('pointermove', function (ev) {
            var alvo = ev.target.closest ? ev.target.closest(par[1]) : null;
            if (!alvo) { return; }
            var caixa = alvo.getBoundingClientRect();
            alvo.style.setProperty('--rato-x', (ev.clientX - caixa.left) + 'px');
            alvo.style.setProperty('--rato-y', (ev.clientY - caixa.top) + 'px');
          });

          /* Ao sair, a luz volta ao sítio de origem — fora do cartão — em vez
             de ficar acesa no último ponto por onde o rato passou. */
          lista.addEventListener('pointerleave', function () {
            var cartões = lista.querySelectorAll(par[1]);
            for (var c = 0; c < cartões.length; c++) {
              cartões[c].style.removeProperty('--rato-x');
              cartões[c].style.removeProperty('--rato-y');
            }
          });
        })(listas[k]);
      }
    })(listasComLuz[n]);
  }

  /* ---------------------------------------------------------------------
     As entradas: cada coisa chega quando é a sua vez.

     Sobem 32px, aparecem e saem de desfocado, por 1,1 s. Dentro de cada secção
     as peças entram escalonadas — a sobrescrita primeiro, o título a seguir, a
     frase depois, e os cartões um atrás do outro —, e é o `data-atraso` escrito
     no HTML que decide quanto espera cada uma.

     **O que esconde é o JavaScript, e não a folha de estilo.** Uma regra de CSS
     a pôr `opacity: 0` à espera de um observador deixa a página inteira em
     branco em quem tenha o JavaScript desligado ou em quem o veja falhar a
     carregar. Assim, o pior caso é não haver animação nenhuma — que é um pior
     caso que se lê.

     **Quem pediu menos movimento não leva nada disto**, nem escondido nem
     animado: sai daqui antes de tocar no primeiro elemento.

     O `fill: 'both'` segura o estado final até a animação ser cancelada no
     `onfinish`, e é cancelada de propósito: uma animação terminada que fique
     agarrada ao elemento impede a folha de estilo de lhe mudar seja o que for
     a seguir.
     --------------------------------------------------------------------- */
  if (!queto && 'IntersectionObserver' in window && document.body.animate) {
    var quadros = {
      sobe: [
        { opacity: 0, transform: 'translateY(32px)', filter: 'blur(6px)' },
        { opacity: 1, transform: 'none', filter: 'none' }
      ],
      cortina: [
        { clipPath: 'inset(100% 0 0 0)', transform: 'scale(1.06)' },
        { clipPath: 'inset(0 0 0 0)', transform: 'scale(1)' }
      ],
      risco: [
        { transform: 'scaleX(0)', transformOrigin: '0 50%' },
        { transform: 'scaleX(1)', transformOrigin: '0 50%' }
      ]
    };

    var porEntrar = document.querySelectorAll('[data-entra]');
    var entrou = false;

    function mostrar(el) {
      var tipo = el.getAttribute('data-entra');
      var passos = quadros[tipo] || quadros.sobe;

      var anim = el.animate(passos, {
        duration: tipo === 'cortina' ? 1600 : 1100,
        delay: parseInt(el.getAttribute('data-atraso') || '0', 10),
        easing: 'cubic-bezier(.2, .7, .1, 1)',
        fill: 'both'
      });

      anim.onfinish = function () {
        anim.cancel();
        el.style.opacity = '';
      };
    }

    if (porEntrar.length) {
      var olho = new IntersectionObserver(function (entradas) {
        for (var e = 0; e < entradas.length; e++) {
          if (!entradas[e].isIntersecting) { continue; }
          entrou = true;
          olho.unobserve(entradas[e].target);
          mostrar(entradas[e].target);
        }
      }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

      for (var q = 0; q < porEntrar.length; q++) {
        /* Só o que sobe começa invisível. Um risco que cresce começa com
           `scaleX(0)`, que já é invisível por si; pôr-lhe opacidade zero era
           escondê-lo duas vezes e arriscar deixá-lo escondido. */
        if ((porEntrar[q].getAttribute('data-entra') || 'sobe') === 'sobe') {
          porEntrar[q].style.opacity = '0';
        }
        olho.observe(porEntrar[q]);
      }

      /* A rede. Se ao fim de três segundos nada entrou — um observador que não
         dispara, uma página aberta num separador de fundo, um browser que faz
         isto de outra maneira —, mostra-se tudo e desiste-se da animação. Uma
         página invisível é pior do que uma página sem efeitos. */
      setTimeout(function () {
        if (entrou) { return; }
        olho.disconnect();
        for (var r = 0; r < porEntrar.length; r++) {
          porEntrar[r].style.opacity = '';
        }
      }, 3000);
    }
  }

  /* ---------------------------------------------------------------------
     Os desenhos.

     Cada tela diz qual leva — `data-desenho` — e com que semente. A semente fixa
     o acaso: o grão e o nevoeiro são aleatórios, mas com a mesma semente saem
     sempre iguais, e uma coisa que mudasse de aspecto a cada visita não era um
     desenho, era um sorteio.

     Procuradas na página inteira e não só dentro da montra: o kit do sabbat
     também leva uma, e ficava preta quando isto só olhava para os produtos.

     Pintadas quando chegam ao ecrã e não todas de uma vez: são telas de 600 por
     800, e pintá-las à entrada da página atrasava o que está mesmo à frente de
     quem chega.
     --------------------------------------------------------------------- */
  var telas = document.querySelectorAll('[data-desenho]');

  function pintar(tela) {
    if (!window.CovilArt || tela.dataset.pintada) { return; }
    tela.dataset.pintada = '1';
    window.CovilArt.paint(
      tela,
      tela.getAttribute('data-desenho'),
      parseInt(tela.getAttribute('data-semente') || '100', 10)
    );
  }

  if (telas.length) {
    if ('IntersectionObserver' in window) {
      var olhoTelas = new IntersectionObserver(function (entradas) {
        for (var t = 0; t < entradas.length; t++) {
          if (!entradas[t].isIntersecting) { continue; }
          olhoTelas.unobserve(entradas[t].target);
          pintar(entradas[t].target);
        }
      }, { rootMargin: '400px' });
      for (var t0 = 0; t0 < telas.length; t0++) { olhoTelas.observe(telas[t0]); }
    } else {
      for (var t1 = 0; t1 < telas.length; t1++) { pintar(telas[t1]); }
    }

    /* A biblioteca é carregada com `defer` e pode chegar depois deste guião.
       Quando chegar, avisa — e aí pinta-se o que já estava à vista. */
    window.addEventListener('covilart', function () {
      for (var t2 = 0; t2 < telas.length; t2++) {
        var r = telas[t2].getBoundingClientRect();
        if (r.top < window.innerHeight + 400 && r.bottom > -400) { pintar(telas[t2]); }
      }
    });
  }

  /* ---------------------------------------------------------------------
     A montra: os filtros, a inclinação e o cesto.
     --------------------------------------------------------------------- */
  var montra = document.querySelector('[data-montra]');

  if (montra) {

    /* Os filtros.

       Escondem cartões em vez de ir buscar outros ao servidor: são oito, já
       estão desenhados, e ir buscar os mesmos oito para esconder quatro era uma
       viagem para nada. A fila está `hidden` no HTML e só aqui se mostra — uma
       fila de filtros que não filtram é pior do que fila nenhuma. */
    var filtros = document.querySelector('[data-filtros]');

    if (filtros) {
      filtros.hidden = false;

      filtros.addEventListener('click', function (ev) {
        var botão = ev.target.closest ? ev.target.closest('[data-filtro]') : null;
        if (!botão) { return; }

        var escolhida = botão.getAttribute('data-filtro');
        var todos = filtros.querySelectorAll('[data-filtro]');
        for (var b = 0; b < todos.length; b++) {
          todos[b].classList.toggle('pastilha--activa', todos[b] === botão);
        }

        var cartões = montra.querySelectorAll('.produto');
        for (var c = 0; c < cartões.length; c++) {
          var suas = (cartões[c].getAttribute('data-categorias') || '').split(' ');
          var fica = escolhida === '' || suas.indexOf(escolhida) !== -1;
          cartões[c].hidden = !fica;

          /* Os que ficam voltam a entrar, escalonados — é o que a maquete faz
             ao trocar de categoria, e é o que diz a quem carregou que alguma
             coisa respondeu. */
          if (fica && !queto && cartões[c].animate) {
            cartões[c].animate(
              [{ opacity: 0, transform: 'translateY(24px) scale(.97)', filter: 'blur(6px)' },
               { opacity: 1, transform: 'none', filter: 'none' }],
              { duration: 800, delay: c * 70, easing: 'cubic-bezier(.2, .7, .1, 1)', fill: 'both' }
            ).onfinish = function (e) { e.target.cancel(); };
          }
        }
      });
    }

    /* A inclinação da moldura para o lado do rato. Três graus chegam: mais do
       que isso e o desenho descola do cartão em vez de responder ao toque. */
    if (!queto) {
      montra.addEventListener('pointermove', function (ev) {
        var moldura = ev.target.closest ? ev.target.closest('[data-inclina]') : null;
        if (!moldura) { return; }
        var caixa = moldura.getBoundingClientRect();
        var x = (ev.clientX - caixa.left) / caixa.width - 0.5;
        var y = (ev.clientY - caixa.top) / caixa.height - 0.5;
        moldura.style.transform =
          'perspective(900px) rotateX(' + (-y * 6).toFixed(2) + 'deg) rotateY(' + (x * 6).toFixed(2) + 'deg)';
      });

      montra.addEventListener('pointerleave', function () {
        var ms = montra.querySelectorAll('[data-inclina]');
        for (var m = 0; m < ms.length; m++) { ms[m].style.transform = ''; }
      });
    }

    /* O cesto. Conta no cabeçalho e não vai ao servidor: neste site não há
       cesto para onde ir. É o que a maquete faz — soma um e anuncia-o. */
    var conta = document.querySelector('.pílula__conta');

    montra.addEventListener('click', function (ev) {
      var botão = ev.target.closest ? ev.target.closest('[data-no-cesto]') : null;
      if (!botão || !conta) { return; }

      conta.textContent = String((parseInt(conta.textContent, 10) || 0) + 1);

      if (!queto && conta.animate) {
        conta.animate(
          [{ transform: 'scale(1)' }, { transform: 'scale(1.8)' }, { transform: 'scale(1)' }],
          { duration: 500, easing: 'cubic-bezier(.2, .7, .1, 1)' }
        );
      }
    });
  }

  /* ---------------------------------------------------------------------
     O boletim da lua.

     **Não envia nada, e é de propósito.** Para o fazer a sério era preciso um
     endereço para onde enviar, uma lista onde guardar e o consentimento a ficar
     registado, e nada disso existe neste site. O que fica é o que a maquete
     também faz: agradecer.

     Um formulário que parecesse guardar e não guardasse era pior do que isto —
     quem o preenchesse ficava à espera de uma carta que nunca vinha. Quem
     escrever o resto tira este bloco e põe o `action` no HTML.
     --------------------------------------------------------------------- */
  var boletim = document.querySelector('[data-boletim]');

  if (boletim) {
    boletim.addEventListener('submit', function (ev) {
      ev.preventDefault();

      var resposta = boletim.querySelector('[data-boletim-resposta]');
      if (resposta) {
        /* A frase vem do HTML e não daqui: traduzi-la em JavaScript era ter as
           cinco línguas escritas num sítio onde o catálogo não chega. */
        resposta.textContent = boletim.getAttribute('data-obrigado') || '';
      }

      boletim.reset();
    });
  }

  /* ---------------------------------------------------------------------
     As letras ocas da ponte.

     A fila de cima sai para a esquerda e apaga-se; a de baixo vem da esquerda e
     acende-se. Andam com o desenrolar e não com o relógio: é a passagem pela
     ponte que as move, e por isso param quando quem lê pára.

     Só enquanto a secção está à vista, e só quando o avanço mudou o suficiente
     para se notar — escrever o mesmo `transform` a cada quadro é obrigar o
     browser a recompor a página sem nada mudar.
     --------------------------------------------------------------------- */
  var ponte = document.querySelector('.secção--ponte');

  if (ponte && !queto) {
    var cima = ponte.querySelector('[data-ponte-cima]');
    var baixo = ponte.querySelector('[data-ponte-baixo]');
    var últimoAvanço = -1;
    var marcada = false;

    function moverPonte() {
      marcada = false;
      if (!cima || !baixo) { return; }

      var caixa = ponte.getBoundingClientRect();
      if (caixa.bottom < 0 || caixa.top > window.innerHeight) { return; }

      var avanço = Math.max(0, Math.min(1,
        (window.innerHeight - caixa.top) / (window.innerHeight + caixa.height)));

      if (Math.abs(avanço - últimoAvanço) < 0.0005) { return; }
      últimoAvanço = avanço;

      var largura = ponte.clientWidth;
      cima.style.transform = 'translate3d(' + (-avanço * largura * 0.6).toFixed(1) + 'px, 0, 0)';
      cima.style.opacity = (1 - avanço * 0.8).toFixed(3);
      baixo.style.transform = 'translate3d(' + (-largura * 0.9 + avanço * largura * 0.6).toFixed(1) + 'px, 0, 0)';
      baixo.style.opacity = (0.2 + avanço * 0.8).toFixed(3);
    }

    function marcarPonte() {
      if (marcada) { return; }
      marcada = true;
      requestAnimationFrame(moverPonte);
    }

    window.addEventListener('scroll', marcarPonte, { passive: true });
    window.addEventListener('resize', marcarPonte);
    moverPonte();
  }

  /* ---------------------------------------------------------------------
     A roda dos signos.

     As doze cartas já vêm no documento, escondidas menos a do dia. Trocar de
     signo é mostrar outra e rodar a roda — nada vai ao servidor, e por isso
     responde no instante.

     **A roda escolhe sempre o caminho curto.** De Peixes para Áries são trinta
     graus para a frente e não trezentos e trinta para trás, e é isso que a conta
     do resto faz: traz a diferença para o intervalo de -180 a 180 e soma-a ao
     ângulo que a roda já tinha. Sem isso, metade das escolhas davam uma volta
     quase completa pelo lado errado.
     --------------------------------------------------------------------- */
  var signos = document.querySelector('[data-signos]');

  if (signos) {
    var roda = signos.querySelector('.roda');
    var escolhido = parseInt(signos.getAttribute('data-escolhido') || '0', 10);
    /* O ângulo acumulado, em graus. Vive aqui e não no DOM porque pode passar
       dos 360 e abaixo de zero — é essa memória que faz o caminho curto. */
    var volta = -escolhido * 30;

    function aplicarVolta() {
      if (roda) { roda.style.setProperty('--roda-volta', volta + 'deg'); }
    }
    aplicarVolta();

    function mostrar(qual) {
      if (qual === escolhido) { return; }

      var alvo = -qual * 30;
      var diferença = ((alvo - volta) % 360 + 540) % 360 - 180;
      volta += diferença;

      escolhido = qual;
      signos.setAttribute('data-escolhido', String(qual));
      aplicarVolta();

      var botões = signos.querySelectorAll('[data-signo]');
      for (var b = 0; b < botões.length; b++) {
        botões[b].setAttribute('aria-pressed',
          parseInt(botões[b].getAttribute('data-signo'), 10) === qual ? 'true' : 'false');
      }

      var cartas = signos.querySelectorAll('[data-carta]');
      for (var c = 0; c < cartas.length; c++) {
        cartas[c].hidden = parseInt(cartas[c].getAttribute('data-carta'), 10) !== qual;
      }

      /* Pelo atributo e não pela propriedade: as constelações são `<svg>`, e num
         elemento de SVG a propriedade `hidden` não existe — escrever-lhe não
         muda atributo nenhum, e a constelação ficava a do signo com que a página
         abriu enquanto tudo o resto trocava. Nas cartas, que são `<article>`, a
         propriedade funciona; aqui tem de ser o atributo, que é o que a regra de
         estilo lê. */
      var ceus = signos.querySelectorAll('[data-constelação]');
      for (var k = 0; k < ceus.length; k++) {
        if (parseInt(ceus[k].getAttribute('data-constelação'), 10) === qual) {
          ceus[k].removeAttribute('hidden');
        } else {
          ceus[k].setAttribute('hidden', '');
        }
      }

      /* A cor do signo passa para a roda, que a usa no aro pontilhado e no
         fundo do botão escolhido. */
      var carta = signos.querySelector('[data-carta="' + qual + '"]');
      if (carta && roda) {
        roda.style.setProperty('--viva', carta.style.getPropertyValue('--viva'));
        roda.style.setProperty('--esbatida', carta.style.getPropertyValue('--esbatida'));
      }

      if (queto) { return; }

      /* A constelação desenha-se a traço, e as estrelas acendem-se a seguir. */
      var ceu = signos.querySelector('[data-constelação="' + qual + '"]');
      if (!ceu) { return; }

      var traços = ceu.querySelectorAll('path[stroke-width=".5"]');
      for (var t = 0; t < traços.length; t++) {
        if (!traços[t].animate) { break; }
        traços[t].animate(
          [{ strokeDasharray: '1 1', strokeDashoffset: 1 }, { strokeDasharray: '1 1', strokeDashoffset: 0 }],
          { duration: 700, delay: 300 + t * 110, easing: 'cubic-bezier(.6, 0, .2, 1)', fill: 'both' }
        );
      }

      var estrelas = ceu.querySelectorAll('circle');
      for (var e = 0; e < estrelas.length; e++) {
        if (!estrelas[e].animate) { break; }
        estrelas[e].animate(
          [{ opacity: 0, transform: 'scale(0)' },
           { opacity: 1, transform: 'scale(1.8)', offset: .6 },
           { opacity: 1, transform: 'scale(1)' }],
          { duration: 600, delay: 200 + e * 110, easing: 'cubic-bezier(.2, .7, .1, 1)', fill: 'both' }
        );
      }
    }

    signos.addEventListener('click', function (ev) {
      var botão = ev.target.closest ? ev.target.closest('[data-signo]') : null;
      if (!botão) { return; }
      mostrar(parseInt(botão.getAttribute('data-signo'), 10));
    });

    /* Partilhar o signo. Só se mostra o botão se houver mesmo como partilhar:
       a partilha do sistema, que no telefone abre a folha de sempre, ou a área
       de transferência. Num browser que não tenha nenhuma das duas, o botão não
       aparece — em vez de aparecer e não fazer nada. */
    var podePartilhar = !!navigator.share;
    var podeCopiar = !!(navigator.clipboard && navigator.clipboard.writeText);

    if (podePartilhar || podeCopiar) {
      var botõesPartilha = signos.querySelectorAll('[data-partilhar]');
      for (var s = 0; s < botõesPartilha.length; s++) { botõesPartilha[s].hidden = false; }

      signos.addEventListener('click', function (ev) {
        var botão = ev.target.closest ? ev.target.closest('[data-partilhar]') : null;
        if (!botão) { return; }

        var frase = botão.getAttribute('data-partilhar');
        var endereço = location.href.split('#')[0] + '#signos';

        if (podePartilhar) {
          navigator.share({ title: document.title, text: frase, url: endereço })
            .catch(function () { /* fechou a folha de partilha; não é erro */ });
          return;
        }

        navigator.clipboard.writeText(endereço).then(function () {
          var antes = botão.textContent;
          botão.textContent = '✓';
          setTimeout(function () { botão.textContent = antes; }, 1600);
        }, function () { /* sem permissão para a área de transferência */ });
      });
    }

    /* O céu por trás. Cento e quarenta estrelas a piscar, cada uma ao seu ritmo,
       e uma em cada nove na cor do signo escolhido — é o que faz o fundo mudar
       de temperatura quando se roda a roda.

       Só desenha enquanto a secção está à vista. Pintar um céu que ninguém vê é
       tirar quadros ao que está à frente de quem lê. */
    var céu = signos.querySelector('[data-céu]');

    if (céu && céu.getContext && !queto) {
      var tinta = céu.getContext('2d');
      var estrelas = [];
      var pedidoCéu = 0;

      for (var n = 0; n < 140; n++) {
        estrelas.push({
          x: Math.random(), y: Math.random(),
          r: 0.4 + Math.random() * 1.3,
          fase: Math.random() * 6.28,
          ritmo: 0.5 + Math.random() * 1.5
        });
      }

      function medirCéu() {
        var d = Math.min(2, window.devicePixelRatio || 1);
        var l = Math.round(céu.clientWidth * d), a = Math.round(céu.clientHeight * d);
        if (céu.width !== l || céu.height !== a) { céu.width = l; céu.height = a; }
      }

      function pintarCéu(agora) {
        pedidoCéu = requestAnimationFrame(pintarCéu);

        var W = céu.width, H = céu.height;
        if (!W || !H) { return; }

        /* A cor do signo, lida da roda — assim o céu acompanha a escolha sem
           precisar de saber nada sobre signos. */
        var cor = (roda && roda.style.getPropertyValue('--viva')) || '#d9b36c';

        tinta.clearRect(0, 0, W, H);
        for (var e = 0; e < estrelas.length; e++) {
          var s = estrelas[e];
          var brilho = 0.25 + 0.75 * Math.abs(Math.sin(agora * 0.001 * s.ritmo + s.fase));
          tinta.globalAlpha = brilho * 0.8;
          tinta.fillStyle = e % 9 === 0 ? cor : '#f3e6c4';
          tinta.beginPath();
          tinta.arc(s.x * W, s.y * H, s.r * (W / Math.max(1, céu.clientWidth)), 0, Math.PI * 2);
          tinta.fill();
        }
        tinta.globalAlpha = 1;
      }

      medirCéu();
      if ('ResizeObserver' in window) { new ResizeObserver(medirCéu).observe(céu); }

      if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (entradas) {
          if (entradas[0].isIntersecting) {
            if (!pedidoCéu) { pedidoCéu = requestAnimationFrame(pintarCéu); }
          } else if (pedidoCéu) {
            cancelAnimationFrame(pedidoCéu);
            pedidoCéu = 0;
          }
        }, { rootMargin: '120px' }).observe(signos);
      } else {
        pedidoCéu = requestAnimationFrame(pintarCéu);
      }
    }

    /* A cor de arranque, para a roda não abrir a ouro e saltar para a do signo
       na primeira escolha. */
    var primeira = signos.querySelector('[data-carta="' + escolhido + '"]');
    if (primeira && roda) {
      roda.style.setProperty('--viva', primeira.style.getPropertyValue('--viva'));
      roda.style.setProperty('--esbatida', primeira.style.getPropertyValue('--esbatida'));
    }
  }

  /* ---------------------------------------------------------------------
     A mesa de tarot.

     Cinco momentos: parada, a baralhar, a cortar, a escolher, e a mostrar. O
     que muda de um para o outro é onde cada carta está — e cada carta sabe a
     sua posição em quatro medidas que este guião escreve e a folha de estilo
     desenha: `--x`, `--y`, `--volta` e `--z`.

     **A carta que sai é a que o acaso do browser deu.** Não há nada combinado
     com o servidor: as vinte e duas vêm todas no documento e o embaralhamento é
     aqui. Uma tiragem simbólica pode ser gratuita e pode ser enfeite, mas não
     deve ser batota.

     **A mesa só se liga com JavaScript.** O `data-pronta` é o que mostra os
     botões; sem ele fica a frase a dizer o que a mesa é e o botão de marcar uma
     leitura a sério, que é a resposta honesta.
     --------------------------------------------------------------------- */
  var mesa = document.querySelector('[data-mesa]');

  if (mesa) {
    var baralho   = mesa.querySelector('[data-baralho]');
    var cartas    = [].slice.call(mesa.querySelectorAll('[data-carta-tarot]'));
    var dito      = mesa.querySelector('[data-mesa-dito]');
    var começar   = mesa.querySelector('[data-mesa-começar]');
    var outra     = mesa.querySelector('[data-mesa-outra]');
    var saltar    = mesa.querySelector('[data-mesa-saltar]');
    var lugares   = [].slice.call(mesa.querySelectorAll('[data-lugar]'));
    var dizeres   = [].slice.call(mesa.querySelectorAll('[data-lugar-dizer]'));

    var frases = {
      nota:    dito ? dito.textContent : '',
      focar:   mesa.getAttribute('data-focar')   || '',
      cortar:  mesa.getAttribute('data-cortar')  || '',
      escolher: mesa.getAttribute('data-escolher') || '',
      mostrar: mesa.getAttribute('data-mostrar') || '',
      invertida: mesa.getAttribute('data-invertida') || ''
    };

    var relógios = [];
    var escolhidas = [];

    function daquiA(ms, oQue) { relógios.push(setTimeout(oQue, ms)); }
    function pararRelógios() {
      for (var r = 0; r < relógios.length; r++) { clearTimeout(relógios[r]); }
      relógios = [];
    }

    /* O tamanho de uma carta vem da largura do pano: numa mesa estreita as
       cartas encolhem com ela, em vez de transbordarem. */
    function medirCarta() {
      var larg = Math.max(56, Math.min(96, mesa.clientWidth * 0.14));
      mesa.style.setProperty('--larg-carta', Math.round(larg) + 'px');
      mesa.style.setProperty('--alt-carta', Math.round(larg * 140 / 84) + 'px');
      return { l: larg, a: larg * 140 / 84 };
    }

    function porCarta(carta, x, y, volta, z) {
      carta.style.setProperty('--x', Math.round(x) + 'px');
      carta.style.setProperty('--y', Math.round(y) + 'px');
      carta.style.setProperty('--volta', volta.toFixed(1) + 'deg');
      carta.style.setProperty('--z', String(z));
    }

    /* O baralho: as vinte e duas empilhadas ao meio, com um desencontro de meio
       pixel entre cada uma para se ver que são muitas. */
    function empilhar(baralhando) {
      var c = medirCarta();
      var meioX = mesa.clientWidth / 2 - c.l / 2;
      var meioY = mesa.clientHeight * 0.42 - c.a / 2;

      for (var i = 0; i < cartas.length; i++) {
        var desvio = baralhando ? (Math.random() - 0.5) * c.l * 1.6 : i * 0.5;
        var alto   = baralhando ? (Math.random() - 0.5) * c.a * 0.5 : i * -0.5;
        var volta  = baralhando ? (Math.random() - 0.5) * 24 : (i - cartas.length / 2) * 0.12;
        porCarta(cartas[i], meioX + desvio, meioY + alto, volta, 10 + i);
      }
    }

    /* O corte: o baralho parte-se em dois montes que se afastam e voltam. */
    function cortar(aberto) {
      var c = medirCarta();
      var meioX = mesa.clientWidth / 2 - c.l / 2;
      var meioY = mesa.clientHeight * 0.42 - c.a / 2;
      var meio = Math.floor(cartas.length / 2);

      for (var i = 0; i < cartas.length; i++) {
        var lado = i < meio ? -1 : 1;
        var fora = aberto ? lado * c.l * 0.8 : i * 0.5;
        porCarta(cartas[i], meioX + fora, meioY + (aberto ? lado * 6 : i * -0.5), lado * (aberto ? 3 : 0), 10 + i);
      }
    }

    /* O leque: as vinte e duas em arco, viradas para baixo, à espera de escolha. */
    function espalhar() {
      var c = medirCarta();
      var centroX = mesa.clientWidth / 2;
      var centroY = mesa.clientHeight * 0.40;
      var raio = Math.min(mesa.clientWidth * 0.42, 260);
      var abertura = 128; // graus que o leque ocupa

      for (var i = 0; i < cartas.length; i++) {
        var t = cartas.length === 1 ? 0.5 : i / (cartas.length - 1);
        var ang = (-abertura / 2 + abertura * t) * Math.PI / 180;
        porCarta(
          cartas[i],
          centroX + Math.sin(ang) * raio - c.l / 2,
          centroY - Math.cos(ang) * raio * 0.34,
          (-abertura / 2 + abertura * t) * 0.55,
          10 + i
        );
        cartas[i].tabIndex = 0;
        cartas[i].removeAttribute('aria-hidden');
      }
    }

    /* Onde pousa a carta de cada lugar: por cima do rectângulo tracejado. */
    function lugarDe(qual) {
      var alvo = lugares[qual];
      if (!alvo) { return { x: 0, y: 0 }; }
      var dela = alvo.getBoundingClientRect();
      var dapano = mesa.getBoundingClientRect();
      return { x: dela.left - dapano.left, y: dela.top - dapano.top };
    }

    function dizer(frase) { if (dito) { dito.textContent = frase; } }

    function trancarCartas() {
      for (var i = 0; i < cartas.length; i++) {
        cartas[i].tabIndex = -1;
        cartas[i].setAttribute('aria-hidden', 'true');
      }
    }

    function recomeçar() {
      pararRelógios();
      escolhidas = [];
      mesa.setAttribute('data-fase', 'parada');

      for (var i = 0; i < cartas.length; i++) {
        cartas[i].removeAttribute('data-virada');
        cartas[i].removeAttribute('data-invertida');
        cartas[i].style.opacity = '';
      }
      for (var d = 0; d < dizeres.length; d++) {
        dizeres[d].removeAttribute('data-virada');
        dizeres[d].querySelector('.lugar__arcano').textContent = '';
        dizeres[d].querySelector('.lugar__sentido').textContent = '';
        dizeres[d].querySelector('.lugar__significado').textContent = '';
      }

      trancarCartas();
      empilhar(false);
      dizer(frases.nota);
      if (começar) { começar.hidden = false; }
      if (outra) { outra.hidden = true; }
      if (saltar) { saltar.hidden = true; }
    }

    function tirar() {
      pararRelógios();
      mesa.setAttribute('data-fase', 'baralhar');
      if (começar) { começar.hidden = true; }
      if (saltar) { saltar.hidden = false; }

      dizer(frases.focar);

      var passo = queto ? 0 : 1;

      // Baralhar: três remexidas.
      for (var v = 0; v < 3; v++) { daquiA(passo * (200 + v * 320), function () { empilhar(true); }); }

      daquiA(passo * 1200, function () {
        mesa.setAttribute('data-fase', 'cortar');
        dizer(frases.cortar);
        cortar(true);
      });
      daquiA(passo * 1800, function () { cortar(false); });

      daquiA(passo * 2300, function () {
        mesa.setAttribute('data-fase', 'escolher');
        dizer(frases.escolher);
        espalhar();
        if (saltar) { saltar.hidden = true; }
      });
    }

    function escolher(carta) {
      if (mesa.getAttribute('data-fase') !== 'escolher') { return; }
      if (escolhidas.indexOf(carta) !== -1 || escolhidas.length >= 3) { return; }

      var qual = escolhidas.length;
      escolhidas.push(carta);

      var onde = lugarDe(qual);
      porCarta(carta, onde.x, onde.y, 0, 60 + qual);

      /* Metade das cartas sai invertida, como num baralho a sério — e é o acaso
         do browser que o decide, carta a carta. */
      if (Math.random() < 0.5) { carta.setAttribute('data-invertida', ''); }

      if (escolhidas.length === 3) {
        mesa.setAttribute('data-fase', 'mostrar');
        dizer(frases.mostrar);

        // As que ficaram por escolher saem de vista.
        for (var i = 0; i < cartas.length; i++) {
          if (escolhidas.indexOf(cartas[i]) === -1) {
            cartas[i].style.opacity = '0';
            cartas[i].tabIndex = -1;
            cartas[i].setAttribute('aria-hidden', 'true');
          }
        }
        if (outra) { outra.hidden = false; }
      }
    }

    function virar(carta) {
      var qual = escolhidas.indexOf(carta);
      if (qual === -1 || carta.hasAttribute('data-virada')) { return; }

      carta.setAttribute('data-virada', '');

      var dizerDoLugar = dizeres[qual];
      if (!dizerDoLugar) { return; }

      var invertida = carta.hasAttribute('data-invertida');
      dizerDoLugar.querySelector('.lugar__arcano').textContent =
        carta.getAttribute('data-romano') + ' · ' + carta.getAttribute('data-nome');
      dizerDoLugar.querySelector('.lugar__sentido').textContent = invertida ? frases.invertida : '';
      dizerDoLugar.querySelector('.lugar__significado').textContent =
        carta.getAttribute(invertida ? 'data-invertido' : 'data-direito');
      dizerDoLugar.setAttribute('data-virada', '');
    }

    mesa.addEventListener('click', function (ev) {
      var carta = ev.target.closest ? ev.target.closest('[data-carta-tarot]') : null;
      if (!carta) { return; }
      if (mesa.getAttribute('data-fase') === 'escolher') { escolher(carta); }
      else { virar(carta); }
    });

    if (começar) { começar.addEventListener('click', tirar); }
    if (outra) { outra.addEventListener('click', recomeçar); }
    if (saltar) {
      saltar.addEventListener('click', function () {
        pararRelógios();
        mesa.setAttribute('data-fase', 'escolher');
        dizer(frases.escolher);
        espalhar();
        saltar.hidden = true;
      });
    }

    window.addEventListener('resize', function () {
      var fase = mesa.getAttribute('data-fase');
      if (fase === 'escolher') { espalhar(); }
      else if (fase === 'parada') { empilhar(false); }
      else if (fase === 'mostrar') {
        for (var i = 0; i < escolhidas.length; i++) {
          var onde = lugarDe(i);
          porCarta(escolhidas[i], onde.x, onde.y, 0, 60 + i);
        }
      }
    });

    /* Só agora se diz que a mesa está de pé: é isto que mostra os botões. */
    mesa.setAttribute('data-pronta', '');
    recomeçar();
  }

  /* ---------------------------------------------------------------------
     A fita das luas ganha vida.

     Três movimentos somados, cada um com a sua razão:

       **A respiração.** Todas sobem e descem devagar, desencontradas umas das
       outras. É o que impede a fita de parecer uma régua.

       **A onda que passa sozinha.** De sete em sete segundos atravessa a fita
       uma corcova que levanta as luas por onde vai. Dá-lhe vida sem exigir nada
       de quem lê.

       **O rato.** As luas mais perto do ponteiro crescem, sobem e inclinam-se
       para ele. A curva é uma gaussiana de 45px: a de baixo do rato leva quase
       tudo, a seguinte metade, e à terceira já não se nota.

     E ao carregar, as luas rodam a fase a partir do ponto tocado — a lua volta
     um ciclo inteiro para trás e volta ao sítio, em cascata.

     Tudo num laço só, e só enquanto a fita está à vista.
     --------------------------------------------------------------------- */
  var fitaDasLuas = document.querySelector('.luas__fila');

  if (fitaDasLuas && !queto) {
    var luas = [].slice.call(fitaDasLuas.children);
    var ratoX = null;
    var ondas = [];
    var pedidoLuas = 0;
    var arranqueLuas = 0;
    var girar = null;   // a rodagem das fases a decorrer, se houver

    /* O desenho da lua para uma fase, de 0 a 1. A mesma conta que o PHP faz
       quando desenha a página — ver App\Services\Lua::desenho. Está nos dois
       sítios porque são dois momentos diferentes: o servidor desenha o mês, e
       isto redesenha-o enquanto roda. */
    function desenhoDaLua(fase) {
      var k = Math.cos(2 * Math.PI * fase);
      var rx = (17 * Math.abs(k)).toFixed(2);
      return fase < 0.5
        ? 'M20,3 A17,17 0 0 1 20,37 A' + rx + ',17 0 0 ' + (k > 0 ? '0' : '1') + ' 20,3Z'
        : 'M20,3 A17,17 0 0 0 20,37 A' + rx + ',17 0 0 ' + (k > 0 ? '1' : '0') + ' 20,3Z';
    }

    /* A fase de origem de cada lua, lida uma vez do que o servidor desenhou.
       Guardada aqui para a rodagem poder voltar sempre ao sítio certo. */
    var fases = [];
    for (var f = 0; f < luas.length; f++) {
      var risco = luas[f].querySelector('path');
      fases.push(risco ? parseFloat(risco.getAttribute('data-fase') || '0') : 0);
    }

    function rodarFases(duração, voltas, origem) {
      var começou = performance.now();
      var meu = { id: Math.random() };
      girar = meu;

      function passo(agora) {
        if (girar !== meu) { return; }
        var acabou = true;

        for (var i = 0; i < luas.length; i++) {
          var risco = luas[i].querySelector('path');
          if (!risco) { continue; }

          var espera = origem == null ? i * 40 : Math.abs(i - origem) * 45;
          var k = Math.max(0, Math.min(1, (agora - começou - espera) / duração));
          if (k < 1) { acabou = false; }

          var suave = 1 - Math.pow(1 - k, 3);
          var fase = (((fases[i] - (1 - suave) * voltas) % 1) + 1) % 1;
          risco.setAttribute('d', desenhoDaLua(fase));
        }

        if (!acabou) { requestAnimationFrame(passo); }
        else { girar = null; }
      }

      requestAnimationFrame(passo);
    }

    function quadroDasLuas(agora) {
      pedidoLuas = requestAnimationFrame(quadroDasLuas);

      var caixa = fitaDasLuas.getBoundingClientRect();
      if (!arranqueLuas) { arranqueLuas = agora; rodarFases(1800, 2, null); }

      var n = luas.length;
      var largura = caixa.width / n;
      var t = (agora - arranqueLuas) / 1000;
      var corcova = (t % 7) / 1.6 * n - 4;

      ondas = ondas.filter(function (o) { return agora - o.quando < 2200; });

      for (var i = 0; i < n; i++) {
        var svg = luas[i].firstElementChild;
        if (!svg) { continue; }

        var y = Math.sin(t * 1.6 + i * 0.42) * 2.2;
        var escala = 1;
        var volta = 0;

        var perto = Math.exp(-Math.pow(i - corcova, 2) / 3);
        y -= perto * 16;
        volta += perto * (i % 2 ? 14 : -14);

        for (var o = 0; o < ondas.length; o++) {
          var raio = (agora - ondas[o].quando) / 1000 * 22;
          var dd = Math.abs(Math.abs(i - ondas[o].onde) - raio);
          var força = Math.exp(-dd * dd / 2) * (1 - (agora - ondas[o].quando) / 2200);
          y -= força * 22;
          escala += força * 0.35;
        }

        if (ratoX !== null) {
          var dx = ratoX - (caixa.left + (i + 0.5) * largura);
          var g = Math.exp(-dx * dx / (2 * 45 * 45));
          escala += g * 0.75;
          y -= g * 6;
          volta += g * dx * -0.15;
        }

        svg.style.transform =
          'translateY(' + y.toFixed(2) + 'px) rotate(' + volta.toFixed(2) + 'deg) scale(' + escala.toFixed(3) + ')';
      }
    }

    fitaDasLuas.addEventListener('pointermove', function (ev) { ratoX = ev.clientX; });
    fitaDasLuas.addEventListener('pointerleave', function () { ratoX = null; });

    fitaDasLuas.addEventListener('click', function (ev) {
      var caixa = fitaDasLuas.getBoundingClientRect();
      var onde = (ev.clientX - caixa.left) / caixa.width * luas.length;
      ondas.push({ quando: performance.now(), onde: onde });
      rodarFases(900, 1, onde);
    });

    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entradas) {
        if (entradas[0].isIntersecting) {
          if (!pedidoLuas) { pedidoLuas = requestAnimationFrame(quadroDasLuas); }
        } else if (pedidoLuas) {
          cancelAnimationFrame(pedidoLuas);
          pedidoLuas = 0;
        }
      }, { rootMargin: '120px' }).observe(fitaDasLuas);
    } else {
      pedidoLuas = requestAnimationFrame(quadroDasLuas);
    }
  }

  /* ---------------------------------------------------------------------
     A luz que acompanha o rato pela página.

     Um disco de 520px com um halo cor de brasa e roxo, somado ao que está por
     baixo — `mix-blend-mode: screen` —, a seguir o ponteiro com atraso. O
     atraso é o ponto: ir atrás a oito por cento da distância em cada quadro dá
     um movimento que acompanha sem colar, e é isso que o faz parecer uma luz e
     não um cursor.

     Posto por JavaScript e não escrito no HTML: sem JavaScript não se move, e
     uma luz parada a meio do ecrã é uma mancha.
     --------------------------------------------------------------------- */
  if (!queto && window.matchMedia && window.matchMedia('(pointer: fine)').matches) {
    var luz = document.createElement('div');
    luz.className = 'luz-do-rato';
    luz.setAttribute('aria-hidden', 'true');
    document.body.appendChild(luz);

    var destinoX = window.innerWidth / 2, destinoY = window.innerHeight / 2;
    var estáX = destinoX, estáY = destinoY;

    window.addEventListener('pointermove', function (ev) {
      destinoX = ev.clientX;
      destinoY = ev.clientY;
    }, { passive: true });

    (function seguir() {
      estáX += (destinoX - estáX) * 0.08;
      estáY += (destinoY - estáY) * 0.08;
      luz.style.transform = 'translate3d(' + estáX.toFixed(1) + 'px, ' + estáY.toFixed(1) + 'px, 0)';
      requestAnimationFrame(seguir);
    })();
  }

})();
