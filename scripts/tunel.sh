#!/usr/bin/env bash
#
# Levanta un túnel público de Cloudflare hacia un puerto local.
# Saliente (funciona desde NAT de hotspot), HTTPS público y URL estable
# mientras el proceso esté vivo (reconecta conservando la misma URL).
#
# Uso: tunel.sh [puerto] [etiqueta]
#   puerto  : puerto local a exponer (def: 8000)
#   etiqueta: nombre para el estado (def: principal)
#
# Escribe scripts/estado_tunel_<puerto>.txt (URL + PID) para check_publico.sh.

set -u

PUERTO="${1:-8000}"
ETIQUETA="${2:-principal}"
BIN="$(command -v cloudflared || echo /usr/local/bin/cloudflared)"
LOG="logs/cloudflared_${PUERTO}.log"
ESTADO="scripts/estado_tunel_${PUERTO}.txt"
mkdir -p logs scripts

if curl -s -o /dev/null -m 3 "http://127.0.0.1:${PUERTO}/login"; then
  echo "Puerto $PUERTO responde en local. OK."
else
  echo "AVISO: nada escucha en 127.0.0.1:${PUERTO}. ¿Servidor arriba?"
fi

[ -x "$BIN" ] || { echo "ERROR: no encuentro cloudflared ($BIN). npm/brew install?"; exit 2; }

# curl tolerante a la DNS local (evita caché negativa del Mac para dominios nuevos).
curl_public() {
  local url="$1" host ip
  host="$(printf '%s' "$url" | sed -E 's|^https?://||;s|/.*$||')"
  ip="$(dig +short "$host" A 2>/dev/null | head -1)"
  if [ -n "$ip" ]; then
    curl -s --resolve "$host:443:$ip" -m 15 -o /dev/null -w '%{http_code}' "$url" 2>/dev/null
  else
    curl -s -m 15 -o /dev/null -w '%{http_code}' "$url" 2>/dev/null
  fi
}

nohup "$BIN" tunnel --url "http://127.0.0.1:${PUERTO}" --edge-ip-version 4 --protocol http2 --no-autoupdate >"$LOG" 2>&1 &
CF_PID=$!

URL=""
for _ in $(seq 1 30); do
  URL="$(grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' "$LOG" | head -1)"
  [ -n "$URL" ] && break
  sleep 1
done

if [ -z "$URL" ]; then
  echo "ERROR: no se obtuvo URL en 30s. Revisa $LOG"
  exit 1
fi

printf 'cloudflared_pid=%s\nurl=%s\netiqueta=%s\ninicio=%s\n' \
  "$CF_PID" "$URL" "$ETIQUETA" "$(date '+%Y-%m-%d %H:%M:%S')" >"$ESTADO"

echo "Tunel '$ETIQUETA' listo -> $URL  (PID $CF_PID)"
echo -n "Verificando respuesta publica..."
for _ in $(seq 1 12); do
  CODE="$(curl_public "$URL/login")"
  [ "$CODE" = "200" ] && break
  sleep 3
done
echo " HTTP ${CODE:-timeout}"
echo "Estado guardado en: $ESTADO"
printf '%s\n' "$URL"

if command -v qrencode >/dev/null 2>&1; then
  echo; qrencode -t ANSIUTF8 -m 2 "$URL"; echo
fi