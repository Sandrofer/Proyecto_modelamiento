 <?php
session_start();
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}

// Obtener el ID del producto a editar
$producto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verificar que el producto existe y pertenece al usuario
$stmt = $conexion->prepare("SELECT * FROM productos WHERE id = ? AND usuario_vendedor = ?");
$stmt->bind_param("is", $producto_id, $_SESSION['username']);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("<script>alert('Producto no encontrado o no tienes permisos para editarlo'); window.location.href='mis_productos.php';</script>");
}

$producto = $resultado->fetch_assoc();
$stmt->close();

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $descripcion = $conexion->real_escape_string($_POST['descripcion']);
    $precio = $_POST['precio'];
    $categoria = $conexion->real_escape_string($_POST['categoria']);
    $ubicacion = $conexion->real_escape_string($_POST['ubicacion']);
    $tipo = $_POST['tipo'];
    $estado_producto = $conexion->real_escape_string($_POST['estado_producto']);
    
    // Campos específicos para servicios
    $tipo_servicio = isset($_POST['tipo_servicio']) ? $conexion->real_escape_string($_POST['tipo_servicio']) : '';
    $duracion_servicio = isset($_POST['duracion_servicio']) ? $conexion->real_escape_string($_POST['duracion_servicio']) : '';
    
    // Procesar horario de atención
    $dias_atencion = isset($_POST['dias_atencion']) ? implode(', ', $_POST['dias_atencion']) : '';
    $hora_inicio = isset($_POST['hora_inicio']) ? $_POST['hora_inicio'] : '';
    $hora_fin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : '';
    
    $horario_atencion = '';
    if ($dias_atencion && $hora_inicio && $hora_fin) {
        $horario_atencion = $dias_atencion . ' de ' . $hora_inicio . ' a ' . $hora_fin;
    }
    
    // Procesar nueva imagen si se subió
    $imagen_path = $producto['imagen']; // Mantener la imagen actual por defecto
    
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['imagen']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $file_name = 'producto_' . time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $file_path)) {
                $imagen_path = $file_path;
                // Opcional: eliminar la imagen anterior
            }
        }
    }
    
    // Actualizar el producto
    $update_stmt = $conexion->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, categoria = ?, ubicacion = ?, tipo = ?, imagen = ?, estado_producto = ?, tipo_servicio = ?, duracion_servicio = ?, horario_atencion = ? WHERE id = ? AND usuario_vendedor = ?");
    $update_stmt->bind_param("ssdssssssssis", $nombre, $descripcion, $precio, $categoria, $ubicacion, $tipo, $imagen_path, $estado_producto, $tipo_servicio, $duracion_servicio, $horario_atencion, $producto_id, $_SESSION['username']);
    
    if ($update_stmt->execute()) {
        $mensaje = "✅ Producto actualizado correctamente";
        $tipo_mensaje = "success";
        // Actualizar los datos locales
        $producto['nombre'] = $nombre;
        $producto['descripcion'] = $descripcion;
        $producto['precio'] = $precio;
        $producto['categoria'] = $categoria;
        $producto['ubicacion'] = $ubicacion;
        $producto['tipo'] = $tipo;
        $producto['imagen'] = $imagen_path;
        $producto['estado_producto'] = $estado_producto;
        $producto['tipo_servicio'] = $tipo_servicio;
        $producto['duracion_servicio'] = $duracion_servicio;
        $producto['horario_atencion'] = $horario_atencion;
    } else {
        $mensaje = "❌ Error al actualizar: " . $update_stmt->error;
        $tipo_mensaje = "error";
    }
    
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - UTASHOP</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .hidden {
            display: none;
        }
        .transition-all {
            transition: all 0.3s ease-in-out;
        }
        .drop-zone {
            border: 2px dashed #cbd5e0;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .drop-zone:hover, .drop-zone.dragover {
            border-color: #3b82f6;
            background-color: #f0f9ff;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 0.5rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Barra de navegación -->
    <nav class="bg-gray-900 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="pagina_principal.php" class="text-2xl font-bold">UTASHOP</a>
            <div class="flex items-center space-x-4">
                <a href="mis_productos.php" class="hover:text-gray-300">
                    <i class="fas fa-arrow-left mr-2"></i>Volver a Mis Productos
                </a>
                <span class="text-gray-300">Editando: <?php echo htmlspecialchars($producto['nombre']); ?></span>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Editar Producto</h1>
            <p class="text-gray-600 mb-8">Modifica la información de tu publicación</p>

            <?php if (isset($mensaje)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $tipo_mensaje == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-md p-6">
                <!-- Tipo de publicación -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">¿Qué quieres publicar? *</label>
                    <div class="flex gap-4">
                        <label class="flex items-center">
                            <input type="radio" name="tipo" value="producto" <?php echo $producto['tipo'] == 'producto' ? 'checked' : ''; ?> class="mr-2">
                            <span class="text-gray-700">📦 Producto</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="tipo" value="servicio" <?php echo $producto['tipo'] == 'servicio' ? 'checked' : ''; ?> class="mr-2">
                            <span class="text-gray-700">🛠️ Servicio</span>
                        </label>
                    </div>
                </div>

                <!-- Información básica -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="nombre" class="block text-gray-700 font-semibold mb-2">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" required 
                               value="<?php echo htmlspecialchars($producto['nombre']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Ej: iPhone 13 Pro o Clases de Guitarra">
                    </div>

                    <div>
                        <label for="precio" class="block text-gray-700 font-semibold mb-2">Precio ($) *</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" required
                               value="<?php echo htmlspecialchars($producto['precio']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Ej: 299.99">
                    </div>
                </div>

                <!-- Categoría y Estado -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="categoria" class="block text-gray-700 font-semibold mb-2">Categoría *</label>
                        <select id="categoria" name="categoria" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Selecciona una categoría</option>
                            <?php
                            $categorias = [
                                'Tecnología', 'Ropa', 'Hogar', 'Deportes', 'Libros', 
                                'Electrodomésticos', 'Muebles', 'Servicios', 'Vehiculos', 
                                'Inmuebles', 'Juguetes', 'Belleza'
                            ];
                            foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $producto['categoria'] == $cat ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="campo-estado">
                        <label for="estado_producto" class="block text-gray-700 font-semibold mb-2">Estado *</label>
                        <select id="estado_producto" name="estado_producto" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php if ($producto['tipo'] == 'servicio'): ?>
                                <option value="disponible" <?php echo $producto['estado_producto'] == 'disponible' ? 'selected' : ''; ?>>✅ Disponible</option>
                                <option value="ocupado" <?php echo $producto['estado_producto'] == 'ocupado' ? 'selected' : ''; ?>>⏳ Ocupado</option>
                                <option value="vacaciones" <?php echo $producto['estado_producto'] == 'vacaciones' ? 'selected' : ''; ?>>🏖️ En vacaciones</option>
                            <?php else: ?>
                                <option value="nuevo" <?php echo $producto['estado_producto'] == 'nuevo' ? 'selected' : ''; ?>>🆕 Nuevo</option>
                                <option value="como nuevo" <?php echo $producto['estado_producto'] == 'como nuevo' ? 'selected' : ''; ?>>✨ Como nuevo</option>
                                <option value="usado" <?php echo $producto['estado_producto'] == 'usado' ? 'selected' : ''; ?>>🔄 Usado</option>
                                <option value="muy usado" <?php echo $producto['estado_producto'] == 'muy usado' ? 'selected' : ''; ?>>⏳ Muy usado</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <!-- Campos específicos para SERVICIOS -->
                <div id="campos-servicio" class="<?php echo $producto['tipo'] == 'servicio' ? '' : 'hidden'; ?> mb-6 transition-all">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4 rounded">
                        <p class="text-blue-700 font-semibold"><i class="fas fa-info-circle mr-2"></i>Información específica del servicio</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label for="tipo_servicio" class="block text-gray-700 font-semibold mb-2">Tipo de servicio</label>
                            <select id="tipo_servicio" name="tipo_servicio"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecciona el tipo</option>
                                <?php
                                $tipos_servicio = [
                                    'Reparación' => '🔧 Reparación y Mantenimiento',
                                    'Clases' => '🎓 Clases y Tutorías',
                                    'Diseño' => '🎨 Diseño Gráfico',
                                    'Programacion' => '💻 Programación y TI',
                                    'Limpieza' => '🧹 Limpieza',
                                    'Transporte' => '🚗 Transporte',
                                    'Eventos' => '🎉 Organización de Eventos',
                                    'Salud' => '🏥 Salud y Bienestar',
                                    'Construccion' => '🏗️ Construcción y Remodelación',
                                    'Otro' => '🔍 Otro servicio'
                                ];
                                foreach ($tipos_servicio as $valor => $texto): ?>
                                    <option value="<?php echo $valor; ?>" <?php echo $producto['tipo_servicio'] == $valor ? 'selected' : ''; ?>>
                                        <?php echo $texto; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="duracion_servicio" class="block text-gray-700 font-semibold mb-2">Duración estimada</label>
                            <select id="duracion_servicio" name="duracion_servicio"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecciona duración</option>
                                <?php
                                $duraciones = [
                                    '1 hora' => '⏱️ 1 hora',
                                    '2 horas' => '⏱️ 2 horas',
                                    'Medio día' => '⏱️ Medio día (4 horas)',
                                    'Día completo' => '⏱️ Día completo (8 horas)',
                                    'Por proyecto' => '📦 Por proyecto',
                                    'Personalizado' => '⚙️ Personalizado'
                                ];
                                foreach ($duraciones as $valor => $texto): ?>
                                    <option value="<?php echo $valor; ?>" <?php echo $producto['duracion_servicio'] == $valor ? 'selected' : ''; ?>>
                                        <?php echo $texto; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Horario de atención (simplificado para edición) -->
                    <div class="mb-4">
                        <label for="horario_atencion" class="block text-gray-700 font-semibold mb-2">Horario de atención</label>
                        <input type="text" id="horario_atencion" name="horario_atencion"
                               value="<?php echo htmlspecialchars($producto['horario_atencion']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Ej: Lunes a Viernes de 9:00 a 18:00">
                    </div>
                </div>

                <!-- Ubicación -->
                <div class="mb-6">
                    <label for="ubicacion" class="block text-gray-700 font-semibold mb-2">Ubicación</label>
                    <input type="text" id="ubicacion" name="ubicacion"
                           value="<?php echo htmlspecialchars($producto['ubicacion']); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Ej: Quito, Ecuador">
                </div>

                <!-- Descripción -->
                <div class="mb-6">
                    <label for="descripcion" class="block text-gray-700 font-semibold mb-2">Descripción *</label>
                    <textarea id="descripcion" name="descripcion" required rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Describe tu producto o servicio de manera detallada..."><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                </div>

                <!-- Imagen actual -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Imagen actual</label>
                    <?php if ($producto['imagen']): ?>
                        <div class="flex items-center gap-4 mb-4">
                            <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                 class="w-32 h-32 object-cover rounded-lg">
                            <div>
                                <p class="text-sm text-gray-600">Imagen actual del producto</p>
                                <p class="text-xs text-gray-500">Puedes cambiarla subiendo una nueva imagen</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 italic">No hay imagen actual</p>
                    <?php endif; ?>
                </div>

                <!-- Nueva imagen -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Cambiar imagen (opcional)</label>
                    
                    <div class="drop-zone" id="dropZone">
                        <input type="file" id="imagen" name="imagen" accept="image/*" class="hidden">
                        <div class="text-center">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                            <p class="text-gray-600 font-semibold">Arrastra y suelta una nueva imagen aquí</p>
                            <p class="text-gray-500 text-sm mt-1">o haz clic para seleccionar</p>
                            <p class="text-gray-400 text-xs mt-2">Formatos: JPG, PNG, GIF, WEBP (Máx. 10MB)</p>
                        </div>
                    </div>
                    
                    <div id="previewContainer" class="hidden mt-4">
                        <p class="text-sm text-gray-600 mb-2">Vista previa de la nueva imagen:</p>
                        <img id="previewImage" class="preview-image mx-auto">
                        <button type="button" id="removeImage" class="mt-2 bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                            <i class="fas fa-times mr-1"></i>Cancelar cambio
                        </button>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex gap-4">
                    <button type="submit" 
                            class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                    </button>
                    <a href="mis_productos.php" 
                       class="bg-gray-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                        Cancelar
                    </a>
                    <a href="?id=<?php echo $producto_id; ?>&eliminar=true" 
                       class="bg-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition duration-300"
                       onclick="return confirm('¿Estás seguro de que quieres eliminar este producto?')">
                        <i class="fas fa-trash mr-2"></i>Eliminar
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tipoRadios = document.querySelectorAll('input[name="tipo"]');
            const camposServicio = document.getElementById('campos-servicio');
            const campoEstado = document.getElementById('campo-estado');
            
            // Función para mostrar/ocultar campos de servicio
            function toggleCamposServicio() {
                const tipoSeleccionado = document.querySelector('input[name="tipo"]:checked').value;
                
                if (tipoSeleccionado === 'servicio') {
                    camposServicio.classList.remove('hidden');
                    campoEstado.querySelector('label').textContent = 'Estado del servicio *';
                    campoEstado.querySelector('select').innerHTML = `
                        <option value="disponible" <?php echo $producto['estado_producto'] == 'disponible' ? 'selected' : ''; ?>>✅ Disponible</option>
                        <option value="ocupado" <?php echo $producto['estado_producto'] == 'ocupado' ? 'selected' : ''; ?>>⏳ Ocupado</option>
                        <option value="vacaciones" <?php echo $producto['estado_producto'] == 'vacaciones' ? 'selected' : ''; ?>>🏖️ En vacaciones</option>
                    `;
                } else {
                    camposServicio.classList.add('hidden');
                    campoEstado.querySelector('label').textContent = 'Estado del producto *';
                    campoEstado.querySelector('select').innerHTML = `
                        <option value="nuevo" <?php echo $producto['estado_producto'] == 'nuevo' ? 'selected' : ''; ?>>🆕 Nuevo</option>
                        <option value="como nuevo" <?php echo $producto['estado_producto'] == 'como nuevo' ? 'selected' : ''; ?>>✨ Como nuevo</option>
                        <option value="usado" <?php echo $producto['estado_producto'] == 'usado' ? 'selected' : ''; ?>>🔄 Usado</option>
                        <option value="muy usado" <?php echo $producto['estado_producto'] == 'muy usado' ? 'selected' : ''; ?>>⏳ Muy usado</option>
                    `;
                }
            }
            
            // Funcionalidad para subida de imágenes
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('imagen');
            const previewContainer = document.getElementById('previewContainer');
            const previewImage = document.getElementById('previewImage');
            const removeButton = document.getElementById('removeImage');
            
            dropZone.addEventListener('click', () => fileInput.click());
            
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        previewContainer.classList.remove('hidden');
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });
            
            removeButton.addEventListener('click', function() {
                fileInput.value = '';
                previewContainer.classList.add('hidden');
            });
            
            // Ejecutar al cargar
            toggleCamposServicio();
            tipoRadios.forEach(radio => {
                radio.addEventListener('change', toggleCamposServicio);
            });
        });
    </script>
</body>
</html>
<?php $conexion->close(); ?>