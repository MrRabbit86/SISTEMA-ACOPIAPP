#!/usr/bin/env bash
#
# Verificación integral del sistema — todos los usuarios/roles (Sprint 6).
# Corre contra MySQL real vía API (tokens Bearer) y panel web (cookies + CSRF).
# Imprime PASS/FAIL por caso y un resumen final.
#
# Requisitos: servidor en $BASE y cliente mysql (root/3l3d3zm4F).
# No modifica las cuentas demo; crea usuarios efímeros *@probe.bo y los elimina al final.

set -u

BASE="${BASE:-http://localhost:8000}"
MYSQL=(-uroot -p3l3d3zm4F)
DB=sistema_reciclaje_2026
TMP="$(mktemp -d)"
PASS=0
FAIL=0

say() { printf '\n\033[36m==== %s ====\033[0m\n' "$1"; }
ok() { PASS=$((PASS+1)); printf '  \033[32mPASS\033[0m  %s\n' "$1"; }
bad() { FAIL=$((FAIL+1)); printf '  \033[31mFAIL\033[0m  %s\n        esperado: %s\n        obtenido: %s\n' "$1" "$2" "$(printf '%s' "$3" | head -c 160)"; }
check() { # desc esperado obtenido
  if [ "$(printf '%s' "$2" | head -c 80)" = "$(printf '%s' "$3" | head -c 80)" ]; then ok "$1"; else bad "$1" "$2" "$3"; fi
}
contains() { # desc substring haystack
  if printf '%s' "$3" | grep -q -- "$2"; then ok "$1"; else bad "$1" "(contiene: $2)" "$(printf '%s' "$3" | head -c 120)"; fi
}

json_contains() { # desc substring haystack_json (decodifica unicode \u)
  local hay
  hay="$(printf '%s' "$3" | python3 -c 'import sys,json;print(json.dumps(json.load(sys.stdin),ensure_ascii=False))' 2>/dev/null)"
  contains "$1" "$2" "$hay"
}

api_register() { # name email pass rol extra_json
  curl -s -X POST "$BASE/api/auth/register" -H 'Accept: application/json' -H 'Content-Type: application/json' \
    -d "{\"name\":\"$1\",\"email\":\"$2\",\"password\":\"$3\",\"rol\":\"$4\"$( [ -n "${5:-}" ] && printf ',%s' "$5" )}"
}
api_login() { # email pass  -> STATUS actual (deja body en $TMP/login.json)
  curl -s -o "$TMP/login.json" -w '%{http_code}' -X POST "$BASE/api/auth/login" \
    -H 'Accept: application/json' -H 'Content-Type: application/json' -d "{\"email\":\"$1\",\"password\":\"$2\"}"
}
token_of() { python3 -c 'import sys,json;print(json.load(sys.stdin).get("token",""))' <<<"$1"; }

web_login() { # jar email pass -> http code POST /login
  curl -s -c "$1" -o "$TMP/l.html" "$BASE/login"
  local csrf
  csrf="$(grep -oE 'name="_token" value="[^"]+"' "$TMP/l.html" | head -1 | sed 's/.*value="//;s/"//')"
  curl -s -b "$1" -c "$1" -X POST "$BASE/login" -d "_token=$csrf&email=$2&password=$3" -o /dev/null -w "%{http_code}"
}
web_page() { # jar url -> deja HTML en $TMP/p.html
  curl -s -b "$1" -o "$TMP/p.html" "$2"
}
web_token() { # -> csrf desde meta de $TMP/p.html
  grep -oE 'name="csrf-token" content="[^"]+"' "$TMP/p.html" | sed 's/.*content="//;s/"//' | head -1
}
web_post() { # jar token_url post_url data
  web_page "$1" "$2"
  local csrf; csrf="$(web_token)"
  curl -s -b "$1" -c "$1" -X POST "$3" -d "_token=$csrf&$4" -o /dev/null -w "%{http_code}"
}
web_del() { # jar token_url del_url
  web_page "$1" "$2"
  local csrf; csrf="$(web_token)"
  curl -s -b "$1" -o /dev/null -w "%{http_code}" -X POST "$3" -d "_token=$csrf&_method=DELETE"
}

cleanup() {
  local sql="
DELETE c FROM comprobantes c JOIN transacciones t ON t.id=c.transaccion_id JOIN users u ON u.id=t.empresa_id WHERE u.email LIKE '%@probe.bo';
DELETE t FROM transacciones t JOIN users u ON u.id=t.empresa_id WHERE u.email LIKE '%@probe.bo';
DELETE o FROM ofertas o JOIN ciudadanos c ON c.id=o.ciudadano_id JOIN users u ON u.id=c.usuario_id WHERE u.email LIKE '%@probe.bo';
DELETE FROM ciudadanos WHERE usuario_id IN (SELECT id FROM users WHERE email LIKE '%@probe.bo');
DELETE FROM empresas_acopiadoras WHERE usuario_id IN (SELECT id FROM users WHERE email LIKE '%@probe.bo');
DELETE FROM users WHERE email LIKE '%@probe.bo';
DELETE FROM categorias_material WHERE nombre='Cobre';
"
  mysql "${MYSQL[@]}" --force "$DB" -e "$sql" >"$TMP/cleanup.log" 2>&1
  local log
  log="$(grep -v "Using a password on the command line" "$TMP/cleanup.log" || true)"
  if [ -n "$log" ]; then
    printf '\n\033[33m[limpieza] MySQL: %s\033[0m\n' "$log"
  fi
  rm -rf "$TMP"
}

trap cleanup EXIT

# ---------- arranque ----------
if ! curl -s -o /dev/null "$BASE/login"; then
  printf 'El servidor no responde en %s. Ejecute: php artisan serve --port=8000\n' "$BASE"
  exit 1
fi

# ==========================================================
say "1. REGISTRO DE CUENTAS NUEVAS (ciudadanos ACTIVOS, empresas PENDIENTE)"
# ==========================================================
R=$(api_register "Probe Ciudadana" "probe_ciudadano@probe.bo" "probe1234" "ciudadano" '"ci":"7800101","zona":"Calacala"')
contains "Registro ciudadano -> aprobado al instante (API)" '"estado":"aprobado"' "$R"
json_contains "Mensaje de registro: cuenta activa" "Ya puede iniciar sesión" "$R"
C_UID=$(python3 -c 'import sys,json;print(json.load(sys.stdin)["usuario"]["id"])' <<<"$R")

L=$(api_login "probe_ciudadano@probe.bo" "probe1234")
check "Ciudadano entra de inmediato (API 200)" "200" "$L"

R=$(api_register "Probe Empresa" "probe_empresa@probe.bo" "probe1234" "empresa" '"nit":"7770001","razon_social":"Probe Reciclaje SRL"')
contains "Registro empresa -> pendiente (API)" '"estado":"pendiente"' "$R"
E_UID=$(python3 -c 'import sys,json;print(json.load(sys.stdin)["usuario"]["id"])' <<<"$R")

L=$(api_login "probe_empresa@probe.bo" "probe1234")
check "Login empresa pendiente bloqueado (API 403)" "403" "$L"
contains "Mensaje pendiente de aprobación" "pendiente" "$(cat "$TMP/login.json")"

R=$(api_register "Probe Emp2" "probe_pend@probe.bo" "probe1234" "empresa" '"nit":"7770003","razon_social":"Probe Emp2 SRL"')
P2_UID=$(python3 -c 'import sys,json;print(json.load(sys.stdin)["usuario"]["id"])' <<<"$R")

api_register "Probe Rechaz" "probe_rechaz@probe.bo" "probe1234" "empresa" '"nit":"7770002","razon_social":"Probe Rechaz SRL"' >/dev/null

# Registro de ciudadano desde el PANEL WEB (pestaña "Registrarse" del login)
curl -s -c "$TMP/webreg.jar" -o "$TMP/lw.html" "$BASE/login"
WRCSRF=$(grep -oE 'name="_token" value="[^"]+"' "$TMP/lw.html" | head -1 | sed 's/.*value="//;s/"//')
WRCODE=$(curl -s -b "$TMP/webreg.jar" -o /dev/null -w '%{http_code}' -X POST "$BASE/login/registrar" \
  -d "_token=$WRCSRF&tipo=ciudadano&name=Probe Web&email=probe_web@probe.bo&password=probe1234&password_confirmation=probe1234&ci=7800103&zona=Muyurina")
check "Registro ciudadano por panel web -> 302" "302" "$WRCODE"
WR_ST=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT estado FROM users WHERE email='probe_web@probe.bo';" 2>/dev/null)
check "Ciudadano registrado por web queda activo (BD)" "aprobado" "$WR_ST"

# Registro de EMPRESA desde el panel web (mismo formulario, tipo=empresa)
curl -s -c "$TMP/webemp.jar" -o "$TMP/le.html" "$BASE/login"
WECS=$(grep -oE 'name="_token" value="[^"]+"' "$TMP/le.html" | head -1 | sed 's/.*value="//;s/"//')
WECODE=$(curl -s -b "$TMP/webemp.jar" -o /dev/null -w '%{http_code}' -X POST "$BASE/login/registrar" \
  -d "_token=$WECS&tipo=empresa&name=Probe Web Empresa&email=probe_webemp@probe.bo&password=probe1234&password_confirmation=probe1234&nit=7779001&razon_social=Probe Web Empresa SRL")
check "Registro empresa por panel web -> 302 (vuelve al login)" "302" "$WECODE"
WEMP_ST=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT estado FROM users WHERE email='probe_webemp@probe.bo';" 2>/dev/null)
check "Empresa registrada por web queda PENDIENTE (BD)" "pendiente" "$WEMP_ST"
WEMP_NIT=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT nit FROM empresas_acopiadoras e JOIN users u ON u.id=e.usuario_id WHERE u.email='probe_webemp@probe.bo';" 2>/dev/null)
check "Perfil empresa web creado con NIT (BD)" "7779001" "$WEMP_NIT"

# ==========================================================
say "2. ADMIN — APROBACIÓN / RECHAZO (API) Y PANEL"
# ==========================================================
curl -s -X POST "$BASE/api/auth/login" -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@reciclaje.bo","password":"admin123456"}' -o "$TMP/al.json"
AT=$(token_of "$(cat "$TMP/al.json")")

R=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$BASE/api/admin/usuarios/$E_UID/aprobar" -H "Authorization: Bearer $AT")
check "Admin aprueba empresa (API) -> 200" "200" "$R"
R=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$BASE/api/admin/usuarios/$C_UID/aprobar" -H "Authorization: Bearer $AT")
check "Aprobar ciudadano ya activo -> 422" "422" "$R"

RH_UID=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT id FROM users WHERE email='probe_rechaz@probe.bo';" 2>/dev/null)
curl -s -X POST "$BASE/api/admin/usuarios/$RH_UID/rechazar" -H "Authorization: Bearer $AT" >/dev/null
L=$(api_login "probe_rechaz@probe.bo" "probe1234")
check "Login cuenta RECHAZADA -> 403" "403" "$L"
contains "Mensaje de rechazo" "rechazada" "$(cat "$TMP/login.json")"

JW=$(web_login "$TMP/adm.jar" "admin@reciclaje.bo" "admin123456")
check "Login web admin -> 302" "302" "$JW"
web_page "$TMP/adm.jar" "$BASE/admin/usuarios"
contains "Panel admin lista usuarios" "Gestión de usuarios" "$(cat "$TMP/p.html")"

RC=$(web_post "$TMP/adm.jar" "$BASE/admin/usuarios" "$BASE/admin/usuarios/$P2_UID/aprobar" "")
check "Aprobación de empresa por panel web -> 302" "302" "$RC"
P2_ST=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT estado FROM users WHERE id=$P2_UID;" 2>/dev/null)
check "probe_pend (empresa) aprobada via web (BD)" "aprobado" "$P2_ST"
RC=$(web_post "$TMP/adm.jar" "$BASE/admin/usuarios" "$BASE/admin/usuarios/$RH_UID/rechazar" "")
check "Rechazar cuenta ya no pendiente (web) -> 422" "422" "$RC"

# ==========================================================
say "3. CATEGORÍAS (ADMIN, PANEL WEB)"
# ==========================================================
RC=$(web_post "$TMP/adm.jar" "$BASE/admin/categorias" "$BASE/admin/categorias" "nombre=Cobre&precio_referencia_kg=9.5")
check "Crear categoría Cobre -> 302" "302" "$RC"
web_page "$TMP/adm.jar" "$BASE/admin/categorias"
contains "Categoría Cobre visible en el panel" "Cobre" "$(cat "$TMP/p.html")"
COBRE_ID=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT id FROM categorias_material WHERE nombre='Cobre';" 2>/dev/null)
RC=$(web_del "$TMP/adm.jar" "$BASE/admin/categorias" "$BASE/admin/categorias/$COBRE_ID")
check "Eliminar categoría Cobre -> 302" "302" "$RC"
web_page "$TMP/adm.jar" "$BASE/admin/categorias"
if grep -q "Cobre" "$TMP/p.html"; then bad "Cobre eliminado del panel" "(ausencia)" "aún visible"; else ok "Cobre eliminado del panel"; fi
RC=$(web_del "$TMP/adm.jar" "$BASE/admin/categorias" "$BASE/admin/categorias/1")
check "Eliminar categoría con ofertas (Plástico PET) -> 422" "422" "$RC"

# ==========================================================
say "4. CIUDADANO (nuevo, aprobado) — OFERTAS API + WEB" 
# ==========================================================
l=$(api_login "probe_ciudadano@probe.bo" "probe1234")
CT=$(token_of "$(cat "$TMP/login.json")")

R=$(curl -s -X POST "$BASE/api/ofertas" -H 'Accept: application/json' -H 'Content-Type: application/json' -H "Authorization: Bearer $CT" \
  -d '{"categoria_id":1,"cantidad_estimada_kg":30,"latitud":-17.3932,"longitud":-66.1561}')
contains "Publicar oferta GPS válido -> pendiente" '"estado":"pendiente"' "$R"
OPA=$(echo "$R" | python3 -c 'import sys,json;print(json.load(sys.stdin)["data"]["id"])')

R=$(curl -s -X POST "$BASE/api/ofertas" -H 'Accept: application/json' -H 'Content-Type: application/json' -H "Authorization: Bearer $CT" \
  -d '{"categoria_id":3,"cantidad_estimada_kg":12,"latitud":-17.2800,"longitud":-66.1200}')
OPW=$(echo "$R" | python3 -c 'import sys,json;print(json.load(sys.stdin)["data"]["id"])')

R=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$BASE/api/ofertas" -H 'Accept: application/json' -H 'Content-Type: application/json' -H "Authorization: Bearer $CT" \
  -d '{"categoria_id":1,"cantidad_estimada_kg":5,"latitud":-17.3932,"longitud":-66.1561}')
check "Oferta duplicada (misma categoría 24h) -> 422" "422" "$R"

R=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$BASE/api/ofertas" -H 'Accept: application/json' -H 'Content-Type: application/json' -H "Authorization: Bearer $CT" \
  -d '{"categoria_id":2,"cantidad_estimada_kg":10,"latitud":-18.0000,"longitud":-66.1561}')
check "GPS fuera de Zona Sur -> 422" "422" "$R"

L=$(api_login "ciudadano@reciclaje.bo" "ciudadano123")
DT=$(token_of "$(cat "$TMP/login.json")")
R=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$BASE/api/ofertas/$OPA/cancelar" -H "Authorization: Bearer $DT")
check "Cancelar oferta ajena -> 403" "403" "$R"

R=$(curl -s -X POST "$BASE/api/ofertas/$OPW/cancelar" -H "Authorization: Bearer $CT")
contains "Ciudadano cancela su oferta -> cancelada" '"estado":"cancelada"' "$R"

R=$(curl -s -X POST "$BASE/api/ofertas" -H 'Accept: application/json' -H 'Content-Type: application/json' -H "Authorization: Bearer $CT" \
  -d '{"categoria_id":4,"cantidad_estimada_kg":8,"latitud":-17.3800,"longitud":-66.1400}')
OPC=$(echo "$R" | python3 -c 'import sys,json;print(json.load(sys.stdin)["data"]["id"])')

CW=$(web_login "$TMP/ciu.jar" "probe_ciudadano@probe.bo" "probe1234")
check "Login web ciudadano (probe) -> 302" "302" "$CW"
web_page "$TMP/ciu.jar" "$BASE/mis-ofertas"
contains "Vista Mis ofertas (web)" "Mis ofertas" "$(cat "$TMP/p.html")"
RC=$(web_post "$TMP/ciu.jar" "$BASE/mis-ofertas" "$BASE/mis-ofertas/$OPC/cancelar" "")
check "Cancelar oferta propia (web) -> 302" "302" "$RC"
R=$(curl -s "$BASE/api/ofertas/$OPC" -H "Authorization: Bearer $CT")
contains "Oferta cancelada por web" '"estado":"cancelada"' "$R"

# Publicación desde el PANEL WEB con descripción y modalidad de entrega
RC=$(web_post "$TMP/ciu.jar" "$BASE/mis-ofertas" "$BASE/mis-ofertas" "categoria_id=2&cantidad_estimada_kg=18&descripcion=Botellas+de+plastico+limpias&modalidad_entrega=recojo_domicilio&latitud=-17.3700&longitud=-66.1400")
check "Ciudadano publica por panel web -> 302" "302" "$RC"
OPW3=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT o.id FROM ofertas o JOIN ciudadanos c ON c.id=o.ciudadano_id JOIN users u ON u.id=c.usuario_id WHERE u.email='probe_ciudadano@probe.bo' ORDER BY o.id DESC LIMIT 1;" 2>/dev/null)
DSC=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT descripcion FROM ofertas WHERE id=$OPW3;" 2>/dev/null)
check "Descripción breve guardada (web)" "Botellas de plastico limpias" "$DSC"
MDL=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT modalidad_entrega FROM ofertas WHERE id=$OPW3;" 2>/dev/null)
check "Modalidad recojo_domicilio guardada (web)" "recojo_domicilio" "$MDL"

for ruta in "ofertas" "transacciones" "reportes" "comprobantes/1"; do
  CODE=$(curl -s -b "$TMP/ciu.jar" -o /dev/null -w '%{http_code}' "$BASE/$ruta")
  check "Ciudadano NO accede a /$ruta -> 403" "403" "$CODE"
done

# ==========================================================
say "5. EMPRESA (nueva, aprobada) — COMPRAS, COMPROBANTES, REPORTES"
# ==========================================================
L=$(api_login "probe_empresa@probe.bo" "probe1234")
ET=$(token_of "$(cat "$TMP/login.json")")

R=$(curl -s -X POST "$BASE/api/transacciones" -H 'Accept: application/json' -H 'Content-Type: application/json' -H "Authorization: Bearer $ET" \
  -d "{\"oferta_id\":$OPA,\"peso_real_kg\":30}")
contains "Empresa registra compra -> completada" '"estado":"completada"' "$R"
v=$(echo "$R" | python3 -c 'import sys,json;print("%.1f"%float(json.load(sys.stdin)["data"]["monto_total"]))')
check "Monto automático = 30×2.50 (referencia Plástico)" "75.0" "$v"
TID=$(echo "$R" | python3 -c 'import sys,json;print(json.load(sys.stdin)["data"]["id"])')

R=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$BASE/api/transacciones" -H 'Accept: application/json' -H 'Content-Type: application/json' -H "Authorization: Bearer $ET" \
  -d "{\"oferta_id\":$OPA,\"peso_real_kg\":5}")
check "Comprar oferta ya completada -> 422" "422" "$R"

R=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$BASE/api/ofertas" -H 'Accept: application/json' -H 'Content-Type: application/json' -H "Authorization: Bearer $ET" \
  -d '{"categoria_id":1,"cantidad_estimada_kg":5,"latitud":-17.3,"longitud":-66.1}')
check "Empresa NO publica oferta -> 403" "403" "$R"

COUNT=$(curl -s "$BASE/api/transacciones" -H "Authorization: Bearer $ET" \
  | python3 -c 'import sys,json;print(len(json.load(sys.stdin)["data"]))')
check "Empresa solo ve sus transacciones (1)" "1" "$COUNT"

R=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/api/transacciones/1" -H "Authorization: Bearer $ET")
check "No ve transacción de otra empresa -> 403" "403" "$R"

APIPDF() { # token transaccion outdir
  curl -s "$BASE/api/transacciones/$2/comprobante" -H "Authorization: Bearer $1" -D "$TMP/c.h" -o "$3"
}
APIPDF "$ET" "$TID" "$TMP/cmp.pdf"
contains "Comprobante: Content-Type PDF" "application/pdf" "$(grep -i '^content-type' "$TMP/c.h")"
head -c 5 "$TMP/cmp.pdf" | grep -q '%PDF' && ok "Comprobante es PDF real" || bad "Comprobante es PDF" "%PDF" "$(head -c 20 "$TMP/cmp.pdf")"
NUM1=$(grep -i 'content-disposition' "$TMP/c.h" | grep -oE 'CMP-[0-9]+-[0-9]+' | head -1)
contains "Número correlativo CMP-$(date +%Y)-####" "CMP-$(date +%Y)" "$NUM1"
APIPDF "$ET" "$TID" "$TMP/cmp2.pdf"
NUM2=$(grep -i 'content-disposition' "$TMP/c.h" | grep -oE 'CMP-[0-9]+-[0-9]+' | head -1)
check "Segunda descarga reutiliza mismo nº (idempotente)" "$NUM1" "$NUM2"
COMP_COUNT=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT COUNT(*) FROM comprobantes WHERE transaccion_id=$TID;" 2>/dev/null)
check "1 solo comprobante en BD por transacción" "1" "$COMP_COUNT"

R=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/api/transacciones/1/comprobante" -H "Authorization: Bearer $ET")
check "No descarga comprobante ajeno -> 403" "403" "$R"

RR=$(curl -s "$BASE/api/reportes" -H "Authorization: Bearer $ET")
check "Reporte empresa: solo sus transacciones (1)" "1" "$(echo "$RR" | python3 -c 'import sys,json;print(json.load(sys.stdin)["transacciones"]["total"])')"
check "Reporte empresa: monto 75" "75.0" "$(echo "$RR" | python3 -c 'import sys,json;print("%.1f"%float(json.load(sys.stdin)["transacciones"]["monto_total"]))')"
json_contains "Reporte incluye Plástico PET" "Plástico PET" "$RR"
curl -s "$BASE/api/reportes/pdf" -H "Authorization: Bearer $ET" -o "$TMP/rep_api.pdf"
head -c 5 "$TMP/rep_api.pdf" | grep -q '%PDF' && ok "Reporte API descarga PDF real" || bad "Reporte API PDF" "%PDF" "$(head -c 20 "$TMP/rep_api.pdf")"

# ==========================================================
say "6. FLUJO COMPLETO EN PANEL WEB (PUBLICAR -> COMPRAR -> COMPROBANTE -> REPORTE)"
# ==========================================================
R=$(curl -s -X POST "$BASE/api/ofertas" -H 'Accept: application/json' -H 'Content-Type: application/json' -H "Authorization: Bearer $CT" \
  -d '{"categoria_id":5,"cantidad_estimada_kg":15,"latitud":-17.4000,"longitud":-66.1600}')
OPW2=$(echo "$R" | python3 -c 'import sys,json;print(json.load(sys.stdin)["data"]["id"])')

EW=$(web_login "$TMP/emp.jar" "probe_empresa@probe.bo" "probe1234")
check "Login web empresa probe -> 302" "302" "$EW"
for ruta in "ofertas" "ofertas/listado" "ofertas/$OPW2"; do
  CODE=$(curl -s -b "$TMP/emp.jar" -o /dev/null -w '%{http_code}' "$BASE/$ruta")
  check "Empresa accede a /$ruta (web) -> 200" "200" "$CODE"
done
web_page "$TMP/emp.jar" "$BASE/ofertas"
contains "Mapa Leaflet carga en /ofertas" "leaflet" "$(cat "$TMP/p.html")"

RC=$(web_post "$TMP/emp.jar" "$BASE/ofertas/$OPW2" "$BASE/ofertas/$OPW2/transaccion" "peso_real_kg=15")
check "Empresa compra por panel web -> 302" "302" "$RC"
TIDW=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT id FROM transacciones WHERE oferta_id=$OPW2;" 2>/dev/null)

web_page "$TMP/emp.jar" "$BASE/transacciones"
contains "Panel transacciones muestra la compra" "Latas de aluminio" "$(cat "$TMP/p.html")"
curl -s -b "$TMP/emp.jar" "$BASE/comprobantes/$TIDW" -o "$TMP/cmpw.pdf"
head -c 5 "$TMP/cmpw.pdf" | grep -q '%PDF' && ok "Comprobante web descargable (PDF)" || bad "Comprobante web PDF" "%PDF" "$(head -c 20 "$TMP/cmpw.pdf")"

web_page "$TMP/emp.jar" "$BASE/reportes"
contains "Panel reportes: montos visibles" "Monto total" "$(cat "$TMP/p.html")"
contains "Reporte incluye Latas de aluminio" "Latas de aluminio" "$(cat "$TMP/p.html")"
curl -s -b "$TMP/emp.jar" "$BASE/reportes/pdf" -o "$TMP/repw.pdf"
head -c 5 "$TMP/repw.pdf" | grep -q '%PDF' && ok "Reporte web exporta PDF" || bad "Reporte web PDF" "%PDF" "$(head -c 20 "$TMP/repw.pdf")"

# Compra de la oferta publicada por el ciudadano desde el panel web (OPW3)
RC=$(web_post "$TMP/emp.jar" "$BASE/ofertas/$OPW3" "$BASE/ofertas/$OPW3/transaccion" "peso_real_kg=18")
check "Empresa compra oferta publicada por web -> 302" "302" "$RC"
TID3=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT id FROM transacciones WHERE oferta_id=$OPW3;" 2>/dev/null)
PT3=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT puntos_otorgados FROM transacciones WHERE id=$TID3;" 2>/dev/null)
check "puntos_otorgados en la transacción (180)" "180" "$PT3"
PTS=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT puntos FROM ciudadanos c JOIN users u ON u.id=c.usuario_id WHERE u.email='probe_ciudadano@probe.bo';" 2>/dev/null)
check "Puntos verdes del ciudadano = 630 (300+150+180)" "630" "$PTS"
curl -s -b "$TMP/ciu.jar" "$BASE/comprobantes/$TID3" -o "$TMP/cmpc.pdf"
head -c 5 "$TMP/cmpc.pdf" | grep -q '%PDF' && ok "Ciudadano (dueño) descarga comprobante de su venta" || bad "Comprobante ciudadano" "%PDF" "$(head -c 20 "$TMP/cmpc.pdf")"

# ==========================================================
say "7. API MÓVIL (CIUDADANO) — mis ofertas / ventas / comprobante del dueño"
# ==========================================================
CIT_ID=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT c.id FROM ciudadanos c JOIN users u ON u.id=c.usuario_id WHERE u.email='probe_ciudadano@probe.bo';" 2>/dev/null)
MIS_COUNT=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT COUNT(*) FROM ofertas WHERE ciudadano_id=$CIT_ID;" 2>/dev/null)
R=$(curl -s "$BASE/api/ofertas/mias" -H "Authorization: Bearer $CT")
check "Mis ofertas (móvil) coincide con BD ($MIS_COUNT)" "$MIS_COUNT" "$(echo "$R" | python3 -c 'import sys,json;print(len(json.load(sys.stdin)["data"]))')"
R=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/api/ofertas/mias" -H "Authorization: Bearer $ET")
check "Empresa no accede a /ofertas/mias -> 403" "403" "$R"

VENTAS_COUNT=$(mysql "${MYSQL[@]}" -N -B "$DB" -e "SELECT COUNT(*) FROM transacciones WHERE oferta_id IN (SELECT id FROM ofertas WHERE ciudadano_id=$CIT_ID);" 2>/dev/null)
R=$(curl -s "$BASE/api/mis-transacciones" -H "Authorization: Bearer $CT")
check "Mis ventas (móvil) coincide con BD ($VENTAS_COUNT)" "$VENTAS_COUNT" "$(echo "$R" | python3 -c 'import sys,json;print(len(json.load(sys.stdin)["data"]))')"
json_contains "Mis ventas incluye a la empresa compradora" "Probe Empresa" "$R"
R=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/api/mis-transacciones" -H "Authorization: Bearer $ET")
check "Empresa no accede a /mis-transacciones -> 403" "403" "$R"

RT=$(curl -s -o "$TMP/api_cmp_due.pdf" -w '%{http_code}' "$BASE/api/transacciones/$TID3/comprobante" -H "Authorization: Bearer $CT")
check "Ciudadano (dueño) descarga comprobante por API -> 200" "200" "$RT"
head -c 5 "$TMP/api_cmp_due.pdf" | grep -q '%PDF' && ok "Comprobante del dueño por API es PDF real" || bad "Comprobante API dueño PDF" "%PDF" "$(head -c 20 "$TMP/api_cmp_due.pdf")"
R=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/api/transacciones/$TID3/comprobante" -H "Authorization: Bearer $DT")
check "Ciudadano NO dueño no descarga comprobante -> 403" "403" "$R"

# ==========================================================
say "RESUMEN"
# ==========================================================
printf 'PASS: %d   FAIL: %d\n' "$PASS" "$FAIL"
if [ "$FAIL" -eq 0 ]; then
  printf '\n\033[32m✔ TODAS LAS PRUEBAS PASARON — el sistema funciona para los 4 roles.\033[0m\n'
else
  printf '\n\033[31m✘ Hubo %d fallos. Revisar arriba.\033[0m\n' "$FAIL"
fi
printf 'Cuentas demo: admin@reciclaje.bo/admin123456 · ciudadano@reciclaje.bo/ciudadano123 · empresa@reciclaje.bo/empresa123456 · pendiente@reciclaje.bo/pendiente123\n'
exit "$FAIL"