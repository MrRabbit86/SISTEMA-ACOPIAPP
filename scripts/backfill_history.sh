#!/usr/bin/env bash
#
# backfill_history.sh — Genera commits backdated en julio 2026 (aprox. 40-45),
# 1-3 por dia (mayormente 2) en dias activos con huecos aleatorios, horarios
# aleatorios entre 08:00 y 20:30. Cada commit agrega una linea a CHANGELOG.md.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

CHANGELOG="CHANGELOG.md"
YEAR=2026
MONTH=07

RANDOM=$RANDOM

# --- Pool de mensajes realistas ---
msgs=(
  "Actualización de documentación"
  "Ajustes en scripts de automatización"
  "Refinamiento de detalles del backend"
  "Optimización de procesos internos"
  "Actualización de configuración"
  "Mejoras menores en la interfaz"
  "Corrección de detalles del frontend"
  "Ajustes en la lógica de reciclaje"
  "Actualización de recursos del proyecto"
  "Avance en módulos del sistema"
)

# --- Dias activos de julio (22 de 31, con huecos aleatorios tipo dia por medio) ---
days=(1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31)
for i in $(seq 30 -1 0); do
  j=$((RANDOM % (i + 1)))
  tmp=${days[i]}
  days[i]=${days[j]}
  days[j]=$tmp
done
active_days=()
while IFS= read -r d; do active_days+=("$d"); done < <(printf '%s\n' "${days[@]:0:22}" | sort -n)

# --- Construir timestamps "YYYY-MM-DD HH:MM" en orden ascendente ---
declare -a stamps=()
for d in "${active_days[@]}"; do
  n=$((1 + RANDOM % 3)) # 1..3 commits por dia activo
  declare -a slots=()
  while [ ${#slots[@]} -lt "$n" ]; do
    m=$((480 + RANDOM % 751)) # 08:00 .. 20:30 en "minuto del dia"
    dup=0
    for s in "${slots[@]+"${slots[@]}"}"; do
      if [ "$s" -eq "$m" ]; then dup=1; break; fi
    done
    if [ "$dup" -eq 0 ]; then slots+=("$m"); fi
  done
  sorted_slots=()
  while IFS= read -r s; do sorted_slots+=("$s"); done < <(printf '%s\n' "${slots[@]}" | sort -n)
  for m in "${sorted_slots[@]}"; do
    stamps+=("$(printf '%04d-%02d-%02d %02d:%02d' "$YEAR" "$MONTH" "$d" $((m / 60)) $((m % 60)))")
  done
done

# --- Inicializar CHANGELOG si no existe ---
if [ ! -s "$CHANGELOG" ]; then
  printf '# Changelog\n\n## Julio 2026\n' > "$CHANGELOG"
fi

# --- Crear los commits backdated ---
count=0
for st in "${stamps[@]}"; do
  msg="${msgs[$((RANDOM % ${#msgs[@]}))]}"
  printf '%s\n' "- ${st} — ${msg}" >> "$CHANGELOG"
  git add "$CHANGELOG"
  GIT_AUTHOR_DATE="$st" GIT_COMMITTER_DATE="$st" \
    git commit -q -m "$msg"
  printf 'commit %-8s %s  %s\n' "$(git rev-parse --short HEAD)" "$st" "$msg"
  count=$((count + 1))
done

echo "--------------------------------------------"
echo "Creados $count commits backdated en julio 2026."
git log --oneline | head -10