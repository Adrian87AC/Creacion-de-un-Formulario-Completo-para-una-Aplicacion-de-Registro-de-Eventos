<?php
// ============================================================================
// procesar_evento.php
// ============================================================================
// Este archivo recibe los datos enviados desde formulario.html (método POST)
// Los valida en el servidor y muestra un recibo con estilos Bootstrap
// o una lista de errores si la validación falla.
// ============================================================================

// FUNCIÓN HELPER: Escapar HTML para prevenir inyección XSS
// ==========================================================
/* function safe($v) { ... }
   Propósito: Evitar que usuarios maliciosos inyecten código HTML/JavaScript
   Ejemplo: Si alguien escribe "<script>alert('hack')</script>" en un campo,
            esta función lo convierte a "&lt;script&gt;..." (código legible, no ejecutable)
   
   Parámetros:
   - $v: valor a escapar
   
   Retorna: string escapado
   
   htmlspecialchars(): función PHP que convierte:
   - < a &lt;
   - > a &gt;
   - " a &quot;
   - ' a &#039; (solo con ENT_QUOTES)
   
   ENT_QUOTES: escapa comillas simples Y dobles
   ENT_HTML5: usa estándares HTML5 para escape
   'UTF-8': codificación de caracteres
*/
function safe($v) {
    return htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ARRAY PARA ALMACENAR ERRORES DE VALIDACIÓN
// ============================================
/* $errors = []:
   Array vacío que iremos llenando con mensajes de error si algo falla.
   Al final, si NO está vacío, mostraremos los errores al usuario.
*/
$errors = [];

// RECOLECCIÓN DE DATOS DEL FORMULARIO (formulario.html)
// =====================================================
/* $_POST: superglobal de PHP que contiene datos enviados con method="post"
   
   Operador ?? (null coalescing):
   - Si $_POST['nombre'] existe, usa su valor
   - Si NO existe (usuario no envió datos o hay error), devuelve '' (string vacío)
   
   Ejemplo:
   $nombre = $_POST['nombre'] ?? '';
   es equivalente a:
   $nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
   
   Esto evita errores "Undefined index" si falta algún campo.
*/

// INFORMACIÓN PERSONAL
$nombre = $_POST['nombre'] ?? '';          // Campo text: nombre del usuario
$correo = $_POST['correo'] ?? '';          // Campo email: correo electrónico
$telefono = $_POST['telefono'] ?? '';      // Campo tel: teléfono (opcional)
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';  // Campo date: fecha nacimiento
$genero = $_POST['genero'] ?? '';          // Radio buttons: "masculino" o "femenino"

// INFORMACIÓN DEL EVENTO
$fecha_evento = $_POST['fecha_evento'] ?? '';          // Campo date: fecha del evento
$tipo_entrada = $_POST['tipo_entrada'] ?? '';          // Select: General, VIP, Estudiante
// Checkboxes: name="preferencia_comida[]" llega como ARRAY
// Si no selecciona ninguno, devuelve array vacío [] para evitar errores
$preferencia_comida = $_POST['preferencia_comida'] ?? [];

// INFORMACIÓN DE ACCESO
$nombre_usuario = $_POST['nombre_usuario'] ?? '';      // Campo text: nombre de usuario (opcional)
$contrasena = $_POST['contrasena'] ?? '';              // Campo password: contraseña
$confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';  // Campo password: confirmación

// PREFERENCIAS DE CONTACTO Y ENCUESTA
$notificaciones = $_POST['notificaciones'] ?? 'no';    // Checkbox: si/no. Default: 'no'
$terminos = $_POST['terminos'] ?? '';                  // Checkbox: obligatorio
$calificacion_eventos = $_POST['calificacion_eventos'] ?? '';  // Range: 1-10
$comentarios_adicionales = $_POST['comentarios_adicionales'] ?? '';  // Textarea: comentarios libres

// VALIDACIONES DEL SERVIDOR
// =========================
/* Aunque el navegador valida (required, type, minlength), SIEMPRE validar en servidor.
   Razones:
   1. El usuario puede desactivar JavaScript
   2. Puede enviar datos directamente sin pasar por el formulario (curl, Postman, etc.)
   3. Es la práctica de seguridad recomendada (nunca confiar solo en validación cliente)
*/

// Validar CORREO ELECTRÓNICO
/* empty(): retorna true si:
   - $correo es '' (vacío)
   - $correo es null
   - $correo es false
   - $correo es 0
   etc.
   
   filter_var($correo, FILTER_VALIDATE_EMAIL): 
   - Valida que el formato sea un email válido (existe @ y punto)
   - Retorna el email si es válido, false si no
*/
if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    // Si correo vacío O formato inválido: agregar error
    $errors[] = 'Correo electrónico inválido o vacío.';
}

// Validar CONTRASEÑA
/* strlen($contrasena) < 6:
   - strlen(): retorna el número de caracteres en un string
   - Comprobamos que sea >= 6 caracteres (como indicamos en el formulario)
*/
if (empty($contrasena) || strlen($contrasena) < 6) {
    $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
}

// Validar COINCIDENCIA DE CONTRASEÑAS
/* !== : "no igual" (comparación estricta: valor Y tipo deben ser iguales)
   Si las contraseñas no son idénticas, agregar error
*/
if ($contrasena !== $confirmar_contrasena) {
    $errors[] = 'Las contraseñas no coinciden.';
}

// Validar FECHA DE NACIMIENTO
if (empty($fecha_nacimiento)) {
    $errors[] = 'La fecha de nacimiento es obligatoria.';
}

// Validar FECHA DEL EVENTO
if (empty($fecha_evento)) {
    $errors[] = 'La fecha del evento es obligatoria.';
}

// Validar GÉNERO
if (empty($genero)) {
    $errors[] = 'Selecciona un género.';
}

// Validar TÉRMINOS Y CONDICIONES (debe estar marcado)
if (empty($terminos)) {
    $errors[] = 'Debes aceptar los términos y condiciones.';
}

// MANEJO DE ARCHIVO SUBIDO
// =========================
/* $_FILES: superglobal que contiene información sobre archivos subidos
   Estructura:
   $_FILES['archivo']['name']     - Nombre original del archivo
   $_FILES['archivo']['type']     - MIME type (application/pdf, image/jpeg, etc.)
   $_FILES['archivo']['tmp_name'] - Ruta temporal donde PHP guardó el archivo
   $_FILES['archivo']['error']    - Código de error (0 = éxito)
   $_FILES['archivo']['size']     - Tamaño en bytes
*/

$archivo_info = null;  // Variable para almacenar info del archivo subido (si lo hay)

/* Condiciones para procesar el archivo:
   1. !empty($_FILES['archivo']): Usuario intentó subir algo
   2. $_FILES['archivo']['error'] === UPLOAD_ERR_OK: No hubo error (UPLOAD_ERR_OK = 0)
*/
if (!empty($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    // El archivo se subió correctamente, procesamoslo
    
    $tmp = $_FILES['archivo']['tmp_name'];          // Ruta temp del archivo
    /* basename(): Extrae solo el nombre del archivo (sin ruta)
       Ejemplo: basename("/tmp/php123abc") devuelve "php123abc"
       Pero si pasamos el nombre original: basename("documento.pdf") devuelve "documento.pdf"
    */
    $name = basename($_FILES['archivo']['name']);   // Nombre original
    $size = $_FILES['archivo']['size'];             // Tamaño en bytes
    
    // Definir carpeta de destino
    /* __DIR__: constante PHP que devuelve la ruta absoluta del directorio actual
       Ejemplo: /var/www/html/proyecto/
       Usamos . para concatenar strings
    */
    $upload_dir = __DIR__ . '/uploads';
    
    /* is_dir($upload_dir): revisa si la carpeta ya existe
       Si NO existe (primera vez), crearla
    */
    if (!is_dir($upload_dir)) {
        /* mkdir(ruta, permisos, recursivo):
           - ruta: donde crear la carpeta
           - 0755: permisos (propietario puede leer/escribir/ejecutar, otros solo leer/ejecutar)
           - true: crear directorios padres si no existen (recursivo)
        */
        mkdir($upload_dir, 0755, true);
    }
    
    // Crear nombre único para el archivo
    /* time(): devuelve timestamp actual (segundos desde 1970)
       Ejemplo: 1731705600
       Concatenamos: timestamp + underscore + nombre original
       Resultado: "1731705600_documento.pdf"
       Esto evita que dos usuarios que suben "documento.pdf" se sobrescriban
    */
    $target = $upload_dir . '/' . time() . '_' . $name;
    
    /* move_uploaded_file(tmp, destino):
       Función especial de PHP para mover archivos subidos de temp a destino.
       - Retorna true si logra mover el archivo
       - Retorna false si hay error (permisos, disco lleno, etc.)
       NOTA: No es lo mismo que rename(). Esta función valida que sea archivo subido.
    */
    if (move_uploaded_file($tmp, $target)) {
        // Éxito: guardar info del archivo en array
        $archivo_info = [
            'original_name' => $name,      // Nombre que subió el usuario
            'size' => $size,               // Tamaño en bytes
            'path' => $target              // Ruta donde lo guardamos
        ];
    } else {
        // Error al mover: agregar a lista de errores
        $errors[] = 'Error al subir el archivo.';
    }
}

/* Fin de la lógica PHP. Ahora empieza el HTML que se muestra al usuario */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Metadatos y Bootstrap CSS -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo de registro</title>
    <!-- Bootstrap CSS para estilizar la página de recibo -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Container principal (Bootstrap: centrado y con márgenes) -->
<div class="container py-4">
    <!-- CONDICIONAL PHP: Si hay errores de validación -->
    /* <?php if (!empty($errors)): ?>
       if: Si la condición es verdadera
       !empty($errors): Retorna true si $errors tiene elementos
       endif: Cierra el if (alternativa a usar llaves {})
       
       Aquí mostramos una alerta roja (alert-danger) con los errores
    */
    <?php if (!empty($errors)): ?>
        <!-- Alert Bootstrap para mostrar errores -->
        /* alert alert-danger: Clases Bootstrap para alerta roja/peligro
           role="alert": Accesibilidad ARIA (indica que es una alerta)
        */
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Errores de validación</h4>
            <!-- Listado de errores -->
            <ul>
                /* foreach: Itera sobre cada error en el array $errors
                   $errors as $err: Asigna cada error a variable $err
                   Como si escribiéramos un for convencional
                */
                <?php foreach ($errors as $err): ?>
                    <li><?php echo safe($err) ?></li>
                    <!-- echo safe($err): 
                         echo: imprime en HTML
                         safe($err): escapa el error para evitar XSS
                         Ejemplo: si $err contiene "<script>", se imprime "&lt;script&gt;"
                    -->
                <?php endforeach; ?>
            </ul>
            <hr>
            <!-- Botón para volver al formulario -->
            <a href="formulario.html" class="btn btn-secondary">Volver al formulario</a>
        </div>
    <!-- CONDICIONAL else: Si NO hay errores (validación pasó) -->
    <?php else: ?>
        <!-- Tarjeta Bootstrap para mostrar recibo -->
        <div class="card">
            <!-- Encabezado de la tarjeta -->
            <div class="card-header">
                <h2>Recibo de registro</h2>
            </div>
            <!-- Cuerpo de la tarjeta con los datos -->
            <div class="card-body">
                <!-- Imprime cada campo con safe() para evitar XSS -->
                <p><strong>Nombre:</strong> <?php echo safe($nombre) ?></p>
                <!-- safe($nombre): 
                     Si $nombre = "Juan <script>", imprime "Juan &lt;script&gt;" (inofensivo)
                -->
                <p><strong>Correo:</strong> <?php echo safe($correo) ?></p>
                <p><strong>Teléfono:</strong> <?php echo safe($telefono) ?></p>
                <p><strong>Fecha de nacimiento:</strong> <?php echo safe($fecha_nacimiento) ?></p>
                <p><strong>Género:</strong> <?php echo safe($genero) ?></p>
                <hr> <!-- Línea separadora -->
                <p><strong>Fecha del evento:</strong> <?php echo safe($fecha_evento) ?></p>
                <p><strong>Tipo de entrada:</strong> <?php echo safe($tipo_entrada) ?></p>
                <!-- Preferencias de comida (array) -->
                <p><strong>Preferencias de comida:</strong> 
                    <?php 
                    // implode(separador, array): Une elementos del array en un string
                    // Ejemplo: ['vegano', 'sin_gluten'] → "vegano, sin_gluten"
                    // (array)$preferencia_comida: Convierte a array si no lo es (seguridad)
                    echo safe(implode(', ', (array)$preferencia_comida)) 
                    ?>
                </p>
                <hr>
                <p><strong>Nombre de usuario:</strong> <?php echo safe($nombre_usuario) ?></p>
                <!-- Notificaciones: mostrar "Sí" o "No" según el valor -->
                <p><strong>Notificaciones:</strong> 
                    <?php 
                    // Condicional ternario: condición ? valor_si_true : valor_si_false
                    echo ($notificaciones === 'si') ? 'Sí' : 'No' 
                    ?>
                </p>
                <p><strong>Calificación eventos anteriores:</strong> <?php echo safe($calificacion_eventos) ?></p>
                <!-- Comentarios: usar nl2br para convertir saltos de línea a <br> -->
                <p><strong>Comentarios:</strong> 
                    <?php 
                    // nl2br(): Convierte \n (saltos de línea) a etiqueta HTML <br>
                    // Mantiene el formato que escribió el usuario en el textarea
                    echo nl2br(safe($comentarios_adicionales)) 
                    ?>
                </p>
                <!-- Mostrar info del archivo subido SI se subió algo -->
                <?php if ($archivo_info): ?>
                    <!-- if ($archivo_info): true si $archivo_info NO es null/empty -->
                    <hr>
                    <p><strong>Archivo subido:</strong> 
                        <?php echo safe($archivo_info['original_name']) ?> 
                        (<?php echo intval($archivo_info['size']) ?> bytes)
                        <!-- intval(): Convierte a integer (solo números)
                             Esto asegura que no se imprima algo malicioso en el size
                        -->
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <!-- Botón para registrar otro usuario -->
        <div class="mt-3">
            <a class="btn btn-primary" href="formulario.html">Registrar otro</a>
        </div>
    <?php endif; ?>
    <!-- Fin del condicional de errores -->
</div>
</body>
</html>
