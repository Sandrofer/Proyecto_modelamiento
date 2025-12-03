<?php
// Asegurarse de que no hay salida antes de session_start()
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión y es moderador
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'moderador'])) {
    die("Acceso denegado. No tienes permisos para acceder a esta página.");
}

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Acción no válida'];
    
    try {
        if (!isset($_POST['accion'])) {
            throw new Exception('No se especificó ninguna acción');
        }

        $accion = $_POST['accion'];
        
        switch ($accion) {
            case 'activar_usuario':
                if (!isset($_POST['usuario_id'])) {
                    throw new Exception('ID de usuario no proporcionado');
                }
                
                $usuario_id = intval($_POST['usuario_id']);
                $query = "UPDATE usuario SET activo = 1, suspendido = 0, motivo_suspension = NULL WHERE id = ?";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("i", $usuario_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al activar el usuario');
                }
                
                $response = ['success' => true, 'message' => 'Usuario activado correctamente'];
                break;
                
            case 'desactivar_usuario':
                if (!isset($_POST['usuario_id'])) {
                    throw new Exception('ID de usuario no proporcionado');
                }
                
                $usuario_id = intval($_POST['usuario_id']);
                $query = "UPDATE usuario SET activo = 0, suspendido = 0, motivo_suspension = NULL WHERE id = ?";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("i", $usuario_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al desactivar el usuario');
                }
                
                $response = ['success' => true, 'message' => 'Usuario desactivado correctamente'];
                break;
                
            case 'suspender_usuario':
                if (!isset($_POST['usuario_id']) || !isset($_POST['motivo'])) {
                    throw new Exception('Datos incompletos para la suspensión');
                }
                
                $usuario_id = intval($_POST['usuario_id']);
                $motivo = trim($_POST['motivo']);
                
                if (empty($motivo)) {
                    throw new Exception('Debe proporcionar un motivo para la suspensión');
                }
                
                $query = "UPDATE usuario SET suspendido = 1, activo = 1, motivo_suspension = ? WHERE id = ?";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("si", $motivo, $usuario_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al suspender el usuario');
                }
                
                $response = ['success' => true, 'message' => 'Usuario suspendido correctamente'];
                break;
                
            case 'quitar_suspension_usuario':
                if (!isset($_POST['usuario_id'])) {
                    throw new Exception('ID de usuario no proporcionado');
                }
                
                $usuario_id = intval($_POST['usuario_id']);
                $query = "UPDATE usuario SET suspendido = 0, motivo_suspension = NULL, activo = 1 WHERE id = ?";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("i", $usuario_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al quitar la suspensión');
                }
                
                $response = ['success' => true, 'message' => 'Suspensión eliminada correctamente'];
                break;
                
            default:
                throw new Exception('Acción no reconocida');
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    echo json_encode($response);
    exit();
}

// Obtener lista de usuarios con paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$inicio = ($pagina - 1) * $por_pagina;

// Construir consulta
$where_conditions = [];
$params = [];
$types = '';

// Filtros
if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
    $busqueda = "%{$_GET['busqueda']}%";
    $where_conditions[] = "(nombre_usuario LIKE ? OR correo_electronico LIKE ? OR nombre LIKE ? OR apellido LIKE ?)";
    $params = array_merge($params, [$busqueda, $busqueda, $busqueda, $busqueda]);
    $types .= 'ssss';
}

if (isset($_GET['estado']) && !empty($_GET['estado'])) {
    switch ($_GET['estado']) {
        case 'activo':
            $where_conditions[] = "activo = 1 AND suspendido = 0";
            break;
        case 'inactivo':
            $where_conditions[] = "activo = 0";
            break;
        case 'suspendido':
            $where_conditions[] = "suspendido = 1";
            break;
    }
}

$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Obtener usuarios
$query = "SELECT SQL_CALC_FOUND_ROWS id, nombre_usuario, correo_electronico, nombre, apellido, 
          fecha_registro, activo, suspendido, motivo_suspension, rol 
          FROM usuario 
          $where_sql 
          ORDER BY fecha_registro DESC 
          LIMIT ?, ?";

$stmt = $conexion->prepare($query);

// Si hay parámetros de búsqueda, agregarlos a la consulta
if (!empty($params)) {
    $params[] = $inicio;
    $params[] = $por_pagina;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $inicio, $por_pagina);
}

$stmt->execute();
$resultado = $stmt->get_result();
$usuarios = $resultado->fetch_all(MYSQLI_ASSOC);

// Obtener total de registros para la paginación
$total_usuarios = $conexion->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$total_paginas = ceil($total_usuarios / $por_pagina);

// Obtener estadísticas
$estadisticas = [
    'total' => $conexion->query("SELECT COUNT(*) FROM usuario")->fetch_row()[0],
    'activos' => $conexion->query("SELECT COUNT(*) FROM usuario WHERE activo = 1 AND suspendido = 0")->fetch_row()[0],
    'inactivos' => $conexion->query("SELECT COUNT(*) FROM usuario WHERE activo = 0")->fetch_row()[0],
    'suspendidos' => $conexion->query("SELECT COUNT(*) FROM usuario WHERE suspendido = 1")->fetch_row()[0],
];

$nombreCompleto = trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''));
$nombreMostrar = !empty($nombreCompleto) ? $nombreCompleto : ($_SESSION['username'] ?? 'Usuario');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - UTASHOP</title>
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
        .stat-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
        }
        .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.25rem;
        }
        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            margin-bottom: 1.5rem;
        }
        .search-input {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            width: 100%;
            max-width: 24rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary {
            background-color: #4f46e5;
            color: white;
        }
        .btn-primary:hover {
            background-color: #4338ca;
        }
        .btn-outline {
            border: 1px solid #d1d5db;
            color: #374151;
        }
        .btn-outline:hover {
            background-color: #f3f4f6;
        }
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
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
                        <span class="ml-auto bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                            <?php echo $estadisticas['total']; ?>
                        </span>
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
                        <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($nombreMostrar); ?></p>
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
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-6 py-4">
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-bold text-gray-900">Gestión de Usuarios</h1>
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                <input type="text" 
                                       id="buscarUsuario" 
                                       class="search-input pl-10" 
                                       placeholder="Buscar usuarios..."
                                       value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtros -->
                    <div class="mt-4 flex flex-wrap items-center gap-4">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">Filtrar por estado:</span>
                            <a href="?" class="px-3 py-1 text-sm rounded-full <?php echo !isset($_GET['estado']) ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-700'; ?>">
                                Todos (<?php echo $estadisticas['total']; ?>)
                            </a>
                            <a href="?estado=activo" class="px-3 py-1 text-sm rounded-full <?php echo ($_GET['estado'] ?? '') === 'activo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'; ?>">
                                Activos (<?php echo $estadisticas['activos']; ?>)
                            </a>
                            <a href="?estado=inactivo" class="px-3 py-1 text-sm rounded-full <?php echo ($_GET['estado'] ?? '') === 'inactivo' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700'; ?>">
                                Inactivos (<?php echo $estadisticas['inactivos']; ?>)
                            </a>
                            <a href="?estado=suspendido" class="px-3 py-1 text-sm rounded-full <?php echo ($_GET['estado'] ?? '') === 'suspendido' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700'; ?>">
                                Suspendidos (<?php echo $estadisticas['suspendidos']; ?>)
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Contenido -->
            <main class="p-6">
                <!-- Tarjetas de estadísticas -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue-100 text-blue-600">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Usuarios</p>
                            <p class="text-2xl font-bold"><?php echo $estadisticas['total']; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-green-100 text-green-600">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Activos</p>
                            <p class="text-2xl font-bold"><?php echo $estadisticas['activos']; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-yellow-100 text-yellow-600">
                            <i class="fas fa-user-slash"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Inactivos</p>
                            <p class="text-2xl font-bold"><?php echo $estadisticas['inactivos']; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-red-100 text-red-600">
                            <i class="fas fa-user-lock"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Suspendidos</p>
                            <p class="text-2xl font-bold"><?php echo $estadisticas['suspendidos']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Tabla de usuarios -->
                <div class="card overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-900">Lista de Usuarios</h2>
                            <div class="flex items-center space-x-2">
                                <a href="registro.php" class="btn btn-primary">
                                    <i class="fas fa-plus mr-2"></i>
                                    Nuevo Usuario
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($usuarios)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No se encontraron usuarios que coincidan con los criterios de búsqueda.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <?php
                                        // Determinar el estado del usuario
                                        $estado = '';
                                        $estadoClase = '';
                                        
                                        if ($usuario['suspendido']) {
                                            $estado = 'Suspendido';
                                            $estadoClase = 'bg-red-100 text-red-800';
                                        } elseif ($usuario['activo']) {
                                            $estado = 'Activo';
                                            $estadoClase = 'bg-green-100 text-green-800';
                                        } else {
                                            $estado = 'Inactivo';
                                            $estadoClase = 'bg-yellow-100 text-yellow-800';
                                        }
                                        
                                        // Determinar la clase del rol
                                        $rolClase = 'bg-blue-100 text-blue-800';
                                        if ($usuario['rol'] === 'administrador') {
                                            $rolClase = 'bg-purple-100 text-purple-800';
                                        } elseif ($usuario['rol'] === 'moderador') {
                                            $rolClase = 'bg-indigo-100 text-indigo-800';
                                        }
                                        ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></div>
                                                        <div class="text-sm text-gray-500">ID: <?php echo $usuario['id']; ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($usuario['correo_electronico']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $rolClase; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($usuario['rol'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $estadoClase; ?>">
                                                    <?php echo $estado; ?>
                                                    <?php if ($usuario['suspendido'] && !empty($usuario['motivo_suspension'])): ?>
                                                        <i class="fas fa-info-circle ml-1" 
                                                           title="<?php echo htmlspecialchars($usuario['motivo_suspension']); ?>">
                                                        </i>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" 
                                                       class="text-indigo-600 hover:text-indigo-900" 
                                                       title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($usuario['activo'] && !$usuario['suspendido']): ?>
                                                        <button onclick="desactivarUsuario(<?php echo $usuario['id']; ?>)" 
                                                                class="text-yellow-600 hover:text-yellow-900" 
                                                                title="Desactivar">
                                                            <i class="fas fa-user-slash"></i>
                                                        </button>
                                                        <button onclick="suspenderUsuario(<?php echo $usuario['id']; ?>)" 
                                                                class="text-red-600 hover:text-red-900" 
                                                                title="Suspender">
                                                            <i class="fas fa-user-lock"></i>
                                                        </button>
                                                    <?php elseif (!$usuario['activo']): ?>
                                                        <button onclick="activarUsuario(<?php echo $usuario['id']; ?>)" 
                                                                class="text-green-600 hover:text-green-900" 
                                                                title="Activar">
                                                            <i class="fas fa-user-check"></i>
                                                        </button>
                                                    <?php elseif ($usuario['suspendido']): ?>
                                                        <button onclick="quitarSuspensionUsuario(<?php echo $usuario['id']; ?>)" 
                                                                class="text-green-600 hover:text-green-900" 
                                                                title="Quitar suspensión">
                                                            <i class="fas fa-unlock"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($pagina > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Anterior
                                    </a>
                                <?php endif; ?>
                                <?php if ($pagina < $total_paginas): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" 
                                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Siguiente
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Mostrando <span class="font-medium"><?php echo (($pagina - 1) * $por_pagina) + 1; ?></span>
                                        a <span class="font-medium"><?php echo min($pagina * $por_pagina, $total_usuarios); ?></span>
                                        de <span class="font-medium"><?php echo $total_usuarios; ?></span> resultados
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <?php if ($pagina > 1): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>" 
                                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Anterior</span>
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $inicio = max(1, $pagina - 2);
                                        $fin = min($total_paginas, $pagina + 2);
                                        
                                        if ($inicio > 1) {
                                            echo '<a href="?' . http_build_query(array_merge($_GET, ['pagina' => 1])) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                            if ($inicio > 2) {
                                                echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                            }
                                        }
                                        
                                        for ($i = $inicio; $i <= $fin; $i++):
                                            $isCurrent = $i == $pagina;
                                        ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>" 
                                               class="relative inline-flex items-center px-4 py-2 border <?php echo $isCurrent ? 'bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?> text-sm font-medium">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php
                                        if ($fin < $total_paginas) {
                                            if ($fin < $total_paginas - 1) {
                                                echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                            }
                                            echo '<a href="?' . http_build_query(array_merge($_GET, ['pagina' => $total_paginas])) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_paginas . '</a>';
                                        }
                                        ?>
                                        
                                        <?php if ($pagina < $total_paginas): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" 
                                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Siguiente</span>
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Función para realizar peticiones AJAX
    function realizarAccion(accion, datos, mensajeExito) {
        // Mostrar indicador de carga
        const loader = document.createElement('div');
        loader.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        loader.innerHTML = '<div class="bg-white p-4 rounded-lg shadow-lg"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div><p class="mt-2 text-gray-700">Procesando...</p></div>';
        document.body.appendChild(loader);

        // Crear formulario para enviar los datos
        const formData = new FormData();
        formData.append('accion', accion);
        
        // Agregar datos adicionales al formulario
        for (const key in datos) {
            formData.append(key, datos[key]);
        }

        // Realizar la petición
        fetch('gestion_usuarios.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            // Eliminar el indicador de carga
            document.body.removeChild(loader);
            
            if (data.success) {
                // Mostrar mensaje de éxito
                Swal.fire({
                    title: '¡Éxito!',
                    text: mensajeExito || data.message,
                    icon: 'success',
                    confirmButtonText: 'Aceptar',
                    timer: 2000,
                    timerProgressBar: true
                }).then(() => {
                    // Recargar la página para ver los cambios
                    window.location.reload();
                });
            } else {
                // Mostrar mensaje de error
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'Ocurrió un error al procesar la solicitud',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        })
        .catch(error => {
            // Eliminar el indicador de carga
            if (document.body.contains(loader)) {
                document.body.removeChild(loader);
            }
            
            // Mostrar mensaje de error
            Swal.fire({
                title: 'Error',
                text: 'Error de conexión. Por favor, inténtalo de nuevo.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
            console.error('Error:', error);
        });
    }

    // Funciones para las acciones de usuario
    function activarUsuario(usuarioId) {
        Swal.fire({
            title: '¿Activar usuario?',
            text: '¿Estás seguro de que deseas activar este usuario?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, activar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                realizarAccion('activar_usuario', {usuario_id: usuarioId}, 'Usuario activado correctamente');
            }
        });
    }

    function desactivarUsuario(usuarioId) {
        Swal.fire({
            title: '¿Desactivar usuario?',
            text: '¿Estás seguro de que deseas desactivar este usuario?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                realizarAccion('desactivar_usuario', {usuario_id: usuarioId}, 'Usuario desactivado correctamente');
            }
        });
    }

    function suspenderUsuario(usuarioId) {
        Swal.fire({
            title: 'Suspender usuario',
            text: 'Ingresa el motivo de la suspensión:',
            input: 'text',
            inputPlaceholder: 'Motivo de la suspensión',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Suspender',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => {
                if (!value) {
                    return 'Debes ingresar un motivo para la suspensión';
                }
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                realizarAccion('suspender_usuario', {
                    usuario_id: usuarioId,
                    motivo: result.value
                }, 'Usuario suspendido correctamente');
            }
        });
    }

    function quitarSuspensionUsuario(usuarioId) {
        Swal.fire({
            title: '¿Quitar suspensión?',
            text: '¿Estás seguro de que deseas quitar la suspensión a este usuario?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, quitar suspensión',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                realizarAccion('quitar_suspension_usuario', {usuario_id: usuarioId}, 'Suspensión eliminada correctamente');
            }
        });
    }

    // Búsqueda en tiempo real
    document.getElementById('buscarUsuario').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            const busqueda = this.value.trim();
            const params = new URLSearchParams(window.location.search);
            
            if (busqueda) {
                params.set('busqueda', busqueda);
            } else {
                params.delete('busqueda');
            }
            
            // Resetear a la primera página al realizar una nueva búsqueda
            params.set('pagina', '1');
            
            window.location.href = '?' + params.toString();
        }
    });
    </script>
</body>
</html>
