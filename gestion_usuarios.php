<?php
// Asegurarse de que no hay salida antes de session_start()
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// CONEXIÓN DIRECTA TEMPORAL
$servidor = "localhost";
$usuario = "root";
$password = "mamiypapi1";
$basededatos = "proyecto_mod";

$conexion = new mysqli($servidor, $usuario, $password, $basededatos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");

// Verificación de sesión temporal
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'moderador') {
    die("Acceso denegado. Rol: " . ($_SESSION['rol'] ?? 'No definido'));
}

// VERIFICAR SI LAS COLUMNAS EXISTEN - SI NO, CREARLAS TEMPORALMENTE
$columnas_necesarias = ['activo', 'suspendido', 'motivo_suspension'];
foreach ($columnas_necesarias as $columna) {
    $result = $conexion->query("SHOW COLUMNS FROM usuario LIKE '$columna'");
    if ($result->num_rows == 0) {
        // Si la columna no existe, la creamos temporalmente
        if ($columna == 'motivo_suspension') {
            $conexion->query("ALTER TABLE usuario ADD COLUMN $columna TEXT NULL");
        } else {
            $conexion->query("ALTER TABLE usuario ADD COLUMN $columna BOOLEAN DEFAULT TRUE");
        }
    }
}
?>

<!-- LUEGO TODO EL CÓDIGO QUE YA TIENES -->



<?php
// Verificar si el usuario ha iniciado sesión y es moderador
//if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'moderador') {
//    header("Location: login.php");
//    exit();
//}

// Procesar búsqueda y filtros
// ... tu código de conexión actual ...

// === PROCESAMIENTO DE ACCIONES (ACTIVAR/DESACTIVAR/SUSPENDER USUARIOS) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Acción no válida'];
    
    if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'moderador'])) {
        $response['message'] = 'No tienes permiso para realizar esta acción';
        echo json_encode($response);
        exit();
    }
    
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
        
        try {
            switch ($accion) {
                case 'activar_usuario':
                    if (isset($_POST['usuario_id'])) {
                        $usuario_id = intval($_POST['usuario_id']);
                        $query = "UPDATE usuario SET activo = 1, suspendido = 0, motivo_suspension = NULL WHERE id = ?";
                        $stmt = $conexion->prepare($query);
                        $stmt->bind_param("i", $usuario_id);
                        if ($stmt->execute()) {
                            $response = ['success' => true, 'message' => 'Usuario activado correctamente'];
                        } else {
                            throw new Exception('Error al activar el usuario');
                        }
                    }
                    break;
                    
                case 'desactivar_usuario':
                    if (isset($_POST['usuario_id'])) {
                        $usuario_id = intval($_POST['usuario_id']);
                        $query = "UPDATE usuario SET activo = 0, suspendido = 0, motivo_suspension = NULL WHERE id = ?";
                        $stmt = $conexion->prepare($query);
                        $stmt->bind_param("i", $usuario_id);
                        if ($stmt->execute()) {
                            $response = ['success' => true, 'message' => 'Usuario desactivado correctamente'];
                        } else {
                            throw new Exception('Error al desactivar el usuario');
                        }
                    }
                    break;
                    
                case 'suspender_usuario':
                    if (isset($_POST['usuario_id']) && isset($_POST['motivo'])) {
                        $usuario_id = intval($_POST['usuario_id']);
                        $motivo = trim($_POST['motivo']);
                        
                        if (empty($motivo)) {
                            throw new Exception('Debes especificar un motivo para la suspensión');
                        }
                        
                        $query = "UPDATE usuario SET suspendido = 1, activo = 1, motivo_suspension = ? WHERE id = ?";
                        $stmt = $conexion->prepare($query);
                        $stmt->bind_param("si", $motivo, $usuario_id);
                        if ($stmt->execute()) {
                            $response = ['success' => true, 'message' => 'Usuario suspendido correctamente'];
                        } else {
                            throw new Exception('Error al suspender el usuario');
                        }
                    } else {
                        $response = ['success' => false, 'message' => 'Error al suspender el usuario'];
                    }
                    break;
                    
                case 'quitar_suspension_usuario':
                    if (isset($_POST['usuario_id'])) {
                        $usuario_id = intval($_POST['usuario_id']);
                        $query = "UPDATE usuario SET suspendido = 0, motivo_suspension = NULL, activo = 1 WHERE id = ?";
                        $stmt = $conexion->prepare($query);
                        $stmt->bind_param("i", $usuario_id);
                        if ($stmt->execute()) {
                            $response = ['success' => true, 'message' => 'Suspensión eliminada correctamente'];
                        } else {
                            throw new Exception('Error al quitar la suspensión');
                        }
                    }
                    break;
                    
                default:
                    $response = ['success' => false, 'message' => 'Acción no reconocida'];
                    break;
            }
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    echo json_encode($response);
    exit();
}
// === FIN DEL CÓDIGO NUEVO ===

// ... tu código actual continúa aquí ...
$where_conditions = [];
$params = [];
$types = '';

if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
    $where_conditions[] = "(nombre_usuario LIKE ? OR correo_electronico LIKE ? OR nombre LIKE ? OR apellido LIKE ?)";
    $busqueda = "%{$_GET['busqueda']}%";
    $params[] = $busqueda;
    $params[] = $busqueda;
    $params[] = $busqueda;
    $params[] = $busqueda;
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

// Construir consulta
$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(' AND ', $where_conditions);
}

// Paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$inicio = ($pagina - 1) * $por_pagina;

// Obtener usuarios
$query = "SELECT SQL_CALC_FOUND_ROWS id, nombre_usuario, correo_electronico, nombre, apellido, fecha_registro, activo, suspendido, motivo_suspension, rol FROM usuario $where_sql ORDER BY fecha_registro DESC LIMIT ?, ?";
$types .= 'ii';
$params[] = $inicio;
$params[] = $por_pagina;

$stmt = $conexion->prepare($query);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
$usuarios = $resultado->fetch_all(MYSQLI_ASSOC);

// Total de usuarios para paginación
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
        /* Copiar todos los estilos del panel_moderador.php */
        :root { --primary: #4F46E5; --primary-light: #EEF2FF; /* ... */ }
        .sidebar {
            width: 280px;
            background-color: #1F2937;
            color: #F9FAFB;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 1.5rem 1.5rem 0.5rem;
            border-bottom: 1px solid #374151;
            margin-bottom: 1rem;
        }

        .sidebar h1 {
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }

        .sidebar p {
            color: #9CA3AF;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #D1D5DB;
            text-decoration: none;
            border-radius: 0.375rem;
            margin: 0.25rem 0.75rem;
            transition: all 0.2s ease;
            font-size: 0.9375rem;
            position: relative;
        }

        .menu-item i {
            width: 24px;
            text-align: center;
            margin-right: 12px;
            font-size: 1.1rem;
            color: #9CA3AF;
            transition: all 0.2s ease;
        }

        .menu-item:hover {
            background-color: #374151;
            color: white;
        }

        .menu-item:hover i {
            color: white;
        }

        .menu-item.active {
            background-color: #4F46E5;
            color: white;
            font-weight: 500;
        }

        .menu-item.active i {
            color: white;
        }

        .menu-item .badge {
            margin-left: auto;
            background-color: #4F46E5;
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-weight: 500;
        }

        .menu-item.active .badge {
            background-color: white;
            color: #4F46E5;
        }

        .user-profile {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #4F46E5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            margin-right: 0.75rem;
        }

        .user-info p:first-child {
            color: white;
            font-weight: 500;
            margin: 0;
            font-size: 0.9375rem;
        }

        .user-info p:last-child {
            color: #9CA3AF;
            font-size: 0.8125rem;
            margin: 0.125rem 0 0;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            color: #EF4444;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.9375rem;
            margin: 0.5rem 0.75rem 1.5rem;
            border-radius: 0.375rem;
        }

        .logout-btn:hover {
            background-color: rgba(239, 68, 68, 0.1);
        }

        .logout-btn i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div>
                    <h1>UTASHOP</h1>
                    <p>Panel de Moderación</p>
                </div>
            </div>
            
            <div class="p-4">
                <!-- Perfil del usuario -->
                <div class="user-profile">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-info">
                        <p><?php echo htmlspecialchars($nombreMostrar ?? 'Usuario'); ?></p>
                        <p>Moderador</p>
                    </div>
                </div>
                
                <!-- Menú de navegación -->
                <nav>
                    <a href="panel_moderador.php" class="menu-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="gestion_usuarios.php" class="menu-item active">
                        <i class="fas fa-users"></i>
                        <span>Usuarios</span>
                        <?php if (isset($estadisticas['total'])): ?>
                        <span class="badge"><?php echo $estadisticas['total']; ?></span>
                        <?php endif; ?>
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
                
                <!-- Botón de cierre de sesión -->
                <a href="cerrar_sesion.php" class="logout-btn">
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
                    <h2 class="text-2xl font-bold text-gray-900">Gestión de Usuarios</h2>
                    <div class="flex items-center space-x-6">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" class="search-input pl-10" placeholder="Buscar..." id="busquedaInput">
                        </div>
                    </div>
                </div>
            </header>

            <!-- Contenido principal -->
            <main class="p-6 flex-1 overflow-y-auto">
                <!-- Tarjetas de estadísticas -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue-100 text-blue-600">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Usuarios</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $estadisticas['total']; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-green-100 text-green-600">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Activos</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $estadisticas['activos']; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-red-100 text-red-600">
                            <i class="fas fa-user-slash"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Inactivos</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $estadisticas['inactivos']; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-yellow-100 text-yellow-600">
                            <i class="fas fa-user-lock"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Suspendidos</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $estadisticas['suspendidos']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Filtros y búsqueda -->
                <div class="card mb-6">
                    <div class="p-4">
                        <form method="GET" action="" class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar usuario</label>
                                <div class="relative">
                                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>" 
                                           class="search-input pl-10" placeholder="Buscar por nombre, usuario o email...">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                <select name="estado" class="search-input">
                                    <option value="">Todos los estados</option>
                                    <option value="activo" <?php echo ($_GET['estado'] ?? '') == 'activo' ? 'selected' : ''; ?>>Activos</option>
                                    <option value="inactivo" <?php echo ($_GET['estado'] ?? '') == 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                                    <option value="suspendido" <?php echo ($_GET['estado'] ?? '') == 'suspendido' ? 'selected' : ''; ?>>Suspendidos</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter mr-2"></i>Filtrar
                                </button>
                                <a href="gestion_usuarios.php" class="btn btn-outline ml-2">
                                    <i class="fas fa-redo mr-2"></i>Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de usuarios -->
                <div class="card">
                    <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">Lista de Usuarios</h3>
                        <div class="flex items-center space-x-2">
                            <input type="text" id="buscarUsuario" placeholder="Buscar usuarios..." class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <select id="filtroEstado" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="todos">Todos los estados</option>
                                <option value="activo">Activos</option>
                                <option value="inactivo">Inactivos</option>
                                <option value="suspendido">Suspendidos</option>
                            </select>
                        </div>
                    </div>
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Información</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registro</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($usuarios as $usuario): 
                                    if ($usuario['suspendido']) {
                                        $estado = 'Suspendido';
                                        $clase_estado = 'bg-red-100 text-red-800';
                                        $icono_estado = 'fas fa-ban';
                                    } elseif ($usuario['activo']) {
                                        $estado = 'Activo';
                                        $clase_estado = 'bg-green-100 text-green-800';
                                        $icono_estado = 'fas fa-check-circle';
                                    } else {
                                        $estado = 'Inactivo';
                                        $clase_estado = 'bg-yellow-100 text-yellow-800';
                                        $icono_estado = 'fas fa-exclamation-circle';
                                    }
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150" data-estado="<?php echo strtolower($estado); ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                                <?php if (!empty($usuario['foto_perfil'])): ?>
                                                    <img src="<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" alt="Foto de perfil" class="h-full w-full object-cover">
                                                <?php else: ?>
                                                    <i class="fas fa-user text-gray-500"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <?php if ($usuario['rol'] === 'usuario'): ?>
                                                    <a href="ver_usuario.php?id=<?php echo $usuario['id']; ?>" class="block">
                                                <?php endif; ?>
                                                    <div class="text-sm font-medium text-gray-900 hover:text-blue-600 transition-colors">
                                                        <?php echo htmlspecialchars($usuario['nombre_usuario']); ?>
                                                    </div>
                                                <?php if ($usuario['rol'] === 'usuario'): ?>
                                                    </a>
                                                <?php endif; ?>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($usuario['correo_electronico']); ?></div>
                                                <div class="mt-1">
                                                    <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-medium rounded-full <?php echo $clase_estado; ?>">
                                                        <i class="<?php echo $icono_estado; ?> mr-1"></i>
                                                        <?php echo $estado; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 font-medium">
                                            <?php echo !empty($usuario['nombre']) ? htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) : 'No especificado'; ?>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <i class="far fa-calendar-alt mr-1"></i>
                                            Registrado el <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?>
                                        </div>
                                        <?php if ($usuario['suspendido'] && !empty($usuario['motivo_suspension'])): ?>
                                            <div class="mt-2 p-2 bg-red-50 border-l-4 border-red-400 rounded-r">
                                                <div class="flex">
                                                    <div class="flex-shrink-0">
                                                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-xs text-red-700">
                                                            <span class="font-medium">Motivo de suspensión:</span> 
                                                            <?php echo htmlspecialchars($usuario['motivo_suspension']); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($usuario['correo_electronico']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-medium rounded-full <?php echo $clase_estado; ?>">
                                            <i class="<?php echo $icono_estado; ?> mr-1"></i>
                                            <?php echo $estado; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($usuario['rol'] === 'usuario'): ?>
                                            <a href="ver_usuario.php?id=<?php echo $usuario['id']; ?>" 
                                               class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150"
                                               title="Ver perfil">
                                                <i class="fas fa-eye mr-1"></i> Ver
                                            </a>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1.5 border border-gray-200 text-xs font-medium rounded-md text-gray-500 bg-gray-100">
                                                <i class="fas fa-ban mr-1"></i> No disponible
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <div class="text-sm text-gray-700">
                            Mostrando <?php echo count($usuarios); ?> de <?php echo $total_usuarios; ?> usuarios
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($pagina > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>" 
                                   class="btn btn-outline">
                                    <i class="fas fa-chevron-left mr-1"></i> Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <?php if ($i == $pagina): ?>
                                    <span class="btn btn-primary"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>" 
                                       class="btn btn-outline"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($pagina < $total_paginas): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" 
                                   class="btn btn-outline">
                                    Siguiente <i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
    // Filtrado de usuarios
    document.addEventListener('DOMContentLoaded', function() {
        const buscarInput = document.getElementById('buscarUsuario');
        const filtroEstado = document.getElementById('filtroEstado');
        const filasUsuarios = document.querySelectorAll('tbody tr[data-estado]');
        
        function filtrarUsuarios() {
            const terminoBusqueda = buscarInput.value.toLowerCase();
            const estadoSeleccionado = filtroEstado.value;
            
            filasUsuarios.forEach(fila => {
                const textoFila = fila.textContent.toLowerCase();
                const estadoFila = fila.getAttribute('data-estado');
                const coincideBusqueda = textoFila.includes(terminoBusqueda);
                const coincideEstado = (estadoSeleccionado === 'todos' || estadoFila === estadoSeleccionado);
                
                if (coincideBusqueda && coincideEstado) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        }
        
        buscarInput.addEventListener('input', filtrarUsuarios);
        filtroEstado.addEventListener('change', filtrarUsuarios);
    });
    
    // Función para realizar peticiones AJAX
    function realizarAccion(accion, usuarioId, datos = {}) {
        const formData = new FormData();
        formData.append('accion', accion);
        formData.append('usuario_id', usuarioId);
        
        // Agregar datos adicionales si los hay
        Object.keys(datos).forEach(key => {
            formData.append(key, datos[key]);
        });
        
        // Mostrar indicador de carga
        Swal.fire({
            title: 'Procesando...',
            text: 'Por favor espera...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        return fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message,
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    didClose: () => {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Ocurrió un error al procesar la solicitud',
                    confirmButtonColor: '#3b82f6'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al procesar la solicitud',
                confirmButtonColor: '#3b82f6'
            });
        });
    }
    
    // Funciones para las acciones de usuario
    function activarUsuario(usuarioId) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Deseas activar este usuario?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, activar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                realizarAccion('activar_usuario', usuarioId);
            }
        });
    }

    function desactivarUsuario(usuarioId) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Deseas desactivar este usuario?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                realizarAccion('desactivar_usuario', usuarioId);
            }
        });
    }

    function mostrarModalSuspender(usuarioId) {
        Swal.fire({
            title: 'Suspender usuario',
            html: `
                <div class="text-left">
                    <p class="mb-4">Ingresa el motivo de la suspensión:</p>
                    <textarea id="motivoSuspension" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="4" placeholder="Motivo de la suspensión..."></textarea>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Suspender',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            preConfirm: () => {
                const motivo = document.getElementById('motivoSuspension').value.trim();
                if (!motivo) {
                    Swal.showValidationMessage('Debes ingresar un motivo para la suspensión');
                    return false;
                }
                return { motivo: motivo };
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                realizarAccion('suspender_usuario', usuarioId, { motivo: result.value.motivo });
            }
        });
    }

    function quitarSuspension(usuarioId) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Deseas quitar la suspensión de este usuario?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, quitar suspensión',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                realizarAccion('quitar_suspension_usuario', usuarioId);
            }
        });
    }

    // Búsqueda en tiempo real  
    document.getElementById('busquedaInput').addEventListener('input', function(e) {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            if (this.value.length >= 3 || this.value.length === 0) {
                this.form.submit();
            }
        }, 500);
    });
    </script>
</body>
</html>