<?php
session_start();
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}

// Obtener información del usuario actual
$nombre_usuario = $_SESSION['usuario_id'];
$sql_usuario = "SELECT * FROM usuario WHERE nombre_usuario = ?";
$stmt = $conexion->prepare($sql_usuario);
$stmt->bind_param("s", $nombre_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
$stmt->close();

// Si no se encuentra el usuario, redirigir
if (!$usuario) {
    echo "Error: No se encontró información del usuario. ";
    echo "Nombre de usuario: " . htmlspecialchars($nombre_usuario);
    exit();
}

// Procesar actualización de foto de perfil
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Determinar qué tipo de actualización se está haciendo
    if (isset($_POST['cambiar_contrasena'])) {
        // CAMBIO DE CONTRASEÑA
        $contrasena_actual = trim($_POST['contrasena_actual']);
        $nueva_contrasena = trim($_POST['nueva_contrasena']);
        $confirmar_contrasena = trim($_POST['confirmar_contrasena']);
        
        // Validaciones de contraseña (las mismas que en registro)
        $errores_contrasena = [];
        
        if (empty($contrasena_actual) || empty($nueva_contrasena) || empty($confirmar_contrasena)) {
            $errores_contrasena[] = "Todos los campos de contraseña son obligatorios.";
        } elseif (!password_verify($contrasena_actual, $usuario['contraseña'])) {
            $errores_contrasena[] = "La contraseña actual es incorrecta.";
        } elseif ($nueva_contrasena !== $confirmar_contrasena) {
            $errores_contrasena[] = "Las nuevas contraseñas no coinciden.";
        } else {
            // Validar requisitos de la nueva contraseña
            if (strlen($nueva_contrasena) < 8) {
                $errores_contrasena[] = "La contraseña debe tener al menos 8 caracteres.";
            }
            if (!preg_match('/[A-Z]/', $nueva_contrasena)) {
                $errores_contrasena[] = "La contraseña debe contener al menos una letra mayúscula.";
            }
            if (!preg_match('/[a-z]/', $nueva_contrasena)) {
                $errores_contrasena[] = "La contraseña debe contener al menos una letra minúscula.";
            }
            if (!preg_match('/\d/', $nueva_contrasena)) {
                $errores_contrasena[] = "La contraseña debe contener al menos un número.";
            }
            if (!preg_match('/[^A-Za-z0-9]/', $nueva_contrasena)) {
                $errores_contrasena[] = "La contraseña debe contener al menos un carácter especial.";
            }
        }
        
        if (empty($errores_contrasena)) {
            // Cambiar contraseña
            $nueva_contrasena_hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
            $sql_actualizar_contrasena = "UPDATE usuario SET contraseña = ? WHERE nombre_usuario = ?";
            $stmt = $conexion->prepare($sql_actualizar_contrasena);
            $stmt->bind_param("ss", $nueva_contrasena_hash, $nombre_usuario);
            
            if ($stmt->execute()) {
                $mensaje = "Contraseña actualizada correctamente.";
                $tipo_mensaje = "exito";
            } else {
                $mensaje = "Error al actualizar la contraseña: " . $conexion->error;
                $tipo_mensaje = "error";
            }
            $stmt->close();
        } else {
            $mensaje = implode(" ", $errores_contrasena);
            $tipo_mensaje = "error";
        }
        
    } elseif (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        // SUBIR FOTO DE PERFIL
        $foto = $_FILES['foto_perfil'];
        
        // Validar tipo de archivo
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($foto['type'], $tipos_permitidos)) {
            $mensaje = "Solo se permiten archivos JPG, PNG o GIF.";
            $tipo_mensaje = "error";
        } elseif ($foto['size'] > 5 * 1024 * 1024) { // 5MB máximo
            $mensaje = "La imagen no debe pesar más de 5MB.";
            $tipo_mensaje = "error";
        } else {
            // Crear directorio de uploads si no existe
            $upload_dir = 'uploads/perfiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generar nombre único para la imagen
            $extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
            $nombre_archivo = $nombre_usuario . '_' . time() . '.' . $extension;
            $ruta_archivo = $upload_dir . $nombre_archivo;
            
            if (move_uploaded_file($foto['tmp_name'], $ruta_archivo)) {
                // Verificar si la columna existe antes de actualizar
                $column_check = $conexion->query("SHOW COLUMNS FROM usuario LIKE 'foto_perfil'");
                if ($column_check->num_rows > 0) {
                    // Actualizar en la base de datos
                    $sql_actualizar_foto = "UPDATE usuario SET foto_perfil = ? WHERE nombre_usuario = ?";
                    $stmt = $conexion->prepare($sql_actualizar_foto);
                    $stmt->bind_param("ss", $ruta_archivo, $nombre_usuario);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Foto de perfil actualizada correctamente.";
                        $tipo_mensaje = "exito";
                        // Recargar datos del usuario
                        $sql_usuario = "SELECT * FROM usuario WHERE nombre_usuario = ?";
                        $stmt = $conexion->prepare($sql_usuario);
                        $stmt->bind_param("s", $nombre_usuario);
                        $stmt->execute();
                        $resultado = $stmt->get_result();
                        $usuario = $resultado->fetch_assoc();
                    } else {
                        $mensaje = "Error al guardar la foto en la base de datos.";
                        $tipo_mensaje = "error";
                    }
                    $stmt->close();
                } else {
                    $mensaje = "Error: La columna para foto de perfil no existe en la base de datos.";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "Error al subir la imagen.";
                $tipo_mensaje = "error";
            }
        }
    }
}

// Obtener estadísticas del usuario
$sql_estadisticas = "SELECT 
    COUNT(*) as total_productos,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as productos_activos
    FROM productos WHERE usuario_vendedor = ?";
$stmt = $conexion->prepare($sql_estadisticas);
$stmt->bind_param("s", $nombre_usuario);
$stmt->execute();
$estadisticas = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - UTASHOP</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .profile-card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .profile-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
        }
        .mensaje-exito {
            background-color: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
        }
        .mensaje-error {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #7f1d1d;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .tab-button {
            transition: all 0.3s ease;
        }
        .tab-button.active {
            background-color: #3B82F6;
            color: white;
        }
        .requisito {
            display: flex;
            align-items: center;
            margin: 5px 0;
            font-size: 13px;
            color: #666;
        }
        .requisito i {
            margin-right: 8px;
            font-size: 10px;
        }
        .requisito.cumplido {
            color: #38a169;
        }
        .requisito.cumplido i {
            color: #38a169;
        }
        .requisito.no-cumplido {
            color: #e53e3e;
        }
        .requisito.no-cumplido i {
            color: #e53e3e;
        }
        .foto-perfil {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .foto-perfil:hover {
            border-color: #3b82f6;
            transform: scale(1.05);
        }
        #foto-perfil-input {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Barra de navegación -->
    <nav class="bg-gray-900 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="pagina_principal.php" class="text-2xl font-bold">UTASHOP</a>
            </div>
            
            <div class="flex-1 max-w-xl mx-4">
                <div class="relative">
                    <input type="text" placeholder="Buscar productos..." class="w-full px-4 py-2 rounded-full text-gray-800 focus:outline-none">
                    <button class="absolute right-3 top-2 text-gray-500">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="flex items-center space-x-6">
                <a href="publicar_producto.php" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded-full font-semibold text-white">
                    <i class="fas fa-plus-circle mr-2"></i>VENDER
                </a>
                <a href="mis_productos.php" class="hover:text-gray-300">
                    <i class="fas fa-store mr-2"></i>Mis Productos
                </a>
                
                <a href="perfil.php" class="hover:text-gray-300 font-semibold text-blue-300">
                    <i class="fas fa-user"></i> 
                    <?php 
                    if (isset($_SESSION['nombre'])) {
                        echo htmlspecialchars($_SESSION['nombre']);
                    } else if (isset($usuario['nombre'])) {
                        echo htmlspecialchars($usuario['nombre']);
                    } else {
                        echo "Usuario";
                    }
                    ?>
                </a>
                
                <a href="productos_guardados.php" class="hover:text-gray-300">
                    <i class="fas fa-heart"></i> Guardados
                </a>
                
                <a href="#" class="carrito-icon hover:text-gray-300">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span class="carrito-contador">0</span>
                </a>
                
                <a href="cerrar_sesion.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-md">
                    Cerrar sesión
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="container mx-auto px-4 py-8">
        <!-- Mensajes -->
        <?php if (!empty($mensaje)): ?>
            <div class="<?php echo $tipo_mensaje == 'exito' ? 'mensaje-exito' : 'mensaje-error'; ?> p-4 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fas <?php echo $tipo_mensaje == 'exito' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Columna izquierda - Información del perfil -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl profile-card p-6">
                    <!-- Pestañas -->
                    <div class="flex border-b border-gray-200 mb-6">
                        <button class="tab-button active py-2 px-4 font-medium text-gray-600 hover:text-blue-600 transition-colors" data-tab="info-perfil">
                            Información Personal
                        </button>
                        <button class="tab-button py-2 px-4 font-medium text-gray-600 hover:text-blue-600 transition-colors" data-tab="cambiar-contrasena">
                            Cambiar Contraseña
                        </button>
                        <button class="tab-button py-2 px-4 font-medium text-red-600 hover:text-red-800 transition-colors" data-tab="desactivar-cuenta">
                            <i class="fas fa-user-slash mr-1"></i>Desactivar Cuenta
                        </button>
                    </div>

                    <!-- Información Personal -->
                    <div id="info-perfil" class="tab-content active">
                        <!-- Foto de perfil -->
                        <div class="flex flex-col items-center mb-6">
                            <form id="form-foto-perfil" method="POST" enctype="multipart/form-data">
                                <input type="file" id="foto-perfil-input" name="foto_perfil" accept="image/jpeg,image/png,image/gif">
                                <label for="foto-perfil-input">
                                    <?php if (isset($usuario['foto_perfil']) && !empty($usuario['foto_perfil'])): ?>
                                        <img src="<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" 
                                             alt="Foto de perfil" 
                                             class="foto-perfil">
                                    <?php else: ?>
                                        <div class="foto-perfil bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white text-4xl font-bold">
                                            <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </label>
                                <p class="text-sm text-gray-500 mt-2 text-center">Haz clic en la foto para cambiarla</p>
                            </form>
                        </div>

                        <div class="text-center mb-6">
                            <h1 class="text-2xl font-bold">
                                <?php 
                                if (isset($usuario['nombre']) && isset($usuario['apellido'])) {
                                    echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']);
                                } else {
                                    echo "Usuario no encontrado";
                                }
                                ?>
                            </h1>
                            <p class="text-gray-600">
                                @<?php 
                                if (isset($usuario['nombre_usuario'])) {
                                    echo htmlspecialchars($usuario['nombre_usuario']);
                                } else {
                                    echo "usuario";
                                }
                                ?>
                            </p>
                            <p class="text-gray-600">
                                Miembro desde 
                                <?php 
                                if (isset($usuario['fecha_registro'])) {
                                    echo date('M Y', strtotime($usuario['fecha_registro']));
                                } else {
                                    echo "Fecha no disponible";
                                }
                                ?>
                            </p>
                        </div>

                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                                    <input type="text" value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100" disabled>
                                    <p class="text-xs text-gray-500 mt-1">El nombre no se puede cambiar</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Apellido</label>
                                    <input type="text" value="<?php echo htmlspecialchars($usuario['apellido'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100" disabled>
                                    <p class="text-xs text-gray-500 mt-1">El apellido no se puede cambiar</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Correo Electrónico</label>
                                    <input type="email" value="<?php echo htmlspecialchars($usuario['correo_electronico'] ?? ''); ?>" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100" disabled>
                                    <p class="text-xs text-gray-500 mt-1">El correo electrónico no se puede cambiar</p>
                                </div>
                            </div>

                            <div class="flex gap-4 pt-4">
                                <a href="pagina_principal.php" class="bg-gray-500 hover:bg-gray-600 px-6 py-3 rounded-lg font-semibold text-white">
                                    <i class="fas fa-arrow-left mr-2"></i>Volver al Inicio
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Cambiar Contraseña -->
                    <div id="cambiar-contrasena" class="tab-content">
                        <h3 class="text-xl font-bold mb-6">Cambiar Contraseña</h3>
                        <form method="POST" action="perfil.php" class="space-y-6" id="form-contrasena">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contraseña Actual *</label>
                                <input type="password" name="contrasena_actual" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       placeholder="Ingresa tu contraseña actual" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nueva Contraseña *</label>
                                <input type="password" name="nueva_contrasena" id="nueva_contrasena"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       placeholder="Ingresa tu nueva contraseña" required>
                                
                                <div id="requisitos" style="margin-top: 10px; text-align: left; display: none;">
                                    <div class="requisito no-cumplido" id="req-longitud">
                                        <i class="fas fa-times"></i>
                                        <span>Mínimo 8 caracteres</span>
                                    </div>
                                    <div class="requisito no-cumplido" id="req-mayuscula">
                                        <i class="fas fa-times"></i>
                                        <span>Al menos una mayúscula</span>
                                    </div>
                                    <div class="requisito no-cumplido" id="req-minuscula">
                                        <i class="fas fa-times"></i>
                                        <span>Al menos una minúscula</span>
                                    </div>
                                    <div class="requisito no-cumplido" id="req-numero">
                                        <i class="fas fa-times"></i>
                                        <span>Al menos un número</span>
                                    </div>
                                    <div class="requisito no-cumplido" id="req-especial">
                                        <i class="fas fa-times"></i>
                                        <span>Al menos un carácter especial</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Nueva Contraseña *</label>
                                <input type="password" name="confirmar_contrasena" id="confirmar_contrasena"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       placeholder="Repite tu nueva contraseña" required>
                                <div id="mensaje-coincidencia" class="text-sm mt-2" style="display: none;"></div>
                            </div>

                            <div class="flex gap-4 pt-4">
                                <button type="submit" name="cambiar_contrasena" class="btn-primary px-6 py-3 rounded-lg font-semibold text-white" id="btn-cambiar-contrasena" disabled>
                                    <i class="fas fa-key mr-2"></i>Cambiar Contraseña
                                </button>
                                <button type="button" class="tab-button bg-gray-500 hover:bg-gray-600 px-6 py-3 rounded-lg font-semibold text-white" data-tab="info-perfil">
                                    <i class="fas fa-arrow-left mr-2"></i>Volver
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Desactivar Cuenta -->
                    <div id="desactivar-cuenta" class="tab-content">
                        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-red-400 text-2xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-lg font-medium text-red-800">¿Estás seguro que deseas desactivar tu cuenta?</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>Al desactivar tu cuenta:</p>
                                        <ul class="list-disc pl-5 mt-1 space-y-1">
                                            <li>Tus datos personales se mantendrán en el sistema pero no serán visibles para otros usuarios</li>
                                            <li>Tus productos activos se marcarán como inactivos</li>
                                            <li>No podrás iniciar sesión hasta que un administrador reactive tu cuenta</li>
                                        </ul>
                                    </div>
                                    <div class="mt-4">
                                        <form id="form-desactivar-cuenta" method="POST" action="desactivar_cuenta.php" class="space-y-4">
                                            <div>
                                                <label for="contrasena_actual_desactivar" class="block text-sm font-medium text-gray-700 mb-1">
                                                    Confirma tu contraseña actual para continuar:
                                                </label>
                                                <input type="password" id="contrasena_actual_desactivar" name="contrasena_actual" 
                                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" 
                                                       required>
                                            </div>
                                            <div class="flex items-center">
                                                <input id="confirmar_desactivar" name="confirmar_desactivar" type="checkbox" 
                                                       class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded" required>
                                                <label for="confirmar_desactivar" class="ml-2 block text-sm text-gray-900">
                                                    Entiendo que esta acción no se puede deshacer
                                                </label>
                                            </div>
                                            <div class="mt-4">
                                                <button type="button" id="btn-desactivar-cuenta" 
                                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                    <i class="fas fa-user-slash mr-2"></i>Desactivar mi cuenta permanentemente
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna derecha - Estadísticas y acciones rápidas -->
            <div class="space-y-6">
                <!-- Estadísticas -->
                <div class="bg-white rounded-xl profile-card p-6">
                    <h2 class="text-xl font-bold mb-4">Mis Estadísticas</h2>
                    <div class="space-y-4">
                        <div class="stat-card rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm opacity-90">Productos Activos</p>
                                    <p class="text-2xl font-bold"><?php echo $estadisticas['productos_activos'] ?? 0; ?></p>
                                </div>
                                <i class="fas fa-box-open text-2xl opacity-80"></i>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm opacity-90">Total Publicados</p>
                                    <p class="text-2xl font-bold"><?php echo $estadisticas['total_productos'] ?? 0; ?></p>
                                </div>
                                <i class="fas fa-chart-bar text-2xl opacity-80"></i>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Acciones rápidas -->
                <div class="bg-white rounded-xl profile-card p-6">
                    <h2 class="text-xl font-bold mb-4">Acciones Rápidas</h2>
                    <div class="space-y-3">
                        <a href="publicar_producto.php" class="flex items-center p-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                            <i class="fas fa-plus-circle mr-3"></i>
                            <span>Publicar nuevo producto</span>
                        </a>
                        
                        <a href="mis_productos.php" class="flex items-center p-3 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors">
                            <i class="fas fa-store mr-3"></i>
                            <span>Gestionar mis productos</span>
                        </a>
                        
                        <a href="productos_guardados.php" class="flex items-center p-3 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition-colors">
                            <i class="fas fa-heart mr-3"></i>
                            <span>Productos guardados</span>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <!-- Pie de página -->
    <footer class="bg-gray-900 text-white py-8 mt-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">Sobre Nosotros</h3>
                    <p class="text-gray-400">UTASHOP es tu plataforma segura para comprar y vender productos.</p>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Enlaces Rápidos</h3>
                    <ul class="space-y-2">
                        <li><a href="pagina_principal.php" class="text-gray-400 hover:text-white">Inicio</a></li>
                        <li><a href="publicar_producto.php" class="text-gray-400 hover:text-white">Vender</a></li>
                        <li><a href="perfil.php" class="text-gray-400 hover:text-white">Mi Perfil</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Contacto</h3>
                    <p class="text-gray-400">Email: utashop@gmail.com</p>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 UTASHOP. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        // Sistema de pestañas
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    
                    // Remover clase active de todos los botones y contenidos
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Agregar clase active al botón y contenido seleccionado
                    this.classList.add('active');
                    document.getElementById(targetTab).classList.add('active');
                });
            });
            
            // Efectos de hover para las tarjetas
            const cards = document.querySelectorAll('.profile-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Validación de contraseña en tiempo real
            const passwordInput = document.getElementById('nueva_contrasena');
            const confirmPasswordInput = document.getElementById('confirmar_contrasena');
            const requisitos = document.getElementById('requisitos');
            const mensajeCoincidencia = document.getElementById('mensaje-coincidencia');
            const btnCambiarContrasena = document.getElementById('btn-cambiar-contrasena');

            let passwordValid = false;
            let confirmPasswordValid = false;

            // Validación en tiempo real de la contraseña
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const tieneLongitud = password.length >= 8;
                const tieneMayuscula = /[A-Z]/.test(password);
                const tieneMinuscula = /[a-z]/.test(password);
                const tieneNumero = /\d/.test(password);
                const tieneEspecial = /[^A-Za-z0-9]/.test(password);
                
                // Mostrar/ocultar contenedor de requisitos
                requisitos.style.display = password ? 'block' : 'none';
                
                // Actualizar iconos de requisitos
                actualizarRequisito('longitud', tieneLongitud);
                actualizarRequisito('mayuscula', tieneMayuscula);
                actualizarRequisito('minuscula', tieneMinuscula);
                actualizarRequisito('numero', tieneNumero);
                actualizarRequisito('especial', tieneEspecial);
                
                // Verificar si todos los requisitos se cumplen
                passwordValid = tieneLongitud && tieneMayuscula && tieneMinuscula && tieneNumero && tieneEspecial;
                
                // Validar coincidencia si hay texto en confirmación
                if (confirmPasswordInput.value) {
                    validarCoincidencia(password, confirmPasswordInput.value);
                }
                
                actualizarBoton();
            });
            
            // Función para actualizar el estado de un requisito
            function actualizarRequisito(id, cumple) {
                const elemento = document.getElementById(`req-${id}`);
                const icono = elemento.querySelector('i');
                
                if (cumple) {
                    elemento.classList.remove('no-cumplido');
                    elemento.classList.add('cumplido');
                    icono.classList.remove('fa-times');
                    icono.classList.add('fa-check');
                } else {
                    elemento.classList.remove('cumplido');
                    elemento.classList.add('no-cumplido');
                    icono.classList.remove('fa-check');
                    icono.classList.add('fa-times');
                }
            }
            
            // Validación de coincidencia de contraseñas
            function validarCoincidencia(password, confirmPassword) {
                if (password && confirmPassword) {
                    if (password !== confirmPassword) {
                        mensajeCoincidencia.textContent = 'Las contraseñas no coinciden';
                        mensajeCoincidencia.style.color = '#e53e3e';
                        mensajeCoincidencia.style.display = 'block';
                        confirmPasswordValid = false;
                        return false;
                    } else {
                        mensajeCoincidencia.textContent = 'Las contraseñas coinciden';
                        mensajeCoincidencia.style.color = '#38a169';
                        mensajeCoincidencia.style.display = 'block';
                        confirmPasswordValid = true;
                        return true;
                    }
                } else {
                    mensajeCoincidencia.style.display = 'none';
                    confirmPasswordValid = false;
                    return false;
                }
            }
            
            // Validar confirmación de contraseña en tiempo real
            confirmPasswordInput.addEventListener('input', function() {
                validarCoincidencia(passwordInput.value, this.value);
                actualizarBoton();
            });

            // Actualizar estado del botón
            function actualizarBoton() {
                const contrasenaActual = document.querySelector('input[name="contrasena_actual"]').value;
                
                if (passwordValid && confirmPasswordValid && contrasenaActual) {
                    btnCambiarContrasena.disabled = false;
                } else {
                    btnCambiarContrasena.disabled = true;
                }
            }

            // Validar campo de contraseña actual
            document.querySelector('input[name="contrasena_actual"]').addEventListener('input', actualizarBoton);

            // Subir foto de perfil automáticamente
            document.getElementById('foto-perfil-input').addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    document.getElementById('form-foto-perfil').submit();
                }
            });

            // Confirmación para desactivar cuenta
            document.getElementById('btn-desactivar-cuenta').addEventListener('click', function(e) {
                if (confirm('¿Estás completamente seguro de que deseas desactivar tu cuenta? Esta acción no se puede deshacer.')) {
                    document.getElementById('form-desactivar-cuenta').submit();
                }
            });
        });
    </script>
</body>
</html>
<?php $conexion->close(); ?>