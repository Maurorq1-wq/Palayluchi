<?php

session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] != "admin") {
    header("Location: index.php");
    exit();
}

require "conexion.php";

$pagina_activa = "usuarios";

$mensaje = "";
$error = "";

// Alta de un usuario nuevo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accion"]) && $_POST["accion"] == "agregar") {

    $usuario = trim($_POST["usuario"]);
    $clave = $_POST["clave"];
    $nombre_completo = trim($_POST["nombre_completo"]);
    $rol = $_POST["rol"];

    // Chequear que el nombre de usuario no exista ya
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
    $stmt->execute(["usuario" => $usuario]);

    if ($stmt->fetch()) {
        $error = "Ya existe un usuario con ese nombre.";
    } else {

        $clave_hasheada = password_hash($clave, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (usuario, clave, nombre_completo, rol)
             VALUES (:usuario, :clave, :nombre_completo, :rol)"
        );

        $stmt->execute([
            "usuario" => $usuario,
            "clave" => $clave_hasheada,
            "nombre_completo" => $nombre_completo,
            "rol" => $rol,
        ]);

        $mensaje = "Usuario cargado correctamente.";
    }
}

// Baja de un usuario (no se puede eliminar a uno mismo)
if (isset($_GET["eliminar"])) {

    $stmt = $pdo->prepare("SELECT usuario FROM usuarios WHERE id = :id");
    $stmt->execute(["id" => $_GET["eliminar"]]);
    $objetivo = $stmt->fetch();

    if ($objetivo && $objetivo["usuario"] == $_SESSION["usuario"]) {
        $error = "No podés eliminar tu propio usuario mientras estás conectado.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute(["id" => $_GET["eliminar"]]);
        header("Location: usuarios.php");
        exit();
    }
}

// Listado completo
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY usuario")->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Usuarios - Zamar Neumáticos</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

    <?php include "menu.php"; ?>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main">

        <header class="topbar">

            <div class="titulo-sistema">
                SISTEMA DE GESTIÓN · ZAMAR NEUMÁTICOS
            </div>

        </header>


        <section class="contenido">

            <div class="encabezado">

                <div>

                    <span>ADMINISTRACIÓN</span>

                    <h1>USUARIOS</h1>

                </div>

            </div>


            <!-- FORMULARIO DE ALTA -->
            <div class="tarjeta">

                <div class="panel-titulo">
                    CARGAR USUARIO
                </div>

                <?php if ($mensaje != "") { ?>
                    <p class="mensaje-ok"><?php echo htmlspecialchars($mensaje); ?></p>
                <?php } ?>

                <?php if ($error != "") { ?>
                    <p class="mensaje-error"><?php echo htmlspecialchars($error); ?></p>
                <?php } ?>

                <form method="POST" class="formulario-stock">

                    <input type="hidden" name="accion" value="agregar">

                    <div>
                        <label>NOMBRE COMPLETO</label>
                        <input type="text" name="nombre_completo" required>
                    </div>

                    <div>
                        <label>USUARIO</label>
                        <input type="text" name="usuario" required>
                    </div>

                    <div>
                        <label>CONTRASEÑA</label>
                        <input type="password" name="clave" required>
                    </div>

                    <div>
                        <label>ROL</label>
                        <select name="rol" required>
                            <option value="vendedor">Vendedor</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>

                    <button type="submit">AGREGAR</button>

                </form>

            </div>


            <!-- LISTADO -->
            <div class="tarjeta">

                <div class="panel-titulo">
                    USUARIOS DEL SISTEMA
                </div>

                <?php if (count($usuarios) === 0) { ?>

                    <div class="panel-vacio">
                        Todavía no hay usuarios registrados.
                    </div>

                <?php } else { ?>

                    <table class="tabla-datos">

                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre completo</th>
                                <th>Rol</th>
                                <th>Alta</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($usuarios as $u) { ?>

                                <tr>
                                    <td><?php echo htmlspecialchars($u["usuario"]); ?></td>
                                    <td><?php echo htmlspecialchars($u["nombre_completo"] ?? ""); ?></td>
                                    <td><?php echo htmlspecialchars($u["rol"]); ?></td>
                                    <td><?php echo htmlspecialchars($u["creado_en"]); ?></td>
                                    <td>
                                        <?php if ($u["usuario"] != $_SESSION["usuario"]) { ?>
                                            <a
                                                href="usuarios.php?eliminar=<?php echo $u["id"]; ?>"
                                                class="accion-eliminar"
                                                onclick="return confirm('¿Eliminar este usuario?');"
                                            >
                                                Eliminar
                                            </a>
                                        <?php } else { ?>
                                            <span style="color:#555; font-size:11px;">(vos)</span>
                                        <?php } ?>
                                    </td>
                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                <?php } ?>

            </div>

        </section>

    </main>

</body>

</html>
```[cite: 2]

---

### `menu.php`

```php
<?php
// Incluir este archivo después de definir $pagina_activa, por ejemplo:
// $pagina_activa = "panel"; // panel | stock | compras | presupuestos | ventas | clientes | proveedores | usuarios
?>
<aside class="sidebar">

    <div class="logo">

        <img src="fotos/logo.png" alt="Logo Zamar Neumáticos">

    </div>


    <nav class="menu">

        

        <!-- STOCK: rueda / neumático -->
        <a href="stock.php" class="menu-item <?php echo ($pagina_activa == "stock") ? "activo" : ""; ?>">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3.5"/><line x1="12" y1="2" x2="12" y2="8.5"/><line x1="12" y1="15.5" x2="12" y2="22"/><line x1="2" y1="12" x2="8.5" y2="12"/><line x1="15.5" y1="12" x2="22" y2="12"/></svg>
            </span>
            <p>Stock</p>
        </a>

        <!-- COMPRAS: carrito -->
        <a href="compra.php" class="menu-item <?php echo ($pagina_activa == "compras") ? "activo" : ""; ?>">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            </span>
            <p>Compras</p>
        </a>

        <!-- PRESUPUESTOS: documento -->
        <a href="presupuestos.php" class="menu-item <?php echo ($pagina_activa == "presupuestos") ? "activo" : ""; ?>">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </span>
            <p>Presupuestos</p>
        </a>

        <!-- VENTAS: signo pesos -->
        <a href="ventas.php" class="menu-item <?php echo ($pagina_activa == "ventas") ? "activo" : ""; ?>">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </span>
            <p>Ventas</p>
        </a>

        <!-- CLIENTES: grupo de personas -->
        <a href="clientes.php" class="menu-item <?php echo ($pagina_activa == "clientes") ? "activo" : ""; ?>">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <p>Clientes</p>
        </a>

        <!-- PROVEEDORES: camión -->
        <a href="proveedores.php" class="menu-item <?php echo ($pagina_activa == "proveedores") ? "activo" : ""; ?>">
            <span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            </span>
            <p>Proveedores</p>
        </a>

        <!-- USUARIOS: una persona (solo admin) -->
        <?php if (isset($_SESSION["rol"]) && $_SESSION["rol"] == "admin") { ?>
            <a href="usuarios.php" class="menu-item <?php echo ($pagina_activa == "usuarios") ? "activo" : ""; ?>">
                <span>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#777777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <p>Usuarios</p>
            </a>
        <?php } ?>

    </nav>


    <div class="usuario">

        <strong><?php echo htmlspecialchars($_SESSION["usuario"] ?? "Usuario"); ?></strong>

        <span><?php echo htmlspecialchars(strtoupper($_SESSION["rol"] ?? "")); ?></span>

        <a href="logout.php" class="boton-salir">
            ⇥ &nbsp; CERRAR SESIÓN
        </a>

    </div>

</aside>


