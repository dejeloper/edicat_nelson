<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="sidebar-nav">
    <ul>
        <li>
            <a href="<?= base_url(); ?>Index/" class="nav-header">
                <i class="fa fa-home"></i> Inicio
            </a>
        </li>
        <!-- Menu Clientes -->
        <?php
        $idPermiso = 1;
        $menu = validarPermisoMenu($idPermiso);
        if ($menu) {
            ?>
            <li>
                <a href="#" data-target=".dashboard-client" class="nav-header collapsed" data-toggle="collapse">
                    <i class="fa fa-address-book"></i> Clientes<i class="fa fa-collapse"></i>
                </a>
            </li>
            <li>
                <ul class="dashboard-client nav nav-list collapse">
                    <?php
                    $idPermiso = 2;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Clientes/Crear/"><span class="fa fa-plus"></span>&nbsp; Nuevo Cliente</a></li>
                        <?php
                    } 
                    $idPermiso = 3;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Clientes/Buscar/"><span class="fa fa-search"></span>&nbsp; Buscar Clientes</a></li>
                        <?php
                    }
                    ?>
                    <?php
                    $idPermiso = 4;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Clientes/Asignados/"><span class="fa fa-user-circle"></span>&nbsp; Clientes Asignados</a></li>
                        <?php
                    }
                    ?>
                </ul>
            </li>
            <?php
        }
        ?>

        <!-- Menu Gestión de Pagos  -->
        <?php
        $idPermiso = 21;
        $menu = validarPermisoMenu($idPermiso);
        if ($menu) {
            ?>
            <li>
                <a href="#" data-target=".dashboard-pay" class="nav-header collapsed" data-toggle="collapse">
                    <i class="fa fa-dollar"></i> Gestión de Pagos<i class="fa fa-collapse"></i>
                </a>
            </li>
            <li>
                <ul class="dashboard-pay nav nav-list collapse">
                    <?php
                    $idPermiso = 22;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Pagos/Admin/"><span class="fa fa-phone"></span>&nbsp; Llamadas del Día</a></li>
                        <?php
                    }
                    ?>
                    <?php
                    $idPermiso = 23;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Pagos/NoLlamada/"><span class="fa fa-ban"></span>&nbsp; Clientes Sin Llamar</a></li>
                        <?php
                    }
                    ?>
                    <?php
                    $idPermiso = 24;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Cobradores/Rellamar/"><span class="fa fa-phone-square"></span>&nbsp; Volver a Llamar</a></li>
                        <?php
                    }
                    ?>
                    <?php
                    $idPermiso = 28;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Pagos/AdminProg/"><span class="fa fa-motorcycle"></span>&nbsp; Recibos de Pago</a></li>
                        <?php
                    }
                    ?>
                    <?php
                    $idPermiso = 30;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Pagos/AdminPagos/"><span class="fa fa-check-square-o"></span>&nbsp; Pagos Confirmados</a></li>
                        <?php
                    }
                    ?>
                </ul>
            </li>
            <?php
        }
        ?>

        <!-- Menu Devoluciones -->
        <?php
        $idPermiso = 32;
        $menu = validarPermisoMenu($idPermiso);
        if ($menu) {
            ?>
            <li>
                <a href="#" data-target=".dashboard-devol" class="nav-header collapsed" data-toggle="collapse">
                    <i class="fa fa-truck"></i> Devoluciones<i class="fa fa-collapse"></i>
                </a>
            </li>
            <li>
                <ul class="dashboard-devol nav nav-list collapse">
                    <?php
                    $idPermiso = 33;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Devoluciones/Admin/"><span class="fa fa-reply"></span>&nbsp; Listado de Devoluciones</a></li>
                        <?php
                    }
                    ?>
                </ul>
            </li>
            <?php
        }
        ?>

        <!-- Menu Reportes -->
        <?php
        $idPermiso = 35;
        $menu = validarPermisoMenu($idPermiso);
        if ($menu) {
            ?>
            <li>
                <a href="#" data-target=".dashboard-reports" class="nav-header collapsed" data-toggle="collapse">
                    <i class="fa fa-list-ol"></i> Reportes<i class="fa fa-collapse"></i>
                </a>
            </li>
            <li>
                <ul class="dashboard-reports nav nav-list collapse">
                    <?php
                    $idPermiso = 36;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Reportes/Contador/Clientes/"><span class="fa fa-address-book"></span>&nbsp; Reporte de Clientes</a></li>
                        <?php
                    }

                    $idPermiso = 37;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Reportes/Contador/Pagos/"><span class="fa fa-dollar"></span>&nbsp; Reporte de Pagos</a></li>
                        <?php
                    }

                    $idPermiso =38;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Reportes/Cartera/Usuarios"><span class="fa fa-sign-in"></span>&nbsp; Cartera Por Usuario</a></li> 
                        <?php
                    }
                    ?>
                </ul>
            </li>
            <?php
        }
        ?>

        <!-- Menú Listas -->
        <?php
        $idPermiso = 39;
        $menu = validarPermisoMenu($idPermiso);
        if ($menu) {
            ?>
            <li>
                <a href="#" data-target=".dashboard-list" class="nav-header collapsed" data-toggle="collapse">
                    <i class="fa fa-database"></i> Listas<i class="fa fa-collapse"></i>
                </a>
            </li>
            <li>
                <ul class="dashboard-list nav nav-list collapse">
                    <?php
                    $idPermiso = 40;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Productos/Admin/"><span class="fa fa-shopping-basket"></span>&nbsp; Productos</a></li>
                        <?php
                    }
                    ?>
                    <?php
                    $idPermiso = 43;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Tarifas/Admin/"><span class="fa fa-tags"></span>&nbsp; Tarifas</a></li>
                        <?php
                    }
                    ?>
                </ul>
            </li>
            <?php
        }
        ?>
        <!-- Menú Configuraciones -->
        <?php
        $idPermiso = 84;
        $menu = validarPermisoMenu($idPermiso);
        if ($menu) {
            ?>
            <li>
                <a href="#" data-target=".dashboard-config" class="nav-header collapsed" data-toggle="collapse">
                    <i class="fa fa-cogs"></i> Configuraciones<i class="fa fa-collapse"></i>
                </a>
            </li>
            <li>
                <ul class="dashboard-config nav nav-list collapse">
                    <?php
                    $idPermiso = 85;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Mantenimiento/Usuarios/Admin/"><span class="fa fa-group"></span>&nbsp; Usuarios</a></li>
                        <?php
                    }
                    ?>
                    <?php
                    $idPermiso = 86;
                    $menu = validarPermisoMenu($idPermiso);
                    if ($menu) {
                        ?>
                        <li><a href="<?= base_url(); ?>Mantenimiento/Permisos/Admin/"><span class="fa fa-unlock-alt"></span>&nbsp;&nbsp;  Permisos</a></li>
                        <?php
                    }
                    ?>
                </ul>
            </li>    
            <?php
        }
        ?>
        <li>
            <a href="<?= base_url('Index/Acercade/'); ?>" class="nav-header">
                <i class="fa fa-info-circle"></i> Acerca de...
            </a>
        </li>
        <li>
            <a href="<?= base_url("Mantenimiento/Usuarios/CambiarPass/") . $this->session->userdata('Codigo') . '/'; ?>" class="nav-header">
                <i class="fa fa-key"></i> Cambiar Contraseña
            </a>
        </li>
        <li>
            <a href="<?= base_url('Login/signOut/'); ?>" class="nav-header cerrarSesion">
                <i class="fa fa-sign-out"></i> Salir
            </a>
        </li>
    </ul>
</div>