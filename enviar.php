<?php
/**
 * Amor y Abrigo Honduras — Manejador de formularios
 * Recibe datos POST y envia correo al refugio via PHP mail()
 * Compatible con Hostinger shared hosting (Apache + PHP)
 */

// ── Configuracion ──────────────────────────────────────────────────
// IMPORTANTE: Cambiar este correo al correo real del refugio en Hostinger
// Puede ser Gmail o el correo profesional (ej: info@amoryabrigohn.org)
define('REFUGIO_EMAIL', 'amoryabrigo13@gmail.com');
define('REFUGIO_NOMBRE', 'Amor y Abrigo Honduras');

// ── Seguridad: solo aceptar POST desde el mismo dominio ────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// ── Limpiar y validar input ────────────────────────────────────────
function clean($val) {
    return htmlspecialchars(strip_tags(trim($val ?? '')), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// ── Obtener datos del formulario ───────────────────────────────────
$tipo      = clean($_POST['tipo'] ?? '');
$nombre    = clean($_POST['nombre'] ?? '');
$correo    = clean($_POST['correo'] ?? '');
$telefono  = clean($_POST['telefono'] ?? '');
$area      = clean($_POST['area'] ?? '');
$disponibilidad = clean($_POST['disponibilidad'] ?? '');
$mensaje   = clean($_POST['mensaje'] ?? '');

// ── Validaciones basicas ───────────────────────────────────────────
if (empty($tipo) || empty($nombre) || empty($correo)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
    exit;
}

if (!validateEmail($correo)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Correo invalido']);
    exit;
}

// ── Construir el correo segun tipo ─────────────────────────────────
$iconos = [
    'Voluntariado' => '🤝',
    'Sponsor'      => '💛',
    'Convenio'     => '🏢',
    'Contacto'     => '✉️',
];
$icono = $iconos[$tipo] ?? '📬';

$asunto = "[Amor y Abrigo] $icono Nueva solicitud de $tipo — $nombre";

$cuerpo = "
=================================================
  AMOR Y ABRIGO HONDURAS — NUEVA SOLICITUD
=================================================

Tipo:          $tipo
Nombre:        $nombre
Correo:        $correo
Telefono:      $telefono
" . ($area ? "Area/Cargo:    $area\n" : "") . "
" . ($disponibilidad ? "Disponibilidad: $disponibilidad\n" : "") . "
-------------------------------------------------
Mensaje / Descripcion:
$mensaje
-------------------------------------------------
Enviado desde el sitio web de Amor y Abrigo
Fecha: " . date('d/m/Y H:i:s') . "
=================================================
";

// ── Headers del correo ─────────────────────────────────────────────
$headers  = "From: " . REFUGIO_NOMBRE . " <noreply@" . ($_SERVER['HTTP_HOST'] ?? 'amoryabrigohn.org') . ">\r\n";
$headers .= "Reply-To: $nombre <$correo>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// ── Enviar correo ──────────────────────────────────────────────────
$enviado = mail(REFUGIO_EMAIL, $asunto, $cuerpo, $headers);

if ($enviado) {
    // Tambien enviar confirmacion al remitente
    $asunto_conf = "Recibimos tu solicitud — Amor y Abrigo Honduras";
    $cuerpo_conf = "Hola $nombre,

Hemos recibido tu solicitud de $tipo correctamente.

El equipo de Amor y Abrigo revisara tu mensaje y te contactara pronto al correo $correo o al telefono $telefono.

Gracias por apoyar nuestra causa. Cada persona que se une hace la diferencia.

Con amor,
El equipo de Amor y Abrigo Honduras
amoryabrigohn.org | +504 9824-8715
";
    $headers_conf  = "From: " . REFUGIO_NOMBRE . " <noreply@" . ($_SERVER['HTTP_HOST'] ?? 'amoryabrigohn.org') . ">\r\n";
    $headers_conf .= "MIME-Version: 1.0\r\n";
    $headers_conf .= "Content-Type: text/plain; charset=UTF-8\r\n";
    mail($correo, $asunto_conf, $cuerpo_conf, $headers_conf);

    echo json_encode(['ok' => true, 'message' => 'Correo enviado correctamente']);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al enviar el correo']);
}
?>
