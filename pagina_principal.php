<?php
session_start();
$conexion = new mysqli("localhost", "root", "mamiypapi1", "proyecto_mod");

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}

// Obtener el ID del usuario actual
$nombre_usuario = $_SESSION['usuario_id'];
$sql_usuario_id = "SELECT id FROM usuario WHERE nombre_usuario = ?";
$stmt = $conexion->prepare($sql_usuario_id);
$stmt->bind_param("s", $nombre_usuario);
$stmt->execute();
$resultado_usuario = $stmt->get_result();
$usuario_data = $resultado_usuario->fetch_assoc();
$usuario_id = $usuario_data['id'];
$stmt->close();

// Procesar agregar/eliminar favoritos
if (isset($_POST['accion_favorito'])) {
    $producto_id = $_POST['producto_id'];
    
    if ($_POST['accion_favorito'] == 'agregar') {
        // Agregar a favoritos
        $sql_agregar = "INSERT INTO productos_favoritos (usuario_id, producto_id) VALUES (?, ?)";
        $stmt = $conexion->prepare($sql_agregar);
        $stmt->bind_param("ii", $usuario_id, $producto_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($_POST['accion_favorito'] == 'eliminar') {
        // Eliminar de favoritos
        $sql_eliminar = "DELETE FROM productos_favoritos WHERE usuario_id = ? AND producto_id = ?";
        $stmt = $conexion->prepare($sql_eliminar);
        $stmt->bind_param("ii", $usuario_id, $producto_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Redirigir para evitar reenvío del formulario
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['busqueda']) ? '?busqueda=' . urlencode($_GET['busqueda']) : ''));
    exit();
}

// Procesar búsqueda
$busqueda = '';
$resultados_busqueda = false;
$sql_productos = "SELECT p.*, u.nombre as vendedor_nombre 
                 FROM productos p 
                 JOIN usuario u ON p.usuario_vendedor = u.nombre_usuario 
                 WHERE p.estado = 'activo'";

if (isset($_GET['busqueda']) && !empty(trim($_GET['busqueda']))) {
    $busqueda = trim($_GET['busqueda']);
    $sql_productos .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.categoria LIKE ? OR u.nombre LIKE ?)";
    $resultados_busqueda = true;
}

$sql_productos .= " ORDER BY p.fecha_publicacion DESC LIMIT 12";

// Preparar y ejecutar consulta de productos
$stmt = $conexion->prepare($sql_productos);
if ($resultados_busqueda) {
    $param_busqueda = "%$busqueda%";
    $stmt->bind_param("ssss", $param_busqueda, $param_busqueda, $param_busqueda, $param_busqueda);
}
$stmt->execute();
$productos_result = $stmt->get_result();
$stmt->close();

// Obtener IDs de productos favoritos del usuario actual
$sql_favoritos = "SELECT producto_id FROM productos_favoritos WHERE usuario_id = ?";
$stmt = $conexion->prepare($sql_favoritos);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$favoritos_result = $stmt->get_result();
$productos_favoritos = [];
while ($favorito = $favoritos_result->fetch_assoc()) {
    $productos_favoritos[] = $favorito['producto_id'];
}
$stmt->close();

// Obtener categorías populares para el sidebar de búsqueda
$sql_categorias = "SELECT categoria, COUNT(*) as total 
                   FROM productos 
                   WHERE categoria IS NOT NULL AND estado = 'activo' 
                   GROUP BY categoria 
                   ORDER BY total DESC 
                   LIMIT 8";
$categorias_result = $conexion->query($sql_categorias);

// Obtener productos populares (más guardados como favoritos)
$sql_populares = "SELECT p.*, u.nombre as vendedor_nombre, 
                 COUNT(pf.id) as total_favoritos
                 FROM productos p 
                 JOIN usuario u ON p.usuario_vendedor = u.nombre_usuario 
                 LEFT JOIN productos_favoritos pf ON p.id = pf.producto_id
                 WHERE p.estado = 'activo' 
                 GROUP BY p.id 
                 ORDER BY total_favoritos DESC 
                 LIMIT 4";
$populares_result = $conexion->query($sql_populares);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTASHOP - Página Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .producto:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .carrito-icon {
            position: relative;
        }
        .carrito-contador {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #EF4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .btn-vender {
            background: linear-gradient(135deg, #28a745, #20c997);
            transition: all 0.3s ease;
        }
        .btn-vender:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        .favorito-btn {
            transition: all 0.3s ease;
        }
        .favorito-btn.favorito {
            color: #EF4444;
        }
        .favorito-btn:hover {
            transform: scale(1.1);
        }
        .categoria-btn {
            transition: all 0.3s ease;
        }
        .categoria-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
                <form method="GET" action="pagina_principal.php" class="relative">
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" 
                           placeholder="Buscar productos..." 
                           class="w-full px-4 py-2 rounded-full text-gray-800 focus:outline-none">
                    <button type="submit" class="absolute right-3 top-2 text-gray-500 hover:text-gray-700">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if ($resultados_busqueda): ?>
                        <a href="pagina_principal.php" class="absolute right-10 top-2 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- Botón VENDER destacado -->
                <a href="publicar_producto.php" class="btn-vender px-4 py-2 rounded-full font-semibold text-white">
                    <i class="fas fa-plus-circle mr-2"></i>VENDER
                </a>
                 <a href="mis_productos.php" class="hover:text-gray-300">
                    <i class="fas fa-store mr-2"></i>Mis Productos
                </a>
    
                <a href="perfil.php" class="hover:text-gray-300">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
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
        <!-- Banner principal -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl p-8 text-white mb-12">
            <h1 class="text-4xl font-bold mb-4">
                <?php if ($resultados_busqueda): ?>
                    Resultados de búsqueda
                <?php else: ?>
                    Bienvenido a UTASHOP
                <?php endif; ?>
            </h1>
            <p class="text-xl mb-6">
                <?php if ($resultados_busqueda): ?>
                    Encontramos <?php echo $productos_result->num_rows; ?> productos para "<?php echo htmlspecialchars($busqueda); ?>"
                <?php else: ?>
                    Compra y vende de forma segura en tu comunidad
                <?php endif; ?>
            </p>
            <div class="flex gap-4">
                <?php if ($resultados_busqueda): ?>
                    <a href="pagina_principal.php" class="bg-white text-blue-800 px-6 py-2 rounded-full font-semibold hover:bg-gray-100">
                        <i class="fas fa-arrow-left mr-2"></i>Volver a todos los productos
                    </a>
                <?php else: ?>
                    <a href="#productos" class="bg-white text-blue-800 px-6 py-2 rounded-full font-semibold hover:bg-gray-100">
                        Explorar Productos
                    </a>
                <?php endif; ?>
                <a href="publicar_producto.php" class="bg-green-500 text-white px-6 py-2 rounded-full font-semibold hover:bg-green-600">
                    <i class="fas fa-plus-circle mr-2"></i>Publicar Producto
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar de filtros y categorías -->
            <div class="lg:col-span-1">
                <!-- Categorías populares -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h3 class="text-xl font-bold mb-4 text-gray-800">Categorías Populares</h3>
                    <div class="space-y-2">
                        <?php if ($categorias_result->num_rows > 0): ?>
                            <?php while($categoria = $categorias_result->fetch_assoc()): ?>
                                <a href="pagina_principal.php?busqueda=<?php echo urlencode($categoria['categoria']); ?>" 
                                   class="categoria-btn block bg-gray-50 hover:bg-blue-50 text-gray-700 hover:text-blue-700 px-4 py-3 rounded-lg transition-all">
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium"><?php echo htmlspecialchars($categoria['categoria']); ?></span>
                                        <span class="bg-gray-200 text-gray-600 text-xs px-2 py-1 rounded-full">
                                            <?php echo $categoria['total']; ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-4">No hay categorías disponibles</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Productos populares -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-xl font-bold mb-4 text-gray-800">Productos Populares</h3>
                    <div class="space-y-4">
                        <?php if ($populares_result->num_rows > 0): ?>
                            <?php while($popular = $populares_result->fetch_assoc()): ?>
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <?php if($popular['imagen']): ?>
                                            <img src="<?php echo htmlspecialchars($popular['imagen']); ?>" 
                                                 alt="<?php echo htmlspecialchars($popular['nombre']); ?>" 
                                                 class="w-12 h-12 object-cover rounded-lg">
                                        <?php else: ?>
                                            <i class="fas fa-image text-gray-400"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            <?php echo htmlspecialchars($popular['nombre']); ?>
                                        </p>
                                        <p class="text-sm text-green-600 font-semibold">
                                            $<?php echo number_format($popular['precio'], 2); ?>
                                        </p>
                                        <div class="flex items-center text-xs text-gray-500">
                                            <i class="fas fa-heart text-red-500 mr-1"></i>
                                            <span><?php echo $popular['total_favoritos']; ?> favoritos</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-4">No hay productos populares</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Contenido principal de productos -->
            <div class="lg:col-span-3">
                <!-- Productos destacados -->
                <h2 id="productos" class="text-2xl font-bold mb-6">
                    <?php if ($resultados_busqueda): ?>
                        Productos Encontrados
                    <?php else: ?>
                        Productos Recientes
                    <?php endif; ?>
                </h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                    <?php if ($productos_result->num_rows > 0): ?>
                        <?php while($producto = $productos_result->fetch_assoc()): ?>
                            <div class="bg-white rounded-lg shadow-md overflow-hidden producto">
                                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                    <?php if($producto['imagen']): ?>
                                        <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" 
                                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                             class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <i class="fas fa-image text-4xl text-gray-400"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="p-4">
                                    <h3 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                    <p class="text-gray-600 mb-2 text-sm"><?php echo htmlspecialchars(substr($producto['descripcion'], 0, 80)); ?>...</p>
                                    <p class="text-sm text-gray-500 mb-3">Vendedor: <?php echo htmlspecialchars($producto['vendedor_nombre']); ?></p>
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-lg text-green-600">$<?php echo number_format($producto['precio'], 2); ?></span>
                                        <div class="flex gap-2">
                                            <form method="POST" class="m-0">
                                                <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                                <?php if (in_array($producto['id'], $productos_favoritos)): ?>
                                                    <input type="hidden" name="accion_favorito" value="eliminar">
                                                    <button type="submit" class="favorito-btn favorito bg-gray-200 p-2 rounded hover:bg-gray-300">
                                                        <i class="fas fa-heart"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="accion_favorito" value="agregar">
                                                    <button type="submit" class="favorito-btn bg-gray-200 text-gray-700 p-2 rounded hover:bg-gray-300">
                                                        <i class="fas fa-heart"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                            <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-full text-center py-12">
                            <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-500 mb-2">
                                <?php if ($resultados_busqueda): ?>
                                    No se encontraron productos para "<?php echo htmlspecialchars($busqueda); ?>"
                                <?php else: ?>
                                    No hay productos publicados
                                <?php endif; ?>
                            </h3>
                            <p class="text-gray-400 mb-4">
                                <?php if ($resultados_busqueda): ?>
                                    Intenta con otros términos de búsqueda
                                <?php else: ?>
                                    Sé el primero en publicar un producto
                                <?php endif; ?>
                            </p>
                            <a href="publicar_producto.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-plus-circle mr-2"></i>Publicar Producto
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sección para vendedores (solo mostrar si no hay búsqueda) -->
                <?php if (!$resultados_busqueda): ?>
                    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-8 text-white mb-12">
                        <div class="flex flex-col md:flex-row justify-between items-center">
                            <div class="mb-6 md:mb-0">
                                <h2 class="text-3xl font-bold mb-2">¿Quieres Vender?</h2>
                                <p class="text-lg opacity-90">Publica tus productos y llega a más clientes</p>
                            </div>
                            <div class="flex gap-4">
                                <a href="publicar_producto.php" class="bg-white text-green-600 px-6 py-3 rounded-full font-semibold hover:bg-gray-100">
                                    <i class="fas fa-plus-circle mr-2"></i>Publicar Producto
                                </a>
                                <a href="mis_productos.php" class="border-2 border-white text-white px-6 py-3 rounded-full font-semibold hover:bg-white hover:text-green-600">
                                    <i class="fas fa-store mr-2"></i>Mis Productos
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Pie de página -->
    <footer class="bg-gray-900 text-white py-8">
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
        // Funcionalidad del carrito
        document.addEventListener('DOMContentLoaded', function() {
            const botonesAgregar = document.querySelectorAll('button:has(.fa-cart-plus)');
            const contadorCarrito = document.querySelector('.carrito-contador');
            let contador = 0;

            botonesAgregar.forEach(boton => {
                boton.addEventListener('click', function() {
                    contador++;
                    contadorCarrito.textContent = contador;
                    
                    const boton = this;
                    boton.innerHTML = '<i class="fas fa-check"></i>';
                    boton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    boton.classList.add('bg-green-500', 'hover:bg-green-600');
                    
                    setTimeout(() => {
                        boton.innerHTML = '<i class="fas fa-cart-plus"></i>';
                        boton.classList.remove('bg-green-500', 'hover:bg-green-600');
                        boton.classList.add('bg-blue-600', 'hover:bg-blue-700');
                    }, 2000);
                });
            });
        });
    </script>
</body>
</html>
<?php $conexion->close(); ?>