#!/usr/bin/env bash
#
# Monta o pacote para o servidor de staging deste site — ver $DOMINIO_DE_SEMPRE.
#
# O que sai é um zip auto-suficiente: os ficheiros versionados, um `vendor/` a
# sério (o do desenvolvimento é um symlink para ../cms e num servidor não
# existe), as pastas de estado, um config já preenchido para este domínio, e um
# LEIA-ME lá dentro com os passos. Quem o abrir daqui a três meses não precisa
# de procurar instruções em lado nenhum.
#
# Fica em `~/Documents/dev/migration-files/<projecto>/`, com uma cópia do
# LEIA-ME ao lado do zip para se poder ler sem o abrir. A pasta é a mesma para
# todos os sites e cada um tem a sua subpasta — ver $FORA, mais abaixo, e o
# LEIA-ME que está na raiz dela. $MIGRATION_FILES no ambiente troca a raiz, para
# quem não tenha os projectos em ~/Documents/dev.
#
# Uso:
#   bin/preparar-staging.sh                          # instalação de raiz
#   bin/preparar-staging.sh --actualizacao           # servidor já instalado
#   bin/preparar-staging.sh --actualizacao --desde X # só o que mudou desde X
#   bin/preparar-staging.sh --dominio exemplo.pt     # para outro servidor
#
# O `--dominio` troca o endereço para onde o pacote vai. Sem ele é o staging,
# que é o caso corrente. O domínio não muda ficheiro nenhum do site: entra no
# config que o pacote leva numa instalação de raiz, e nas instruções do LEIA-ME
# — que é onde alguém o vai ler para saber onde correr as migrações.
#
# O `--desde` faz um pacote DELTA: leva só os ficheiros que mudaram entre essa
# revisão e agora. Serve para entregas seguidas, em que reenviar as fotografias
# todas e o CMS inteiro por causa de duas folhas de estilo é meia hora de FTP
# para nada. Sem valor escrito, parte da última etiqueta `entrega/*` — que este
# guião põe ao fim de cada pacote, exactamente para a próxima saber onde
# começar.
#
# O que o delta NÃO consegue fazer é apagar: o FTP escreve por cima, não remove.
# Os ficheiros que desapareceram entre as duas revisões vão listados no LEIA-ME
# para serem apagados à mão, e o delta recusa-se a sair quando o CMS mudou de
# versão — aí desaparecem ficheiros de dentro do vendor/, são demasiados para
# uma lista, e o pacote inteiro é mais seguro.
#
# A diferença entre instalar e actualizar é outra, e é importante: em modo de
# actualização o
# pacote **não leva config/config.php**. O que está no servidor tem as
# credenciais da base de dados, a chave da aplicação e o símbolo das migrações;
# enviar por cima o ficheiro gerado aqui apagava as três — o site ficava sem
# base de dados e toda a gente com sessão iniciada era posta fora.
#
# Nunca inclui: config/config.php do desenvolvimento (tem as credenciais
# locais), storage/ com sessões e registos, nem os uploads do editor.
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

# ---------------------------------------------------------------------------
# O QUE MUDA DE PROJECTO PARA PROJECTO. Três linhas, e são estas.
#
# Este guião é para ser copiado tal e qual para os outros sites: tudo o resto
# — a subpasta das entregas, o nome do cookie da sessão, os caminhos — sai da
# pasta onde o git está. Ao copiar, trocam-se estas três e mais nada.
#
# A abreviatura é o que vai no nome do zip. Vazia, fica o nome do projecto;
# existe só porque 'blandywinelodge' num nome de ficheiro é meio nome de
# ficheiro.
# ---------------------------------------------------------------------------
DOMINIO_DE_SEMPRE="casadoze.admedia.pt"
NOME_DO_SITE="Casa de Zé"
ABREVIATURA=""

MODO="instalacao"
DESDE=""
PEDIU_DELTA=0
OUTRO_DOMINIO=""
while [ $# -gt 0 ]; do
  case "$1" in
    --actualizacao) MODO="actualizacao" ;;
    # O `if` e não `shift` a seco: com `--desde` em último lugar não há segundo
    # argumento para saltar, e um `shift` sobre a lista vazia devolve erro — que
    # com `set -e` mata o guião sem imprimir uma linha. Foi assim que este modo
    # falhou em silêncio da primeira vez que correu.
    --desde)        PEDIU_DELTA=1; DESDE="${2:-}"; if [ $# -gt 1 ]; then shift; fi ;;
    --desde=*)      PEDIU_DELTA=1; DESDE="${1#*=}" ;;
    --dominio)      OUTRO_DOMINIO="${2:-}"; if [ $# -gt 1 ]; then shift; fi ;;
    --dominio=*)    OUTRO_DOMINIO="${1#*=}" ;;
    *) echo "Argumento que não conheço: $1" >&2; exit 1 ;;
  esac
  shift
done

if [ "$PEDIU_DELTA" = "1" ] && [ "$MODO" != "actualizacao" ]; then
  echo "O --desde só faz sentido com --actualizacao: instalar de raiz precisa de tudo." >&2
  exit 1
fi

# Sem revisão escrita, parte da última entrega. É para isso que a etiqueta
# existe — e não havendo nenhuma, é sinal de que esta é a primeira e não há
# delta nenhum a fazer.
if [ "$PEDIU_DELTA" = "1" ] && [ -z "$DESDE" ]; then
  DESDE=$(git describe --tags --abbrev=0 --match 'entrega/*' 2>/dev/null || true)
  if [ -z "$DESDE" ]; then
    echo "Não há nenhuma etiqueta entrega/* a que voltar. Escreva a revisão: --desde <rev>" >&2
    exit 1
  fi
fi

if [ -n "$DESDE" ] && ! git rev-parse --verify --quiet "${DESDE}^{commit}" > /dev/null; then
  echo "A revisão '${DESDE}' não existe neste repositório." >&2
  exit 1
fi

DOMINIO="$DOMINIO_DE_SEMPRE"
# Escrito à mão na linha de comando ganha ao de sempre. O nome do ficheiro
# acompanha, para dois pacotes do mesmo dia não se confundirem na pasta.
if [ -n "$OUTRO_DOMINIO" ]; then DOMINIO="$OUTRO_DOMINIO"; fi
DATA=$(date +%Y%m%d-%H%M)

# ---------------------------------------------------------------------------
# Onde o pacote fica.
#
# Fora da árvore do projecto, sempre: um zip dentro dela é uma cópia dela a
# viver lá dentro, e já entrou duas vezes num `git add -A` por arrasto.
#
# E numa pasta por projecto, debaixo da pasta partilhada. Flat, com o nome do
# projecto só no nome do ficheiro, a pasta de entregas de seis sites fica uma
# lista única por data em que ninguém encontra a entrega que procura.
#
# O nome do projecto é o da pasta onde o git está, e não um nome escrito aqui:
# este guião é para ser copiado para os outros sites, e um nome escrito à mão é
# um nome que alguém se esquece de trocar.
# ---------------------------------------------------------------------------
PROJECTO=$(basename "$(pwd)")
[ -n "$ABREVIATURA" ] || ABREVIATURA="$PROJECTO"
if [ -n "$DESDE" ]; then
  NOME="${ABREVIATURA}-incremental-${DATA}"
elif [ "$MODO" = "actualizacao" ]; then
  NOME="${ABREVIATURA}-actualizacao-${DATA}"
else
  NOME="${ABREVIATURA}-completo-${DATA}"
fi
FORA="${MIGRATION_FILES:-$HOME/Documents/dev/migration-files}/${PROJECTO}"
mkdir -p "$FORA"
DEST="${FORA}/${NOME}"

# ---------------------------------------------------------------------------
# Nada por commitar.
#
# Um pacote montado sobre alterações que não estão no git é um pacote que não
# se consegue reproduzir: descobre-se um defeito no servidor e não há nenhuma
# revisão a que voltar para o encontrar.
# ---------------------------------------------------------------------------
if [ -n "$(git status --porcelain)" ]; then
  echo "Há alterações por commitar. O pacote sai do que está no git — commite primeiro:" >&2
  git status --short >&2
  exit 1
fi

rm -rf "$DEST"
mkdir -p "$DEST"

if [ -n "$DESDE" ]; then
  # ---------------------------------------------------------------------------
  # O delta: só o que mudou entre as duas revisões.
  #
  # `--diff-filter=ACMR` é o que interessa a quem envia por FTP: acrescentados,
  # copiados, modificados e mudados de nome. Os apagados (D) ficam de fora de
  # propósito — não há como enviar a ausência de um ficheiro —, e por isso são
  # listados no LEIA-ME em vez de silenciados.
  #
  # `git archive` com a lista de caminhos preserva as pastas, que é o que faz o
  # zip descompactar por cima do servidor sem ninguém ter de pensar onde vai
  # cada ficheiro.
  # ---------------------------------------------------------------------------
  echo "→ Delta ${DESDE}..$(git rev-parse --short HEAD)…"

  MUDADOS=$(git diff --name-only --diff-filter=ACMR "$DESDE" HEAD)
  APAGADOS=$(git diff --name-only --diff-filter=D "$DESDE" HEAD)

  if [ -z "$MUDADOS" ]; then
    echo "Não mudou ficheiro nenhum desde ${DESDE}. Não há pacote a fazer." >&2
    exit 1
  fi

  # Um pacote delta só se descompacta por cima de um servidor que esteja
  # exactamente na revisão de onde ele parte. Se o CMS mudou de versão pelo
  # meio, há ficheiros a desaparecer de dentro do vendor/ e uma lista a pedir
  # que os apaguem à mão não é uma entrega — é uma armadilha.
  CMS_ANTES=$(git show "${DESDE}:composer.lock" 2>/dev/null | grep -A2 '"name": "admedia/cms"' | grep -oE '"version": "[^"]+"' || true)
  CMS_AGORA=$(grep -A2 '"name": "admedia/cms"' composer.lock | grep -oE '"version": "[^"]+"' || true)
  if [ "$CMS_ANTES" != "$CMS_AGORA" ]; then
    echo "O CMS mudou de versão desde ${DESDE} (${CMS_ANTES} → ${CMS_AGORA})." >&2
    echo "Um delta não apaga ficheiros do vendor/. Monte o pacote inteiro: sem --desde." >&2
    exit 1
  fi

  echo "$MUDADOS" | tr '\n' '\0' | xargs -0 git archive HEAD -- | tar -x -C "$DEST"
  echo "   $(echo "$MUDADOS" | wc -l | tr -d ' ') ficheiros"
  [ -n "$APAGADOS" ] && echo "   $(echo "$APAGADOS" | wc -l | tr -d ' ') apagados, listados no LEIA-ME"
else
  echo "→ Ficheiros versionados (revisão $(git rev-parse --short HEAD))…"
  git archive HEAD | tar -x -C "$DEST"
fi

# ---------------------------------------------------------------------------
# O vendor/, a sério.
#
# Em desenvolvimento, vendor/admedia/cms é um symlink para ../cms — é o que faz
# editar o CMS de dentro do site ser editar o repositório do CMS. Num servidor
# esse caminho não existe, por isso aqui o symlink é desfeito numa cópia real.
#
# O autoloader não precisa de ser gerado outra vez: ele referencia
# `$vendorDir . '/admedia/cms/src/...'`, que é relativo, e uma pasta a sério no
# lugar do symlink resolve igual.
# ---------------------------------------------------------------------------
CMS_VER=$(grep -oE "CURRENT = '[^']+'" vendor/admedia/cms/src/Core/Version.php | grep -oE "[0-9]+\.[0-9]+\.[0-9]+")

if [ -n "$DESDE" ]; then
  # O CMS não mudou de versão — foi confirmado lá em cima, e é a condição para
  # este modo existir. Reenviar 40 MB de ficheiros idênticos era o desperdício
  # que o delta veio acabar.
  echo "→ vendor/: fica de fora (CMS ${CMS_VER}, o mesmo que já está no servidor)"
else
  echo "→ vendor/ com o symlink desfeito…"
  if [ ! -e vendor/autoload.php ]; then
    echo "Falta o vendor/ local — corra composer install." >&2
    exit 1
  fi
  # -L segue os symlinks e copia o que está do outro lado.
  cp -RL vendor "$DEST/vendor"
  # O repositório do CMS não vai atrás.
  rm -rf "$DEST/vendor/admedia/cms/.git" "$DEST/vendor/admedia/cms/docs"
  echo "   CMS $CMS_VER"
fi

# ---------------------------------------------------------------------------
# Os assets do backoffice, republicados a partir do pacote que vai no zip.
#
# Feito aqui e não no repositório: assim o pacote leva-os sempre em passo com o
# CMS que o acompanha, e montar um zip não deixa a árvore de trabalho suja.
# ---------------------------------------------------------------------------
if [ -n "$DESDE" ]; then
  echo "→ Assets do backoffice: ficam de fora (saem do CMS, que não mudou)"
else
  echo "→ Assets do backoffice…"
  ( cd "$DEST" && php bin/cms-publish.php | sed 's/^/   /' )
fi

# ---------------------------------------------------------------------------
# As pastas de estado. Vazias, e com as permissões certas.
#
# 0700 porque em alojamento partilhado a pasta temporária do sistema é comum a
# todas as contas, e um ficheiro de sessão legível por outra conta é uma sessão
# de administrador entregue a alguém.
# ---------------------------------------------------------------------------
if [ -n "$DESDE" ]; then
  echo "→ storage/: fica de fora (já existe no servidor, com as sessões lá dentro)"
else
  echo "→ storage/…"
  mkdir -p "$DEST/storage/sessions" "$DEST/storage/logs"
  touch "$DEST/storage/sessions/.gitkeep" "$DEST/storage/logs/.gitkeep"
  chmod 700 "$DEST/storage" "$DEST/storage/sessions" "$DEST/storage/logs"
fi

# ---------------------------------------------------------------------------
# O config deste domínio.
#
# A chave e o símbolo das migrações são gerados agora, um por pacote: uma chave
# escrita num ficheiro versionado é uma chave que já não é segredo. As
# credenciais da base de dados ficam por preencher, porque não são minhas para
# adivinhar.
# ---------------------------------------------------------------------------
if [ "$MODO" = "actualizacao" ]; then
  echo "→ config/config.php: fica de fora (o do servidor é que manda)"
  MIG_TOKEN="O-QUE-ESTÁ-NO-SEU-CONFIG"
else
echo "→ config/config.php para ${DOMINIO}…"
APP_KEY=$(php -r 'echo bin2hex(random_bytes(32));')
MIG_TOKEN=$(php -r 'echo bin2hex(random_bytes(24));')

cat > "$DEST/config/config.php" <<PHP
<?php
/**
 * Configuração do staging — ${DOMINIO}
 *
 * Gerado por bin/preparar-staging.sh a $(date '+%Y-%m-%d %H:%M'), sobre a
 * revisão $(git rev-parse --short HEAD) e o CMS ${CMS_VER}.
 *
 * FALTA PREENCHER: as três linhas de 'db' marcadas com PREENCHER.
 * Este ficheiro nunca é versionado.
 */

return [
    'app' => [
        'name'     => "${NOME_DO_SITE}",
        'url'      => 'https://${DOMINIO}',   // sem barra no fim
        // Staging é um sítio público: 'production' e debug falso. Com debug
        // ligado, um erro mostra o caminho absoluto dos ficheiros e o rasto da
        // pilha a quem passar por lá. Os erros ficam em storage/logs/app.log.
        'env'      => 'production',
        'debug'    => false,
        'timezone' => 'Europe/Lisbon',
        'locale'   => 'pt',
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'PREENCHER',
        'user'    => 'PREENCHER',
        'pass'    => 'PREENCHER',
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'name'      => '${PROJECTO}_session',
        'lifetime'  => 7200,
        // Exige HTTPS. Se o subdomínio ainda não tiver certificado, o cookie
        // não é enviado e ninguém consegue entrar no backoffice — ver o
        // LEIA-ME, ponto 1.
        'secure'    => true,
        'httponly'  => true,
        'samesite'  => 'Lax',
        'save_path' => __DIR__ . '/../storage/sessions',
    ],

    'security' => [
        'app_key' => '${APP_KEY}',
        // Permite correr as migrações pelo browser, para alojamento sem shell.
        // Limpar depois de migrar — ver o LEIA-ME, ponto 6.
        'migration_token' => '${MIG_TOKEN}',
    ],

    'uploads' => [
        'public_dir'      => (defined('CMS_PUBLIC') ? CMS_PUBLIC : __DIR__ . '/../public') . '/assets/uploads',
        'public_url'      => '/assets/uploads',
        'public_max_size' => 8 * 1024 * 1024,
        'public_mimes'    => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    ],

    'cms' => [
        'update_manifest' => 'https://raw.githubusercontent.com/pfnunes15/admedia-cms-releases/main/latest.json',
    ],

    'mail' => [
        'from_email'      => '',   // vazio não envia nada
        'from_name'       => "${NOME_DO_SITE}",
        'smtp_host'       => '',
        'smtp_port'       => 587,
        'smtp_encryption' => 'tls',
        'smtp_user'       => '',
        'smtp_pass'       => '',
        'ehlo'            => '',
    ],
];
PHP
fi

# ---------------------------------------------------------------------------
# Fora dos motores de busca.
#
# Um staging indexado é o site do cliente a aparecer duas vezes no Google, uma
# delas com lorem ipsum. Este ficheiro é só do pacote de staging e não existe
# no repositório de propósito — em produção ele não pode ir.
# ---------------------------------------------------------------------------
# Num delta não se escreve: o ficheiro já está no servidor e é igual, e um
# pacote que não o traz é um pacote que não lhe pode tocar.
if [ -z "$DESDE" ]; then
cat > "$DEST/public/robots.txt" <<'ROBOTS'
# Servidor de staging. Nada aqui deve ser indexado.
# APAGAR este ficheiro quando este domínio passar a ser o site a sério.
User-agent: *
Disallow: /
ROBOTS
fi

# E o mesmo por cabeçalho, que é o que realmente segura.
#
# O robots.txt sozinho não chega: neste alojamento a Cloudflare serve um
# robots.txt gerido por ela — com `User-agent: * / Allow: /` — e o ficheiro do
# site nunca chega a ser lido. Um cabeçalho atravessa a Cloudflare, e
# `X-Robots-Tag: noindex` é mais forte do que um Disallow: diz para não
# indexar, e não apenas para não visitar.
#
# Acrescentado à cópia e não ao ficheiro versionado: em produção isto seria o
# site a não existir.
#
# `if [ -f ]` e não à sorte. Num delta o .htaccess só está no pacote se tiver
# mudado, e `cat >>` sobre um caminho que não existe CRIA o ficheiro — um
# .htaccess de sete linhas, com o bloco do noindex e mais nada, a aterrar por
# cima do que reescreve para public/ e fecha config/, views/ e storage/ à web.
# Seria um pacote a desligar o site e a abrir as pastas todas ao mesmo tempo.
if [ -f "$DEST/public/.htaccess" ]; then
cat >> "$DEST/public/.htaccess" <<'NOINDEX'

# ---------------------------------------------------------------------------
# STAGING — fora dos motores de busca.
#
# APAGAR ESTE BLOCO quando este domínio passar a ser o site a sério. Enquanto
# ele aqui estiver, o Google é instruído a não indexar uma única página.
#
# Está aqui e não só no robots.txt porque há alojamentos — este, com Cloudflare
# à frente — que servem um robots.txt próprio e engolem o do site. Um cabeçalho
# passa.
# ---------------------------------------------------------------------------
<IfModule mod_headers.c>
    Header always set X-Robots-Tag "noindex, nofollow, noarchive"
</IfModule>
NOINDEX
fi

# ---------------------------------------------------------------------------
# O LEIA-ME, dentro do pacote. Um por modo: instalar de raiz e actualizar são
# duas tarefas diferentes, e uma lista com «faça isto só se for a primeira vez»
# em cada passo é uma lista que ninguém segue até ao fim.
# ---------------------------------------------------------------------------
if [ -n "$DESDE" ]; then
cat > "$DEST/LEIA-ME.md" <<MD
# ${NOME_DO_SITE} — actualização parcial do staging

Pacote de $(date '+%Y-%m-%d %H:%M'), revisão \`$(git rev-parse --short HEAD)\`,
CMS ${CMS_VER}. Domínio: **https://${DOMINIO}**

Este pacote **só traz o que mudou** desde \`${DESDE}\`. Não é um site
completo: descompactado sozinho não funciona, e não é para isso que serve. É
para ser enviado por cima de um servidor que já está a correr essa revisão.

O que ficou de fora, e porquê:

| Não vem aqui | Porquê |
|---|---|
| \`vendor/\` | O CMS continua na ${CMS_VER}, o mesmo que está no servidor |
| \`public/assets/admin/\` | Sai do CMS, que não mudou |
| \`storage/\` | Já existe no servidor, com as sessões iniciadas lá dentro |
| \`config/config.php\` | O do servidor tem as credenciais, a chave e o símbolo |
| \`public/robots.txt\`, \`public/.htaccess\` | Já lá estão, e iguais |

## Enviar

Envie **tudo o que está aqui** para a pasta do subdomínio, por cima do que lá
está. A estrutura de pastas é a mesma, por isso cada ficheiro vai ao sítio dele.

São $(echo "$MUDADOS" | wc -l | tr -d ' ') ficheiros:

\`\`\`
$(echo "$MUDADOS" | sed 's/^/  /')
\`\`\`
$(if [ -n "$APAGADOS" ]; then
echo
echo "## Apagar à mão"
echo
echo "Estes deixaram de existir. O FTP escreve por cima mas não remove, por isso"
echo "ficam no servidor até alguém os apagar — e enquanto lá estiverem, são"
echo "código morto a ser servido:"
echo
echo '```'
echo "$APAGADOS" | sed 's/^/  /'
echo '```'
fi)

## Correr as migrações

Este pacote traz $(ls "$DEST/database/migrations"/*.sql 2>/dev/null | wc -l | tr -d ' ') migrações novas. O servidor aplica as que lhe faltarem e
ignora as que já tem. Com shell:

\`\`\`bash
php bin/migrate.php --pending    # ver o que falta
php bin/migrate.php              # aplicar
\`\`\`

Sem shell, pelo browser, com o símbolo que está no seu \`config/config.php\`
em \`security.migration_token\`:

\`\`\`
https://${DOMINIO}/_migrate?token=SIMBOLO&dry=1   # listar
https://${DOMINIO}/_migrate?token=SIMBOLO         # aplicar
\`\`\`

Se esse campo estiver vazio — e deve estar, entre actualizações —, ponha lá um
símbolo qualquer o tempo de correr as migrações e **esvazie-o outra vez a
seguir**.

## Confirmar

- \`https://${DOMINIO}/\` abre, e a página está como estava em local. Se vir a
  versão antiga é a cache do browser: **Cmd+Shift+R**.
- \`php bin/migrate.php --pending\` não devolve nada por aplicar.
- \`https://${DOMINIO}/admin\` continua a entrar com a mesma senha.

## Se alguma coisa correr mal

Um delta não se desfaz sozinho — não há ficheiro anterior dentro dele para
repor. O caminho de volta é o último pacote **inteiro** que está na pasta
\`migration-files/${PROJECTO}\`: envie esse por cima, sem tocar no
\`config.php\`. As migrações já aplicadas ficam, e não fazem mal: o conteúdo
que elas escreveram é o que o site mostra.
MD
elif [ "$MODO" = "actualizacao" ]; then
cat > "$DEST/LEIA-ME.md" <<MD
# ${NOME_DO_SITE} — actualização do staging

Pacote de $(date '+%Y-%m-%d %H:%M'), revisão \`$(git rev-parse --short HEAD)\`,
CMS ${CMS_VER}. Domínio: **https://${DOMINIO}**

Este pacote é uma **actualização** de um servidor já instalado. Não traz
\`config/config.php\` de propósito: o que está no servidor tem as credenciais
da base de dados, a chave da aplicação e o símbolo das migrações, e enviar
outro por cima apagava as três.

## Enviar

Envie **tudo o que está aqui** para a pasta do subdomínio, por cima do que lá
está. Não há nada para apagar primeiro.

Confirme que estas sobem por inteiro — são as pastas onde os clientes de FTP
costumam falhar ficheiros novos ao sincronizar:

\`\`\`
$(cd "$DEST" && for d in public/assets/img public/assets/fonts public/assets/video database/migrations; do
    [ -d "$d" ] && printf '%-26s %3s ficheiros\n' "$d/" "$(find "$d" -type f | wc -l | tr -d ' ')"
  done)
\`\`\`

## Correr as migrações

O pacote traz $(ls "$DEST/database/migrations"/*.sql 2>/dev/null | wc -l | tr -d ' ') migrações do site ao todo. O servidor aplica as que lhe
faltarem e ignora as que já tem, por isso não é preciso saber quais são. Com
shell:

\`\`\`bash
php bin/migrate.php --pending    # ver o que falta
php bin/migrate.php              # aplicar
\`\`\`

Sem shell, pelo browser, com o símbolo que está no seu \`config/config.php\`
em \`security.migration_token\`:

\`\`\`
https://${DOMINIO}/_migrate?token=SIMBOLO&dry=1   # listar
https://${DOMINIO}/_migrate?token=SIMBOLO         # aplicar
\`\`\`

Se esse campo estiver vazio — e deve estar, entre actualizações —, ponha lá um
símbolo qualquer o tempo de correr as migrações e **esvazie-o outra vez a
seguir**.

## Confirmar

Estes valem para qualquer actualização. O que ESTA entrega trouxe está no
commit \`$(git rev-parse --short HEAD)\` — é lá que se vê o que olhar, em vez
de uma lista escrita aqui que envelhece à primeira entrega seguinte.

- \`https://${DOMINIO}/\` abre, e a página está como estava em local. Se vir a
  versão antiga é a cache do browser: **Cmd+Shift+R**. Os endereços do CSS e do
  JavaScript levam a data do ficheiro, por isso isto quase nunca acontece.
- \`php bin/migrate.php --pending\` não devolve nada por aplicar.
- \`https://${DOMINIO}/admin\` continua a entrar com a mesma senha. Se pedir
  senha outra vez, o \`config.php\` foi substituído — reponha-o.

## Se alguma coisa correr mal

O pacote anterior continua em \`migration-files/${PROJECTO}\`. Voltar atrás
é enviar esse por cima, sem tocar no \`config.php\`.
MD
else
cat > "$DEST/LEIA-ME.md" <<MD
# ${NOME_DO_SITE} — staging

Pacote de $(date '+%Y-%m-%d %H:%M'), revisão \`$(git rev-parse --short HEAD)\`,
CMS ${CMS_VER}. Domínio: **https://${DOMINIO}**

## O que está aqui dentro

\`\`\`
config/     configuração (o config.php já vem preenchido para este domínio)
database/   as migrações deste site
public/     a pasta a servir pela web
storage/    sessões e registos — fora da web, e a 0700
vendor/     o CMS, cópia real (não é symlink)
views/      os modelos
\`\`\`

## Instalar, por esta ordem

**1. O certificado, primeiro.**
O \`config/config.php\` tem \`session.secure => true\`, que exige HTTPS: sem
certificado no subdomínio, o cookie de sessão não é enviado e **ninguém
consegue entrar no backoffice**. No cPanel, SSL/TLS Status, emitir para
\`${DOMINIO}\`. Se por alguma razão isso não for possível agora, mude essa
linha para \`false\` — e volte a pô-la a \`true\` no dia em que houver
certificado.

**2. Subir os ficheiros.**
Tudo o que está aqui, para a pasta do subdomínio.

**3. A pasta servida pela web.**
Aponte a document root do subdomínio para \`public/\`. É o melhor: assim mais
nada é alcançável pela web. Se o alojamento não deixar, funciona igual com a
root na raiz do pacote — o \`.htaccess\` reescreve para \`public/\` e nega o
acesso a \`config\`, \`src\`, \`views\`, \`database\`, \`storage\`, \`bin\` e
\`vendor\`.

**4. A base de dados.**
Criar uma, em utf8mb4:

\`\`\`sql
CREATE DATABASE ${ABREVIATURA}_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
\`\`\`

E preencher as três linhas marcadas \`PREENCHER\` em \`config/config.php\`.

**5. As permissões de \`storage/\`.**
\`storage\`, \`storage/sessions\` e \`storage/logs\` têm de ler **0700**. Os
ficheiros de sessão guardam identidades com sessão iniciada, e em alojamento
partilhado uma pasta legível por outra conta é uma sessão de administrador
entregue a alguém. O zip já os traz assim, mas o FTP nem sempre respeita
permissões — confirme no File Manager, com "Show Hidden Files" ligado.

**6. Correr as migrações.**
Com shell:

\`\`\`bash
php bin/migrate.php --pending    # ver o que corre
php bin/migrate.php              # aplicar
\`\`\`

Sem shell, pelo browser:

\`\`\`
https://${DOMINIO}/_migrate?token=${MIG_TOKEN}&dry=1   # listar
https://${DOMINIO}/_migrate?token=${MIG_TOKEN}         # aplicar
\`\`\`

**Depois de migrar, esvazie \`security.migration_token\` no config.** Enquanto
ele estiver posto, quem souber o endereço corre migrações no site.

**7. O primeiro administrador.**
Só funciona com a tabela de utilizadores vazia, e a senha é mostrada uma vez:

\`\`\`
https://${DOMINIO}/_migrate?token=${MIG_TOKEN}&seed-admin=voce@exemplo.com&name=O+Seu+Nome
\`\`\`

Ou, com shell:

\`\`\`bash
ADMIN_EMAIL=voce@exemplo.com ADMIN_NAME="O Seu Nome" php bin/seed-admin.php
\`\`\`

## Confirmar que ficou bem

- \`https://${DOMINIO}/\` abre a homepage, com fotografias.
- \`https://${DOMINIO}/loja\` abre a loja, vazia.
- \`https://${DOMINIO}/admin\` pede senha, e entra.
- Depois de entrar, aparece um ficheiro em \`storage/sessions\`. Se essa pasta
  ficar vazia, o PHP não conseguiu escrever lá e caiu na pasta partilhada —
  \`storage/logs/app.log\` traz um aviso \`[CMS][session]\` a dizer isso.
- O \`public/assets/uploads/.htaccess\` subiu. É o que impede que alguma coisa
  nessa pasta seja executada. É um ficheiro escondido: os clientes de FTP e o
  File Manager precisam os dois de mostrar escondidos.

## O que este pacote NÃO traz

- **E-mail.** O \`mail.from_email\` está vazio, por isso nada é enviado — nem
  recuperações de senha, nem confirmações de encomenda. Para staging costuma
  ser o que se quer; preencha quando quiser testar envios.
- **Pagamentos.** As chaves do Stripe põem-se em Definições, no backoffice. Sem
  elas a loja mostra os produtos e recusa finalizar, que é o comportamento
  certo e está escrito na página.
- **Uploads.** A pasta \`public/assets/uploads\` vai vazia. As fotografias que
  se vêem são as do desenho e estão em \`public/assets/img\`.

## Quando isto passar a ser o site a sério

1. **Tirar as duas travas dos motores de busca.** Neste pacote o site está
   fechado à indexação em dois sítios, e em produção qualquer um deles é o
   site a não existir:
   - apagar \`public/robots.txt\`;
   - apagar o bloco marcado \`STAGING\` no fim de \`public/.htaccess\`.

   São dois porque um não chega: com a Cloudflare à frente, o \`robots.txt\`
   do site é substituído por um dela, que diz \`Allow: /\`. O cabeçalho
   \`X-Robots-Tag\` do \`.htaccess\` é o que realmente segura.
2. Trocar \`app.url\` para o domínio definitivo.
3. Confirmar que \`security.migration_token\` está vazio.
4. Gerar um \`app_key\` novo, se o staging tiver sido partilhado com terceiros.
MD
fi

# ---------------------------------------------------------------------------
# O zip.
# ---------------------------------------------------------------------------
echo "→ A fechar o zip…"
( cd "$FORA" && zip -qr "${NOME}.zip" "$NOME" -x '*.DS_Store' )

# O mesmo LEIA-ME, fora do zip. A pasta de entregas junta seis sites e meses de
# pacotes: poder ler o que um deles leva dentro sem o descompactar é a diferença
# entre escolher o certo e abrir quatro.
cp "$DEST/LEIA-ME.md" "${FORA}/${NOME}.md"

TAMANHO=$(du -h "${FORA}/${NOME}.zip" | cut -f1)
FICHEIROS=$(find "$DEST" -type f | wc -l | tr -d ' ')

echo
echo "✓ ${FORA}/${NOME}.zip  (${TAMANHO}, ${FICHEIROS} ficheiros)"
echo "  domínio   ${DOMINIO}"
echo "  revisão   $(git rev-parse --short HEAD)"
echo "  CMS       ${CMS_VER}"
echo
echo "  Os passos estão no LEIA-ME.md, dentro do pacote."
if [ -n "$DESDE" ]; then
  echo "  Delta desde ${DESDE}: só ficheiros alterados, sem vendor/ nem storage/."
  echo "  Não leva config/config.php — o do servidor fica."
  [ -n "$APAGADOS" ] && echo "  Há ficheiros para apagar À MÃO no servidor — a lista está no LEIA-ME."
elif [ "$MODO" = "actualizacao" ]; then
  echo "  Actualização: não leva config/config.php — o do servidor fica."
else
  echo "  Falta preencher as credenciais da base de dados em config/config.php."
fi

# ---------------------------------------------------------------------------
# A etiqueta da entrega.
#
# É ela que dá ao próximo pacote um ponto de partida. Sem isto, «só o que mudou
# desde a última vez» era uma pergunta a que ninguém sabia responder daí a três
# semanas — e um delta montado a partir da revisão errada é um pacote que deixa
# metade do site por actualizar sem dar erro nenhum.
#
# Só depois de o zip estar fechado: uma etiqueta a apontar para uma entrega que
# falhou a meio é pior do que etiqueta nenhuma.
# ---------------------------------------------------------------------------
ETIQUETA="entrega/${DATA}"
git tag -a "$ETIQUETA" -m "Entrega para ${DOMINIO} — ${NOME}" 2>/dev/null \
  && echo "  Etiqueta ${ETIQUETA} posta. Empurre-a: git push origin ${ETIQUETA}" \
  || echo "  (etiqueta ${ETIQUETA} já existia)"
