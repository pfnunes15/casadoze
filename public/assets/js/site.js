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

     Pontos de luz a subir devagar por cima da fotografia. Desenhadas num
     canvas e não com elementos: cem divs a mexer são cem coisas para o
     browser recompor em cada quadro, e num telefone isso vê-se.

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
    var quantas = parseInt(tela.getAttribute('data-brasas') || '70', 10);
    var larg = 0, alt = 0, dpr = 1;

    function medir() {
      /* O canvas tem duas medidas: a que ocupa na página e a dos pixéis que
         tem lá dentro. Sem multiplicar pela densidade do ecrã, num telefone
         as brasas saem desfocadas. */
      dpr = Math.min(window.devicePixelRatio || 1, 2);
      larg = tela.clientWidth;
      alt = tela.clientHeight;
      tela.width = Math.round(larg * dpr);
      tela.height = Math.round(alt * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function nascer(brasa, deBaixo) {
      brasa.x = Math.random() * larg;
      brasa.y = deBaixo ? alt + Math.random() * 40 : Math.random() * alt;
      brasa.r = 0.6 + Math.random() * 1.6;
      brasa.sobe = 0.15 + Math.random() * 0.45;
      brasa.oscila = 0.3 + Math.random() * 0.9;
      brasa.fase = Math.random() * Math.PI * 2;
      brasa.luz = 0.15 + Math.random() * 0.5;
    }

    medir();
    for (var i = 0; i < quantas; i++) { brasas.push({}); nascer(brasas[i], false); }

    var último = 0;
    function quadro(agora) {
      /* O tempo que passou desde o quadro anterior, e não um passo fixo: num
         ecrã de 120 Hz um passo fixo fazia as brasas subirem ao dobro da
         velocidade. Limitado a 50 ms para o separador que volta de segundo
         plano não dar um salto de cinco segundos de uma vez. */
      var dt = Math.min(agora - último, 50) / 16.67;
      último = agora;

      ctx.clearRect(0, 0, larg, alt);

      for (var j = 0; j < brasas.length; j++) {
        var b = brasas[j];
        b.y -= b.sobe * dt;
        b.fase += 0.02 * dt;

        if (b.y < -10) { nascer(b, true); }

        var x = b.x + Math.sin(b.fase) * b.oscila * 6;
        ctx.beginPath();
        ctx.arc(x, b.y, b.r, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(224, 134, 60, ' + b.luz.toFixed(3) + ')';
        ctx.fill();
      }

      requestAnimationFrame(quadro);
    }
    requestAnimationFrame(quadro);

    /* Medir outra vez quando a janela muda de tamanho, e só quando ela para
       de mudar: medir a cada pixel de arrasto é remedir a tela cinquenta
       vezes por segundo sem nada ganhar. */
    var espera;
    window.addEventListener('resize', function () {
      clearTimeout(espera);
      espera = setTimeout(medir, 150);
    });
  }

  /* ---------------------------------------------------------------------
     A agenda das consultas: escolher o dia, e depois a hora.

     As horas vêm do servidor em JSON — /consultas/vagas — e não escritas na
     página, por uma razão: a página pode estar numa cache e as horas mudam a
     cada marcação. Uma página guardada que mostrasse horas já tomadas punha
     as pessoas a carregar em botões que recusam.

     **Sem JavaScript isto não existe, e o formulário continua a funcionar**:
     o `<noscript>` da secção mostra a lista inteira das horas como campos de
     opção, e quem escolhe uma marca do mesmo jeito. Ver views/sections/
     consultas.php.
     --------------------------------------------------------------------- */
  document.querySelectorAll('[data-agenda]').forEach(function (agenda) {
    var idDaConsulta = agenda.getAttribute('data-consulta');
    var caixaDias    = agenda.querySelector('[data-dias]');
    var caixaHoras   = agenda.querySelector('[data-horas]');
    var campoVaga    = agenda.querySelector('[data-vaga]');
    var vazia        = agenda.querySelector('[data-vazia]');

    if (!idDaConsulta || !caixaDias || !caixaHoras || !campoVaga) { return; }

    fetch(agenda.getAttribute('data-fonte') + '?consulta=' + encodeURIComponent(idDaConsulta), {
      headers: { 'Accept': 'application/json' }
    })
      .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
      .then(function (dados) {
        var dias = (dados && dados.dias) || [];
        if (!dias.length) {
          if (vazia) { vazia.hidden = false; }
          return;
        }

        agenda.hidden = false;

        dias.forEach(function (dia, i) {
          var b = document.createElement('button');
          b.type = 'button';
          b.className = 'agenda__opção';
          b.textContent = dia.rotulo;
          b.setAttribute('aria-pressed', i === 0 ? 'true' : 'false');
          b.addEventListener('click', function () {
            caixaDias.querySelectorAll('[aria-pressed]').forEach(function (o) {
              o.setAttribute('aria-pressed', 'false');
            });
            b.setAttribute('aria-pressed', 'true');
            desenharHoras(dia);
          });
          caixaDias.appendChild(b);
        });

        desenharHoras(dias[0]);
      })
      .catch(function () {
        /* A agenda não chegou. O que se mostra é «fala com a Casa» e não um
           erro: quem quer marcar não tem nada a fazer com um código de
           estado, e a Casa tem telefone. */
        if (vazia) { vazia.hidden = false; }
      });

    function desenharHoras(dia) {
      caixaHoras.textContent = '';
      campoVaga.value = '';

      (dia.horas || []).forEach(function (hora, i) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'agenda__opção';
        b.textContent = hora.hora;
        b.setAttribute('aria-pressed', i === 0 ? 'true' : 'false');
        b.addEventListener('click', function () {
          caixaHoras.querySelectorAll('[aria-pressed]').forEach(function (o) {
            o.setAttribute('aria-pressed', 'false');
          });
          b.setAttribute('aria-pressed', 'true');
          campoVaga.value = hora.id;
        });
        caixaHoras.appendChild(b);

        // A primeira fica escolhida, para não haver um formulário com uma
        // escolha em branco à espera de quem não percebeu que tinha de a fazer.
        if (i === 0) { campoVaga.value = hora.id; }
      });
    }
  });
})();
