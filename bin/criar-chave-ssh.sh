#!/usr/bin/env bash
#
# Cria a chave SSH para o servidor deste site e deixa-a pronta a usar.
#
# Quatro passos, por esta ordem:
#   1. gera um par de chaves ed25519 em ~/.ssh/<nome>
#   2. escreve a entrada no ~/.ssh/config, para bastar `ssh <atalho>`
#   3. mostra a chave PÚBLICA — é essa, e só essa, que se cola no cPanel
#   4. experimenta a ligação e diz se já funciona
#
# A chave privada nunca é mostrada, nem copiada, nem enviada para lado nenhum.
# Fica num ficheiro a 0600 e é o `ssh` que lhe vai lá. Se alguma vez precisar
# de a mostrar a alguém, não precisa: é sempre a pública que se partilha.
#
# Uso:
#   bin/criar-chave-ssh.sh                                  # pergunta o que falta
#   bin/criar-chave-ssh.sh --servidor 77.37.37.247 --utilizador u707430235 --porta 65002
#   bin/criar-chave-ssh.sh --autorizar intemporaneo         # autoriza-a daqui, sem painel
#   bin/criar-chave-ssh.sh --testar                         # só experimenta a ligação
#   bin/criar-chave-ssh.sh --mostrar                        # volta a mostrar a pública
#
# O `--autorizar` é para quando já existe um atalho que entra no mesmo
# servidor. Numa conta Hostinger a chave é da conta e não do site: autorizada
# por um, vale para todos. Poupa a volta ao painel — que é onde se falha o
# segundo botão.
#
# Correr isto duas vezes não faz mal: uma chave que já exista não é
# substituída, e a entrada no config não é duplicada.
set -euo pipefail

# ---------------------------------------------------------------------------
# O QUE MUDA DE PROJECTO PARA PROJECTO. Duas linhas.
#
# O atalho é o nome por que vai tratar o servidor a partir daqui: `ssh
# intemporaneo`, `rsync ... intemporaneo:...`. O nome da chave acompanha.
# ---------------------------------------------------------------------------
ATALHO="casadoze"
NOME_DO_SITE="Casa de Zé"

# Trocável para ensaios, sem tocar no ~/.ssh verdadeiro.
SSH_DIR="${SSH_DIR:-$HOME/.ssh}"

SERVIDOR=""
UTILIZADOR=""
PORTA="22"
COM_FRASE=0
SO_TESTAR=0
SO_MOSTRAR=0
AUTORIZAR=0
VIA=""

while [ $# -gt 0 ]; do
  case "$1" in
    --servidor)    SERVIDOR="${2:-}";   if [ $# -gt 1 ]; then shift; fi ;;
    --servidor=*)  SERVIDOR="${1#*=}" ;;
    --utilizador)  UTILIZADOR="${2:-}"; if [ $# -gt 1 ]; then shift; fi ;;
    --utilizador=*) UTILIZADOR="${1#*=}" ;;
    --porta)       PORTA="${2:-22}";    if [ $# -gt 1 ]; then shift; fi ;;
    --porta=*)     PORTA="${1#*=}" ;;
    --atalho)      ATALHO="${2:-}";     if [ $# -gt 1 ]; then shift; fi ;;
    --atalho=*)    ATALHO="${1#*=}" ;;
    # Uma frase-passe é mais segura, mas obriga a escrevê-la em cada ligação
    # (ou a carregar a chave no ssh-agent). Sem ela, o rsync e o artisan
    # correm sozinhos, que é o que um guião de entrega precisa.
    --com-frase)   COM_FRASE=1 ;;
    # Autorizar a chave daqui, por uma ligação que JÁ funcione — ver
    # autorizar(), mais abaixo.
    --autorizar)   AUTORIZAR=1; VIA="${2:-}"; if [ $# -gt 1 ]; then shift; fi ;;
    --autorizar=*) AUTORIZAR=1; VIA="${1#*=}" ;;
    --testar)      SO_TESTAR=1 ;;
    --mostrar)     SO_MOSTRAR=1 ;;
    -h|--ajuda)    sed -n '2,30p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
    *) echo "Argumento que não conheço: $1" >&2; exit 1 ;;
  esac
  shift
done

CHAVE="${SSH_DIR}/${ATALHO}"
CONFIG="${SSH_DIR}/config"

# ---------------------------------------------------------------------------
# Modos curtos, que não criam nada.
# ---------------------------------------------------------------------------
experimentar() {
  echo "→ A experimentar \`ssh ${ATALHO}\`…"
  # BatchMode: se a chave não servir, falha já em vez de ficar a pedir senha.
  # -F com o config que este guião escreve. Por omissão é o mesmo ficheiro que
  # o ssh já leria; dizê-lo explicitamente é o que faz o SSH_DIR dos ensaios
  # funcionar de facto, em vez de testar silenciosamente o ~/.ssh verdadeiro.
  if ssh -F "$CONFIG" \
         -o BatchMode=yes -o ConnectTimeout=10 -o StrictHostKeyChecking=accept-new \
         "$ATALHO" 'echo "ligado a $(hostname), como $(whoami)"' 2>/dev/null; then
    echo
    echo "✓ A chave já está autorizada no servidor."
    return 0
  fi

  echo
  echo "Ainda não liga. Normalmente é uma de três:"
  echo "  · a chave pública ainda não foi colada no cPanel, ou foi colada mas"
  echo "    não foi AUTORIZADA (no cPanel é um segundo botão, depois de importar)"
  echo "  · o servidor ou o utilizador estão errados em ${CONFIG}"
  echo "  · o alojamento tem o SSH desligado, ou noutra porta"
  echo
  echo "Para ver o que o ssh diz:  ssh -v ${ATALHO}"
  return 1
}

# ---------------------------------------------------------------------------
# Autorizar a chave sem passar pelo painel.
#
# **Só funciona se já houver uma ligação que entre.** Numa conta Hostinger a
# chave é da conta e não do site: quem já tenha um atalho a funcionar para
# outro site da mesma conta pode autorizar esta por aí, e poupa a volta ao
# painel — que é onde se falha o segundo botão.
#
# Acrescenta, nunca substitui: o authorized_keys tem as chaves de todos os
# outros sites da conta, e reescrevê-lo era pôr toda a gente de fora. E não
# acrescenta duas vezes a mesma.
# ---------------------------------------------------------------------------
autorizar() {
  local via="$1"
  local publica
  publica="$(cat "${CHAVE}.pub")"

  echo "→ A autorizar a chave no servidor, por \`ssh ${via}\`…"

  if ! ssh -o BatchMode=yes -o ConnectTimeout=10 "$via" 'true' 2>/dev/null; then
    echo "  Não consigo entrar por \`ssh ${via}\`. Sem uma ligação que já" >&2
    echo "  funcione não há por onde autorizar — cole a pública no painel." >&2
    return 1
  fi

  # O `grep -qxF` compara a linha inteira e sem interpretar nada: uma chave
  # pública tem `+` e `/` lá dentro, que numa expressão regular seriam outra
  # coisa.
  ssh -o BatchMode=yes "$via" "
    set -e
    mkdir -p ~/.ssh && chmod 700 ~/.ssh
    touch ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys
    antes=\$(wc -l < ~/.ssh/authorized_keys)
    if grep -qxF '${publica}' ~/.ssh/authorized_keys; then
      echo \"  já lá estava (\${antes} chaves no ficheiro)\"
    else
      printf '%s\n' '${publica}' >> ~/.ssh/authorized_keys
      depois=\$(wc -l < ~/.ssh/authorized_keys)
      echo \"  acrescentada (\${antes} → \${depois} chaves)\"
    fi
  "
}

mostrar_publica() {
  echo
  echo "────────────────────────────────────────────────────────────────────"
  echo "A CHAVE PÚBLICA. É esta que se cola no painel do alojamento — pode"
  echo "ser vista por qualquer pessoa, não é segredo."
  echo "────────────────────────────────────────────────────────────────────"
  cat "${CHAVE}.pub"
  echo "────────────────────────────────────────────────────────────────────"
}

if [ "$SO_MOSTRAR" = "1" ]; then
  [ -f "${CHAVE}.pub" ] || { echo "Ainda não há chave em ${CHAVE}.pub." >&2; exit 1; }
  mostrar_publica
  exit 0
fi

if [ "$SO_TESTAR" = "1" ]; then
  experimentar
  exit $?
fi

if [ "$AUTORIZAR" = "1" ] && [ -f "${CHAVE}.pub" ]; then
  [ -n "$VIA" ] || { echo "O --autorizar precisa de um atalho que já entre: --autorizar outro-site" >&2; exit 1; }
  autorizar "$VIA"
  echo
  experimentar
  exit $?
fi

# ---------------------------------------------------------------------------
# O que o painel do alojamento sabe e este guião não: o servidor e o
# utilizador. São precisos para a entrada no ~/.ssh/config — e SÓ para isso.
#
# A chave não depende de nenhum dos dois, por isso nunca se trava a sua
# criação à espera deles: quem está a meio de colar a pública no painel
# precisa dela agora, e o endereço do servidor pode vir a seguir.
#
# Só se pergunta se houver alguém do outro lado para responder. Com a entrada
# redireccionada — dentro de outro guião, ou numa janela que não é um
# terminal — um `read` fica à espera de uma resposta que não vem.
# ---------------------------------------------------------------------------
if [ -t 0 ]; then
  if [ -z "$SERVIDOR" ]; then
    printf 'Servidor (o mesmo que usa no FTP, ex. s12.alojamento.pt) — Enter para saltar: '
    read -r SERVIDOR
  fi
  if [ -z "$UTILIZADOR" ]; then
    printf 'Utilizador do alojamento — Enter para saltar: '
    read -r UTILIZADOR
  fi
fi

ESCREVER_CONFIG=1
if [ -z "$SERVIDOR" ] || [ -z "$UTILIZADOR" ]; then
  ESCREVER_CONFIG=0
fi

# ---------------------------------------------------------------------------
# 1. O par de chaves.
#
# ed25519 e não RSA: é mais curta, mais rápida, e é o que qualquer servidor
# com menos de dez anos aceita. Um RSA de 2048 ainda é aceite em todo o lado
# mas já não é o que se recomenda.
# ---------------------------------------------------------------------------
mkdir -p "$SSH_DIR"
chmod 700 "$SSH_DIR"

if [ -f "$CHAVE" ]; then
  echo "→ Já existe uma chave em ${CHAVE} — fica como está."
  echo "  (para fazer uma nova, apague-a primeiro, ou use --atalho com outro nome)"
else
  echo "→ A gerar a chave em ${CHAVE}…"

  # -C é só um rótulo, para se saber de onde veio a chave quando houver cinco
  # delas na lista do cPanel daqui a um ano.
  ROTULO="${USER}@$(hostname -s) — ${NOME_DO_SITE}"

  if [ "$COM_FRASE" = "1" ]; then
    echo "  Vai pedir uma frase-passe. Terá de a escrever em cada ligação, a"
    echo "  não ser que carregue a chave no ssh-agent: ssh-add ${CHAVE}"
    ssh-keygen -t ed25519 -C "$ROTULO" -f "$CHAVE" > /dev/null
  else
    ssh-keygen -t ed25519 -C "$ROTULO" -f "$CHAVE" -N "" > /dev/null
  fi

  echo "  feito"
fi

chmod 600 "$CHAVE"
chmod 644 "${CHAVE}.pub"

# ---------------------------------------------------------------------------
# 2. A entrada no ~/.ssh/config.
#
# É isto que faz `ssh intemporaneo` bastar, em vez de ter de escrever o
# utilizador, o servidor, a porta e o caminho da chave de cada vez — e é
# também o que faz o rsync e os guiões de entrega funcionarem sem alterações.
#
# Não se reescreve uma entrada que já exista: pode ter sido afinada à mão.
# ---------------------------------------------------------------------------
touch "$CONFIG"
chmod 600 "$CONFIG"

if [ "$ESCREVER_CONFIG" = "0" ]; then
  echo "→ ${CONFIG}: por escrever — falta o servidor ou o utilizador."
  echo "  A chave está feita e já pode ser colada no painel. Quando souber os"
  echo "  dois valores, corra outra vez com eles e a entrada é escrita então:"
  echo "    bin/criar-chave-ssh.sh --servidor 'SERVIDOR' --utilizador 'CONTA'"
elif grep -qE "^[[:space:]]*Host[[:space:]]+${ATALHO}([[:space:]]|$)" "$CONFIG"; then
  echo "→ ${CONFIG} já tem uma entrada 'Host ${ATALHO}' — fica como está."
  echo "  Confirme que aponta para ${UTILIZADOR}@${SERVIDOR}:${PORTA}."
else
  echo "→ A escrever a entrada 'Host ${ATALHO}' em ${CONFIG}…"
  {
    echo ""
    echo "# ${NOME_DO_SITE} — criado por bin/criar-chave-ssh.sh a $(date '+%Y-%m-%d')"
    echo "Host ${ATALHO}"
    echo "    HostName ${SERVIDOR}"
    echo "    User ${UTILIZADOR}"
    echo "    Port ${PORTA}"
    echo "    IdentityFile ${CHAVE}"
    # Só esta chave, e mais nenhuma. Sem isto o ssh oferece todas as chaves
    # que tiver, e um servidor que corte ao fim de seis tentativas recusa a
    # ligação antes de chegar à certa.
    echo "    IdentitiesOnly yes"
  } >> "$CONFIG"
  echo "  feito"
fi

# ---------------------------------------------------------------------------
# 3. A chave pública, e o que fazer com ela.
# ---------------------------------------------------------------------------
mostrar_publica

cat <<FIM

Onde colar: no painel do alojamento, em SSH / Chaves SSH. Pede dois campos:

  Nome            ${ATALHO}   (é só uma etiqueta, para a distinguir na lista)
  Chave pública   a linha inteira acima, de 'ssh-ed25519' até ao fim

Três coisas que correm mal na colagem:

  · a linha tem de ficar INTEIRA, numa só linha — um editor que a parta a
    meio produz uma chave que o servidor recusa sem dizer porquê
  · tem de levar o 'ssh-ed25519 ' do início; sem esse prefixo não é válida
  · nunca o ficheiro sem .pub, que é o privado e não sai desta máquina

Para a copiar directamente:  pbcopy < ${CHAVE}.pub

Se o painel for cPanel há um segundo passo que toda a gente falha: depois de
importar, Manage → Authorize. Uma chave importada mas não autorizada não
entra. Noutros painéis basta gravar.

Quando estiver feito:

  bin/criar-chave-ssh.sh --testar

FIM

# ---------------------------------------------------------------------------
# 4. Experimentar já, para o caso de a chave ter sido autorizada entretanto.
# ---------------------------------------------------------------------------
if [ "$ESCREVER_CONFIG" = "1" ]; then
  experimentar || true
fi
