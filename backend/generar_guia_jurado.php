<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\SimpleType\Jc;

$phpWord = new PhpWord();

// Estilos base
$phpWord->addFontStyle('title', ['name' => 'Calibri', 'size' => 24, 'bold' => true, 'color' => '165226']);
$phpWord->addFontStyle('heading1', ['name' => 'Calibri', 'size' => 16, 'bold' => true, 'color' => '165226']);
$phpWord->addFontStyle('heading2', ['name' => 'Calibri', 'size' => 13, 'bold' => true, 'color' => '073123']);
$phpWord->addFontStyle('normal', ['name' => 'Calibri', 'size' => 11]);
$phpWord->addFontStyle('bold', ['name' => 'Calibri', 'size' => 11, 'bold' => true]);
$phpWord->addFontStyle('code', ['name' => 'Consolas', 'size' => 10, 'color' => '333333']);
$phpWord->addFontStyle('small', ['name' => 'Calibri', 'size' => 10]);

$section = $phpWord->addSection([
    'marginLeft' => 1440,
    'marginRight' => 1440,
    'marginTop' => 1440,
    'marginBottom' => 1440,
]);

// ==================== PORTADA ====================
$section->addText('GUÍA DE PREPARACIÓN PARA JURADO', 'title', ['alignment' => Jc::CENTER]);
$section->addTextBreak(2);
$section->addText('Sistema de Reciclaje - Zona Sur Cochabamba', 'heading1', ['alignment' => Jc::CENTER]);
$section->addTextBreak(1);
$section->addText('Presentación Técnica y Funcional', 'heading2', ['alignment' => Jc::CENTER]);
$section->addTextBreak(2);
$section->addText('Fecha: ' . date('d/m/Y'), 'normal', ['alignment' => Jc::CENTER]);
$section->addText('Versión: 1.0', 'normal', ['alignment' => Jc::CENTER]);
$section->addPageBreak();

// ==================== 1. STACK TECNOLÓGICO ====================
addHeading($section, '1. Stack Tecnológico', 'heading1');

$table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 80]);
$table->addRow();
$table->addCell(3000, ['bgColor' => '165226'])->addText('Capa', 'bold', ['color' => 'FFFFFF']);
$table->addCell(6000, ['bgColor' => '165226'])->addText('Tecnologías', 'bold', ['color' => 'FFFFFF']);

$stack = [
    ['Backend', 'Laravel 13.x, PHP 8.3'],
    ['Autenticación', 'Laravel Sanctum (API tokens) + Session (Web)'],
    ['Base de Datos', 'MySQL (Eloquent ORM)'],
    ['Frontend Web', 'Blade + TailwindCSS 4 + Vite'],
    ['PWA', 'Service Worker, Web Manifest, instalable'],
    ['PDF', 'DomPDF (barryvdh/laravel-dompdf)'],
    ['Testing', 'PHPUnit, Laravel Pest'],
    ['Calidad', 'Laravel Pint (code style), Rector'],
];

foreach ($stack as $row) {
    $table->addRow();
    $table->addCell()->addText($row[0], 'bold');
    $table->addCell()->addText($row[1], 'normal');
}

$section->addTextBreak(1);

// ==================== 2. ARQUITECTURA Y PATRONES ====================
addHeading($section, '2. Arquitectura y Patrones de Diseño', 'heading1');

$table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 80]);
$table->addRow();
$table->addCell(3000, ['bgColor' => '165226'])->addText('Patrón', 'bold', ['color' => 'FFFFFF']);
$table->addCell(6000, ['bgColor' => '165226'])->addText('Dónde se usa / Descripción', 'bold', ['color' => 'FFFFFF']);

$patterns = [
    ['Service Layer', 'OfertaService, CompraService (lógica de negocio aislada)'],
    ['Result Object', 'App\\Support\\Result (evita excepciones como control de flujo)'],
    ['Enums tipados', 'Rol, EstadoUsuario, EstadoOferta, EstadoTransaccion, ModalidadEntrega'],
    ['Query Scopes', 'existeDuplicada, obtenerPrecioReferencia en modelos'],
    ['Middleware RBAC', 'EnsureRol (roles: admin, empresa, ciudadano)'],
    ['Form Requests', 'Validación desacoplada (OfertaRequest, RegisterRequest, etc.)'],
    ['API Resources', 'Transformación consistente (UsuarioResource, OfertaResource, etc.)'],
    ['DB Transactions + Row Locking', 'lockForUpdate() en servicios críticos (concurrencia)'],
];

foreach ($patterns as $row) {
    $table->addRow();
    $table->addCell()->addText($row[0], 'bold');
    $table->addCell()->addText($row[1], 'normal');
}

$section->addTextBreak(1);

// ==================== 3. AUTENTICACIÓN Y AUTORIZACIÓN ====================
addHeading($section, '3. Autenticación y Autorización', 'heading1');

$table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 80]);
$table->addRow();
$table->addCell(3000, ['bgColor' => '165226'])->addText('Aspecto', 'bold', ['color' => 'FFFFFF']);
$table->addCell(6000, ['bgColor' => '165226'])->addText('Implementación', 'bold', ['color' => 'FFFFFF']);

$auth = [
    ['Web (Blade)', 'Session + auth middleware + EnsureRol'],
    ['API (Móvil/PWA)', 'Sanctum tokens (auth:sanctum) + EnsureRol'],
    ['Roles', 'admin, empresa, ciudadano (enum Rol)'],
    ['Estados usuario', 'pendiente, aprobado, rechazado (enum EstadoUsuario)'],
    ['Registro empresa', 'Requiere aprobación admin (pendiente → aprobado/rechazado)'],
    ['Registro ciudadano', 'Activo inmediato'],
    ['Throttling', 'throttle:login.web / throttle:login.api'],
];

foreach ($auth as $row) {
    $table->addRow();
    $table->addCell()->addText($row[0], 'bold');
    $table->addCell()->addText($row[1], 'normal');
}

$section->addPageBreak();

// ==================== 4. API REST ====================
addHeading($section, '4. API REST - Endpoints Principales', 'heading1');

$table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 80]);
$table->addRow();
$table->addCell(1500, ['bgColor' => '165226'])->addText('Método', 'bold', ['color' => 'FFFFFF']);
$table->addCell(3500, ['bgColor' => '165226'])->addText('Endpoint', 'bold', ['color' => 'FFFFFF']);
$table->addCell(4000, ['bgColor' => '165226'])->addText('Descripción', 'bold', ['color' => 'FFFFFF']);

$endpoints = [
    ['POST', '/api/auth/register', 'Registro (ciudadano/empresa)'],
    ['POST', '/api/auth/login', 'Login → token Bearer'],
    ['POST', '/api/auth/logout', 'Revoca token actual'],
    ['GET', '/api/auth/me', 'Usuario autenticado'],
    ['GET', '/api/categorias', 'Catálogo materiales (público)'],
    ['GET', '/api/ofertas', 'Listado (empresa/admin)'],
    ['POST', '/api/ofertas', 'Crear oferta (ciudadano)'],
    ['GET', '/api/ofertas/{id}', 'Ver oferta'],
    ['POST', '/api/ofertas/{id}/cancelar', 'Cancelar (dueño)'],
    ['GET', '/api/ofertas/mias', 'Mis ofertas (ciudadano)'],
    ['GET', '/api/transacciones', 'Historial (empresa/admin)'],
    ['POST', '/api/transacciones', 'Registrar compra (empresa)'],
    ['GET', '/api/transacciones/{id}', 'Detalle'],
    ['GET', '/api/transacciones/{id}/comprobante', 'PDF comprobante'],
    ['GET', '/api/mis-transacciones', 'Mis compras (empresa)'],
    ['GET', '/api/reportes', 'Reportes (empresa/admin)'],
    ['GET', '/api/reportes/pdf', 'Exportar PDF'],
    ['GET', '/api/admin/usuarios', 'Gestión usuarios (admin)'],
    ['POST', '/api/admin/usuarios/{id}/aprobar', 'Aprobar empresa'],
    ['POST', '/api/admin/usuarios/{id}/rechazar', 'Rechazar empresa'],
];

foreach ($endpoints as $row) {
    $table->addRow();
    $table->addCell()->addText($row[0], 'code');
    $table->addCell()->addText($row[1], 'code');
    $table->addCell()->addText($row[2], 'normal');
}

$section->addTextBreak(1);

// ==================== 5. FUNCIONALIDADES CLAVE ====================
addHeading($section, '5. Funcionalidades Clave (Para Demostrar)', 'heading1');

$table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 80]);
$table->addRow();
$table->addCell(2500, ['bgColor' => '165226'])->addText('Módulo', 'bold', ['color' => 'FFFFFF']);
$table->addCell(3500, ['bgColor' => '165226'])->addText('Qué hace', 'bold', ['color' => 'FFFFFF']);
$table->addCell(3000, ['bgColor' => '165226'])->addText('Valor de negocio', 'bold', ['color' => 'FFFFFF']);

$features = [
    ['Ofertas Ciudadano', 'Publicar material reciclable con foto, GPS, categoría, kg estimados', 'Conecta oferta-demanda local'],
    ['Mapa Ofertas', 'Visualización geográfica (Leaflet/JS) para empresas', 'Decisión rápida de recolección'],
    ['Compra/Transacción', 'Empresa registra peso real + precio → genera comprobante PDF', 'Trazabilidad y formalización'],
    ['Anti-duplicados', '1 oferta por material/ciudadano/24h (scope + lock)', 'Calidad de datos'],
    ['Concurrencia segura', 'lockForUpdate() en compra evita doble venta', 'Integridad transaccional'],
    ['Reportes PDF', 'DomPDF con agrupación por categoría/fecha', 'Auditoría y gestión'],
    ['Admin Usuarios', 'Aprobar/rechazar empresas pendientes', 'Control de acceso'],
    ['PWA', 'Instalable, offline-capable, icons, manifest', 'UX móvil nativa'],
];

foreach ($features as $row) {
    $table->addRow();
    $table->addCell()->addText($row[0], 'bold');
    $table->addCell()->addText($row[1], 'normal');
    $table->addCell()->addText($row[2], 'normal');
}

$section->addPageBreak();

// ==================== 6. PREGUNTAS PROBABLES DEL JURADO ====================
addHeading($section, '6. Preguntas Probables del Jurado', 'heading1');

addHeading($section, '6.1 Técnicas (Arquitectura/Código)', 'heading2');

$techQuestions = [
    ['¿Por qué Service Layer y no todo en Controllers?', 'Separación de responsabilidades, testeable, reutilizable (API + Web)'],
    ['¿Cómo evitan condiciones de carrera en compras concurrentes?', 'lockForUpdate() + transacción DB + UNIQUE constraint en transacciones.oferta_id'],
    ['¿Qué ventajas tiene el patrón Result vs Excepciones?', 'Control de flujo explícito, tipos de error codificados, sin stack trace costoso'],
    ['¿Por qué Enums tipados (PHP 8.1+) en lugar de strings/constants?', 'Type safety, autocompletado, refactoring seguro, validación en BD (casts)'],
    ['¿Cómo manejan archivos subidos (fotos ofertas)?', 'Storage local + nombre único + validación MIME/tamaño en Request'],
];

addQATable($section, $techQuestions);

addHeading($section, '6.2 Seguridad', 'heading2');

$secQuestions = [
    ['¿Cómo protegen endpoints sensibles?', 'auth:sanctum + EnsureRol middleware + Form Requests + Policies implícitas'],
    ['¿Sanctum vs Passport vs JWT?', 'Sanctum: simple, SPA-first, tokens revocables, sin OAuth overhead'],
    ['¿Validación de coordenadas GPS?', 'Rules numéricas + rangos lat/lng en OfertaRequest'],
];

addQATable($section, $secQuestions);

addHeading($section, '6.3 Base de Datos', 'heading2');

$dbQuestions = [
    ['¿Relaciones clave?', 'User 1:1 Ciudadano/Empresa, User 1:N Ofertas/Transacciones, Categoria 1:N Ofertas'],
    ['¿Índices importantes?', 'ofertas.estado, ofertas.ciudadano_id, transacciones.oferta_id (UNIQUE), coordenadas'],
];

addQATable($section, $dbQuestions);

addHeading($section, '6.4 Escalabilidad/Despliegue', 'heading2');

$scaleQuestions = [
    ['¿Cómo escalarían a 10k usuarios?', 'Queue workers (jobs), Redis cache, read replicas, CDN para assets, horizontal scaling'],
    ['¿PWA vs App nativa?', 'PWA: una codebase, instalable, offline, push notifications futuras, menor mantenimiento'],
];

addQATable($section, $scaleQuestions);

addHeading($section, '6.5 Negocio/Dominio', 'heading2');

$bizQuestions = [
    ['¿Por qué precio de referencia automático?', 'Evita negociación asimétrica, transparencia, dato histórico para reportes'],
    ['¿Cómo evitan ofertas fantasmas?', 'Validación 24h duplicados + estado PENDIENTE → EN_PROCESO → COMPLETADA/CANCELADA'],
    ['¿Trazabilidad completa?', 'Oferta → Transacción → Comprobante PDF (hash/ID único) → Reporte agregado'],
];

addQATable($section, $bizQuestions);

$section->addPageBreak();

// ==================== 7. PREGUNTAS TRAMPA / DIFÍCILES ====================
addHeading($section, '7. Preguntas Trampa / Difíciles', 'heading1');

$table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 80]);
$table->addRow();
$table->addCell(4000, ['bgColor' => '165226'])->addText('Pregunta', 'bold', ['color' => 'FFFFFF']);
$table->addCell(5000, ['bgColor' => '165226'])->addText('Respuesta Clave', 'bold', ['color' => 'FFFFFF']);

$trickQuestions = [
    ['¿Qué pasa si 2 empresas compran la misma oferta al mismo segundo?', 'lockForUpdate() serializa + UNIQUE oferta_id en transacciones → una falla limpio'],
    ['¿Y si el usuario borra la foto después de publicar?', 'Soft delete no implementado; archivo queda en storage (política de retención pendiente)'],
    ['¿Cómo manejan zona horaria en reportes?', 'America/La_Paz en config/app.php + datetime casts'],
    ['¿Qué pasa si Sanctum token expira en medio de uso?', '401 → frontend redirige a login (PWA: SW puede cachear vista offline)'],
    ['¿Testean concurrencia?', 'PHPUnit con ConcurrencyTest simulando requests paralelos (ver tests/)'],
    ['¿Por qué no usan Policies de Laravel?', 'Middleware EnsureRol cubre RBAC plano; Policies para permisos por recurso (futuro)'],
    ['¿Migraciones reversibles?', 'Sí, down() en todas; EstadoUsuario/Rol enums requieren cuidado en rollback'],
];

foreach ($trickQuestions as $row) {
    $table->addRow();
    $table->addCell()->addText($row[0], 'normal');
    $table->addCell()->addText($row[1], 'normal');
}

$section->addPageBreak();

// ==================== 8. PUNTOS FUERTES ====================
addHeading($section, '8. Puntos Fuertes para Destacar', 'heading1');

$strongPoints = [
    'Clean Architecture real: Service Layer + Result + Enums + Resources',
    'Concurrencia resuelta correctamente (locks + constraints)',
    'PWA completa: instalable, manifest, SW, icons, responsive',
    'Multi-tenant lógico: roles bien separados, flujo empresa requiere aprobación',
    'Trazabilidad total: oferta → compra → comprobante → reporte PDF',
    'Testing: Feature + Unit tests cubriendo flujos críticos',
    'Código moderno: PHP 8.3 (readonly, enums, attributes, match)',
];

foreach ($strongPoints as $point) {
    $section->addListItem($point, 0, 'normal');
}

$section->addTextBreak(1);

// ==================== 9. TIPS PARA LA EXPO ====================
addHeading($section, '9. Tips para la Exposición', 'heading1');

$tips = [
    'Demo live: Login admin → crear categoría → login ciudadano → publicar oferta con foto + GPS → login empresa → ver en mapa → comprar → descargar comprobante PDF',
    'Muestra código: Abre OfertaService::publicar() y CompraService::registrar() para explicar locks',
    'Menciona: "Preparado para móvil nativo vía PWA + API Sanctum"',
    'Si preguntan por móvil: "La API está lista; el frontend PWA cubre 90% casos; app nativa consumiría misma API"',
    'Usa cuentas demo: admin@reciclaje.bo / admin123456 | empresa@reciclaje.bo / empresa123456 | ciudadano@reciclaje.bo / ciudadano123',
];

foreach ($tips as $tip) {
    $section->addListItem($tip, 0, 'normal');
}

$section->addTextBreak(2);
$section->addText('---', 'normal', ['alignment' => Jc::CENTER]);
$section->addText('Fin del documento - ¡Éxito en la presentación!', 'heading2', ['alignment' => Jc::CENTER]);

// Guardar
$outputPath = __DIR__ . '/storage/app/public/guia_jurado_reciclaje.docx';
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save($outputPath);

echo "DOCX generado: $outputPath\n";
echo "Tamaño: " . filesize($outputPath) . " bytes\n";

// Funciones helper
function addHeading($section, $text, $style) {
    $section->addText($text, $style);
    $section->addTextBreak(1);
}

function addQATable($section, $questions) {
    $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 80]);
    $table->addRow();
    $table->addCell(4000, ['bgColor' => '165226'])->addText('Pregunta', 'bold', ['color' => 'FFFFFF']);
    $table->addCell(5000, ['bgColor' => '165226'])->addText('Respuesta', 'bold', ['color' => 'FFFFFF']);
    
    foreach ($questions as $row) {
        $table->addRow();
        $table->addCell()->addText($row[0], 'normal');
        $table->addCell()->addText($row[1], 'normal');
    }
    $section->addTextBreak(1);
}