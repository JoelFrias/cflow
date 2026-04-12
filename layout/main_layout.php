<?php
// layout/main_layout.php - Plantilla principal del sistema

require_once 'config/database.php';
redirectIfNotLoggedIn();
$user_id = $_SESSION['user_id'];
$current_module = $_GET['module'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>CFlow - <?= ucfirst($current_module) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* ============================================ */
        /* ESTILOS PARA SIDEBAR RESPONSIVE - CORREGIDO */
        /* ============================================ */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f4f6f9;
            overflow-x: hidden;
        }
        
        /* Sidebar - Estilos base */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            z-index: 1050;
            overflow-y: auto;
            transition: transform 0.3s ease-in-out;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        /* Sidebar en desktop - siempre visible */
        @media (min-width: 992px) {
            .sidebar {
                transform: translateX(0) !important;
            }
            
            .main-content {
                margin-left: 280px;
                width: calc(100% - 280px);
            }
            
            .toggle-sidebar-btn {
                display: none !important;
            }
            
            .mobile-top-bar {
                display: none !important;
            }
        }
        
        /* Sidebar en móvil - oculto por defecto */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding-top: 70px;
            }
            
            .toggle-sidebar-btn {
                display: block;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1060;
                background: #1a1a2e;
                color: white;
                border: none;
                border-radius: 8px;
                width: 45px;
                height: 45px;
                font-size: 1.3rem;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                cursor: pointer;
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 1040;
                display: none;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
            
            .mobile-top-bar {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                background: white;
                padding: 10px 20px;
                position: fixed;
                top: 0;
                right: 0;
                left: 0;
                z-index: 1020;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
        }
        
        /* Logo del sidebar */
        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-header h4 {
            margin: 10px 0 5px 0;
            font-size: 1.2rem;
        }
        
        .sidebar-header small {
            font-size: 0.7rem;
            opacity: 0.7;
        }
        
        /* Navegación */
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-nav li a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #ffc107;
        }
        
        .sidebar-nav li a.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: #ffc107;
        }
        
        .sidebar-nav li a i {
            width: 25px;
            margin-right: 12px;
            font-size: 1.1rem;
        }
        
        .sidebar-nav li a span {
            font-size: 0.9rem;
        }
        
        /* Footer del sidebar */
        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            padding: 0 20px;
        }
        
        .sidebar-footer hr {
            border-color: rgba(255,255,255,0.1);
            margin: 10px 0;
        }
        
        /* Main content */
        .main-content {
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Top bar móvil */
        .mobile-top-bar {
            align-items: center;
            gap: 15px;
        }
        
        .user-info-mobile {
            background: #f0f0f0;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .date-badge {
            background: #e9ecef;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        /* Scrollbar */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 5px;
        }
        
        /* Footer general */
        .footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 0.8rem;
            border-top: 1px solid #dee2e6;
            margin-top: 30px;
        }
        
        /* Ajustes de cards */
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Botón para toggle sidebar en móvil -->
    <button class="toggle-sidebar-btn" id="toggleSidebarBtn">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay para cerrar sidebar en móvil -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-coins fa-2x mb-2"></i>
            <h4>CFlow - Finanzas Personales</h4>
            <small>Controla tu dinero</small>
        </div>
        
        <ul class="sidebar-nav">
            <li>
                <a href="?module=dashboard" class="<?= $current_module == 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="?module=transactions" class="<?= $current_module == 'transactions' ? 'active' : '' ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transacciones</span>
                </a>
            </li>
            <li>
                <a href="?module=accounts" class="<?= $current_module == 'accounts' ? 'active' : '' ?>">
                    <i class="fas fa-wallet"></i>
                    <span>Cuentas</span>
                </a>
            </li>
            <li>
                <a href="?module=cards" class="<?= $current_module == 'cards' ? 'active' : '' ?>">
                    <i class="fas fa-credit-card"></i>
                    <span>Tarjetas</span>
                </a>
            </li>
            <li>
                <a href="?module=goals" class="<?= $current_module == 'goals' ? 'active' : '' ?>">
                    <i class="fas fa-piggy-bank"></i>
                    <span>Metas de Ahorro</span>
                </a>
            </li>
            <li>
                <a href="?module=debts" class="<?= $current_module == 'debts' ? 'active' : '' ?>">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Deudas</span>
                </a>
            </li>
            <li>
                <a href="?module=reminders" class="<?= $current_module == 'reminders' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i>
                    <span>Recordatorios</span>
                </a>
            </li>
            <li>
                <a href="?module=categories" class="<?= $current_module == 'categories' ? 'active' : '' ?>">
                    <i class="fas fa-tags"></i>
                    <span>Categorías</span>
                </a>
            </li>
            <li>
                <a href="?module=profile" class="<?= $current_module == 'profile' ? 'active' : '' ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>Mi Perfil</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-user-circle"></i>
                    <small><?= htmlspecialchars($_SESSION['user_username'] ?? $_SESSION['user_name']) ?></small>
                </div>
                <a href="logout.php" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top bar para móvil -->
        <div class="mobile-top-bar">
            <div class="user-info-mobile">
                <i class="fas fa-user-circle"></i>
                <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
            </div>
        </div>
        
        <!-- Contenido del módulo -->
        <?php
        $module_path = '';
        switch($current_module) {
            case 'dashboard': $module_path = 'modules/dashboard.php'; break;
            case 'accounts': $module_path = 'modules/accounts.php'; break;
            case 'transactions': $module_path = 'modules/transactions.php'; break;
            case 'cards': $module_path = 'modules/cards.php'; break;
            case 'debts': $module_path = 'modules/debts.php'; break;
            case 'categories': $module_path = 'modules/categories.php'; break;
            case 'reminders': $module_path = 'modules/reminders.php'; break;
            case 'goals': $module_path = 'modules/goals.php'; break;
            case 'profile': $module_path = 'modules/profile.php';
    break;
            default: $module_path = 'modules/dashboard.php';
        }
        
        if(file_exists($module_path)) {
            include $module_path;
        } else {
            echo '<div class="alert alert-danger">Módulo no encontrado</div>';
        }
        ?>
        
        <!-- Footer -->
        <div class="footer">
            <small>CFlow &copy; <?= date('Y') ?> - Controla tu dinero en DOP y USD</small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/functions.js"></script>
    
    <script>
        // ============================================
        // SIDEBAR TOGGLE PARA MÓVIL - CORREGIDO
        // ============================================
        (function() {
            // Esperar a que el DOM esté listo
            document.addEventListener('DOMContentLoaded', function() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                const toggleBtn = document.getElementById('toggleSidebarBtn');
                
                // Verificar que los elementos existan
                if (!sidebar || !overlay || !toggleBtn) {
                    console.log('Elementos del sidebar no encontrados');
                    return;
                }
                
                // Función para abrir sidebar
                function openSidebar() {
                    sidebar.classList.add('open');
                    overlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
                
                // Función para cerrar sidebar
                function closeSidebar() {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
                
                // Toggle sidebar al hacer click en el botón
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (sidebar.classList.contains('open')) {
                        closeSidebar();
                    } else {
                        openSidebar();
                    }
                });
                
                // Cerrar al hacer click en el overlay
                overlay.addEventListener('click', function() {
                    closeSidebar();
                });
                
                // Cerrar al hacer click en un enlace del sidebar (en móvil)
                const navLinks = document.querySelectorAll('.sidebar-nav a');
                navLinks.forEach(function(link) {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 991) {
                            closeSidebar();
                        }
                    });
                });
                
                // Cerrar al presionar ESC
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                        closeSidebar();
                    }
                });
                
                // Ajustar al cambiar tamaño de ventana
                window.addEventListener('resize', function() {
                    if (window.innerWidth > 991) {
                        // En desktop, asegurar que el sidebar esté visible
                        sidebar.classList.remove('open');
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
                
                console.log('Sidebar inicializado correctamente');
            });
        })();
    </script>
</body>
</html>