<?php
session_start();
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}

// Procesar eliminación de producto
if (isset($_GET['eliminar'])) {
    $producto_id = intval($_GET['eliminar']);
    
    // Verificar que el producto pertenece al usuario
    $check_stmt = $conexion->prepare("SELECT usuario_vendedor FROM productos WHERE id = ?");
    $check_stmt->bind_param("i", $producto_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $producto = $check_result->fetch_assoc();
        
        if ($producto['usuario_vendedor'] === $_SESSION['username']) {
            // Eliminación suave (recomendado)
            $delete_stmt = $conexion->prepare("UPDATE productos SET eliminado = TRUE, fecha_eliminacion = NOW(), estado = 'inactivo' WHERE id = ?");
            $delete_stmt->bind_param("i", $producto_id);
            
            if ($delete_stmt->execute()) {
                $mensaje = "✅ Producto eliminado correctamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "❌ Error al eliminar el producto";
                $tipo_mensaje = "error";
            }
            $delete_stmt->close();
        } else {
            $mensaje = "❌ No tienes permisos para eliminar este producto";
            $tipo_mensaje = "error";
        }
    }
    $check_stmt->close();
}

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_estado'])) {
    $producto_id = intval($_POST['producto_id']);
    $nuevo_estado = $conexion->real_escape_string($_POST['nuevo_estado']);
    
    // Verificar que el producto pertenece al usuario
    $check_stmt = $conexion->prepare("SELECT usuario_vendedor FROM productos WHERE id = ?");
    $check_stmt->bind_param("i", $producto_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $producto = $check_result->fetch_assoc();
        
        if ($producto['usuario_vendedor'] === $_SESSION['username']) {
            $update_stmt = $conexion->prepare("UPDATE productos SET estado = ? WHERE id = ?");
            $update_stmt->bind_param("si", $nuevo_estado, $producto_id);
            
            if ($update_stmt->execute()) {
                $mensaje = "✅ Estado del producto actualizado correctamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "❌ Error al actualizar el estado";
                $tipo_mensaje = "error";
            }
            $update_stmt->close();
        } else {
            $mensaje = "❌ No tienes permisos para modificar este producto";
            $tipo_mensaje = "error";
        }
    }
    $check_stmt->close();
}

// Obtener productos del usuario
$stmt = $conexion->prepare("SELECT * FROM productos WHERE usuario_vendedor = ? AND (eliminado = FALSE OR eliminado IS NULL) ORDER BY fecha_publicacion DESC");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$productos = $stmt->get_result();

// Obtener estadísticas de productos por estado
$estadisticas_stmt = $conexion->prepare("
    SELECT estado, COUNT(*) as total 
    FROM productos 
    WHERE usuario_vendedor = ? AND (eliminado = FALSE OR eliminado IS NULL) 
    GROUP BY estado
");
$estadisticas_stmt->bind_param("s", $_SESSION['username']);
$estadisticas_stmt->execute();
$estadisticas_result = $estadisticas_stmt->get_result();

$contadores = [
    'activo' => 0,
    'inactivo' => 0,
    'revision' => 0,
    'rechazado' => 0,
    'vendido' => 0,
    'pendiente' => 0
];

while ($row = $estadisticas_result->fetch_assoc()) {
    $contadores[$row['estado']] = $row['total'];
}
$estadisticas_stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Productos - UTASHOP</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .producto-card {
            transition: all 0.3s ease;
        }
        .producto-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .estado-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
        }
        .estado-activo { background-color: #10B981; color: white; }
        .estado-inactivo { background-color: #6B7280; color: white; }
        .estado-vendido { background-color: #EF4444; color: white; }
        .estado-pendiente { background-color: #F59E0B; color: white; }
        .estado-revision { background-color: #3B82F6; color: white; }
        .estado-rechazado { background-color: #DC2626; color: white; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Barra de navegación -->
    <nav class="bg-gray-900 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="pagina_principal.php" class="text-2xl font-bold">UTASHOP</a>
            <div class="flex items-center space-x-4">
                <a href="pagina_principal.php" class="hover:text-gray-300">
                    <i class="fas fa-home mr-2"></i>Inicio
                </a>
                <a href="publicar_producto.php" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-md">
                    <i class="fas fa-plus mr-2"></i>Publicar
                </a>
                <span class="text-gray-300">Hola, <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Mis Productos</h1>
                    <p class="text-gray-600">Gestiona tus publicaciones</p>
                </div>
                <a href="publicar_producto.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
                    <i class="fas fa-plus-circle mr-2"></i>Nuevo Producto
                </a>
            </div>

            <?php if (isset($mensaje)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $tipo_mensaje == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-8">
                <div class="bg-white p-4 rounded-lg shadow text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $productos->num_rows; ?></div>
                    <div class="text-gray-600">Total</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo $contadores['activo']; ?></div>
                    <div class="text-gray-600">Activos</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow text-center">
                    <div class="text-2xl font-bold text-red-600"><?php echo $contadores['vendido']; ?></div>
                    <div class="text-gray-600">Vendidos</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow text-center">
                    <div class="text-2xl font-bold text-yellow-600"><?php echo $contadores['pendiente']; ?></div>
                    <div class="text-gray-600">Pendientes</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow text-center">
                    <div class="text-2xl font-bold text-gray-600"><?php echo $contadores['inactivo']; ?></div>
                    <div class="text-gray-600">Inactivos</div>
                </div>
            </div>

            <!-- Lista de productos -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <?php if ($productos->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while($producto = $productos->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <?php if($producto['imagen']): ?>
                                                    <img class="h-10 w-10 rounded-lg object-cover" src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                                <?php else: ?>
                                                    <div class="h-10 w-10 bg-gray-200 rounded-lg flex items-center justify-center">
                                                        <i class="fas fa-image text-gray-400"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($producto['categoria']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-green-600">$<?php echo number_format($producto['precio'], 2); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form method="POST" class="flex items-center space-x-2">
                                            <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                            <select name="nuevo_estado" onchange="this.form.submit()" class="text-sm border rounded p-1">
                                                <option value="activo" <?php echo $producto['estado'] == 'activo' ? 'selected' : ''; ?>>Activo</option>
                                                <option value="inactivo" <?php echo $producto['estado'] == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                                <option value="vendido" <?php echo $producto['estado'] == 'vendido' ? 'selected' : ''; ?>>Vendido</option>
                                                <option value="pendiente" <?php echo $producto['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                            </select>
                                            <input type="hidden" name="cambiar_estado" value="1">
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d/m/Y', strtotime($producto['fecha_publicacion'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="editar_producto.php?id=<?php echo $producto['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit mr-1"></i>Editar
                                            </a>
                                            <a href="?eliminar=<?php echo $producto['id']; ?>" 
                                               class="text-red-600 hover:text-red-900"
                                               onclick="return confirm('¿Estás seguro de que quieres eliminar este producto?')">
                                                <i class="fas fa-trash mr-1"></i>Eliminar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-500 mb-2">No tienes productos publicados</h3>
                        <p class="text-gray-400 mb-6">Comienza publicando tu primer producto o servicio</p>
                        <a href="publicar_producto.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-plus-circle mr-2"></i>Publicar Primer Producto
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal de confirmación (mejorado) -->
    <script>
        function confirmarEliminacion(nombre) {
            return confirm(`¿Estás seguro de que quieres eliminar "${nombre}"?\nEsta acción no se puede deshacer.`);
        }
        
        // Agregar confirmación a todos los enlaces de eliminar
        document.addEventListener('DOMContentLoaded', function() {
            const enlacesEliminar = document.querySelectorAll('a[href*="eliminar="]');
            enlacesEliminar.forEach(enlace => {
                enlace.addEventListener('click', function(e) {
                    const nombreProducto = this.closest('tr').querySelector('.text-sm.font-medium.text-gray-900').textContent;
                    if (!confirmarEliminacion(nombreProducto)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php 
$stmt->close();
$conexion->close();
?>