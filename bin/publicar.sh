#!/usr/bin/env bash
#
# Publica este site no servidor, por SSH.
#
# Substitui o caminho antigo — montar um zip com `bin/preparar-staging.sh` e
# enviá-lo por FTP —, que existia porque se julgava não haver SSH no
# alojamento. Há: é a mesma conta Hostinger dos outros sites, a chave é da
# conta e não do site, e o servidor tem `rsync`, `git`, `composer` e PHP 8.2.
#
# O zip continua a servir para quem não tem a chave, e para a primeira
# instalação num servidor vazio. Para o dia-a-dia é isto.
#
# Uso:
#   bin/publicar.sh                  # ENSAIO: diz o que faria, e não faz nada
#   bin/publicar.sh --a-serio        # faz
#   bin/publicar.sh --a-serio --migrar   # faz, e corre as migrações a seguir
#
# **O ensaio é o que acontece por omissão, de propósito.** Publicar escreve por
# cima de um site que está no ar; um guião que o faz sem se pedir duas vezes é
# um guião que o faz por engano. O ensaio imprime a lista do que mudaria,
# incluindo o que seria apagado.
#
# ---------------------------------------------------------------------------
# O QUE NUNCA VAI, E PORQUÊ
#
#   config/config.php   O do servidor tem as credenciais da base de dados, a
#                       chave da aplicação e o símbolo das migrações. Enviar o
#                       de desenvolvimento por cima apagava as três: o site
#                       ficava sem base de dados e toda a gente com sessão
#                       iniciada era posta fora.
#   storage/            Sessões e registos. São do servidor.
#   public/assets/uploads/  O que a Casa carregou pelo backoffice. É do
#                       servidor, e de lá não sai.
#   docs/               3,4 MB de maquetes que o site não serve.
#   *.md                README, notas, changelogs — do pacote também. Vivem no
#                       git e na máquina de desenvolvimento, e num servidor são
#                       só superfície: contam o que o site é e onde está o que
#                       interessa, a quem for ver.
#   .git/ e .access     História e credenciais anotadas à mão.
#   vendor/admedia/cms/.git/  1,4 MB, e com o endereço do repositório privado
#                       lá dentro. Já foi enviado uma vez por engano.
#
# A licença do TinyMCE é a excepção, e é obrigatória: é software de terceiros
# distribuído com o site, e a licença acompanha-o.
#
# O `--delete` do rsync **está ligado**, e é a diferença que mais conta em
# relação ao FTP: o que deixou de existir aqui desaparece de lá. Sem isso, uma
# vista apagada continuava a ser servida pelo servidor meses depois. As pastas
# acima estão protegidas pelos `--exclude`, e o rsync não apaga o que exclui.
# ---------------------------------------------------------------------------
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

SERVIDOR="casadoze"
REMOTO="domains/casadoze.admedia.pt/public_html"
ENDERECO="https://casadoze.admedia.pt"

A_SERIO=0
MIGRAR=0
while [ $# -gt 0 ]; do
  case "$1" in
    --a-serio|--a-sério) A_SERIO=1 ;;
    --migrar)            MIGRAR=1 ;;
    *) echo "Argumento que não conheço: $1" >&2; exit 1 ;;
  esac
  shift
done

# ---------------------------------------------------------------------------
# Antes de tocar no servidor.
# ---------------------------------------------------------------------------

# Trabalho por commitar é trabalho que ninguém mais tem. Publicá-lo põe no ar
# uma versão que não existe no repositório, e que ninguém consegue reproduzir.
if [ -n "$(git status --porcelain)" ]; then
  echo "A árvore tem alterações por commitar:" >&2
  git status --short >&2
  echo >&2
  echo "Commite-as antes de publicar — o que está no ar tem de existir no repositório." >&2
  exit 1
fi

if [ ! -d vendor/admedia/cms ]; then
  echo "Falta o vendor/. Corra 'composer install' antes de publicar." >&2
  exit 1
fi

# O `cms-publish` copia as folhas do backoffice do pacote para public/assets.
# Correr isto aqui e não no servidor é de propósito: as folhas vão versionadas,
# e o servidor não tem de saber que o pacote existe. Se alguma mudou, a árvore
# deixa de estar limpa — e o teste acima apanha-o.
php vendor/admedia/cms/bin/cms-publish.php >/dev/null
if [ -n "$(git status --porcelain)" ]; then
  echo "O cms-publish mexeu em ficheiros. Veja o que mudou, commite, e publique." >&2
  git status --short >&2
  exit 1
fi

REVISAO="$(git rev-parse --short HEAD)"
RAMO="$(git rev-parse --abbrev-ref HEAD)"

echo "Casa de Zé → $ENDERECO"
echo "  revisão: $REVISAO ($RAMO)"
echo "  destino: $SERVIDOR:$REMOTO"
echo

OPCOES=(
  --archive               # permissões, datas, symlinks
  --compress
  --delete                # o que deixou de existir aqui desaparece de lá
  --human-readable
  --itemize-changes
  --exclude=/.git/
  --exclude=/.github/
  --exclude=/.claude/
  --exclude=/.access
  --exclude=/docs/
  --exclude=/node_modules/
  --exclude=/storage/
  --exclude=/config/config.php
  --exclude=/public/assets/uploads/
  --exclude=/vendor/admedia/cms/.git/
  --exclude=.DS_Store
  --exclude=*.zip
  # A documentação fica no git e na máquina de desenvolvimento. A licença do
  # TinyMCE é software de terceiros e tem de acompanhar o que se distribui.
  --include=*/license.md
  --include=*/LICENSE.md
  --exclude=*.md
  --exclude=.editorconfig
  --exclude=phpunit.xml
  --exclude=phpunit.xml.dist
  --exclude=.phpcs.xml
  --exclude=phpstan.neon
)

if [ "$A_SERIO" != "1" ]; then
  OPCOES+=(--dry-run)
  echo "ENSAIO — nada é escrito. Para publicar a sério: bin/publicar.sh --a-serio"
  echo
fi

rsync "${OPCOES[@]}" ./ "$SERVIDOR:$REMOTO/"

if [ "$A_SERIO" != "1" ]; then
  echo
  echo "Fim do ensaio. As linhas acima com '*deleting' são o que seria apagado."
  exit 0
fi

# ---------------------------------------------------------------------------
# Depois de copiar.
# ---------------------------------------------------------------------------

# As pastas de estado não vão no rsync — são do servidor — mas têm de existir e
# ser escrevíveis, ou o site não guarda sessão nenhuma e não escreve registos.
ssh "$SERVIDOR" "cd ~/$REMOTO && mkdir -p storage/sessions storage/logs public/assets/uploads && chmod -R 755 storage public/assets/uploads"

if [ "$MIGRAR" = "1" ]; then
  echo
  echo "Migrações:"
  ssh "$SERVIDOR" "cd ~/$REMOTO && php bin/migrate.php"
fi

# ---------------------------------------------------------------------------
# Confirmar que não foi parar lá o que não devia.
#
# Um `--exclude` escrito ao contrário não dá erro: deixa passar, e ninguém dá
# por isso. Por isso pergunta-se ao servidor em vez de se confiar na lista.
# ---------------------------------------------------------------------------
SOBRAS=$(ssh "$SERVIDOR" "cd ~/$REMOTO && find . \( -name '*.md' ! -iname 'license.md' \) -o -name '.git' -o -name '.claude' -o -name '.access' -o -name 'phpunit.xml*' 2>/dev/null | head -20")

if [ -n "$SOBRAS" ]; then
  echo
  echo "AVISO — isto está no servidor e não devia:" >&2
  echo "$SOBRAS" >&2
  echo "Apague-o à mão, e veja o que falta aos --exclude deste guião." >&2
fi

echo
echo "Publicado. $ENDERECO está na revisão $REVISAO."
if [ "$MIGRAR" != "1" ]; then
  echo "As migrações NÃO correram. Para as correr: bin/publicar.sh --a-serio --migrar"
fi
