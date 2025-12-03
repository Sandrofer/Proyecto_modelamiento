<?php
session_start();

// Verificar si el usuario es moderador o administrador
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['moderador', 'administrador'])) {
    header('Location: index.php');
    exit();
}

// Verificar que se haya proporcionado un ID de usuario
if (!isset($_GET['id'])) {
    header('Location: gestion_usuarios.php');
    exit();
}

$usuario_id = intval($_GET['id']);

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");

// Obtener información del usuario
$query = "SELECT * FROM usuario WHERE id = ? AND rol = 'usuario'";
$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    // Usuario no encontrado o no es un usuario normal
    header('Location: gestion_usuarios.php?error=usuario_no_encontrado');
    exit();
}

$usuario = $resultado->fetch_assoc();

// Obtener productos del usuario
$query_productos = "SELECT * FROM productos WHERE usuario_vendedor = ? ORDER BY fecha_publicacion DESC";
$stmt_productos = $conexion->prepare($query_productos);
$stmt_productos->bind_param("s", $usuario['nombre_usuario']);
$stmt_productos->execute();
$productos = $stmt_productos->get_result();

// Procesar cambios de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $nuevo_estado = $_POST['nuevo_estado'];
    $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
    
    // Validar el estado
    if (!in_array($nuevo_estado, ['activo', 'inactivo', 'suspendido'])) {
        $mensaje_error = "Estado no válido";
    } elseif ($nuevo_estado === 'suspendido' && empty($motivo)) {
        $mensaje_error = "Debe proporcionar un motivo para la suspensión";
    } else {
        // Actualizar el estado del usuario
        $query_actualizar = "UPDATE usuario SET ";
        $params = [];
        $types = "";
        
        switch ($nuevo_estado) {
            case 'activo':
                $query_actualizar .= "activo = 1, suspendido = 0, motivo_suspension = NULL";
                $mensaje_exito = "Usuario activado correctamente";
                break;
                
            case 'inactivo':
                $query_actualizar .= "activo = 0, suspendido = 0, motivo_suspension = NULL";
                $mensaje_exito = "Usuario desactivado correctamente";
                break;
                
            case 'suspendido':
                $query_actualizar .= "suspendido = 1, activo = 1, motivo_suspension = ?";
                $params[] = $motivo;
                $types .= "s";
                $mensaje_exito = "Usuario suspendido correctamente";
                break;
        }
        
        $query_actualizar .= " WHERE id = ?";
        $params[] = $usuario_id;
        $types .= "i";
        
        $stmt_actualizar = $conexion->prepare($query_actualizar);
        
        if (!empty($params)) {
            $stmt_actualizar->bind_param($types, ...$params);
        }
        
        if ($stmt_actualizar->execute()) {
            $mensaje_exito = $mensaje_exito;
            // Actualizar los datos del usuario
            $usuario['activo'] = ($nuevo_estado === 'activo' || $nuevo_estado === 'suspendido') ? 1 : 0;
            $usuario['suspendido'] = ($nuevo_estado === 'suspendido') ? 1 : 0;
            $usuario['motivo_suspension'] = ($nuevo_estado === 'suspendido') ? $motivo : null;
        } else {
            $mensaje_error = "Error al actualizar el estado del usuario";
        }
    }
}

// Determinar el estado actual
$estado_actual = '';
if ($usuario['suspendido']) {
    $estado_actual = 'Suspendido';
    $clase_estado = 'bg-red-100 text-red-800';
} elseif ($usuario['activo']) {
    $estado_actual = 'Activo';
    $clase_estado = 'bg-green-100 text-green-800';
} else {
    $estado_actual = 'Inactivo';
    $clase_estado = 'bg-yellow-100 text-yellow-800';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario - UTASHOP</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            width: 260px;
            background-color: #1f2937;
            color: #fff;
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
        }
        .main-content {
            margin-left: 260px;
            flex: 1;
            overflow-y: auto;
            height: 100vh;
            padding: 2rem;
        }
        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #9ca3af;
            border-radius: 0.375rem;
            margin: 0.25rem 0.5rem;
            transition: all 0.2s;
        }
        .menu-item:hover, .menu-item.active {
            background-color: #374151;
            color: #fff;
        }
        .menu-item i {
            width: 1.5rem;
            margin-right: 0.75rem;
            text-align: center;
        }
        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-white">UTASHOP</h1>
                        <p class="text-sm text-gray-400">Panel de Control</p>
                    </div>
                </div>
                
                <!-- Menú de navegación -->
                <nav class="mt-8">
                    <a href="panel_moderador.php" class="menu-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="gestion_usuarios.php" class="menu-item active">
                        <i class="fas fa-users"></i>
                        <span>Usuarios</span>
                    </a>
                    <a href="gestion_productos.php" class="menu-item">
                        <i class="fas fa-box"></i>
                        <span>Productos</span>
                    </a>
                    <a href="reportes.php" class="menu-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reportes</span>
                    </a>
                    <a href="configuracion.php" class="menu-item">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                </nav>
            </div>
            
            <div class="mt-auto p-4 border-t border-gray-700">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?></p>
                        <p class="text-xs text-gray-400"><?php echo ucfirst($_SESSION['rol'] ?? 'usuario'); ?></p>
                    </div>
                </div>
                <a href="cerrar_sesion.php" class="mt-4 flex items-center text-sm text-gray-400 hover:text-white">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Cerrar sesión
                </a>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="main-content">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Perfil de Usuario</h1>
                    <nav class="flex mt-2" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-2">
                            <li>
                                <a href="gestion_usuarios.php" class="text-gray-500 hover:text-gray-700">Usuarios</a>
                            </li>
                            <li>
                                <span class="mx-2 text-gray-400">/</span>
                            </li>
                            <li class="text-gray-700 font-medium">
                                <?php echo htmlspecialchars($usuario['nombre_usuario']); ?>
                            </li>
                        </ol>
                    </nav>
                </div>
                <a href="gestion_usuarios.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver a la lista
                </a>
            </div>

            <?php if (isset($mensaje_exito)): ?>
                <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    <?php echo htmlspecialchars($mensaje_exito); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($mensaje_error)): ?>
                <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?php echo htmlspecialchars($mensaje_error); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Información del usuario -->
                <div class="lg:col-span-1">
                    <div class="card">
                        <div class="text-center">
                        <?php if (!empty($usuario['foto_perfil'])): ?>
                            <div class="mx-auto h-32 w-32 rounded-full bg-gray-200 overflow-hidden mb-4">
                                <img src="<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" alt="Foto de perfil" class="h-full w-full object-cover">
                            </div>
                        <?php else: ?>
                            <div class="mx-auto h-32 w-32 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 text-4xl mb-4">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                            <h2 class="text-xl font-bold text-gray-900">
                                <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                            </h2>
                            <p class="text-gray-600">@<?php echo htmlspecialchars($usuario['nombre_usuario']); ?></p>
                            
                            <div class="mt-4">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $clase_estado; ?>">
                                    <?php echo $estado_actual; ?>
                                </span>
                            </div>
                            
                            <?php if ($usuario['suspendido'] && !empty($usuario['motivo_suspension'])): ?>
                                <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md">
                                    <p class="text-sm text-red-700">
                                        <strong>Motivo de suspensión:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($usuario['motivo_suspension'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-6 border-t border-gray-200 pt-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-3">Información de contacto</h3>
                            <div class="space-y-2">
                                <div class="flex items-center text-sm text-gray-700">
                                    <i class="fas fa-envelope mr-2 text-gray-400"></i>
                                    <?php echo htmlspecialchars($usuario['correo_electronico']); ?>
                                </div>
                                <div class="flex items-center text-sm text-gray-700">
                                    <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>
                                    Miembro desde <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cambiar estado de la cuenta -->
                        <div class="mt-6 border-t border-gray-200 pt-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-3">Estado de la cuenta</h3>
                            <form method="post" class="space-y-3" id="formEstado">
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <input id="estado_activo" name="nuevo_estado" type="radio" value="activo" 
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                               <?php echo ($estado_actual === 'Activo') ? 'checked' : ''; ?>>
                                        <label for="estado_activo" class="ml-3 block text-sm font-medium text-gray-700">
                                            Activo
                                        </label>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <input id="estado_inactivo" name="nuevo_estado" type="radio" value="inactivo" 
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                               <?php echo ($estado_actual === 'Inactivo') ? 'checked' : ''; ?>>
                                        <label for="estado_inactivo" class="ml-3 block text-sm font-medium text-gray-700">
                                            Inactivo
                                        </label>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="estado_suspendido" name="nuevo_estado" type="radio" value="suspendido" 
                                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                                   <?php echo ($estado_actual === 'Suspendido') ? 'checked' : ''; ?>>
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="estado_suspendido" class="font-medium text-gray-700">Suspendido</label>
                                            <div id="motivo_suspension_container" class="mt-1 <?php echo ($estado_actual !== 'Suspendido') ? 'hidden' : ''; ?>">
                                                <label for="motivo" class="block text-xs text-gray-500 mb-1">Motivo de la suspensión:</label>
                                                <textarea id="motivo" name="motivo" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"><?php echo htmlspecialchars($usuario['motivo_suspension'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" name="cambiar_estado" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Actualizar estado
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Productos del usuario -->
                <div class="lg:col-span-2">
                    <div class="card">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-medium text-gray-900">Productos publicados</h2>
                            <span class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded-full">
                                <?php echo $productos->num_rows; ?> productos
                            </span>
                        </div>
                        
                        <?php if ($productos->num_rows > 0): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php while ($producto = $productos->fetch_assoc()): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-md overflow-hidden">
                                                            <?php if (!empty($producto['imagen'])): ?>
                                                                <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="" class="h-full w-full object-cover">
                                                            <?php else: ?>
                                                                <div class="h-full w-full flex items-center justify-center text-gray-400">
                                                                    <i class="fas fa-image"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900">
                                                                <?php echo htmlspecialchars($producto['nombre']); ?>
                                                            </div>
                                                            <div class="text-sm text-gray-500">
                                                                <?php echo substr(htmlspecialchars($producto['descripcion']), 0, 30); ?>...
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    $<?php echo number_format($producto['precio'], 2); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php
                                                    $estado_producto = '';
                                                    $clase_estado_producto = '';
                                                    
                                                    switch ($producto['estado']) {
                                                        case 'activo':
                                                            $estado_producto = 'Activo';
                                                            $clase_estado_producto = 'bg-green-100 text-green-800';
                                                            break;
                                                        case 'pendiente':
                                                            $estado_producto = 'Pendiente';
                                                            $clase_estado_producto = 'bg-yellow-100 text-yellow-800';
                                                            break;
                                                        case 'inactivo':
                                                            $estado_producto = 'Inactivo';
                                                            $clase_estado_producto = 'bg-gray-100 text-gray-800';
                                                            break;
                                                        case 'vendido':
                                                            $estado_producto = 'Vendido';
                                                            $clase_estado_producto = 'bg-blue-100 text-blue-800';
                                                            break;
                                                        default:
                                                            $estado_producto = ucfirst($producto['estado']);
                                                            $clase_estado_producto = 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $clase_estado_producto; ?>">
                                                        <?php echo $estado_producto; ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo date('d/m/Y', strtotime($producto['fecha_publicacion'])); ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-box-open text-4xl text-gray-300 mb-2"></i>
                                <p class="text-gray-500">Este usuario no ha publicado ningún producto.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Mostrar/ocultar campo de motivo de suspensión
        document.querySelectorAll('input[name="nuevo_estado"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const motivoContainer = document.getElementById('motivo_suspension_container');
                if (this.value === 'suspendido') {
                    motivoContainer.classList.remove('hidden');
                } else {
                    motivoContainer.classList.add('hidden');
                }
            });
        });

        // Validación del formulario
        document.getElementById('formEstado').addEventListener('submit', function(e) {
            const estadoSuspendido = document.getElementById('estado_suspendido');
            const motivo = document.getElementById('motivo');
            
            if (estadoSuspendido.checked && motivo.value.trim() === '') {
                e.preventDefault();
                Swal.fire({
                    title: 'Error',
                    text: 'Debe proporcionar un motivo para la suspensión',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    </script>
</body>
</html>
<?php
// Cerrar conexiones
$stmt->close();
$stmt_productos->close();
if (isset($stmt_actualizar)) {
    $stmt_actualizar->close();
}
$conexion->close();
?>
