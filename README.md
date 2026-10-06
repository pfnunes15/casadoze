# Casa de Zé

> **Aviso: este repositório já não é o que está escrito a seguir.**
>
> O site foi **reduzido à porta de entrada**. Saíram a loja pública, o cesto, o
> checkout, as páginas de produto, as marcações, a entrada e o backoffice
> inteiro. Ficou uma página — a `home` — construída contra a maquete aprovada em
> `docs/maquete.html`, e os dados que ela lê.
>
> A tabela de rotas tem **um** endereço: `GET /`. Quem procurar aqui o que o
> texto abaixo descreve não o encontra.
>
> **O que saiu está no histórico**, no commit anterior a esta redução, e
> recupera-se de lá. O resto do texto deste ficheiro fica como estava porque
> continua a descrever o projecto que era — e porque é a ele que se volta se
> alguém quiser o site inteiro de novo.
>
> Consequências que valem a aviso:
>
> - **A Casa já não tem como editar nada.** Sem backoffice, os textos mudam-se
>   por SQL ou por migração.
> - **As existências em armazém estão a 12 e são inventadas** — ver
>   `database/migrations/0006_a_montra_da_maquete.sql`. Têm de ser corrigidas
>   antes de abrir.
> - O boletim do rodapé **não envia nada**, e o botão «+ Adicionar» conta no
>   cesto do cabeçalho sem ir ao servidor: não há cesto para onde ir.

O site, a loja e as consultas. O núcleo do CMS é o mesmo que corre a Quinta da
Moscadinha, o Barbeito e o Blandy's — vem do pacote `admedia/cms`, não está
copiado aqui. Deste projecto é o que o distingue: o catálogo de blocos
(`config/blocks.php`), os modelos em `views/sections/`, a folha de estilo, e em
`src/` o motor de marcação de consultas, que o pacote não tem.

Duas coisas se vendem aqui, e são de naturezas diferentes:

- **A loja** — velas, óleos, cristais, ervas. Catálogo, cesto, checkout, Stripe,
  IVA, portes e encomendas, tudo do pacote. Liga-se com um ficheiro; ver **A
  loja**.
- **As consultas** — leituras de tarot e trabalhos espirituais com o Zé, online
  ou presencialmente. Não vêm do pacote: um CMS partilhado não tem consultas de
  tarot. Ver **As consultas**.

## Requisitos

- PHP 8.1 ou mais recente
- MySQL 5.7+ / MariaDB 10.3+
- Apache com `mod_rewrite` (os `.htaccess` contam com ele)
- Composer, para instalar o pacote do CMS

## Onde o CMS vive

O pacote é consumido por um repositório `path` do Composer apontado a `../cms`,
com symlink. Este projecto fica **ao lado dos outros sites e do CMS**, que é o
que faz esse `../cms` apontar para o sítio certo sem mais nada:

```
Documents/dev/
  asjp/
  barbeito/
  blandywinelodge/
  casadoze/            este projecto
  cms/                 o pacote admedia/cms
  madeiratraveltaxi/
  quintadamoscadinha/
  travellider/
```

O `vendor/admedia/cms` fica um symlink relativo — `../../../cms/`, o mesmo que o
Barbeito tem — para a cópia de trabalho em `Documents/dev/cms`, que é a mesma que
todos os outros usam. **Editar o CMS a partir daqui é editar esse repositório** —
é o mesmo ficheiro —, e commita-se lá. Não há segunda cópia a divergir.

Daí que o sítio no disco não seja indiferente: um projecto noutra pasta tem de
inventar um symlink só para o `../cms` resolver, e um symlink inventado é uma
coisa que não está no repositório e que a máquina seguinte não tem.

Depois de cada `composer install` ou `composer update`, publicar os assets do
backoffice:

```bash
php bin/cms-publish.php
```

O browser só alcança o que está debaixo do docroot, e `vendor/` não está — por
isso a folha de estilo do backoffice, o guião dele e o editor de texto são
copiados para `public/assets/`.

## Arrumação

```
config/     configuração, a tabela de rotas, o catálogo de blocos, as definições
database/   migrações deste site (a sequência dele; a do CMS vem do pacote)
docs/       a maquete aprovada, como referência de desenho
lang/       as frases deste site, nas cinco línguas
public/     docroot: front controller, assets, uploads
src/        Controllers, Models, Services — o motor das consultas
storage/    sessões, registos — fora do docroot, nunca legíveis pela web
views/      modelos
```

## Instalar localmente

```bash
composer install
php bin/cms-publish.php
cp config/config.example.php config/config.php
# editar config/config.php: base de dados, app.url, app_key
mysql -e "CREATE DATABASE casadoze CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php bin/migrate.php
ADMIN_EMAIL=voce@exemplo.pt ADMIN_NAME="O Seu Nome" php bin/seed-admin.php
php bin/gerar-vagas.php
php -S 127.0.0.1:8000 index.php
```

As migrações semeiam o site com o conteúdo da maquete aprovada: a porta de
entrada com os seus seis blocos, as três categorias e os oito produtos da loja, e
as quatro consultas. Não é conteúdo definitivo — é o que a Casa vai editar — mas
é conteúdo a sério e não «Lorem ipsum»: um site semeado com texto falso é um site
que ninguém consegue avaliar, porque não se vê se um título é comprido de mais
nem se uma frase cabe no cartão.

**Onde a maquete tinha um `[x]` ficou um `[x]`.** Os prazos de envio, a morada, o
telefone, o NIF e os preços das consultas estavam escritos assim porque ninguém
os tinha dito ainda. Semeá-los com números inventados era pior do que a falta: um
prazo de envio errado é uma promessa que a Casa não sabe que fez. Para saber o que
falta escrever, procure por `[` nas migrações, ou abra Definições.

Localmente, `app.env` fica em `development` e `app.debug` em `true`. Em produção
têm de ser `production` / `false` — a aplicação recusa arrancar de outra maneira.

## Migrações: duas sequências

O CMS e o site numeram as suas independentemente, cada um a partir de `0001`. A
tabela `migrations` guarda o par `(source, filename)`, e é isso que permite ao
site ter as suas sem renumerar as do pacote. Com a loja ligada há **três**
sequências: `cms`, `shop` e `site`.

```bash
php bin/migrate.php --pending    # mostrar o que correria
php bin/migrate.php              # aplicar
```

Sem shell, pôr `security.migration_token` em `config/config.php` — uma cadeia
aleatória de 32 ou mais caracteres — e abrir:

```
https://o-site/_migrate?token=O_TOKEN&dry=1   # listar
https://o-site/_migrate?token=O_TOKEN         # aplicar
```

Limpar o token assim que o site estiver no ar. O endereço recusa agir enquanto ele
estiver vazio, e tira-se de vez apagando a linha dele em `config/routes.php`.

## As cinco línguas

Português, inglês, francês, alemão e espanhol, como a maquete pedia. Quais estão
ligadas é uma definição (`i18n.locales`), não uma linha de código: a Casa liga e
desliga uma em Definições &rsaquo; Idiomas.

**O que é texto da Casa está na base de dados; o que é moldura está em `lang/`.**
Os títulos, os textos, os nomes das consultas e dos produtos têm uma linha por
língua nas tabelas e editam-se no backoffice — um nome de produto escrito num
ficheiro PHP era um nome que a Casa não podia mudar sem um programador. Os botões,
as etiquetas dos campos e as mensagens de erro estão em `lang/{código}.php`, por
cima do catálogo do pacote.

Uma frase que falte aparece no ecrã como a própria chave, `marcacao.pick`, que se
vê e se corrige — em vez de um botão sem texto que ninguém repara que está vazio.
O que falte numa língua cai no inglês antes de cair na chave, e por isso o
`lang/en.php` está completo: é a rede debaixo das outras quatro.

**Uma limitação conhecida:** o `format_date()` do pacote só traduz os nomes dos
meses e dos dias para português. Em inglês, francês, alemão e espanhol uma data
sai com os nomes em inglês. Nada parte; está dito aqui porque se vê, e corrige-se
no pacote e não neste site — é uma função que os cinco sites partilham.

## O desenho

### A folha de estilo é mobile-first

`public/assets/css/main.css` é escrita a partir do telemóvel: as regras sem
condição são o telefone, e os ecrãs largos entram em `@media (min-width: …)`. Ao
contrário — desenhar o computador e depois descontar — cada ecrã pequeno acaba a
desfazer regras que não devia ter herdado, e a folha fica com mais excepções do
que desenho.

As cores e as letras da maquete estão todas em variáveis no topo. Uma cor escrita
a meio da folha é uma cor que ninguém encontra no dia em que a Casa quiser mudar o
ouro.

### As letras vêm daqui e não do Google

Três famílias — IM Fell English SC nos títulos, Cormorant Garamond na prosa, IBM
Plex Mono nas etiquetas —, desempacotadas da maquete para
`public/assets/fonts/`. Uma letra que vem de fora é um pedido a outro servidor
antes de a página desenhar, e são também os endereços de quem visita a ir a casa
de terceiros sem ninguém ter perguntado.

Só os subconjuntos `latin` e `latin-ext`, que é o que as cinco línguas precisam:
nove ficheiros, 230 KB ao todo. O cirílico e o vietnamita que a maquete trazia
ficaram de fora.

### O que o JavaScript faz, e o que não é preciso que ele faça

`public/assets/js/site.js`, sem dependências e sem passo de compilação. **Nada ali
é necessário para a página se ler**: o cabeçalho fica transparente, o painel do
menu fica fechado, e a agenda das consultas aparece como um `<select>` com as
horas todas. É o que torna o site melhor quando há JavaScript, não o que o torna
possível.

O cabeçalho ganha fundo ao descer com um `IntersectionObserver` sobre uma
sentinela no topo do documento, e não com um listener de `scroll`: esse corria
dezenas de vezes por segundo a medir a página: este corre duas.

As brasas da abertura são um canvas e não cem elementos a mexer, e não correm
para quem pediu menos movimento no sistema.

### Os blocos

Sete, em `config/blocks.php`, com a documentação de como se escreve uma entrada.
Um bloco é uma entrada nesse ficheiro mais um parcial em `views/sections/` com o
mesmo nome da chave — o formulário do backoffice é gerado da entrada, não há
formulário para escrever. Um bloco sem parcial não aparece sequer no selector
(`Blocks::renderable`).

| bloco | o que é |
|---|---|
| `abertura` | a fotografia de ecrã inteiro, com as brasas e dois botões |
| `loja-montra` | uma grelha de produtos **da loja** e um botão para ela |
| `consultas` | os cartões das consultas, cada um com a sua agenda |
| `ze` | o retrato, e as duas listas: o que faço e o que não faço |
| `testemunhos` | palavras de quem passou pela Casa |
| `garantias` | envios, pagamento, devoluções, embalagem |
| `texto` | prosa, para as páginas que são texto |

**As consultas e os produtos não se escrevem dentro de um bloco.** São registos,
em Consultas e em Loja; o bloco é onde o editor decide que a lista aparece. Na
maquete estavam escritos à mão dentro da página, e isso queria dizer duas coisas:
que a mesma consulta em duas páginas eram duas consultas diferentes para quem
contasse horas, e que mudar um preço era mexer em todas as páginas onde ele
aparecia.

### O que da maquete ainda não está feito

A maquete tem quatro peças que este site ainda não desenha, e ficam ditas para não
se confundir o que está feito com o que foi visto:

- **o calendário lunar** («Agora na Casa», com o próximo sabbat e a fase da lua);
- **o tarot interactivo**, em que se tira três cartas na própria página;
- **a roda dos signos**, com as medidas de amor, trabalho e energia;
- **o grimório**, a faixa dos vídeos do YouTube.

São quatro blocos novos — uma entrada em `config/blocks.php` e um parcial cada —
mais o guião de cada um. Nenhum é necessário para vender nem para marcar, e é por
isso que não vieram primeiro. O markup da maquete está em `docs/maquete.html`, com
a arte em canvas e a matemática das fases da lua lá dentro.

## As consultas

A Casa define horas com lotação, e quem marca desconta delas no instante. Sem
pagamento — paga-se à chegada, ou combina-se na conversa — e sem confirmação à
mão: a hora fica logo segura.

### As cinco tabelas, e porquê cinco

| | |
|---|---|
| `consultations` | a consulta — o que é, quanto dura, quanto custa, onde acontece |
| `consultation_schedules` | o horário: «quartas às 21h» |
| `consultation_closures` | os dias em que não há — Natal, férias |
| `consultation_slots` | cada vaga concreta, com os lugares tomados |
| `bookings` | quem vem |

Vem do enoturismo do Barbeito, onde esta forma já está provada, com três
diferenças que são deste sítio.

**O preço é um número e não uma frase.** No Barbeito a linha «90 min · €25 por
pessoa» é texto escrito à mão. Aqui são dois números — `duration_min` e
`price_cents` — e a linha é feita à saída, em `Consultation::linha()`: duas
verdades sobre a mesma coisa acabam sempre a discordar, e um preço que se venha a
cobrar online tem de estar em cêntimos de qualquer maneira. `price_cents` a NULL
não é zero: zero é grátis e NULL é «falamos primeiro», que é o que a página mostra
como «Sob consulta».

**Uma consulta pode não se marcar por hora nenhuma.** É o `is_bookable`. Os
trabalhos espirituais preparam-se depois de uma conversa, e não há horas para
escolher: o cartão deles mostra «Falar com o Zé» em vez de uma agenda. Sem esta
coluna, a única saída era deixá-los sem horário e esperar que a página percebesse
o vazio — e uma consulta sem horas por engano ficava igual a uma que nunca as
devia ter.

**A lotação é um, e a coluna existe à mesma.** Uma leitura é de uma pessoa. A
coluna fica porque um círculo ou um workshop é a mesma mesa com mais cadeiras, e
isso escreve-se mudando um número e não acrescentando uma tabela.

### Porquê uma tabela de vagas e não só o horário

Um horário sabe dizer que às quartas há consulta; não sabe segurar uma hora. Para
descontar lotação é preciso uma linha que se possa trancar. O horário gera-as para
a frente (`App\Services\GeradorDeVagas`) e a partir daí cada vaga vive por si —
muda-se-lhe a lotação, fecha-se, abre-se uma avulsa — sem mexer no horário nem
perder quem já lá está.

A lotação desconta-se numa instrução, e é o `WHERE` que faz o trabalho:

```sql
UPDATE consultation_slots SET seats_taken = seats_taken + :n
 WHERE id = :id AND is_open = 1 AND seats_taken + :n <= capacity
```

Duas pessoas a marcar a última hora no mesmo instante fazem dois `UPDATE`, e o
segundo actualiza zero linhas — é essa a que ouve que já não há hora. Contar
primeiro e inserir depois é a versão errada disto: entre a contagem e a inserção
cabe outra marcação.

### O Zé é um só: o choque entre consultas

**Isto não existe no Barbeito e tem de existir aqui.** Lá uma visita é de grupo e
há mais do que um guia: duas visitas à mesma hora são duas salas, e nada se
sobrepõe. Aqui é uma pessoa — uma leitura de três cartas às 21h e um Baralho
Cigano às 21h são o Zé em dois sítios. A lotação da vaga não apanha isto, porque
são vagas diferentes e cada uma tem o seu lugar livre.

`ConsultationSlot::conflict()` responde a «que marcação viva se cruza com esta
hora», e `Booking::create()` pergunta-lho antes de segurar o lugar.

**A agenda tranca-se com um nome, e não com `FOR UPDATE`.** A primeira versão
lia os conflitos com `FOR UPDATE`, que é a resposta de manual. Duas marcações ao
mesmo tempo para horas que se cruzam davam um *deadlock* do InnoDB — cada
transacção trancava as linhas da sua vaga e depois pedia as da outra, em ordens
opostas. O resultado ficava correcto, porque o MySQL mata uma das duas, mas a que
morria levava uma excepção em vez de ouvir «essa hora já não está livre»: a pessoa
certa recebia um erro de servidor. **Está testado**, com dois processos a correr
ao mesmo instante, e foi assim que apareceu.

Um tranco com nome (`GET_LOCK`) não tem ordem para inverter, e por isso não pode
haver *deadlock*. E diz o que é: a agenda do Zé é uma só, e uma pessoa de cada vez
é que escreve nela. O custo é as marcações ficarem em fila — numa Casa com uma
pessoa a atender, uma fila que nunca tem mais do que uma pessoa.

### O gerador

`php bin/gerar-vagas.php` enche o calendário a partir do horário, até 90 dias.
Correr as vezes que forem precisas não faz mal — o índice único de consulta mais
hora torna-o idempotente, e o que já existe fica com as marcações que tiver.

Para um cron de madrugada:

```
17 4 * * *  cd /caminho/do/site && php bin/gerar-vagas.php >> storage/logs/vagas.log 2>&1
```

Sem cron o site desenrasca-se — o gerador corre sozinho quando o calendário fica a
menos de duas semanas do fim —, mas aí é quem abre o backoffice a pagar a geração.

### Três coisas que não acontecem, de propósito

- **Apagar uma linha do horário não apaga as vagas que ela gerou.** Podem ter
  gente marcada. O que fica é uma vaga sem horário, igual a uma avulsa.
- **Fechar um dia não apaga as vagas — fecha-as.** As marcações ficam de pé e
  alguém tem de avisar essas pessoas; um fecho que apagasse as vagas apagava com
  elas a lista de quem era preciso avisar.
- **Baixar a lotação de uma vaga nunca desce abaixo do que já está marcado.**
  Baixar não desmarca ninguém, só faria a vaga mentir.

E, no mesmo espírito: apagar uma consulta ou uma vaga com marcações por acontecer
é recusado, com a contagem à vista e a alternativa dita — despublicar a consulta,
ou fechar a hora.

### O que uma marcação guarda, e porque o endereço dela leva um símbolo

`code` é o que a pessoa lê e diz ao telefone; `token` é o que abre a página de
cancelamento e nunca aparece escrito em lado nenhum senão na ligação do e-mail. São
dois porque servem coisas diferentes: um código curto para ser lido em voz alta é
curto de mais para ser um segredo.

A coluna `note` é o que a pessoa escreve sobre o que a traz, e numa casa destas é a
coluna mais sensível da base de dados. É por causa dela que a página de uma
marcação se abre pelo `token` e não pelo `code`: um endereço adivinhável dava a
qualquer pessoa o nome, o telefone e a aflição de outra.

### O backoffice: três ecrãs

Três, e não quatro. Havia «Vagas» e «Marcações» separados, e a divisão era do
programa e não de quem o usa: uma vaga só existe para ser marcada, e quem gere
estava sempre a saltar de um ecrã para o outro a meio de uma chamada.

- **Marcações** — duas abas. O **calendário** é onde há hora e é por onde se
  marca, em semana (para quem gere) ou em mês (para quem atende o telefone). A
  **lista** é quem vem, por dia e dentro do dia por hora.
- **Consultas** — a consulta e o seu horário no mesmo ecrã. Duas listas: as que
  se marcam por hora, e as que começam com uma conversa.
- **Dias encerrados** — por mês, com os anuais marcados.

O ecrã de uma vaga não é um quarto ecrã: é o detalhe de um dia do calendário, e
abre-se de lá. É também onde se marca por telefone, que é onde se está a olhar
quando o telefone toca — e onde se vê o aviso de que aquela hora se cruza com
outra, antes de a prometer a alguém.

**Marcar por telefone passa pelo mesmo `Booking::create()` que o site.** É o que
faz uma marcação feita ao telefone descontar a hora, não se cruzar com outra
consulta e receber um código. Uma segunda via de escrita acabaria a divergir da
primeira, e a que divergisse seria essa, que ninguém testa tantas vezes. O que é
diferente é o que se exige: aqui não há caixa de consentimento nem travão de
repetição — quem está a escrever é a Casa, ao telefone com a pessoa.

### O que ainda não faz

**Não manda e-mails.** Nem a confirmação a quem marcou, nem o aviso à Casa, nem o
lembrete na véspera. A coluna `mailed_at` está na tabela e `Booking::markMailed()`
está escrito à espera disso. O que falta é a vista do e-mail e a chamada no fim de
`BookingController::store` — e as chaves de SMTP em `config/config.php`, sem as
quais nada sai de qualquer maneira.

**Não cobra.** Paga-se à chegada. O dia em que passar a cobrar, o que muda é o fim
de `store`: em vez de dar a marcação por confirmada, cria a encomenda e manda a
pessoa ao pagamento. A hora continua a segurar-se antes, que é o que faz a agenda
não vender o que não tem. Ver **A loja**, a seguir, para onde isso iria encostar.

## A loja

A camada de loja do pacote — catálogo, cesto, checkout, Stripe, IVA, portes,
encomendas — **está ligada**. É a existência de `config/shop.php` que a liga (ver
`App::hasShop`): sem esse ficheiro, as migrações da loja não correm e as tabelas
dela não são criadas.

Os endereços são deste site e estão nesse ficheiro: `/loja`, `/carrinho`,
`/checkout`. Um link escreve-se com `Shop::to('product', ['slug' => $slug])`, que
já põe o prefixo da língua — nada de `locale_url('/loja/...')` à mão, que é como
um site ganha uma segunda língua e só descobre os links partidos meses depois.

As rotas são montadas por `Shop::routes($r)` e `Shop::adminRoutes($r)` e não
copiadas para aqui: a ordem por que se registam é conhecimento da loja —
`/loja/categoria/{slug}` tem de vir antes de `/loja/{slug}`, ou «categoria» é
procurada como se fosse um produto.

### Antes de vender

Três coisas, e nenhuma é código:

1. **As chaves do Stripe**, em Definições &rsaquo; Loja. Enquanto estiverem
   vazias a loja mostra os produtos e não deixa finalizar a compra — que é o que
   se quer enquanto ela está a ser preparada.
2. **O webhook.** No Stripe, em Programadores &rsaquo; Webhooks, um destino para
   `https://o-site/pagamento/stripe/aviso`, evento
   `checkout.session.completed`, e o segredo (`whsec_…`) em Definições. **Sem
   isto as encomendas nunca passam a pagas**, porque é o que prova que o
   pagamento é real: quem volta ao site pode ter fechado a janela antes de pagar,
   e quem paga pode fechá-la depois.
3. **O stock.** As migrações semeiam os oito produtos com stock a **zero**, de
   propósito: um produto sem stock mostra-se na montra e diz «esgotado» em vez de
   se deixar comprar, e é isso que se quer enquanto ninguém contou o que está na
   prateleira. Pôr um número inventado era prometer o que ninguém verificou.

Falta também o IVA confirmado — está a 23, o do continente — e as fotografias dos
produtos, que a maquete não tinha: os cartões dela desenhavam cada produto num
canvas, à mão. Enquanto não houver fotografia, o cartão sai com o nome e o preço,
que é o que a grelha sabe fazer.

## Publicar (alojamento partilhado, cPanel ou FTP)

Não há SSH no alojamento, por isso publicar é copiar ficheiros:

1. Enviar tudo excepto `config/config.php`, `storage/` e `docs/`.
2. Criar `config/config.php` a partir do `.example`, com `app.env` em
   `production` e `app.debug` em `false`.
3. Apontar o docroot a `public/`. Onde não for possível, o `.htaccess` da raiz
   encaminha para lá — mas apontar o docroot é melhor, porque então nada mais é
   alcançável pela web.
4. Correr as migrações pelo `/_migrate?token=…`, e limpar o token a seguir.
5. `php bin/cms-publish.php` não corre sem shell: copiar à mão o que ele copiaria,
   de `vendor/admedia/cms/public/` para `public/assets/`.
6. Confirmar que `storage/sessions/` existe e é escrevível, e que não é alcançável
   pela web.
7. Pôr o cron do `bin/gerar-vagas.php`. Sem ele o site desenrasca-se, mas é quem
   abre o backoffice a pagar a geração.

## Antes de lançar: correr os sites todos

```bash
cd ../cms && php bin/check-sites.php
```

Os sites instalam o pacote por *path repository* com symlink, o que quer dizer que
saltam para a versão nova no instante em que o repositório do CMS muda. Não há um
`composer update` a servir de porta. Um site que ninguém abra é um site onde a
regressão só aparece quando o cliente lá for.

Este projecto tem duas cópias de vistas do pacote, e as duas são dívida que esse
verificador não apanha:

- `views/layouts/admin.php` — copiado só para pôr «Consultas» na barra lateral.
  Procure por «ACRESCENTADO NESTE SITE»; é a única diferença.
- `views/pages/composed.php` — copiado porque o do pacote escreve as classes
  `page-view` e `page-shell`, que são do CSS do CMS e este site não tem.

Quando o pacote mexer numa delas, a cópia não acompanha. Ao actualizar o CMS,
compare os dois ficheiros.

## Licença

Proprietária.
