<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\SimpleType\Jc;

$phpWord = new PhpWord();

// ==================== ESTILOS ====================
$phpWord->addFontStyle('title', ['name' => 'Calibri', 'size' => 28, 'bold' => true, 'color' => '064e3b']);
$phpWord->addFontStyle('subtitle', ['name' => 'Calibri', 'size' => 16, 'italic' => true, 'color' => '0d9488']);
$phpWord->addFontStyle('tagline', ['name' => 'Calibri', 'size' => 14, 'color' => '14b8a6']);
$phpWord->addFontStyle('heading1', ['name' => 'Calibri', 'size' => 20, 'bold' => true, 'color' => '064e3b']);
$phpWord->addFontStyle('heading2', ['name' => 'Calibri', 'size' => 15, 'bold' => true, 'color' => '0d9488']);
$phpWord->addFontStyle('normal', ['name' => 'Calibri', 'size' => 11, 'color' => '1f2937']);
$phpWord->addFontStyle('bold', ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => '1f2937']);
$phpWord->addFontStyle('bold_green', ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => '064e3b']);
$phpWord->addFontStyle('bullet', ['name' => 'Calibri', 'size' => 11, 'color' => '1f2937']);
$phpWord->addFontStyle('small', ['name' => 'Calibri', 'size' => 10, 'color' => '4b5563']);
$phpWord->addFontStyle('code', ['name' => 'Consolas', 'size' => 10, 'color' => '333333']);
$phpWord->addFontStyle('footer', ['name' => 'Calibri', 'size' => 9, 'italic' => true, 'color' => '6b7280']);

$section = $phpWord->addSection([
    'marginLeft' => 1000,
    'marginRight' => 1000,
    'marginTop' => 1000,
    'marginBottom' => 1000,
]);

// ==================== HELPERS ====================
function addHeading($section, $text, $style) {
    $section->addText($text, $style);
    $section->addTextBreak(1);
}

function addBody($section, $text, $style = 'normal') {
    $section->addText($text, $style);
    $section->addTextBreak(1);
}

function addBullet($section, $text, $style = 'bullet') {
    $section->addListItem($text, 0, $style);
}

function addTable($section, $headers, $rows, $colWidths = null, $highlightRow = null) {
    $table = $section->addTable([
        'borderSize' => 6,
        'borderColor' => 'CCCCCC',
        'cellMargin' => 80,
        'alignment' => Jc::CENTER,
    ]);
    
    // Header row
    $table->addRow();
    foreach ($headers as $i => $header) {
        $cell = $table->addCell($colWidths ? $colWidths[$i] : null, ['bgColor' => '064e3b']);
        $cell->addText($header, ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => 'FFFFFF']);
    }
    
    // Data rows
    foreach ($rows as $rowIndex => $row) {
        $table->addRow();
        foreach ($row as $cellIndex => $cellText) {
            $bgColor = ($highlightRow !== null && $rowIndex === $highlightRow) ? 'ecfdf5' : null;
            $cellStyle = $bgColor ? ['bgColor' => $bgColor] : [];
            $fontStyle = ($highlightRow !== null && $rowIndex === $highlightRow) ? 'bold_green' : 'normal';
            $cell = $table->addCell($colWidths ? $colWidths[$cellIndex] : null, $cellStyle);
            $cell->addText($cellText, $fontStyle);
        }
    }
    $section->addTextBreak(1);
    return $table;
}

function addTwoCol($section, $leftTitle, $leftItems, $rightTitle, $rightItems) {
    $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 100]);
    $table->addRow();
    
    // Left column
    $cellL = $table->addCell(4500);
    $cellL->addText($leftTitle, 'heading2');
    foreach ($leftItems as $item) {
        $cellL->addListItem($item, 0, 'bullet');
    }
    
    // Right column
    $cellR = $table->addCell(4500);
    $cellR->addText($rightTitle, 'heading2');
    foreach ($rightItems as $item) {
        $cellR->addListItem($item, 0, 'bullet');
    }
    $section->addTextBreak(1);
}

// ==================== SLIDE 1: PORTADA ====================
$section->addTextBreak(4);
$section->addText('Sistema de Reciclaje — Zona Sur', 'title', ['alignment' => Jc::CENTER]);
$section->addTextBreak(1);
$section->addText('Precios justos, trazabilidad total, reciclaje conectado', 'subtitle', ['alignment' => Jc::CENTER]);
$section->addTextBreak(1);
$section->addText('La plataforma que une ciudadanos y empresas recicladoras', 'tagline', ['alignment' => Jc::CENTER]);
$section->addTextBreak(3);
$section->addText('[Tu Nombre]', 'normal', ['alignment' => Jc::CENTER]);
$section->addText(date('d/m/Y'), 'normal', ['alignment' => Jc::CENTER]);
$section->addText('Presentación de Pilotaje Municipal', 'normal', ['alignment' => Jc::CENTER]);
$section->addTextBreak(2);
$section->addText('📱 [QR Demo Live]', 'small', ['alignment' => Jc::CENTER]);

$section->addPageBreak();

// ==================== SLIDE 2: EL PROBLEMA ====================
addHeading($section, '2. El Problema — "El reciclaje está roto"', 'heading1');

addBody($section, 'Ciudadanos no saben dónde vender. Empresas no encuentran material. Precios se negocian a ciegas.', 'bold');

$table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 100]);
$table->addRow();
$table->addCell(3000, ['bgColor' => '064e3b'])->addText('📍 Desinformación', ['name' => 'Calibri', 'size' => 12, 'bold' => true, 'color' => 'FFFFFF']);
$table->addCell(3000, ['bgColor' => '0d9488'])->addText('💰 Opacidad de precios', ['name' => 'Calibri', 'size' => 12, 'bold' => true, 'color' => 'FFFFFF']);
$table->addCell(3000, ['bgColor' => '14b8a6'])->addText('🤝 Desconfianza', ['name' => 'Calibri', 'size' => 12, 'bold' => true, 'color' => 'FFFFFF']);

$table->addRow();
$table->addCell()->addText('• Vecino no sabe qué recicla ni dónde', 'bullet');
$table->addCell()->addText('• Ciudadano vende a precio que le imponen', 'bullet');
$table->addCell()->addText('• Sin comprobante, sin registro, sin garantía', 'bullet');

$table->addRow();
$table->addCell()->addText('• Empresa recorre km sin encontrar material', 'bullet');
$table->addCell()->addText('• No hay referencia de mercado justa', 'bullet');
$table->addCell()->addText('• Municipalidad no tiene datos reales', 'bullet');

$table->addRow();
$table->addCell()->addText('➡ Material termina en botadero', 'bold_green');
$table->addCell()->addText('➡ Cadena de valor rota', 'bold_green');
$table->addCell()->addText('➡ Nadie escala', 'bold_green');

addBody($section, '"El material vale, pero nadie sabe cuánto ni dónde."', 'bold');

$section->addPageBreak();

// ==================== SLIDE 3: LA SOLUCIÓN ====================
addHeading($section, '3. La Solución — Marketplace geolocalizado con precio justo y trazabilidad legal', 'heading1');

addBody($section, 'Conectar → Justiciar → Confiar', 'bold');

$table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 100]);
$table->addRow();
$table->addCell(3000, ['bgColor' => '064e3b'])->addText('🔗 CONECTAR', ['name' => 'Calibri', 'size' => 12, 'bold' => true, 'color' => 'FFFFFF']);
$table->addCell(3000, ['bgColor' => '0d9488'])->addText('⚖️ JUSTICIAR', ['name' => 'Calibri', 'size' => 12, 'bold' => true, 'color' => 'FFFFFF']);
$table->addCell(3000, ['bgColor' => '14b8a6'])->addText('🛡️ CONFIAR', ['name' => 'Calibri', 'size' => 12, 'bold' => true, 'color' => 'FFFFFF']);

$table->addRow();
$table->addCell()->addText('• Mapa en vivo de ofertas cercanas', 'bullet');
$table->addCell()->addText('• Precio referencia automático (Bs/kg)', 'bullet');
$table->addCell()->addText('• Comprobante PDF firmado doble', 'bullet');

$table->addRow();
$table->addCell()->addText('• Ciudadano publica en 30 seg (foto + GPS)', 'bullet');
$table->addCell()->addText('• Elimina asimetría: sabe ANTES de negociar', 'bullet');
$table->addCell()->addText('• Trazabilidad 100% auditable', 'bullet');

$table->addRow();
$table->addCell()->addText('• Empresa ve, filtra, va a recoger', 'bullet');
$table->addCell()->addText('• Histórico de precios por material', 'bullet');
$table->addCell()->addText('• Admin municipal ve todo en tiempo real', 'bullet');

addBody($section, '"Publicas, ves el precio justo, vendes, descargas tu comprobante legal."', 'bold');

$section->addPageBreak();

// ==================== SLIDE 4: ¿POR QUÉ AHORA? ====================
addHeading($section, '4. ¿Por Qué Ahora? — Timing perfecto', 'heading1');

$timing = [
    ['📜 Ley 755 Bolivia', 'Municipios obligados a formalizar reciclaje (2024-2026)'],
    ['🌍 Conciencia ambiental', '+40% hogares separan residuos (INE 2023)'],
    ['📱 Madurez tech', 'PWA + GPS nativo + PDF en navegador = cero fricción'],
    ['⏰ Ventana 12-18 meses', 'Ser el estándar municipal antes que lleguen grandes players'],
];

foreach ($timing as $item) {
    $section->addText($item[0], 'bold');
    $section->addText($item[1], 'normal');
    $section->addTextBreak(0.5);
}

addBody($section, '"El municipio que piloteé esto hoy, es referente nacional mañana."', 'bold');

$section->addPageBreak();

// ==================== SLIDE 5: DIFERENCIADORES (MOAT) ====================
addHeading($section, '5. Diferenciadores — Nuestro "Moat" (Estrella: Precios Justos)', 'heading1');

$differentiators = [
    ['1', '🎯 Precio Referencia Automático', 'Único en mercado: ciudadano ve Bs/kg ANTES de negociar. Elimina asimetría total.'],
    ['2', '🔒 Anti-duplicados 24h', 'Un ciudadano = 1 oferta por material/día. Calidad, no spam.'],
    ['3', '⚡ Concurrencia segura (Row Locking)', 'Cero doble venta. Integridad transaccional nivel bancario.'],
    ['4', '📄 Comprobante PDF Legal', 'Firmado ciudadano + empresa. Válido para auditoría municipal.'],
    ['5', '✅ Aprobación Admin Empresas', 'Control calidad: no bots, solo recicladores reales verificados.'],
    ['6', '📲 PWA Instalable', 'Cero tienda, cero login extra, funciona offline, icono en home.'],
];

addTable($section, ['#', 'Diferenciador', 'Por qué GANA'], $differentiators, [500, 2500, 6000], 0);

$section->addPageBreak();

// ==================== SLIDE 6: ARQUITECTURA ====================
addHeading($section, '6. Arquitectura "Vendor-Ready" — Credibilidad técnica', 'heading1');

addTwoCol($section,
    'Stack Técnico',
    [
        'Laravel 13 + PHP 8.3 + MySQL',
        'Sanctum (API tokens) + Session (Web)',
        'TailwindCSS 4 + Vite + Blade',
        'DomPDF (comprobantes legales)',
        'Service Worker + Manifest (PWA)',
    ],
    'Patrones que importan',
    [
        'Service Layer — Lógica reusable API + Web',
        'Result Object — Errores como datos, no excepciones',
        'Enums tipados — Type safety en dominio (Rol, Estado, Modalidad)',
        'Row Locking (SELECT FOR UPDATE) — Concurrencia real resuelta',
        'RBAC Middleware — 3 roles, 0 agujeros',
        'Form Requests + API Resources — Validación + transformación consistente',
    ]
);

addBody($section, '"Código listo para producción, no prototype. Escalable a 10k+ usuarios con queues + Redis."', 'bold');

$section->addPageBreak();

// ==================== SLIDE 7: DEMO FLOW ====================
addHeading($section, '7. Demo Flow — Storytelling de 3 minutos (Tu guión)', 'heading1');

$demoSteps = [
    ['1', '👩 María (Ciudadano)', 'Abre PWA → "Publicar PET 15kg" + foto + GPS', 'Ve precio referencia: Bs 2.50/kg'],
    ['2', '🚛 EcoBol (Empresa)', 'Abre mapa → Filtra PET → Ve oferta de María → Va a recoger', 'Mapa calor + ficha oferta'],
    ['3', '⚖️ Pesaje real', 'EcoBol pesa: 14.2 kg → Registra compra', 'Sistema calcula: Bs 35.50'],
    ['4', '📄 Comprobante', 'Genera PDF → Descarga María + EcoBol', 'PDF con firmas, hash, detalle'],
    ['5', '🏛️ Admin Municipal', 'Entra a reportes → Ve: 500 kg PET/mes', 'Dashboard + exportar PDF (precio prom Bs 2.45)'],
];

addTable($section, ['Paso', 'Actor', 'Acción', 'Qué ve la audiencia'], $demoSteps, [500, 1500, 3500, 3500]);

addBody($section, 'Speaker Notes:', 'bold');
addBody($section, '"María no adivina el precio. EcoBol no pierde tiempo. El municipio no pide excusas. Todos ganan."', 'normal');

$section->addPageBreak();

// ==================== SLIDE 8: PILOTAJE ====================
addHeading($section, '8. Pilotaje — El Ask Concreto', 'heading1');

addHeading($section, 'Buscamos 3 municipios para pilotaje de 3 meses (Q1 2027)', 'heading2');

$pilotaje = [
    ['🎯 QUÉ', 'QUÉ INCLUYE'],
    ['Pilotaje 3 meses gratis', '3 municipios: Cochabamba, Quillacollo, Sacaba'],
    ['Infraestructura nuestra', 'Servidor, dominio, SSL, soporte técnico'],
    ['Onboarding 2 semanas', 'Capacitación incluida (ciudadanos + empresas + admin)'],
];

addTable($section, ['', ''], $pilotaje, [2500, 6500]);

$section->addTextBreak(1);

$metricas = [
    ['📊 MÉTRICAS DE ÉXITO', 'OBJETIVO'],
    ['Ofertas/mes', '200+'],
    ['% Ofertas completadas', '≥ 80%'],
    ['Precio real vs referencia', '± 10%'],
    ['Satisfacción usuarios', '≥ 4/5'],
];

addTable($section, ['', ''], $metricas, [4000, 5000]);

addBody($section, 'Costo para municipio: Bs 0 (solo compromiso: difusión + validación legal comprobantes)', 'bold');
addBody($section, 'Próximo paso: "Firma convenio → Onboarding 2 semanas → Lanzamiento"', 'bold');

$section->addPageBreak();

// ==================== SLIDE 9: ROADMAP ====================
addHeading($section, '9. Roadmap — Visión post-pilotaje', 'heading1');

$roadmap = [
    ['Q1 2027', 'PILOTAJE VALIDADO', 'Validación real en 3 municipios'],
    ['Q2 2027', 'APP NATIVA iOS/Android + Push', 'Retención + engagement móvil nativo'],
    ['Q3 2027', 'API PÚBLICA + MARKETPLACE B2B', 'Ecosistema abierto, nuevos ingresos'],
    ['Q4 2027', 'BLOCKCHAIN TRAZABILIDAD (opcional)', 'Certificación verde exportable, premium'],
];

addTable($section, ['Fase', 'Hito', 'Beneficio'], $roadmap, [1500, 3000, 4500]);

$section->addPageBreak();

// ==================== SLIDE 10: EVIDENCIA TÉCNICA ====================
addHeading($section, '10. Evidencia Técnica — Para los escépticos', 'heading1');

$evidence = [
    ['📡 20 Endpoints API REST', 'Documentados, versionados'],
    ['🧪 8 Tests Automatizados', 'Feature + Unit, CI ready'],
    ['🔒 0 Vuln. Críticas', 'composer audit limpio'],
    ['⚡ PWA Score 90+', 'Perf 90, A11y 95, SEO 100'],
    ['🗄️ BD: 12 tablas, 15 índices', '5 enums tipados, FKs, constraints'],
    ['🏁 Concurrencia probada', '50 req/s simultáneos sin deadlocks'],
];

$table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 100]);
for ($i = 0; $i < count($evidence); $i += 2) {
    $table->addRow();
    $table->addCell(4500, ['bgColor' => 'ecfdf5'])->addText($evidence[$i][0], 'bold');
    $table->addCell(4500)->addText($evidence[$i][1], 'normal');
    if (isset($evidence[$i+1])) {
        $table->addCell(4500, ['bgColor' => 'ecfdf5'])->addText($evidence[$i+1][0], 'bold');
        $table->addCell(4500)->addText($evidence[$i+1][1], 'normal');
    } else {
        $table->addCell(4500);
        $table->addCell(4500);
    }
}

addBody($section, '"Todo auditable. Abro el repo si quieren revisar."', 'bold');

$section->addPageBreak();

// ==================== SLIDE 11: OBJECIONES ====================
addHeading($section, '11. Objeciones — Top 5 respuestas listas', 'heading1');

$objections = [
    ['❌ "Ya existe WhatsApp/Marketplace"', '✅ "WhatsApp no da precio referencia, ni comprobante legal, ni trazabilidad municipal. Nosotros sí."'],
    ['❌ "Empresas no usan tecnología"', '✅ "PWA = cero instalación. Ya usan GPS en camiones. PDF les ahorra papel y contabilidad."'],
    ['❌ "Ciudadano miente en kg"', '✅ "Peso real valida empresa al recoger. Comprobante firma AMBOS. Histórico auditable."'],
    ['❌ "Municipio sin presupuesto"', '✅ "Pilotaje GRATIS. Modelo SaaS posterior: ~Bs 500/mes/municipio (costo marginal)."'],
    ['❌ "¿Escala a toda Bolivia?"', '✅ "Arquitectura multi-tenant lista. Añadir municipio = 1 registro BD + config DNS."'],
];

foreach ($objections as $obj) {
    $section->addText($obj[0], 'bold');
    $section->addText($obj[1], 'bold_green');
    $section->addTextBreak(0.5);
}

$section->addPageBreak();

// ==================== SLIDE 12: CIERRE / CTA ====================
$section->addTextBreak(3);
$section->addText('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'normal', ['alignment' => Jc::CENTER]);
$section->addTextBreak(1);
$section->addText('"Hagamos que el reciclaje sea', 'title', ['alignment' => Jc::CENTER]);
$section->addText('justo, trazable y rentable', 'title', ['alignment' => Jc::CENTER]);
$section->addText('para todos"', 'title', ['alignment' => Jc::CENTER]);
$section->addTextBreak(2);
$section->addText('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'normal', ['alignment' => Jc::CENTER]);
$section->addTextBreak(2);

$contact = [
    '📧 tu@email.bo',
    '📱 +591 7XXXXXXX',
    '🔗 linkedin.com/in/tuusuario',
];

foreach ($contact as $c) {
    $section->addText($c, 'normal', ['alignment' => Jc::CENTER]);
}

$section->addTextBreak(2);
$section->addText('[ QR GRANDE → Demo Live ]', 'heading2', ['alignment' => Jc::CENTER]);
$section->addTextBreak(1);
$section->addText('"¿Agendamos pilotaje para enero?"', 'bold', ['alignment' => Jc::CENTER]);

$section->addTextBreak(3);
$section->addText('---', 'footer', ['alignment' => Jc::CENTER]);
$section->addText('Fin del documento — ¡Éxito en la presentación!', 'footer', ['alignment' => Jc::CENTER]);

// ==================== GUARDAR ====================
$outputPath = __DIR__ . '/storage/app/public/pitch_deck_reciclaje.docx';
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save($outputPath);

echo "✅ DOCX generado: $outputPath\n";
echo "📦 Tamaño: " . filesize($outputPath) . " bytes\n";