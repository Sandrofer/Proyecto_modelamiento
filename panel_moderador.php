<?php
// Asegurarse de que no hay salida antes de session_start()
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'basededatos.php';

// Verificar si el usuario ha iniciado sesión y es moderador
if (!isset($_SESSION['username']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'moderador') {
    // Depuración
    error_log("Acceso denegado. Se requiere rol de moderador.");
    header("Location: index.html");
    exit();
}

// Funciones de moderación
function activarUsuario($usuario_id, $conexion) {
    $query = "UPDATE usuario SET activo = 1, suspendido = 0 WHERE id = $usuario_id";
    return $conexion->query($query);
}

function desactivarUsuario($usuario_id, $conexion) {
    $query = "UPDATE usuario SET activo = 0 WHERE id = $usuario_id";
    return $conexion->query($query);
}

function suspenderUsuario($usuario_id, $motivo, $conexion) {
    $motivo = $conexion->real_escape_string($motivo);
    $query = "UPDATE usuario SET suspendido = 1, motivo_suspension = '$motivo' WHERE id = $usuario_id";
    return $conexion->query($query);
}

function quitarSuspensionUsuario($usuario_id, $conexion) {
    $query = "UPDATE usuario SET suspendido = 0, motivo_suspension = NULL WHERE id = $usuario_id";
    return $conexion->query($query);
}

function aprobarProducto($producto_id, $conexion) {
    $query = "UPDATE productos SET estado = 'activo', fecha_aprobacion = NOW() WHERE id = $producto_id";
    return $conexion->query($query);
}

function rechazarProducto($producto_id, $motivo, $conexion) {
    $motivo = $conexion->real_escape_string($motivo);
    $query = "UPDATE productos SET estado = 'rechazado', motivo_rechazo = '$motivo' WHERE id = $producto_id";
    return $conexion->query($query);
}

// Procesar acciones de moderación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'Acción no válida'];
    
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
        
        switch ($accion) {
            case 'activar_usuario':
                if (isset($_POST['usuario_id'])) {
                    $usuario_id = intval($_POST['usuario_id']);
                    if (activarUsuario($usuario_id, $conexion)) {
                        $response = ['success' => true, 'message' => 'Usuario activado correctamente'];
                    } else {
                        $response = ['success' => false, 'message' => 'Error al activar el usuario'];
                    }
                }
                break;
                
            case 'desactivar_usuario':
                if (isset($_POST['usuario_id'])) {
                    $usuario_id = intval($_POST['usuario_id']);
                    if (desactivarUsuario($usuario_id, $conexion)) {
                        $response = ['success' => true, 'message' => 'Usuario desactivado correctamente'];
                    } else {
                        $response = ['success' => false, 'message' => 'Error al desactivar el usuario'];
                    }
                }
                break;
                
            case 'suspender_usuario':
                if (isset($_POST['usuario_id']) && isset($_POST['motivo'])) {
                    $usuario_id = intval($_POST['usuario_id']);
                    $motivo = $_POST['motivo'];
                    if (suspenderUsuario($usuario_id, $motivo, $conexion)) {
                        $response = ['success' => true, 'message' => 'Usuario suspendido correctamente'];
                    } else {
                        $response = ['success' => false, 'message' => 'Error al suspender el usuario'];
                    }
                }
                break;
                
            case 'quitar_suspension_usuario':
                if (isset($_POST['usuario_id'])) {
                    $usuario_id = intval($_POST['usuario_id']);
                    if (quitarSuspensionUsuario($usuario_id, $conexion)) {
                        $response = ['success' => true, 'message' => 'Suspensión eliminada correctamente'];
                    } else {
                        $response = ['success' => false, 'message' => 'Error al quitar la suspensión'];
                    }
                }
                break;
                
            case 'aprobar_producto':
                if (isset($_POST['producto_id'])) {
                    $producto_id = intval($_POST['producto_id']);
                    if (aprobarProducto($producto_id, $conexion)) {
                        $response = ['success' => true, 'message' => 'Producto aprobado correctamente'];
                    } else {
                        $response = ['success' => false, 'message' => 'Error al aprobar el producto'];
                    }
                }
                break;
                
            case 'rechazar_producto':
                if (isset($_POST['producto_id']) && isset($_POST['motivo'])) {
                    $producto_id = intval($_POST['producto_id']);
                    $motivo = $_POST['motivo'];
                    if (rechazarProducto($producto_id, $motivo, $conexion)) {
                        $response = ['success' => true, 'message' => 'Producto rechazado correctamente'];
                    } else {
                        $response = ['success' => false, 'message' => 'Error al rechazar el producto'];
                    }
                }
                break;
        }
        
        // Si es una petición AJAX, devolver la respuesta como JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        } else {
            // Redirigir a la misma página para actualizar los datos
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// Obtener estadísticas para el dashboard
try {
    // Total de usuarios
    $total_usuarios = $conexion->query("SELECT COUNT(*) as total FROM usuario")->fetch_assoc()['total'];
    
    // Total de productos activos (no eliminados)
    $total_productos = $conexion->query("SELECT COUNT(*) as total FROM productos WHERE eliminado = 0")->fetch_assoc()['total'];
    
    // Obtener los últimos usuarios registrados
    $ultimos_usuarios = $conexion->query("SELECT nombre_usuario, fecha_registro, correo_electronico FROM usuario ORDER BY fecha_registro DESC LIMIT 5");
    
    // Obtener los últimos productos publicados
    $ultimos_productos = $conexion->query("SELECT nombre, fecha_publicacion, estado FROM productos WHERE eliminado = 0 ORDER BY fecha_publicacion DESC LIMIT 5");
    
    // Obtener productos por estado
    $productos_por_estado = [
        'activo' => $conexion->query("SELECT COUNT(*) as total FROM productos WHERE estado = 'activo' AND eliminado = 0")->fetch_assoc()['total'],
        'inactivo' => $conexion->query("SELECT COUNT(*) as total FROM productos WHERE estado = 'inactivo' AND eliminado = 0")->fetch_assoc()['total'],
        'revision' => $conexion->query("SELECT COUNT(*) as total FROM productos WHERE estado = 'revision' AND eliminado = 0")->fetch_assoc()['total'],
        'rechazado' => $conexion->query("SELECT COUNT(*) as total FROM productos WHERE estado = 'rechazado' AND eliminado = 0")->fetch_assoc()['total']
    ];
    
    $error_bd = false;
} catch (Exception $e) {
    // En caso de error, establecer valores por defecto
    error_log("Error en las consultas: " . $e->getMessage());
    $total_usuarios = 0;
    $total_productos = 0;
    $ultimos_usuarios = [];
    $ultimos_productos = [];
    $productos_por_estado = [
        'activo' => 0,
        'inactivo' => 0,
        'revision' => 0,
        'rechazado' => 0
    ];
    $error_bd = true;
}

// Preparar nombre para mostrar
$nombreCompleto = trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''));
$nombreMostrar = !empty($nombreCompleto) ? $nombreCompleto : ($_SESSION['username'] ?? 'Usuario');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Moderación - UTASHOP</title>
    <meta name="description" content="Panel de moderación para gestionar contenido de UTASHOP">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-light: #EEF2FF;
            --secondary: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --success: #10B981;
            --dark: #1F2937;
            --light: #F9FAFB;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9FAFB;
            color: #1F2937;
        }
        
        .sidebar {
            background-color: white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            transition: all 0.3s ease-in-out;
            width: 280px;
        }
        
        .main-content {
            margin-left: 0;
            transition: all 0.3s ease-in-out;
            margin-left: 280px;
            min-height: 100vh;
        }
        
        .menu-item {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            color: #4B5563;
            background-color: transparent;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            margin: 0 0.5rem;
            border-left: 3px solid transparent;
        }
        
        .menu-item:hover {
            color: #4F46E5;
            background-color: #EEF2FF;
        }
        
        .menu-item i {
            margin-right: 0.75rem;
            font-size: 1.125rem;
            width: 1.5rem;
            text-align: center;
            color: #6B7280;
        }
        
        .menu-item:hover i {
            color: #4F46E5;
        }
        
        .menu-item.active {
            background-color: #EEF2FF;
            color: #4F46E5;
            font-weight: 500;
            border-left-color: #4F46E5;
        }
        
        .menu-item.active i {
            color: #4F46E5;
        }
        
        .card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #F3F4F6;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .stat-card {
            background-color: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            border: 1px solid #F3F4F6;
        }
        
        .stat-icon {
            padding: 0.75rem;
            border-radius: 0.75rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: #DCFCE7;
            color: #166534;
        }
        
        .badge-warning {
            background-color: #FEF3C7;
            color: #92400E;
        }
        
        .badge-danger {
            background-color: #FEE2E2;
            color: #991B1B;
        }
        
        .badge-info {
            background-color: #DBEAFE;
            color: #1E40AF;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: background-color 0.2s ease;
            outline: none;
            focus: ring-2;
            focus: ring-offset-2;
        }
        
        .btn-primary {
            background-color: #4F46E5;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #4338CA;
        }
        
        .btn-outline {
            border: 1px solid #D1D5DB;
            color: #374151;
        }
        
        .btn-outline:hover {
            background-color: #F9FAFB;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 0.5rem;
            border: 1px solid #E5E7EB;
        }
        
        table {
            min-width: 100%;
            divide-y: divide-gray-200;
        }
        
        th {
            padding: 0.75rem 1.5rem;
            background-color: #F9FAFB;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 500;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        td {
            padding: 1rem 1.5rem;
            white-space: nowrap;
            font-size: 0.875rem;
            color: #374151;
        }
        
        tr:hover {
            background-color: #F9FAFB;
        }
        
        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.5rem;
            background-color: white;
            border-top: 1px solid #E5E7EB;
            border-radius: 0 0 0.5rem 0.5rem;
        }
        
        .search-input {
            display: block;
            width: 100%;
            padding-left: 2.5rem;
            padding-right: 0.75rem;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            background-color: white;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        
        .search-input:focus {
            outline: none;
            ring: 2px;
            ring-color: #4F46E5;
            border-color: #4F46E5;
        }
        
        .sidebar-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #F3F4F6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: 0.75rem;
            background-color: transparent;
            transition: background-color 0.2s ease;
        }
        
        .user-profile:hover {
            background-color: #F9FAFB;
        }
        
        .notification-badge {
            position: absolute;
            top: -0.25rem;
            right: -0.25rem;
            width: 1.25rem;
            height: 1.25rem;
            background-color: #EF4444;
            color: white;
            font-size: 0.75rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div>
                    <h1 class="text-xl font-bold text-indigo-600">UTASHOP</h1>
                    <p class="text-sm text-gray-500">Panel de Moderación</p>
                </div>
            </div>
            
            <div class="p-4">
                <!-- Perfil del usuario -->
                <div class="user-profile mb-6">
                    <div class="relative">
                        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600">
                            <i class="fas fa-user text-xl"></i>
                        </div>
                        <span class="notification-badge">3</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 truncate">
                            <?php echo htmlspecialchars($nombreMostrar); ?>
                        </p>
                        <p class="text-sm text-gray-500 truncate">Moderador</p>
                    </div>
                </div>
                
                <!-- Menú de navegación -->
                <nav class="space-y-1.5">
                    <a href="panel_moderador.php" class="menu-item active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="gestion_usuarios.php" class="menu-item">
                        <i class="fas fa-users"></i>
                        <span>Usuarios</span>
                        <span class="ml-auto bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                            <?php echo $total_usuarios; ?>
                        </span>
                    </a>
                    <a href="gestion_productos.php" class="menu-item">
                        <i class="fas fa-box"></i>
                        <span>Productos</span>
                        <span class="ml-auto bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                            <?php echo $total_productos; ?>
                        </span>
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
            
            <!-- Pie del sidebar -->
            <div class="mt-auto p-4 border-t border-gray-100">
                <a href="cerrar_sesion.php" class="menu-item text-red-600 hover:bg-red-50">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar sesión</span>
                </a>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="main-content flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex justify-between items-center px-6 py-4">
                    <h2 class="text-2xl font-bold text-gray-900">Panel de Control</h2>
                    <div class="flex items-center space-x-6">
                        <!-- Barra de búsqueda -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" class="search-input pl-10" placeholder="Buscar...">
                        </div>
                        
                        <!-- Notificaciones -->
                        <div class="relative">
                            <button class="p-2 text-gray-500 hover:text-gray-700 focus:outline-none relative">
                                <i class="fas fa-bell text-xl"></i>
                                <span class="notification-badge">3</span>
                            </button>
                        </div>
                        
                        <!-- Perfil -->
                        <div class="flex items-center space-x-3">
                            <div class="text-right hidden md:block">
                                <p class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($nombreMostrar); ?>
                                </p>
                                <p class="text-xs text-gray-500">Moderador</p>
                            </div>
                            <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Contenido principal -->
            <main class="p-6 flex-1 overflow-y-auto">
                <!-- Tarjetas de estadísticas -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Tarjeta de Usuarios -->
                    <div class="stat-card group">
                        <div class="stat-icon bg-indigo-100 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Usuarios</p>
                            <div class="flex items-baseline justify-between">
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_usuarios); ?></p>
                                <span class="text-sm text-green-600 font-medium">
                                    <i class="fas fa-arrow-up"></i> 12%
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                <span class="text-green-600 font-medium">+24</span> este mes
                            </p>
                        </div>
                    </div>
                    
                    <!-- Tarjeta de Productos -->
                    <div class="stat-card group">
                        <div class="stat-icon bg-green-100 text-green-600 group-hover:bg-green-600 group-hover:text-white transition-colors">
                            <i class="fas fa-box text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Productos</p>
                            <div class="flex items-baseline justify-between">
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_productos); ?></p>
                                <span class="text-sm text-green-600 font-medium">
                                    <i class="fas fa-arrow-up"></i> 8%
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                <span class="text-green-600 font-medium">+15</span> esta semana
                            </p>
                        </div>
                    </div>
                    
                    <!-- Tarjeta de Productos en Revisión -->
                    <div class="stat-card group">
                        <div class="stat-icon bg-yellow-100 text-yellow-600 group-hover:bg-yellow-600 group-hover:text-white transition-colors">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">En Revisión</p>
                            <div class="flex items-baseline justify-between">
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($productos_por_estado['revision']); ?></p>
                                <span class="text-sm text-yellow-600 font-medium">
                                    <i class="fas fa-exclamation-circle"></i> Pendiente
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                <span class="text-yellow-600 font-medium">+3</span> por revisar
                            </p>
                        </div>
                    </div>
                    
                    <!-- Tarjeta de Actividad -->
                    <div class="stat-card group">
                        <div class="stat-icon bg-purple-100 text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Actividad</p>
                            <div class="flex items-baseline justify-between">
                                <p class="text-2xl font-bold text-gray-900">85%</p>
                                <span class="text-sm text-green-600 font-medium">
                                    <i class="fas fa-arrow-up"></i> 5.2%
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                <span class="text-green-600 font-medium">+12%</span> que el mes pasado
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Sección de actividad reciente -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Últimos usuarios registrados -->
                    <div class="card">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Usuarios Recientes</h3>
                                <a href="gestion_usuarios.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Ver todos</a>
                            </div>
                        </div>
                        <div class="overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Correo</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Registro</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while ($usuario = $ultimos_usuarios->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></div>
                                                    <div class="text-xs text-gray-500">Usuario</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($usuario['correo_electronico'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                            <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Últimos productos -->
                    <div class="card">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-900">Productos Recientes</h3>
                        </div>
                        <div class="overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while ($producto = $ultimos_productos->fetch_assoc()): 
                                        $estado_clases = [
                                            'activo' => 'bg-green-100 text-green-800',
                                            'inactivo' => 'bg-gray-100 text-gray-800',
                                            'revision' => 'bg-yellow-100 text-yellow-800',
                                            'rechazado' => 'bg-red-100 text-red-800'
                                        ];
                                        $clase = $estado_clases[strtolower($producto['estado'])] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                                                    <i class="fas fa-box"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                                    <div class="text-xs text-gray-500">Categoría</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d/m/Y H:i', strtotime($producto['fecha_publicacion'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $clase; ?>">
                                                <?php echo ucfirst($producto['estado']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Resumen de productos por estado -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Gráfico de productos por estado -->
                    <div class="card">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-900">Distribución de Productos</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center p-4 rounded-xl bg-green-50">
                                    <div class="text-3xl font-bold text-green-600"><?php echo $productos_por_estado['activo']; ?></div>
                                    <div class="mt-1 text-sm font-medium text-green-800">Activos</div>
                                    <div class="mt-1 text-xs text-green-600">
                                        <i class="fas fa-arrow-up"></i> 12% este mes
                                    </div>
                                </div>
                                <div class="text-center p-4 rounded-xl bg-yellow-50">
                                    <div class="text-3xl font-bold text-yellow-600"><?php echo $productos_por_estado['revision']; ?></div>
                                    <div class="mt-1 text-sm font-medium text-yellow-800">En Revisión</div>
                                    <div class="mt-1 text-xs text-yellow-600">
                                        <i class="fas fa-arrow-down"></i> 3% esta semana
                                    </div>
                                </div>
                                <div class="text-center p-4 rounded-xl bg-red-50">
                                    <div class="text-3xl font-bold text-red-600"><?php echo $productos_por_estado['rechazado']; ?></div>
                                    <div class="mt-1 text-sm font-medium text-red-800">Rechazados</div>
                                    <div class="mt-1 text-xs text-red-600">
                                        <i class="fas fa-arrow-up"></i> 5% este mes
                                    </div>
                                </div>
                                <div class="text-center p-4 rounded-xl bg-gray-50">
                                    <div class="text-3xl font-bold text-gray-600"><?php echo $productos_por_estado['inactivo']; ?></div>
                                    <div class="mt-1 text-sm font-medium text-gray-800">Inactivos</div>
                                    <div class="mt-1 text-xs text-gray-600">
                                        <i class="fas fa-minus"></i> Sin cambios
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actividad reciente -->
                    <div class="card">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-900">Actividad Reciente</h3>
                        </div>
                        <div class="p-6">
                            <div class="flow-root">
                                <ul class="-mb-8">
                                    <li>
                                        <div class="relative pb-8">
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center ring-8 ring-white">
                                                        <i class="fas fa-check text-green-600"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-600">Producto <span class="font-medium text-gray-900">"iPhone 13 Pro"</span> aprobado</p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        <time datetime="2023-11-05">Hace 2h</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="relative pb-8">
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-yellow-100 flex items-center justify-center ring-8 ring-white">
                                                        <i class="fas fa-exclamation text-yellow-600"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-600">Nuevo producto <span class="font-medium text-gray-900">"MacBook Pro M2"</span> en revisión</p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        <time datetime="2023-11-05">Hace 5h</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="relative pb-8">
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center ring-8 ring-white">
                                                        <i class="fas fa-user-plus text-blue-600"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-600">Nuevo usuario <span class="font-medium text-gray-900">maria_gonzalez</span> registrado</p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        <time datetime="2023-11-04">Ayer</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="mt-6 flex">
                                <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                    Ver toda la actividad
                                    <span aria-hidden="true"> &rarr;</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Función para mostrar un modal de confirmación
    function confirmarAccion(titulo, texto, icono, confirmButtonText, callback) {
        Swal.fire({
            title: titulo,
            text: texto,
            icon: icono,
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                callback();
            }
        });
    }

    // Función para mostrar un modal de entrada de texto
    function mostrarInput(titulo, texto, inputPlaceholder, confirmButtonText, callback) {
        Swal.fire({
            title: titulo,
            text: texto,
            input: 'text',
            inputPlaceholder: inputPlaceholder,
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => {
                if (!value) {
                    return 'Por favor, ingresa un motivo';
                }
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                callback(result.value);
            }
        });
    }

    // Función para realizar una petición AJAX
    function realizarAccion(url, data, successMessage) {
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: successMessage || data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Recargar la página después de un breve retraso
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Ocurrió un error inesperado',
                    confirmButtonText: 'Aceptar'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al procesar la solicitud',
                confirmButtonText: 'Aceptar'
            });
        });
    }

    // Funciones para las acciones de usuario
    function activarUsuario(usuarioId) {
        confirmarAccion(
            '¿Activar usuario?',
            '¿Estás seguro de que deseas activar este usuario?',
            'question',
            'Sí, activar',
            () => {
                realizarAccion(
                    'panel_moderador.php',
                    { accion: 'activar_usuario', usuario_id: usuarioId },
                    'Usuario activado correctamente'
                );
            }
        );
    }

    function desactivarUsuario(usuarioId) {
        confirmarAccion(
            '¿Desactivar usuario?',
            '¿Estás seguro de que deseas desactivar este usuario?',
            'warning',
            'Sí, desactivar',
            () => {
                realizarAccion(
                    'panel_moderador.php',
                    { accion: 'desactivar_usuario', usuario_id: usuarioId },
                    'Usuario desactivado correctamente'
                );
            }
        );
    }

    function suspenderUsuario(usuarioId) {
        mostrarInput(
            'Suspender usuario',
            'Ingresa el motivo de la suspensión:',
            'Motivo de la suspensión',
            'Suspender',
            (motivo) => {
                realizarAccion(
                    'panel_moderador.php',
                    { accion: 'suspender_usuario', usuario_id: usuarioId, motivo: motivo },
                    'Usuario suspendido correctamente'
                );
            }
        );
    }

    function quitarSuspensionUsuario(usuarioId) {
        confirmarAccion(
            '¿Quitar suspensión?',
            '¿Estás seguro de que deseas quitar la suspensión a este usuario?',
            'question',
            'Sí, quitar suspensión',
            () => {
                realizarAccion(
                    'panel_moderador.php',
                    { accion: 'quitar_suspension_usuario', usuario_id: usuarioId },
                    'Suspensión eliminada correctamente'
                );
            }
        );
    }

    // Funciones para las acciones de productos
    function aprobarProducto(productoId) {
        confirmarAccion(
            '¿Aprobar producto?',
            '¿Estás seguro de que deseas aprobar este producto?',
            'question',
            'Sí, aprobar',
            () => {
                realizarAccion(
                    'panel_moderador.php',
                    { accion: 'aprobar_producto', producto_id: productoId },
                    'Producto aprobado correctamente'
                );
            }
        );
    }

    function rechazarProducto(productoId) {
        mostrarInput(
            'Rechazar producto',
            'Ingresa el motivo del rechazo:',
            'Motivo del rechazo',
            'Rechazar',
            (motivo) => {
                realizarAccion(
                    'panel_moderador.php',
                    { accion: 'rechazar_producto', producto_id: productoId, motivo: motivo },
                    'Producto rechazado correctamente'
                );
            }
        );
    }
    </script>
    <script>
        // Cerrar sesión con confirmación
        document.querySelector('a[href="cerrar_sesion.php"]').addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                e.preventDefault();
            }
        });

        // Marcar elemento activo en el menú
        document.querySelectorAll('.menu-item').forEach(item => {
            if (item.href === window.location.href) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Inicializar gráficos (ejemplo con Chart.js)
        document.addEventListener('DOMContentLoaded', function() {
            // Gráfico de productos por estado
            const ctx = document.createElement('canvas');
            ctx.id = 'productosChart';
            document.querySelector('.card:nth-child(1) .p-6').appendChild(ctx);
            
            // Convertir el array PHP a JSON seguro para JavaScript
            const productosData = <?php echo json_encode([
                'activo' => $productos_por_estado['activo'] ?? 0,
                'revision' => $productos_por_estado['revision'] ?? 0,
                'rechazado' => $productos_por_estado['rechazado'] ?? 0,
                'inactivo' => $productos_por_estado['inactivo'] ?? 0
            ]); ?>;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Activos', 'En Revisión', 'Rechazados', 'Inactivos'],
                    datasets: [{
                        data: [
                            productosData.activo,
                            productosData.revision,
                            productosData.rechazado,
                            productosData.inactivo
                        ],
                        backgroundColor: [
                            '#10B981',
                            '#F59E0B',
                            '#EF4444',
                            '#9CA3AF'
                        ],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>