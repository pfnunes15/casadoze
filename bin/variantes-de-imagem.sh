#!/usr/bin/env bash
#
# Turn a source photograph into the variants the site actually serves.
#
# Browsers get WebP and AVIF only — never the original. A phone photograph
# carries EXIF with GPS coordinates in it, and every encoder below drops the
# metadata rather than copying it across.
#
# Usage:
#   bin/variantes-de-imagem.sh <source> <name> [width ...]
#
#   bin/variantes-de-imagem.sh ~/Downloads/foto.jpeg abertura 640 960 1254
#
# Writes public/assets/img/<name>-<width>.webp and .avif. The source itself is
# not copied into the repository: keep it wherever the originals live, so a
# variant can be regenerated without asking the client for the file again.
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

ORIGEM="${1:?falta o ficheiro de origem}"
NOME="${2:?falta o nome}"
shift 2
LARGURAS=("$@")
[ ${#LARGURAS[@]} -gt 0 ] || LARGURAS=(640 960 1280)

[ -f "$ORIGEM" ] || { echo "Não encontro $ORIGEM" >&2; exit 1; }
for t in cwebp avifenc sips; do
  command -v "$t" >/dev/null || { echo "Falta o $t. brew install webp libavif" >&2; exit 1; }
done

DESTINO="public/assets/img"
mkdir -p "$DESTINO"

ORIGINAL_W=$(sips -g pixelWidth "$ORIGEM" | awk '/pixelWidth/{print $2}')
echo "$ORIGEM — ${ORIGINAL_W}px de largura"

TEMP="$(mktemp -d)"
trap 'rm -rf "$TEMP"' EXIT

for W in "${LARGURAS[@]}"; do
  # Pedir uma variante maior do que o original é esticar pixéis: sai borrado e
  # mais pesado do que o que se queria poupar.
  if [ "$W" -gt "$ORIGINAL_W" ]; then
    echo "  ${W}px — saltado, o original só tem ${ORIGINAL_W}px"
    continue
  fi

  # sips reescreve a cópia, nunca o original.
  cp "$ORIGEM" "$TEMP/base.jpg"
  sips --resampleWidth "$W" "$TEMP/base.jpg" --out "$TEMP/${W}.jpg" >/dev/null

  # -metadata none: fora o EXIF, com as coordenadas que uma fotografia de
  # telemóvel traz lá dentro.
  cwebp -quiet -q 82 -metadata none "$TEMP/${W}.jpg" -o "$DESTINO/${NOME}-${W}.webp"
  avifenc -q 60 --speed 4 "$TEMP/${W}.jpg" "$DESTINO/${NOME}-${W}.avif" >/dev/null

  printf "  %-6s webp %-7s avif %s\n" "${W}px" \
    "$(du -h "$DESTINO/${NOME}-${W}.webp" | cut -f1)" \
    "$(du -h "$DESTINO/${NOME}-${W}.avif" | cut -f1)"
done

echo
echo "Feito. O original não foi copiado para o repositório — guarde-o onde estão os outros."
