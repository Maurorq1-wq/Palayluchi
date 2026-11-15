<?php

session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require "conexion.php";

if (!isset($_GET["id"])) {
    die("Falta indicar qué venta mostrar.");
}

$venta_id = (int) $_GET["id"];

$stmt = $pdo->prepare(
    "SELECT v.*, c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
            c.dni_cuit, c.telefono, c.email, c.direccion,
            u.usuario AS vendedor
     FROM ventas v
     JOIN clientes c ON c.id = v.cliente_id
     JOIN usuarios u ON u.id = v.usuario_id
     WHERE v.id = :id"
);
$stmt->execute(["id" => $venta_id]);
$venta = $stmt->fetch();

if (!$venta) {
    die("La venta solicitada no existe.");
}

$stmtDetalle = $pdo->prepare(
    "SELECT d.*, n.marca, n.modelo, n.medida
     FROM detalle_venta d
     JOIN neumaticos n ON n.id = d.neumatico_id
     WHERE d.venta_id = :id"
);
$stmtDetalle->execute(["id" => $venta_id]);
$detalle = $stmtDetalle->fetchAll();

// Ganancia exacta: (precio de venta - costo del día de la venta) x cantidad
$total_costo = 0;
$total_ganancia = 0;

foreach ($detalle as $i => $d) {
    $costo_linea = $d["costo_unitario"] * $d["cantidad"];
    $ganancia_linea = $d["subtotal"] - $costo_linea;

    $detalle[$i]["costo_linea"] = $costo_linea;
    $detalle[$i]["ganancia_linea"] = $ganancia_linea;

    $total_costo += $costo_linea;
    $total_ganancia += $ganancia_linea;
}

$margen = $venta["total"] > 0 ? ($total_ganancia / $venta["total"]) * 100 : 0;

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Venta #<?php echo $venta["id"]; ?> - Zamar Neumáticos</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
            background-color: #fff;
            padding: 40px;
        }

        .hoja {
            max-width: 800px;
            margin: 0 auto;
        }

        .encabezado {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #111;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .encabezado h1 {
            font-size: 22px;
            letter-spacing: 2px;
        }

        .encabezado span {
            display: block;
            color: #666;
            font-size: 11px;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .numero {
            text-align: right;
        }

        .numero strong {
            font-size: 20px;
        }

        .numero span {
            display: block;
            color: #666;
            font-size: 11px;
            margin-top: 4px;
        }

        .estado {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .estado.completada { background-color: #d4edda; color: #1e5c2c; }
        .estado.anulada { background-color: #f8d7da; color: #7a1e26; }

        .datos-cliente {
            margin-bottom: 25px;
        }

        .datos-cliente h2 {
            font-size: 12px;
            letter-spacing: 1px;
            color: #666;
            margin-bottom: 8px;
        }

        .datos-cliente p {
            font-size: 13px;
            line-height: 1.6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
            text-align: left;
        }

        th {
            background-color: #111;
            color: #fff;
            font-size: 11px;
            letter-spacing: 1px;
        }

        .total-fila td {
            border-top: 2px solid #111;
            border-bottom: none;
            font-weight: bold;
            font-size: 15px;
        }

        .ganancia-positiva { color: #1e5c2c; font-weight: bold; }
        .ganancia-negativa { color: #b3261e; font-weight: bold; }

        /* Cuadro resumen de ganancia */
        .resumen-ganancia {
            border: 1px solid #ddd;
            padding: 15px 18px;
            margin-top: 10px;
            background-color: #fafafa;
        }

        .resumen-ganancia h2 {
            font-size: 12px;
            letter-spacing: 1px;
            color: #666;
            margin-bottom: 10px;
        }

        .resumen-ganancia .fila {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 4px 0;
        }

        .resumen-ganancia .fila.final {
            border-top: 1px solid #ccc;
            margin-top: 6px;
            padding-top: 8px;
            font-size: 15px;
            font-weight: bold;
        }

        .pie {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #777;
            text-align: center;
        }

        .acciones {
            text-align: right;
            margin-bottom: 25px;
        }

        .acciones button,
        .acciones a {
            display: inline-block;
            padding: 10px 18px;
            font-size: 12px;
            letter-spacing: 1px;
            text-decoration: none;
            border: 1px solid #111;
            color: #111;
            background: #fff;
            cursor: pointer;
            margin-left: 8px;
        }

        .acciones button {
            background-color: #ffd900;
            border-color: #ffd900;
            font-weight: bold;
        }

        /* Al imprimir, ocultamos los botones */
        @media print {
            .acciones {
                display: none;
            }
            body {
                padding: 0;
            }
        }

    </style>

</head>

<body>

    <div class="hoja">

        <div class="acciones">
            <a href="ventas.php">Volver</a>
            <button onclick="window.print()">Imprimir</button>
        </div>

        <div class="encabezado">

            <div>
                <h1>ZAMAR NEUMÁTICOS</h1>
                <span>Sistema de gestión · Comprobante de venta</span>
            </div>

            <div class="numero">
                <strong>Venta #<?php echo $venta["id"]; ?></strong>
                <span>Fecha: <?php echo htmlspecialchars($venta["fecha"]); ?></span>
                <span>Método de pago: <?php echo htmlspecialchars($venta["metodo_pago"]); ?></span>
                <span>Vendedor: <?php echo htmlspecialchars($venta["vendedor"]); ?></span>
                <span class="estado <?php echo htmlspecialchars($venta["estado"]); ?>">
                    <?php echo htmlspecialchars($venta["estado"]); ?>
                </span>
            </div>

        </div>


        <div class="datos-cliente">
            <h2>CLIENTE</h2>
            <p>
                <strong><?php echo htmlspecialchars($venta["cliente_nombre"] . " " . $venta["cliente_apellido"]); ?></strong><br>
                <?php if ($venta["dni_cuit"]) { ?>DNI/CUIT: <?php echo htmlspecialchars($venta["dni_cuit"]); ?><br><?php } ?>
                <?php if ($venta["telefono"]) { ?>Tel: <?php echo htmlspecialchars($venta["telefono"]); ?><br><?php } ?>
                <?php if ($venta["email"]) { ?>Email: <?php echo htmlspecialchars($venta["email"]); ?><br><?php } ?>
                <?php if ($venta["direccion"]) { ?>Dirección: <?php echo htmlspecialchars($venta["direccion"]); ?><?php } ?>
            </p>
        </div>


        <table>

            <thead>
                <tr>
                    <th>Neumático</th>
                    <th>Cant.</th>
                    <th>Precio unit.</th>
                    <th>Costo unit.</th>
                    <th>Subtotal</th>
                    <th>Ganancia</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($detalle as $d) { ?>

                    <tr>
                        <td><?php echo htmlspecialchars($d["marca"] . " " . $d["modelo"] . " (" . $d["medida"] . ")"); ?></td>
                        <td><?php echo $d["cantidad"]; ?></td>
                        <td>$ <?php echo number_format($d["precio_unitario"], 2, ",", "."); ?></td>
                        <td>$ <?php echo number_format($d["costo_unitario"], 2, ",", "."); ?></td>
                        <td>$ <?php echo number_format($d["subtotal"], 2, ",", "."); ?></td>
                        <td class="<?php echo $d["ganancia_linea"] >= 0 ? "ganancia-positiva" : "ganancia-negativa"; ?>">
                            $ <?php echo number_format($d["ganancia_linea"], 2, ",", "."); ?>
                        </td>
                    </tr>

                <?php } ?>

                <tr class="total-fila">
                    <td colspan="4" style="text-align:right;">TOTAL</td>
                    <td colspan="2">$ <?php echo number_format($venta["total"], 2, ",", "."); ?></td>
                </tr>

            </tbody>

        </table>


        <div class="resumen-ganancia">

            <h2>RESUMEN DE GANANCIA</h2>

            <div class="fila">
                <span>Total vendido</span>
                <span>$ <?php echo number_format($venta["total"], 2, ",", "."); ?></span>
            </div>

            <div class="fila">
                <span>Costo de la mercadería</span>
                <span>$ <?php echo number_format($total_costo, 2, ",", "."); ?></span>
            </div>

            <div class="fila final">
                <span>Ganancia de la venta</span>
                <span class="<?php echo $total_ganancia >= 0 ? "ganancia-positiva" : "ganancia-negativa"; ?>">
                    $ <?php echo number_format($total_ganancia, 2, ",", "."); ?>
                    (<?php echo number_format($margen, 1, ",", "."); ?>%)
                </span>
            </div>

        </div>


        <div class="pie">
            Zamar Neumáticos · Sistema de gestión
        </div>

    </div>

</body>

</html>
