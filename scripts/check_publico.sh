#!/usr/bin/env bash
#
# Watchdog de tuneles: verifica cada N segundos que la URL publica responda.
# IMPORTANTE: no mata ningun proceso (conserva la URL). Si uno cae, imprime
# la URL de respaldo para mostrarla en pantalla al instante.
#
# Uso: check_publico.sh [intervalo_segundos]   (def: 20)

set -u

INTERVALO="${1:-20}"
ARCHIVOS=(scripts/estado_tunel_8000.txt scripts/estado_tunel_8001.txt)

echo "Watchdog activo cada ${INTERVALO}s. [Ctrl+C] para detener."

while true; do
  for F in "${ARCHIVOS[@]}"; do
    [ -f "$F" ] || continue
    URL="$(sed -n 's/^url=//p' "$F")"
    ETIQ="$(sed -n 's/^etiqueta=//p' "$F")"
    [ -n "$URL" ] || continue
    CODE="$(curl -s -o /dev/null -m 15 -w '%{http_code}' "$URL/login" 2>/dev/null)"
    if [ "$CODE" = "200" ]; then
      printf '[%s] %s -> OK (200)\n' "$(date '+%H:%M:%S')" "$ETIQ"
    else
      printf '[%s] ALERTA: tunel %s NO responde (code=%s)\n' "$(date '+%H:%M:%S')" "$ETIQ" "${CODE:-timeout}"
      printf '   URLs disponibles para mostrar al publico:\n'
      for G in "${ARCHIVOS[@]}"; do
        [ -f "$G" ] && printf '   %s\n' "$(sed -n 's/^url=//p' "$G")"
      done
    fi
  done
  sleep "$INTERVALO"
done