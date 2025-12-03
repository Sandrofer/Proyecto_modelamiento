<?php
// Iniciar sesión
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: index.php');
    exit();
}

// Incluir la conexión a la base de datos
require 'basededatos.php';

// Obtener estadísticas generales
$estadisticas = [
    'total_usuarios' => $conexion->query("SELECT COUNT(*) as total FROM usuario")->fetch_assoc()['total'],
    'total_productos' => $conexion->query("SELECT COUNT(*) as total FROM productos")->fetch_assoc()['total'],
    'productos_activos' => $conexion->query("SELECT COUNT(*) as total FROM productos WHERE estado = 'activo'")->fetch_assoc()['total'],
    'productos_revision' => $conexion->query("SELECT COUNT(*) as total FROM productos WHERE estado = 'revision'")->fetch_assoc()['total'],
    'ingresos_mes' => $conexion->query("SELECT SUM(total) as total FROM ventas WHERE MONTH(fecha_venta) = MONTH(CURRENT_DATE()) AND YEAR(fecha_venta) = YEAR(CURRENT_DATE())")->fetch_assoc()['total'] ?? 0,
    'ventas_mes' => $conexion->query("SELECT COUNT(*) as total FROM ventas WHERE MONTH(fecha_venta) = MONTH(CURRENT_DATE()) AND YEAR(fecha_venta) = YEAR(CURRENT_DATE())")->fetch_assoc()['total'],
];

// Obtener datos para gráficos
$ventas_por_mes = $conexion->query("
    SELECT 
        DATE_FORMAT(fecha_venta, '%Y-%m') as mes,
        COUNT(*) as total_ventas,
        SUM(total) as monto_total
    FROM ventas
    WHERE fecha_venta >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mes
    ORDER BY mes
")->fetch_all(MYSQLI_ASSOC);

$categorias_populares = $conexion->query("
    SELECT 
        c.nombre as categoria,
        COUNT(p.id) as total_productos
    FROM categorias c
    LEFT JOIN productos p ON c.id = p.categoria_id
    GROUP BY c.id
    ORDER BY total_productos DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - UTASHOP</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background-color: #1e40af;
            color: white;
            padding: 1rem;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            border-radius: 0.375rem;
            color: #e5e7eb;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .menu-item:hover, .menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .menu-item i {
            margin-right: 0.75rem;
            width: 1.5rem;
            text-align: center;
        }
        
        .stat-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-value {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1f2937;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .stat-change {
            display: flex;
            align-items: center;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
        
        .stat-change.positive {
            color: #10b981;
        }
        
        .stat-change.negative {
            color: #ef4444;
        }
        
        .chart-container {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="mb-8">
                <h1 class="text-xl font-bold">UTASHOP</h1>
                <p class="text-sm text-blue-200">Panel de Administración</p>
            </div>
            
            <nav class="space-y-1">
                <a href="panel_administrador.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="gestion_usuarios.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Usuarios</span>
                </a>
                <a href="gestion_productos.php" class="menu-item">
                    <i class="fas fa-box"></i>
                    <span>Productos</span>
                </a>
                <a href="reportes.php" class="menu-item active">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                </a>
                <a href="cerrar_sesion.php" class="menu-item text-red-200 hover:bg-red-600">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </nav>
        </div>
        
        <!-- Contenido principal -->
        <div class="main-content w-full">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Reportes y Estadísticas</h1>
                <p class="text-gray-600">Visualiza y analiza el rendimiento de la plataforma</p>
            </div>
            
            <!-- Tarjetas de resumen -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total de usuarios -->
                <div class="stat-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo number_format($estadisticas['total_usuarios']); ?></div>
                            <div class="stat-label">Usuarios Totales</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up mr-1"></i> 12% este mes
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Productos activos -->
                <div class="stat-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fas fa-box text-xl"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo number_format($estadisticas['productos_activos']); ?></div>
                            <div class="stat-label">Productos Activos</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up mr-1"></i> 8% este mes
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ventas del mes -->
                <div class="stat-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                            <i class="fas fa-shopping-cart text-xl"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo number_format($estadisticas['ventas_mes']); ?></div>
                            <div class="stat-label">Ventas del Mes</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up mr-1"></i> 15% vs mes anterior
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ingresos del mes -->
                <div class="stat-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                            <i class="fas fa-dollar-sign text-xl"></i>
                        </div>
                        <div>
                            <div class="stat-value">$<?php echo number_format($estadisticas['ingresos_mes'], 2); ?></div>
                            <div class="stat-label">Ingresos del Mes</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up mr-1"></i> 18% vs mes anterior
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráficos -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Gráfico de ventas -->
                <div class="chart-container">
                    <h2 class="chart-title">Ventas Mensuales</h2>
                    <canvas id="ventasChart"></canvas>
                </div>
                
                <!-- Gráfico de categorías -->
                <div class="chart-container">
                    <h2 class="chart-title">Categorías más Populares</h2>
                    <canvas id="categoriasChart"></canvas>
                </div>
            </div>
            
            <!-- Tabla de productos en revisión -->
            <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">Productos en Revisión</h2>
                        <a href="gestion_productos.php?estado=revision" class="text-sm text-indigo-600 hover:text-indigo-800">Ver todos</a>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendedor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $productos_revision = $conexion->query("
                                SELECT p.*, u.nombre_usuario as vendedor 
                                FROM productos p 
                                JOIN usuario u ON p.vendedor_id = u.id 
                                WHERE p.estado = 'revision' 
                                ORDER BY p.fecha_publicacion DESC 
                                LIMIT 5
                            ")->fetch_all(MYSQLI_ASSOC);
                            
                            foreach ($productos_revision as $producto): 
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-md" src="<?php echo htmlspecialchars($producto['imagen_url'] ?? 'img/placeholder-product.jpg'); ?>" alt="">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($producto['vendedor']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">$<?php echo number_format($producto['precio'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d/m/Y', strtotime($producto['fecha_publicacion'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="gestion_productos.php?accion=aprobar&id=<?php echo $producto['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Aprobar</a>
                                    <a href="gestion_productos.php?accion=rechazar&id=<?php echo $producto['id']; ?>" class="text-red-600 hover:text-red-900">Rechazar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($productos_revision)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay productos en revisión en este momento.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Gráfico de ventas mensuales
        const ventasCtx = document.getElementById('ventasChart').getContext('2d');
        const ventasChart = new Chart(ventasCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php 
                    $meses = [];
                    foreach ($ventas_por_mes as $venta) {
                        $fecha = DateTime::createFromFormat('Y-m', $venta['mes']);
                        $meses[] = '"' . $fecha->format('M Y') . '"';
                    }
                    echo implode(', ', $meses);
                    ?>
                ],
                datasets: [{
                    label: 'Ventas',
                    data: [
                        <?php echo implode(', ', array_column($ventas_por_mes, 'total_ventas')); ?>
                    ],
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderColor: '#4f46e5',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        
        // Gráfico de categorías populares
        const categoriasCtx = document.getElementById('categoriasChart').getContext('2d');
        const categoriasChart = new Chart(categoriasCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php 
                    $nombres = [];
                    $totales = [];
                    foreach ($categorias_populares as $categoria) {
                        $nombres[] = '"' . addslashes($categoria['categoria']) . '"';
                        $totales[] = $categoria['total_productos'];
                    }
                    echo implode(', ', $nombres);
                    ?>
                ],
                datasets: [{
                    data: [<?php echo implode(', ', $totales); ?>],
                    backgroundColor: [
                        '#4f46e5',
                        '#6366f1',
                        '#818cf8',
                        '#a5b4fc',
                        '#c7d2fe'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>
