<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit;
}

// Verificar que hay un pedido reciente
if (!isset($_SESSION['ultimo_pedido_id'])) {
    header('Location: ../index.html');
    exit;
}

$pedido_id = $_SESSION['ultimo_pedido_id'];

// Obtener detalles del pedido
require_once '../carrito/db.php';

try {
    $stmt = $pdo->prepare("
        SELECT p.*, mp.tipo as metodo_pago_tipo, mp.numero_enmascarado 
        FROM pedido p 
        LEFT JOIN metodo_pago mp ON p.metodo_pago_id = mp.id 
        WHERE p.id = ? AND p.usuario_id = ?
    ");
    $stmt->execute([$pedido_id, $_SESSION['user_id']]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        header('Location: ../index.html');
        exit;
    }
    
    // Obtener productos del pedido
    $stmt = $pdo->prepare("
        SELECT pd.*, pr.nombre, pr.descripcion 
        FROM pedido_detalle pd 
        JOIN productos pr ON pd.producto_id = pr.id 
        WHERE pd.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $productos_pedido = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error al obtener detalles del pedido: " . $e->getMessage());
    header('Location: ../index.html');
    exit;
}

// Limpiar el ID del pedido de la sesión para evitar accesos repetidos
unset($_SESSION['ultimo_pedido_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Compra Exitosa! - Supermercado</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --verde-exito: #28a745;
            --verde-claro: #d4edda;
            --verde-oscuro: #155724;
            --azul: #007bff;
            --gris-claro: #f8f9fa;
            --texto: #333;
            --blanco: #ffffff;
            --sombra: 0 4px 12px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .container {
            background: var(--blanco);
            border-radius: 20px;
            box-shadow: var(--sombra);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-exito {
            background: var(--verde-exito);
            color: var(--blanco);
            text-align: center;
            padding: 3rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .header-exito::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.1; }
        }

        .icono-exito {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 1s ease-in-out;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .header-exito h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .header-exito p {
            font-size: 1.1rem;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }

        .contenido {
            padding: 2rem;
        }

        .detalle-pedido {
            background: var(--gris-claro);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .detalle-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            font-size: 0.95rem;
        }

        .detalle-row:last-child {
            margin-bottom: 0;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--verde-oscuro);
            border-top: 2px solid var(--verde-exito);
            padding-top: 0.8rem;
        }

        .productos-lista {
            background: var(--blanco);
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin: 1rem 0;
        }

        .producto-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .producto-item:last-child {
            border-bottom: none;
        }

        .producto-info h4 {
            color: var(--texto);
            margin-bottom: 0.3rem;
        }

        .producto-info p {
            color: #666;
            font-size: 0.9rem;
        }

        .producto-precio {
            font-weight: 600;
            color: var(--azul);
        }

        .botones {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-primario {
            background: var(--verde-exito);
            color: var(--blanco);
        }

        .btn-primario:hover {
            background: var(--verde-oscuro);
            transform: translateY(-2px);
        }

        .btn-secundario {
            background: var(--azul);
            color: var(--blanco);
        }

        .btn-secundario:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .tiempo-entrega {
            background: linear-gradient(45deg, #ffecd2 0%, #fcb69f 100%);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            margin: 1.5rem 0;
        }

        .tiempo-entrega i {
            font-size: 2rem;
            color: #ff6b35;
            margin-bottom: 0.5rem;
        }

        .tiempo-entrega h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .tiempo-entrega p {
            color: #666;
        }

        @media (max-width: 600px) {
            body {
                padding: 1rem;
            }
            
            .header-exito {
                padding: 2rem 1rem;
            }
            
            .icono-exito {
                font-size: 3rem;
            }
            
            .header-exito h1 {
                font-size: 1.5rem;
            }
            
            .botones {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-exito">
            <div class="icono-exito">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>¡Perfecto!</h1>
            <p>Tus productos están en camino</p>
        </div>

        <div class="contenido">
            <div class="detalle-pedido">
                <h3><i class="fas fa-receipt"></i> Detalles de tu Pedido</h3>
                <div class="detalle-row">
                    <span>Número de Pedido:</span>
                    <span><strong>#<?php echo str_pad($pedido['id'], 6, '0', STR_PAD_LEFT); ?></strong></span>
                </div>
                <div class="detalle-row">
                    <span>Fecha:</span>
                    <span><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></span>
                </div>
                <div class="detalle-row">
                    <span>Método de Pago:</span>
                    <span><?php echo htmlspecialchars($pedido['metodo_pago_tipo']); ?>
                        <?php if ($pedido['numero_enmascarado']): ?>
                            (<?php echo htmlspecialchars($pedido['numero_enmascarado']); ?>)
                        <?php endif; ?>
                    </span>
                </div>
                <div class="detalle-row">
                    <span>Dirección de Envío:</span>
                    <span><?php echo htmlspecialchars($pedido['direccion_envio']); ?></span>
                </div>
                <div class="detalle-row">
                    <span>Total Pagado:</span>
                    <span>$<?php echo number_format($pedido['total'], 2); ?></span>
                </div>
            </div>

            <div class="productos-lista">
                <h4 style="padding: 1rem; margin: 0; background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                    <i class="fas fa-shopping-bag"></i> Productos Comprados
                </h4>
                <?php foreach ($productos_pedido as $producto): ?>
                <div class="producto-item">
                    <div class="producto-info">
                        <h4><?php echo htmlspecialchars($producto['nombre']); ?></h4>
                        <p>Cantidad: <?php echo $producto['cantidad']; ?> × $<?php echo number_format($producto['precio_unitario'], 2); ?></p>
                    </div>
                    <div class="producto-precio">
                        $<?php echo number_format($producto['subtotal'], 2); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="tiempo-entrega">
                <i class="fas fa-truck"></i>
                <h3>Tiempo de Entrega</h3>
                <p>Tu pedido llegará en 2-3 días hábiles</p>
            </div>

            <div class="botones">
                <a href="../index.html" class="btn btn-primario">
                    <i class="fas fa-home"></i> Volver al Inicio
                </a>
                <a href="../productos.php" class="btn btn-secundario">
                    <i class="fas fa-shopping-cart"></i> Seguir Comprando
                </a>
            </div>
        </div>
    </div>

    <script>
        // Confetti effect
        function createConfetti() {
            const colors = ['#ff6b35', '#28a745', '#007bff', '#ffc107', '#dc3545'];
            
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.style.position = 'fixed';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.top = '-10px';
                    confetti.style.width = '10px';
                    confetti.style.height = '10px';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.borderRadius = '50%';
                    confetti.style.zIndex = '9999';
                    confetti.style.pointerEvents = 'none';
                    confetti.style.animation = `fall 3s linear forwards`;
                    
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => {
                        confetti.remove();
                    }, 3000);
                }, i * 100);
            }
        }

        // Add falling animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fall {
                to {
                    transform: translateY(100vh) rotate(360deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Trigger confetti on load
        window.addEventListener('load', () => {
            setTimeout(createConfetti, 500);
        });
    </script>
</body>
</html>