#!/usr/bin/env bash
#
# levantar_todo.sh — Arranque integral para presentacion / demo.
# De UN solo comando garantiza (en ~30 s):
#   1) MySQL arriba
#   2) Servidores en :8000 y :8001
#   3) Tuneles publicos Cloudflare en cada puerto
#   4) QR vigente (logs/url_principal_qr.png) + URLs impresas
#   5) (opcional) caffeinate mientras dure la demo
#
# Uso: ./scripts/levantar_todo.sh [--caffeinate]
#
# Idempotente: si ya esta arriba, no duplica procesos. No reinicia tuneles vivos
# (no cambia la URL). Solo relanza lo que haga falta.

set -u

BASE_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$BASE_DIR"

MYSQL_PASS="3l3d3zm4F"
DB="sistema_reciclaje_2026"
PUERTOS=(8000 8001)
CAFFEINATE=0
[ "${1:-}" = "--caffeinate" ] && CAFFEINATE=1

mkdir -p logs scripts

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

say() { printf '\n\033[36m== %s ==\033[0m\n' "$1"; }

# ---------- 1/5 MySQL ----------
say "1/5 MySQL"
if mysqladmin -uroot -p"$MYSQL_PASS" status >/dev/null 2>&1; then
  echo "  MySQL ya activo."
else
  echo "  arrancando MySQL..."
  brew services start mysql >/dev/null 2>&1 || mysqld_safe --user=_mysql >logs/mysql.log 2>&1 &
  for _ in $(seq 1 12); do mysqladmin -uroot -p"$MYSQL_PASS" status >/dev/null 2>&1 && break; sleep 5; done
fi
mysql -uroot -p"$MYSQL_PASS" -e "USE $DB;" >/dev/null 2>&1 \
  && echo "  base $DB accesible." || echo "  AVISO: base $DB no accesible."

# ---------- 2/5 Servidores ----------
say "2/5 Servidores Laravel"
ensure_server() {
  local port="$1"
  if lsof -nP -iTCP:"$port" -sTCP:LISTEN >/dev/null 2>&1; then
    echo "  :$port ya escucha."
  else
    echo "  arrancando php artisan serve :$port ..."
    (cd backend && nohup php -d display_errors=0 -d log_errors=1 artisan serve --host=127.0.0.1 --port="$port" >"../logs/serve_${port}.log" 2>&1 &)
    for _ in $(seq 1 10); do lsof -nP -iTCP:"$port" -sTCP:LISTEN >/dev/null 2>&1 && break; sleep 1; done
  fi
}
for p in "${PUERTOS[@]}"; do ensure_server "$p"; done

# ---------- 3/5 Tuneles ----------
say "3/5 Tuneles Cloudflare"
for p in "${PUERTOS[@]}"; do
  ESTADO="scripts/estado_tunel_${p}.txt"
  URL=""
  [ -f "$ESTADO" ] && URL="$(sed -n 's/^url=//p' "$ESTADO")"
  ETIQUETA="principal"
  [ "$p" = "8001" ] && ETIQUETA="respaldo"
  if [ -n "$URL" ] && [ "$(curl_public "$URL/login")" = "200" ]; then
    echo "  tunel :$p ya responde: $URL"
  else
    echo "  (re)creando tunel :$p ..."
    ./scripts/tunel.sh "$p" "$ETIQUETA" >/dev/null 2>&1 || { echo "  fallo en tunel :$p — log: logs/cloudflared_${p}.log"; }
  fi
  sed -i '' "s/^etiqueta=.*/etiqueta=$ETIQUETA/" "$ESTADO" 2>/dev/null || true
done

# ---------- 4/5 Estado real de cada URL ----------
say "4/5 URLs publicas"
ACTIVA=""
for p in "${PUERTOS[@]}"; do
  ESTADO="scripts/estado_tunel_${p}.txt"
  [ -f "$ESTADO" ] || continue
  ETIQ="$(sed -n 's/^etiqueta=//p' "$ESTADO")"
  URL="$(sed -n 's/^url=//p' "$ESTADO")"
  [ -n "$URL" ] || continue
  CODE="$(curl_public "$URL/login")"
  printf '  [%s] %s -> HTTP %s\n' "$ETIQ" "$URL" "${CODE:-timeout}"
  [ "${CODE:-}" = "200" ] && [ -z "$ACTIVA" ] && ACTIVA="$URL"
done

if [ -n "$ACTIVA" ]; then
  echo "  URL activa elegida: $ACTIVA"
  if command -v qrencode >/dev/null 2>&1; then
    qrencode -o logs/url_principal_qr.png -s 12 -m 2 "$ACTIVA"
    echo "  QR regenerado: logs/url_principal_qr.png ($(stat -f%z logs/url_principal_qr.png) bytes)"
  fi
  echo
  qrencode -t ANSIUTF8 -m 2 "$ACTIVA" 2>/dev/null | sed 's/^/  /'
  echo
else
  echo "  NINGUNA URL responde aun. Revise logs/cloudflared_*.log y el internet."
fi

# ---------- 5/5 caffeinate (opcional) ----------
if [ "$CAFFEINATE" = "1" ]; then
  caffeinate -dimsu >/dev/null 2>&1 &
  echo "caffeinate activo (PID $!) — la Mac no dormira. Para: kill $!  (o reiniciar)"
fi

cat <<EOF

  Cuentas demo:
    admin@reciclaje.bo / admin123456
    ciudadano@reciclaje.bo / ciudadano123
    empresa@reciclaje.bo / empresa123456
  Si algo falla en sala:  ./scripts/tunel.sh 8000   (crea tunel nuevo + QR)
  Vigia continuo:        ./scripts/check_publico.sh 20
EOF
exit 0