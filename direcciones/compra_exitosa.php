<?php
session_start();

// Verificar usuario logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit;
}

// Verificar que hay una venta reciente
if (!isset($_SESSION['ultima_venta_id'])) {
    header('Location: ../index.html');
    exit;
}

$venta_id = $_SESSION['ultima_venta_id'];

// Obtener detalles de la venta
require_once '../carrito/db.php';

try {
    // Obtener info de la venta
    $stmt = $pdo->prepare("
        SELECT v.*, d.calle_numero, d.piso_depto, d.ciudad, d.provincia, d.codigo_postal, u.nombre_usuario
        FROM venta v
        LEFT JOIN direcciones d ON v.id_direccion = d.id_direccion
        LEFT JOIN usuario u ON v.id_usuario = u.id_usuario
        WHERE v.id_venta = ? AND v.id_usuario = ?
    ");
    $stmt->execute([$venta_id, $_SESSION['user_id']]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$venta) {
        header('Location: ../index.html');
        exit;
    }
    
    // Obtener productos de la venta
    $stmt = $pdo->prepare("
        SELECT dv.*, p.nombre_producto 
        FROM detalle_venta dv
        JOIN producto p ON dv.id_producto = p.id_producto
        WHERE dv.id_venta = ?
    ");
    $stmt->execute([$venta_id]);
    $productos_venta = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error al obtener venta: " . $e->getMessage());
    header('Location: ../index.html');
    exit;
}

// Limpiar la sesión
unset($_SESSION['ultima_venta_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Compra Exitosa! - Supermercado</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
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
            background: #28a745;
            color: white;
            text-align: center;
            padding: 3rem 2rem;
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

        .contenido {
            padding: 2rem;
        }

        .detalle-venta {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .detalle-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
        }

        .producto-item {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
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
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-primario {
            background: #28a745;
            color: white;
        }

        .btn-secundario {
            background: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-exito">
            <div class="icono-exito">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>¡Compra Exitosa!</h1>
            <p>Tu pedido ha sido registrado</p>
        </div>

        <div class="contenido">
            <div class="detalle-venta">
                <h3><i class="fas fa-receipt"></i> Detalles de la Venta</h3>
                <div class="detalle-row">
                    <span>Número de Venta:</span>
                    <span><strong>#<?php echo str_pad($venta['id_venta'], 6, '0', STR_PAD_LEFT); ?></strong></span>
                </div>
                <div class="detalle-row">
                    <span>Fecha:</span>
                    <span><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></span>
                </div>
                <div class="detalle-row">
                    <span>Total:</span>
                    <span><strong>$<?php echo number_format($venta['total_venta'], 2); ?></strong></span>
                </div>
                <div class="detalle-row">
                    <span>Dirección:</span>
                    <span><?php echo htmlspecialchars($venta['calle_numero']); ?></span>
                </div>
            </div>

            <h4>Productos Comprados:</h4>
            <?php foreach ($productos_venta as $prod): ?>
            <div class="producto-item">
                <strong><?php echo htmlspecialchars($prod['nombre_producto']); ?></strong><br>
                Cantidad: <?php echo $prod['cantidad']; ?> × $<?php echo number_format($prod['precio_unitario_venta'], 2); ?>
                = <strong>$<?php echo number_format($prod['cantidad'] * $prod['precio_unitario_venta'], 2); ?></strong>
            </div>
            <?php endforeach; ?>

            <div class="botones">
                <a href="../index.html" class="btn btn-primario">
                    <i class="fas fa-home"></i> Volver al Inicio
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