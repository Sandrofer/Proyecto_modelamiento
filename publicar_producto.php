<?php
session_start();
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = "PROD_" . uniqid(); // Generar código único
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
    
    // Procesar la imagen subida
    $imagen_path = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['imagen']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Crear directorio de uploads si no existe
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generar nombre único para la imagen
            $file_extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $file_name = 'producto_' . time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            // Mover el archivo
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $file_path)) {
                $imagen_path = $file_path;
            } else {
                $mensaje = "❌ Error al subir la imagen";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "❌ Formato de imagen no permitido. Use JPG, PNG, GIF o WEBP";
            $tipo_mensaje = "error";
        }
    }
    
    // Solo insertar si no hay errores
    if (!isset($mensaje)) {
        $stmt = $conexion->prepare("INSERT INTO productos (codigo, nombre, descripcion, precio, categoria, ubicacion, tipo, usuario_vendedor, imagen, estado_producto, tipo_servicio, duracion_servicio, horario_atencion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdsssssssss", $codigo, $nombre, $descripcion, $precio, $categoria, $ubicacion, $tipo, $_SESSION['username'], $imagen_path, $estado_producto, $tipo_servicio, $duracion_servicio, $horario_atencion);
        
        if ($stmt->execute()) {
            $mensaje = "✅ Producto publicado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "❌ Error al publicar: " . $stmt->error;
            $tipo_mensaje = "error";
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar Producto - UTASHOP</title>
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
                <a href="pagina_principal.php" class="hover:text-gray-300">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
                <span class="text-gray-300">Hola, <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Publicar Producto o Servicio</h1>
            <p class="text-gray-600 mb-8">Completa la información de tu publicación</p>

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
                            <input type="radio" name="tipo" value="producto" checked class="mr-2">
                            <span class="text-gray-700"> Producto</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="tipo" value="servicio" class="mr-2">
                            <span class="text-gray-700"> Servicio</span>
                        </label>
                    </div>
                </div>

                <!-- Información básica -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="nombre" class="block text-gray-700 font-semibold mb-2">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Ej: iPhone 13 Pro o Clases de Guitarra">
                    </div>

                    <div>
                        <label for="precio" class="block text-gray-700 font-semibold mb-2">Precio ($) *</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" required
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
                            <option value="Tecnología"> Tecnología</option>
                            <option value="Ropa"> Ropa y Accesorios</option>
                            <option value="Hogar"> Hogar</option>
                            <option value="Deportes"> Deportes</option>
                            <option value="Libros">Libros</option>
                            <option value="Electrodomésticos"> Electrodomésticos</option>
                            <option value="Muebles"> Muebles</option>
                            <option value="Servicios"> Servicios</option>
                            <option value="Vehiculos"> Vehículos</option>
                            <option value="Inmuebles"> Inmuebles</option>
                            <option value="Juguetes"> Juguetes</option>
                            <option value="Belleza"> Belleza y Cuidado Personal</option>
                        </select>
                    </div>

                    <div id="campo-estado">
                        <label for="estado_producto" class="block text-gray-700 font-semibold mb-2">Estado *</label>
                        <select id="estado_producto" name="estado_producto" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="nuevo"> Nuevo</option>
                            <option value="como nuevo"> Como nuevo</option>
                            <option value="usado"> Usado</option>
                            <option value="muy usado"> Muy usado</option>
                        </select>
                    </div>
                </div>

                <!-- Campos específicos para SERVICIOS -->
                <div id="campos-servicio" class="hidden mb-6 transition-all">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4 rounded">
                        <p class="text-blue-700 font-semibold"><i class="fas fa-info-circle mr-2"></i>Información específica del servicio</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label for="tipo_servicio" class="block text-gray-700 font-semibold mb-2">Tipo de servicio *</label>
                            <select id="tipo_servicio" name="tipo_servicio"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecciona el tipo</option>
                                <option value="Reparación"> Reparación y Mantenimiento</option>
                                <option value="Clases"> Clases y Tutorías</option>
                                <option value="Diseño"> Diseño Gráfico</option>
                                <option value="Programacion"> Programación y TI</option>
                                <option value="Limpieza"> Limpieza</option>
                                <option value="Transporte"> Transporte</option>
                                <option value="Eventos"> Organización de Eventos</option>
                                <option value="Salud"> Salud y Bienestar</option>
                                <option value="Construccion"> Construcción y Remodelación</option>
                                <option value="Otro"> Otro servicio</option>
                            </select>
                        </div>

                        <div>
                            <label for="duracion_servicio" class="block text-gray-700 font-semibold mb-2">Duración estimada</label>
                            <select id="duracion_servicio" name="duracion_servicio"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecciona duración</option>
                                <option value="1 hora"> 1 hora</option>
                                <option value="2 horas"> 2 horas</option>
                                <option value="Medio día"> Medio día (4 horas)</option>
                                <option value="Día completo"> Día completo (8 horas)</option>
                                <option value="Por proyecto"> Por proyecto</option>
                                <option value="Personalizado"> Personalizado</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Horario de atención *</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-600 text-sm mb-2">Días de atención</label>
                                <div class="flex flex-wrap gap-2">
                                    <label class="flex items-center bg-gray-100 px-3 py-2 rounded-lg">
                                        <input type="checkbox" name="dias_atencion[]" value="Lunes" class="mr-2">
                                        <span class="text-sm">Lun</span>
                                    </label>
                                    <label class="flex items-center bg-gray-100 px-3 py-2 rounded-lg">
                                        <input type="checkbox" name="dias_atencion[]" value="Martes" class="mr-2">
                                        <span class="text-sm">Mar</span>
                                    </label>
                                    <label class="flex items-center bg-gray-100 px-3 py-2 rounded-lg">
                                        <input type="checkbox" name="dias_atencion[]" value="Miércoles" class="mr-2">
                                        <span class="text-sm">Mié</span>
                                    </label>
                                    <label class="flex items-center bg-gray-100 px-3 py-2 rounded-lg">
                                        <input type="checkbox" name="dias_atencion[]" value="Jueves" class="mr-2">
                                        <span class="text-sm">Jue</span>
                                    </label>
                                    <label class="flex items-center bg-gray-100 px-3 py-2 rounded-lg">
                                        <input type="checkbox" name="dias_atencion[]" value="Viernes" class="mr-2">
                                        <span class="text-sm">Vie</span>
                                    </label>
                                    <label class="flex items-center bg-gray-100 px-3 py-2 rounded-lg">
                                        <input type="checkbox" name="dias_atencion[]" value="Sábado" class="mr-2">
                                        <span class="text-sm">Sáb</span>
                                    </label>
                                    <label class="flex items-center bg-gray-100 px-3 py-2 rounded-lg">
                                        <input type="checkbox" name="dias_atencion[]" value="Domingo" class="mr-2">
                                        <span class="text-sm">Dom</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-gray-600 text-sm mb-2">Horario</label>
                                <div class="flex items-center gap-2">
                                    <input type="time" name="hora_inicio" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <span class="text-gray-500">a</span>
                                    <input type="time" name="hora_fin"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ubicación -->
                <div class="mb-6">
                    <label for="ubicacion" class="block text-gray-700 font-semibold mb-2">Ubicación</label>
                    <input type="text" id="ubicacion" name="ubicacion"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Ej: Quito, Ecuador">
                </div>

                <!-- Descripción -->
                <div class="mb-6">
                    <label for="descripcion" class="block text-gray-700 font-semibold mb-2">Descripción *</label>
                    <textarea id="descripcion" name="descripcion" required rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Describe tu producto o servicio de manera detallada..."></textarea>
                </div>

                <!-- Imagen con arrastre -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Imagen del producto/servicio</label>
                    
                    <div class="drop-zone" id="dropZone">
                        <input type="file" id="imagen" name="imagen" accept="image/*" class="hidden">
                        <div class="text-center">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                            <p class="text-gray-600 font-semibold">Arrastra y suelta tu imagen aquí</p>
                            <p class="text-gray-500 text-sm mt-1">o haz clic para seleccionar</p>
                            <p class="text-gray-400 text-xs mt-2">Formatos: JPG, PNG, GIF, WEBP (Máx. 10MB)</p>
                        </div>
                    </div>
                    
                    <div id="previewContainer" class="hidden mt-4">
                        <p class="text-sm text-gray-600 mb-2">Vista previa:</p>
                        <img id="previewImage" class="preview-image mx-auto">
                        <button type="button" id="removeImage" class="mt-2 bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                            <i class="fas fa-times mr-1"></i>Eliminar imagen
                        </button>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex gap-4">
                    <button type="submit" 
                            class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                        <i class="fas fa-plus-circle mr-2"></i>Publicar
                    </button>
                    <a href="pagina_principal.php" 
                       class="bg-gray-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                        Cancelar
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
                        <option value="disponible"> Disponible</option>
                        <option value="ocupado"> Ocupado</option>
                        <option value="vacaciones"> En vacaciones</option>
                    `;
                } else {
                    camposServicio.classList.add('hidden');
                    campoEstado.querySelector('label').textContent = 'Estado del producto *';
                    campoEstado.querySelector('select').innerHTML = `
                        <option value="nuevo"> Nuevo</option>
                        <option value="como nuevo"> Como nuevo</option>
                        <option value="usado"> Usado</option>
                        <option value="muy usado"> Muy usado</option>
                    `;
                }
            }
            
            // Funcionalidad para subida de imágenes con arrastre
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('imagen');
            const previewContainer = document.getElementById('previewContainer');
            const previewImage = document.getElementById('previewImage');
            const removeButton = document.getElementById('removeImage');
            
            // Click en la zona de drop
            dropZone.addEventListener('click', () => {
                fileInput.click();
            });
            
            // Cambio en el input de archivo
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        previewContainer.classList.remove('hidden');
                    }
                    
                    reader.readAsDataURL(file);
                }
            });
            
            // Arrastrar y soltar
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                dropZone.classList.add('dragover');
            }
            
            function unhighlight() {
                dropZone.classList.remove('dragover');
            }
            
            dropZone.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length) {
                    fileInput.files = files;
                    const event = new Event('change');
                    fileInput.dispatchEvent(event);
                }
            }
            
            // Eliminar imagen
            removeButton.addEventListener('click', function() {
                fileInput.value = '';
                previewContainer.classList.add('hidden');
            });
            
            // Ejecutar al cargar y cuando cambie la selección
            toggleCamposServicio();
            tipoRadios.forEach(radio => {
                radio.addEventListener('change', toggleCamposServicio);
            });
        });
    </script>
</body>
</html>
<?php $conexion->close(); ?>