# O que este pacote traz, e o que custa

O `LEIA-ME.md` ao lado é do designer e fica como veio. Esta nota é a leitura
desta casa: o que já existe no motor, o que é vista e o que é domínio novo.

Chegou a 19 de Outubro de 2026, em `Covil da Bruxa Website Design.zip`. Ficou
aqui tudo menos a pasta `uploads/` — 14 MB de capturas de WhatsApp, que são o
material de conversa do designer e não o desenho.

## As imagens

Três das oito são **os mesmos bytes** que já estão em `public/assets/img/`,
com outro nome:

| no desenho | no site |
|---|---|
| `casa-de-ze.jpg` | `abertura.jpg` |
| `sigil.png` | `logotipo.png` |
| `ze-retrato-2.png` | `ze.png` |

A quarta era o letreiro do rodapé, `casa-de-ze-lettering.png`. Já não está no
site: era meio mega de imagem com o nome da casa escrito dentro, e com a mudança
para Covil Ancestral ficou errado. O rodapé desenha-o agora em SVG, em
`views/partials/letreiro.php`, a partir do nome que está nas Definições.

Ficam aqui à mesma, com os nomes do desenho, para as páginas abrirem num browser
sem truques — que é para o que serve uma referência.

Novas há uma que conta: **`sigil.svg`**, 8,6 KB de vector onde o PNG pesava 95.
Todas as treze páginas do desenho já o usam em vez do PNG. Está copiado para
`public/assets/img/logotipo.svg`.

As outras três — `banner.jpg`, `logo.png`, `square.jpg` — não são usadas por
página nenhuma. São o material de marca que falta ao site: o `og:image`, o ícone
e o quadrado das redes.

## A porta de entrada mudou

A `Landing Page.dc.html` **não é igual** ao `docs/maquete.html` contra o qual a
porta de entrada foi construída. Fora a serialização (`sc-camel-view-box` passou
a `viewBox`, que é a mesma coisa escrita de outra maneira), mudou isto:

- **`max-width: 1280px` passou a `1360px`** em todas as secções, com
  `box-sizing: border-box`. O miolo do site está a 1280. **São 80px de largura
  em cada secção da página inteira.**
- O cabeçalho e o menu ganham **«Sala»**, e as ligações deixam de ser `#` com um
  «em breve»: vão para as páginas a sério.
- Os cartões da loja passam a **ligações** para a ficha do produto — o nome é um
  `<a>` e o cartão inteiro tem `role="link"`.
- O cesto do cabeçalho **abre uma gaveta lateral** com as linhas, as
  quantidades, o subtotal e a barra dos portes grátis aos 40 €. Hoje diz um
  aviso e não abre nada.
- O rodapé liga ao `Legal` por âncora — `#faq`, `#devolucoes`, `#termos`,
  `#privacidade`, `#cookies`, `#litigios` — e o livro de reclamações vai para o
  `livroreclamacoes.pt` a sério.
- O sigilo passa a `sigil.svg`.

## As treze páginas

Por ordem de quanto motor falta, e não por ordem de importância.

### Já construído

| página | estado |
|---|---|
| `Landing Page` | a 1360, sigilo em vector, cartões a ligar à ficha, gaveta do cesto |
| `Loja` | montra com filtros, ficha, cesto, checkout em três passos, confirmação |
| `Consultas` | lista, ficha com calendário e vagas, marcação e página da marcação |
| `Sala` | a equipa e o perfil de cada pessoa, com backoffice |
| `Grimorio` | as sessões e a página de cada uma, com backoffice |
| `Casa` | página do CMS, com percurso, princípios, testemunhos e FAQ |
| `Contactos` | página do CMS, com o formulário que grava em Pedidos |
| `Legal` | página do CMS, cinco documentos com âncoras |
| `404` | com três saídas |
| `Rodape` | já estava |

**O que não se fez, e porquê** — está escrito por extenso no cabeçalho de cada
vista; em resumo:

- *As estrelas e as avaliações*, em todo o lado. Não há tabela de avaliações, e
  uma média inventada é um número que ninguém disse.
- *Os campos do cartão no checkout.* Põem este site dentro do âmbito do
  PCI-DSS. Quem cobra tem página própria para isso.
- *A morada de entrega no checkout.* Vem da página de quem recebe o pagamento,
  e é de lá que entra na encomenda.
- *A contagem de visualizações do Grimório.* Vem da API do YouTube.
- *O vale de oferta*, nas consultas e na loja. Não há vales.
- *A agenda por pessoa.* A agenda é da consulta; dá-la a cada taróloga é mexer
  no motor que já trabalha.

**O que falta decidir e não é código:** as zonas e os escalões de envio, as
chaves de pagamento, e o texto legal — que é o rascunho do designer, adaptado ao
que o site faz, e tem de ser lido por quem responde por ele.

### Quase só vistas — o motor está no pacote

- **`Casa`** — o Zé: percurso, quatro principios, FAQ. É conteúdo numa página de
  blocos.
- **`Contactos`** — formulário com tópicos e consentimento. Os `enquiries` já
  existem.
- **`Legal`** — termos, privacidade, cookies, devoluções, litígios e FAQ num
  documento com navegação lateral. Conteúdo.
- **`404`** — uma página.
- **`Rodape`**, **`Cookies`** — componentes partilhados, e o rodapé já está
  construído.

### Motor desta casa, já escrito, mas sem o lado público

- **`Consultas`** — lista, ficha, marcação com calendário e vagas, testemunhos,
  checkout em quatro passos, confirmação e «As tuas marcações». As cinco tabelas
  estão de pé e o `Booking::create()` é o mesmo que o backoffice usa. A parte
  pública foi tirada na redução **porque a maquete antiga não tinha agenda na
  porta de entrada**. Esta tem: há calendário, vagas e remarcação.

### Domínio novo

- **`Sala`** — a equipa de tarólogas. Perfil, baralho, signo, especialidade,
  formatos, línguas, anos de mesa, estrelas, avaliações, vagas próprias e preço
  próprio. **O motor de hoje assume uma pessoa só**: as `consultations` não têm
  quem as dá, e os horários não são de ninguém. Pede tabela de tarólogas,
  horário por pessoa, avaliações, e a marcação a saber com quem.
- **`Grimorio`** — sessões do YouTube: lista, página de vídeo, busca e temas.
  O `Admedia\Cms\Video` ajuda com os embeds; a tabela e as vistas são novas.
- **`Pesquisa`** — busca no site, com abas por tipo. O pacote não tem busca
  pública.
- **`Conta`** — a maior peça nova do lado público. Entrar, criar conta, link por
  email, Google, Apple, encontrar encomenda sem conta; e depois resumo,
  encomendas com seguimento CTT, referência Multibanco, devoluções com etiqueta
  pré-paga, fatura em PDF, consultas com remarcação, vales de oferta, moradas,
  dados e preferências. O pacote tem a tabela `customers` e o `CustomerAuth` com
  entrar e sair. **Mais nada.**
- **`Painel`** — um segundo backoffice, em cima do que o CMS já faz. Hoje,
  agenda em semana/dia/mês, horário semanal, folgas, regras de marcação, mesa,
  avaliações com moderação e resposta, faturação com CSV e SAF-T, receita,
  pagamentos, definições e equipa com papéis por módulo.

## As quatro coisas que não se resolvem com código

1. **Multibanco.** O desenho dá referência e entidade. Há um `Gateway` e um
   registo de gateways no pacote, e só o Stripe implementado. Precisa de conta
   num fornecedor — Easypay, IfthenPay ou equivalente.
2. **Faturas e SAF-T.** O próprio desenho escreve «faturas emitidas por
   [programa certificado pela AT]». Em Portugal isso é um programa certificado,
   não é uma vista.
3. **Seguimento CTT.** O número de registo mostra-se sem API; seguir a encomenda
   dentro do site precisa de contrato.
4. **Entrar com Google e com Apple.** Credenciais de OAuth, e termos a aceitar
   em cada um.

## O Painel e o backoffice do CMS pedem uma decisão

São dois backoffices para a mesma casa. O do CMS acabou de ser reposto e corre o
que é partilhado com os outros sete sites — páginas, blocos, menus, pedidos,
definições, utilizadores, catálogo e as consultas. O `Painel` desenha outra
coisa: a agenda do dia, o que há para preparar, as tarefas, as avaliações por
moderar, a receita.

Três saídas, e nenhuma é neutra:

1. **O Painel só leva o que o CMS não tem** — a agenda em semana/mês, as
   avaliações, a faturação — e o resto continua no backoffice do CMS. Menos
   trabalho, e duas barras laterais com dois desenhos.
2. **O backoffice do CMS veste o desenho do Painel.** Um ecrã só, e o CMS deixa
   de ser o mesmo nos oito sites — o que é precisamente o que o torna barato.
3. **O Painel substitui o backoffice aqui.** O mais fiel ao desenho, e o mais
   caro: é reescrever páginas, blocos, menus, produtos e encomendas.
