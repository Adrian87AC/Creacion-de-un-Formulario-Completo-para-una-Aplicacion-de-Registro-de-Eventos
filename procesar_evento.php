<?php
// procesar_evento.php
// Recibe los datos POST del formulario y muestra un recibo Bootstrap-styled

function safe($v) {
    return htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$errors = [];

// Required fields
$nombre = $_POST['nombre'] ?? '';
$correo = $_POST['correo'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
$genero = $_POST['genero'] ?? '';
$fecha_evento = $_POST['fecha_evento'] ?? '';
$tipo_entrada = $_POST['tipo_entrada'] ?? '';
$preferencia_comida = $_POST['preferencia_comida'] ?? [];
$nombre_usuario = $_POST['nombre_usuario'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';
$confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
$notificaciones = $_POST['notificaciones'] ?? 'no';
$terminos = $_POST['terminos'] ?? '';
$calificacion_eventos = $_POST['calificacion_eventos'] ?? '';
$comentarios_adicionales = $_POST['comentarios_adicionales'] ?? '';

// Validaciones básicas
if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Correo electrónico inválido o vacío.';
}
if (empty($contrasena) || strlen($contrasena) < 6) {
    $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
}
if ($contrasena !== $confirmar_contrasena) {
    $errors[] = 'Las contraseñas no coinciden.';
}
if (empty($fecha_nacimiento)) {
    $errors[] = 'La fecha de nacimiento es obligatoria.';
}
if (empty($fecha_evento)) {
    $errors[] = 'La fecha del evento es obligatoria.';
}
if (empty($genero)) {
    $errors[] = 'Selecciona un género.';
}
if (empty($terminos)) {
    $errors[] = 'Debes aceptar los términos y condiciones.';
}

// Manejo del archivo
$archivo_info = null;
if (!empty($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['archivo']['tmp_name'];
    $name = basename($_FILES['archivo']['name']);
    $size = $_FILES['archivo']['size'];

    $upload_dir = __DIR__ . '/uploads';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $target = $upload_dir . '/' . time() . '_' . $name;
    if (move_uploaded_file($tmp, $target)) {
        $archivo_info = ['original_name' => $name, 'size' => $size, 'path' => $target];
    } else {
        $errors[] = 'Error al subir el archivo.';
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo de registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Errores de validación</h4>
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?php echo safe($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <hr>
            <a href="formulario.html" class="btn btn-secondary">Volver al formulario</a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h2>Recibo de registro</h2>
            </div>
            <div class="card-body">
                <p><strong>Nombre:</strong> <?php echo safe($nombre) ?></p>
                <p><strong>Correo:</strong> <?php echo safe($correo) ?></p>
                <p><strong>Teléfono:</strong> <?php echo safe($telefono) ?></p>
                <p><strong>Fecha de nacimiento:</strong> <?php echo safe($fecha_nacimiento) ?></p>
                <p><strong>Género:</strong> <?php echo safe($genero) ?></p>
                <hr>
                <p><strong>Fecha del evento:</strong> <?php echo safe($fecha_evento) ?></p>
                <p><strong>Tipo de entrada:</strong> <?php echo safe($tipo_entrada) ?></p>
                <p><strong>Preferencias de comida:</strong> <?php echo safe(implode(', ', (array)$preferencia_comida)) ?></p>
                <hr>
                <p><strong>Nombre de usuario:</strong> <?php echo safe($nombre_usuario) ?></p>
                <p><strong>Notificaciones:</strong> <?php echo ($notificaciones === 'si') ? 'Sí' : 'No' ?></p>
                <p><strong>Calificación eventos anteriores:</strong> <?php echo safe($calificacion_eventos) ?></p>
                <p><strong>Comentarios:</strong> <?php echo nl2br(safe($comentarios_adicionales)) ?></p>
                <?php if ($archivo_info): ?>
                    <hr>
                    <p><strong>Archivo subido:</strong> <?php echo safe($archivo_info['original_name']) ?> (<?php echo intval($archivo_info['size']) ?> bytes)</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-3">
            <a class="btn btn-primary" href="formulario.html">Registrar otro</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
