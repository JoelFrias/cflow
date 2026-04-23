<?php
// layout/main_layout.php - Plantilla principal del sistema

require_once 'config/database.php';
redirectIfNotLoggedIn();
$user_id = $_SESSION['user_id'];
$current_module = $_GET['module'] ?? 'dashboard';

// Detectar la ruta base relativa al archivo de layout
$base_path = str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 1);
if (empty($base_path)) $base_path = '';
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
    <!-- ✅ Favicon con ruta absoluta desde la raíz del dominio -->
    <link rel="icon" type="image/x-icon" href="/assets/img/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/assets/img/favicon.ico">

    <style>
        /* ============================================ */
        /* ESTILOS BASE                                 */
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

        /* ============================================ */
        /* SIDEBAR — solo visible en desktop            */
        /* ============================================ */

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

        /* Desktop: sidebar siempre visible */
        @media (min-width: 992px) {
            .sidebar {
                transform: translateX(0) !important;
            }

            .main-content {
                margin-left: 280px;
                width: calc(100% - 280px);
                padding: 20px;
            }

            /* Ocultar todo lo que es solo móvil */
            .toggle-sidebar-btn,
            .mobile-top-bar,
            .mobile-bottom-nav,
            .more-drawer-overlay,
            .more-drawer { display: none !important; }
        }

        /* Móvil: sidebar completamente oculto — la nav inferior lo reemplaza */
        @media (max-width: 991px) {
            .sidebar { display: none !important; }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 30px 15px 92px 15px; /* top-bar arriba + bottom-nav (76px) + margen */
            }

            /* Ocultar el botón hamburguesa clásico */
            .toggle-sidebar-btn,
            .sidebar-overlay { display: none !important; }
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

        /* Navegación del sidebar */
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
        }

        /* Scrollbar del sidebar */
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 5px; }

        /* ============================================ */
        /* BARRA SUPERIOR MÓVIL                        */
        /* ============================================ */

        .mobile-top-bar {
            display: none;
        }

        @media (max-width: 991px) {
            .mobile-top-bar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: linear-gradient(90deg, #1a1a2e 0%, #16213e 100%);
                padding: 12px 20px;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1020;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            }

            .mobile-top-bar .app-brand {
                display: flex;
                align-items: center;
                gap: 10px;
                color: white;
                font-size: 1rem;
                font-weight: 600;
                letter-spacing: 0.03em;
            }

            .mobile-top-bar .app-brand i {
                color: #ffc107;
                font-size: 1.2rem;
            }

            .mobile-top-bar .user-pill {
                background: rgba(255,255,255,0.12);
                border: 1px solid rgba(255,255,255,0.15);
                color: rgba(255,255,255,0.9);
                padding: 5px 12px;
                border-radius: 20px;
                font-size: 0.8rem;
                display: flex;
                align-items: center;
                gap: 6px;
            }
        }

        /* ============================================ */
        /* BARRA INFERIOR MÓVIL — NAVEGACIÓN PRINCIPAL */
        /* ============================================ */

        .mobile-bottom-nav {
            display: none;
        }

        @media (max-width: 991px) {
            .mobile-bottom-nav {
                display: flex;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 76px;
                background: #1a1a2e;
                z-index: 1030;
                border-radius: 18px 18px 0 0;
                box-shadow: 0 -4px 24px rgba(0,0,0,0.35),
                            0 -1px 0   rgba(255,255,255,0.06);
                align-items: stretch;
            }

            .mobile-bottom-nav .nav-item {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 5px;
                color: rgba(255,255,255,0.45);
                text-decoration: none;
                font-size: 0.65rem;
                font-weight: 500;
                letter-spacing: 0.02em;
                cursor: pointer;
                border: none;
                background: none;
                transition: color 0.2s, background 0.2s;
                padding: 10px 0 8px;
                position: relative;
            }

            .mobile-bottom-nav .nav-item i {
                font-size: 1.25rem;
                transition: transform 0.2s;
            }

            .mobile-bottom-nav .nav-item:hover {
                color: rgba(255,255,255,0.85);
                background: rgba(255,255,255,0.05);
            }

            .mobile-bottom-nav .nav-item.active {
                color: #ffc107;
            }

            .mobile-bottom-nav .nav-item.active::before {
                content: '';
                position: absolute;
                top: 0;
                left: 22%;
                right: 22%;
                height: 3px;
                background: #ffc107;
                border-radius: 0 0 4px 4px;
            }

            .mobile-bottom-nav .nav-item.active i {
                transform: translateY(-2px);
            }

            .mobile-bottom-nav .nav-item.more-btn i {
                font-size: 1.2rem;
            }

            .mobile-bottom-nav .nav-item.more-btn.drawer-open {
                color: #ffc107;
                background: rgba(255, 193, 7, 0.08);
            }
        }

        /* ============================================ */
        /* DRAWER — MENÚ SECUNDARIO DESDE ABAJO        */
        /* ============================================ */

        .more-drawer-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            backdrop-filter: blur(2px);
        }

        .more-drawer-overlay.active {
            display: block;
        }

        .more-drawer {
            position: fixed;
            bottom: 0;                   /* ← anclado al borde inferior del viewport */
            left: 0;
            right: 0;
            background: #1a1a2e;
            z-index: 1041;
            border-radius: 18px 18px 0 0;
            /* padding-bottom deja espacio sobre la bottom-nav cuando el drawer está abierto */
            padding: 8px 0 92px;
            transform: translateY(100%); /* oculto: 100% de su propia altura = fuera del viewport */
            transition: transform 0.32s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 -6px 28px rgba(0,0,0,0.4);
            will-change: transform;
        }

        .more-drawer.open {
            transform: translateY(0);    /* visible: se desliza desde abajo */
        }

        /* Manija visual del drawer */
        .drawer-handle {
            width: 40px;
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 99px;
            margin: 6px auto 14px;
        }

        /* Grid de opciones dentro del drawer */
        .drawer-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px 0;
            padding: 0 8px;
        }

        .drawer-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 14px 8px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 500;
            text-align: center;
            transition: background 0.15s, color 0.15s;
        }

        .drawer-item i {
            font-size: 1.3rem;
        }

        .drawer-item:hover,
        .drawer-item:active {
            background: rgba(255,255,255,0.08);
            color: white;
        }

        .drawer-item.active {
            color: #ffc107;
            background: rgba(255, 193, 7, 0.08);
        }

        /* Separador y botón de cerrar sesión en el drawer */
        .drawer-divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,0.08);
            margin: 8px 20px;
        }

        .drawer-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 4px 20px 0;
            padding: 12px;
            color: #f87171;
            text-decoration: none;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: background 0.15s;
        }

        .drawer-logout:hover {
            background: rgba(248, 113, 113, 0.1);
            color: #f87171;
        }

        /* ============================================ */
        /* FOOTER GENERAL                              */
        /* ============================================ */

        .footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 0.8rem;
            border-top: 1px solid #dee2e6;
            margin-top: 30px;
        }

        /* Cards */
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        /* ============================================ */
        /* OVERLAY DE CARGA DE MÓDULO                  */
        /* ============================================ */

        .cf-loader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(30, 30, 35, 0.72);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.18s ease, visibility 0.18s ease;
            pointer-events: none;
        }

        .cf-loader.visible {
            opacity: 1;
            visibility: visible;
            pointer-events: all;
        }

        .cf-loader-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            animation: cfLoaderIn 0.25s ease both;
        }

        @keyframes cfLoaderIn {
            from { transform: translateY(12px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .cf-loader-icon {
            font-size: 2.6rem;
            color: #ffc107;
            margin-bottom: 4px;
            filter: drop-shadow(0 0 12px rgba(255,193,7,0.45));
        }

        .cf-loader-brand {
            font-size: 1.4rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.08em;
        }

        .cf-loader-text {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.6);
            letter-spacing: 0.04em;
            margin: 10px 0 18px;
            text-transform: uppercase;
        }

        .cf-loader-dots {
            display: flex;
            gap: 9px;
            align-items: center;
        }

        .cf-loader-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #ffc107;
            animation: cfDotBounce 1.1s ease-in-out infinite;
        }

        .cf-loader-dot:nth-child(1) { animation-delay: 0s;    }
        .cf-loader-dot:nth-child(2) { animation-delay: 0.18s; }
        .cf-loader-dot:nth-child(3) { animation-delay: 0.36s; }

        @keyframes cfDotBounce {
            0%, 70%, 100% { transform: translateY(0);     opacity: 0.35; }
            35%            { transform: translateY(-13px); opacity: 1;    }
        }
    </style>
</head>
<body>

    <!-- ══════════════════════════════════════════ -->
    <!-- OVERLAY DE CARGA                          -->
    <!-- ══════════════════════════════════════════ -->
    <div id="cfLoader" class="cf-loader">
        <div class="cf-loader-inner">
            <i class="fas fa-coins cf-loader-icon"></i>
            <span class="cf-loader-brand">CFlow</span>
            <p class="cf-loader-text" id="cfLoaderText">Cargando...</p>
            <div class="cf-loader-dots">
                <div class="cf-loader-dot"></div>
                <div class="cf-loader-dot"></div>
                <div class="cf-loader-dot"></div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════ -->
    <!-- SIDEBAR — Solo visible en desktop          -->
    <!-- ══════════════════════════════════════════ -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-coins fa-2x mb-2" style="color:#ffc107;"></i>
            <h4>CFlow</h4>
            <small>Controla tu dinero</small>
        </div>

        <ul class="sidebar-nav">
            <li><a href="?module=dashboard"    class="<?= $current_module == 'dashboard'    ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
            <li><a href="?module=transactions" class="<?= $current_module == 'transactions' ? 'active' : '' ?>"><i class="fas fa-exchange-alt"></i><span>Transacciones</span></a></li>
            <li><a href="?module=accounts"     class="<?= $current_module == 'accounts'     ? 'active' : '' ?>"><i class="fas fa-wallet"></i><span>Cuentas</span></a></li>
            <li><a href="?module=cards"        class="<?= $current_module == 'cards'        ? 'active' : '' ?>"><i class="fas fa-credit-card"></i><span>Tarjetas</span></a></li>
            <li><a href="?module=goals"        class="<?= $current_module == 'goals'        ? 'active' : '' ?>"><i class="fas fa-piggy-bank"></i><span>Metas de Ahorro</span></a></li>
            <li><a href="?module=debts"        class="<?= $current_module == 'debts'        ? 'active' : '' ?>"><i class="fas fa-hand-holding-usd"></i><span>Deudas</span></a></li>
            <li><a href="?module=reminders"    class="<?= $current_module == 'reminders'    ? 'active' : '' ?>"><i class="fas fa-bell"></i><span>Recordatorios</span></a></li>
            <li><a href="?module=categories"   class="<?= $current_module == 'categories'   ? 'active' : '' ?>"><i class="fas fa-tags"></i><span>Categorías</span></a></li>
            <li><a href="?module=profile"      class="<?= $current_module == 'profile'      ? 'active' : '' ?>"><i class="fas fa-user-circle"></i><span>Mi Perfil</span></a></li>
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

    <!-- ══════════════════════════════════════════ -->
    <!-- BARRA SUPERIOR — Solo móvil               -->
    <!-- ══════════════════════════════════════════ -->
    <div class="mobile-top-bar">
        <div class="app-brand">
            <i class="fas fa-coins"></i>
            <span>CFlow</span>
        </div>
        <div class="user-pill">
            <i class="fas fa-user-circle"></i>
            <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
        </div>
    </div>

    <!-- ══════════════════════════════════════════ -->
    <!-- MAIN CONTENT                              -->
    <!-- ══════════════════════════════════════════ -->
    <div class="main-content" id="mainContent">

        <?php
        $module_path = '';
        switch($current_module) {
            case 'dashboard':    $module_path = 'modules/dashboard.php';    break;
            case 'accounts':     $module_path = 'modules/accounts.php';     break;
            case 'transactions': $module_path = 'modules/transactions.php'; break;
            case 'cards':        $module_path = 'modules/cards.php';        break;
            case 'debts':        $module_path = 'modules/debts.php';        break;
            case 'categories':   $module_path = 'modules/categories.php';   break;
            case 'reminders':    $module_path = 'modules/reminders.php';    break;
            case 'goals':        $module_path = 'modules/goals.php';        break;
            case 'profile':      $module_path = 'modules/profile.php';      break;
            default:             $module_path = 'modules/dashboard.php';
        }

        if (file_exists($module_path)) {
            include $module_path;
        } else {
            echo '<div class="alert alert-danger">Módulo no encontrado</div>';
        }
        ?>

        <div class="footer">
            <small>CFlow &copy; <?= date('Y') ?> - Controla tu dinero en DOP y USD</small>
        </div>
    </div>

    <!-- ══════════════════════════════════════════ -->
    <!-- BARRA INFERIOR — Solo móvil               -->
    <!-- ══════════════════════════════════════════ -->
    <nav class="mobile-bottom-nav" id="mobileBottomNav">
        <a href="?module=dashboard" class="nav-item <?= $current_module == 'dashboard' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="?module=transactions" class="nav-item <?= $current_module == 'transactions' ? 'active' : '' ?>">
            <i class="fas fa-exchange-alt"></i>
            <span>Transacciones</span>
        </a>
        <a href="?module=accounts" class="nav-item <?= $current_module == 'accounts' ? 'active' : '' ?>">
            <i class="fas fa-wallet"></i>
            <span>Cuentas</span>
        </a>
        <a href="?module=cards" class="nav-item <?= $current_module == 'cards' ? 'active' : '' ?>">
            <i class="fas fa-credit-card"></i>
            <span>Tarjetas</span>
        </a>
        <!-- Botón "Más" -->
        <button class="nav-item more-btn" id="moreBtn" aria-label="Más opciones">
            <i class="fas fa-bars"></i>
            <span>Más</span>
        </button>
    </nav>

    <!-- ══════════════════════════════════════════ -->
    <!-- OVERLAY + DRAWER — opciones secundarias   -->
    <!-- ══════════════════════════════════════════ -->
    <div class="more-drawer-overlay" id="drawerOverlay"></div>

    <div class="more-drawer" id="moreDrawer">
        <div class="drawer-handle"></div>

        <div class="drawer-grid">
            <a href="?module=goals" class="drawer-item <?= $current_module == 'goals' ? 'active' : '' ?>">
                <i class="fas fa-piggy-bank"></i>
                <span>Metas de<br>Ahorro</span>
            </a>
            <a href="?module=debts" class="drawer-item <?= $current_module == 'debts' ? 'active' : '' ?>">
                <i class="fas fa-hand-holding-usd"></i>
                <span>Deudas</span>
            </a>
            <a href="?module=reminders" class="drawer-item <?= $current_module == 'reminders' ? 'active' : '' ?>">
                <i class="fas fa-bell"></i>
                <span>Recordatorios</span>
            </a>
            <a href="?module=categories" class="drawer-item <?= $current_module == 'categories' ? 'active' : '' ?>">
                <i class="fas fa-tags"></i>
                <span>Categorías</span>
            </a>
            <a href="?module=profile" class="drawer-item <?= $current_module == 'profile' ? 'active' : '' ?>">
                <i class="fas fa-user-circle"></i>
                <span>Mi Perfil</span>
            </a>
        </div>

        <hr class="drawer-divider">

        <a href="logout.php" class="drawer-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar sesión</span>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/functions.js"></script>

    <script>
    // ════════════════════════════════════════════
    //  DRAWER — MENÚ SECUNDARIO MÓVIL
    // ════════════════════════════════════════════
    (function () {
        const moreBtn      = document.getElementById('moreBtn');
        const drawer       = document.getElementById('moreDrawer');
        const overlay      = document.getElementById('drawerOverlay');

        if (!moreBtn || !drawer || !overlay) return;

        function openDrawer() {
            drawer.classList.add('open');
            overlay.classList.add('active');
            moreBtn.classList.add('drawer-open');
            document.body.style.overflow = 'hidden';
        }

        function closeDrawer() {
            drawer.classList.remove('open');
            overlay.classList.remove('active');
            moreBtn.classList.remove('drawer-open');
            document.body.style.overflow = '';
        }

        moreBtn.addEventListener('click', function () {
            drawer.classList.contains('open') ? closeDrawer() : openDrawer();
        });

        overlay.addEventListener('click', closeDrawer);

        // Soporte swipe hacia abajo para cerrar
        let touchStartY = 0;
        drawer.addEventListener('touchstart', function (e) {
            touchStartY = e.touches[0].clientY;
        }, { passive: true });

        drawer.addEventListener('touchend', function (e) {
            const diff = e.changedTouches[0].clientY - touchStartY;
            if (diff > 60) closeDrawer();
        }, { passive: true });

        // Cerrar con ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeDrawer();
        });

        // Si el módulo activo está en el drawer, marcarlo como activo en el botón "Más"
        const drawerModules = ['goals', 'debts', 'reminders', 'categories', 'profile'];
        const currentModule = '<?= $current_module ?>';
        if (drawerModules.includes(currentModule)) {
            moreBtn.classList.add('active');
        }
    })();

    // ════════════════════════════════════════════
    //  LOADER DE NAVEGACIÓN — compatibilidad móvil
    // ════════════════════════════════════════════
    (function () {
        const loader     = document.getElementById('cfLoader');
        const loaderText = document.getElementById('cfLoaderText');
        if (!loader) return;

        const moduleLabels = {
            'dashboard':    'Dashboard',
            'transactions': 'Transacciones',
            'accounts':     'Cuentas',
            'cards':        'Tarjetas',
            'goals':        'Metas de Ahorro',
            'debts':        'Deudas',
            'reminders':    'Recordatorios',
            'categories':   'Categorías',
            'profile':      'Mi Perfil',
        };

        // ── Mostrar el loader y GARANTIZAR un frame pintado antes de navegar ──
        // En móvil (iOS/Android) el browser no pinta entre el click y la
        // navegación, así que hacemos preventDefault, forzamos dos rAF y
        // luego navegamos manualmente.
        function showLoaderThenGo(text, href) {
            loaderText.textContent = text;

            // Aplicar visibilidad directamente (sin transición CSS) para
            // que el primer frame ya muestre el overlay completo.
            loader.style.transition  = 'none';
            loader.style.opacity     = '1';
            loader.style.visibility  = 'visible';
            loader.style.pointerEvents = 'all';

            // Doble requestAnimationFrame: el primero encola el paint,
            // el segundo se ejecuta DESPUÉS de que el browser ya pintó.
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    window.location.href = href;
                });
            });
        }

        function moduleFromHref(href) {
            try {
                const url = new URL(href, window.location.href);
                const mod = url.searchParams.get('module');
                return mod ? (moduleLabels[mod] || mod) : null;
            } catch { return null; }
        }

        // ── Interceptar clics en enlaces ──
        document.addEventListener('click', function (e) {
            const link = e.target.closest('a[href]');
            if (!link) return;

            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript')) return;

            // Cancelar la navegación nativa del browser
            e.preventDefault();
            e.stopPropagation();

            if (href.includes('logout')) {
                showLoaderThenGo('Cerrando sesión...', href);
                return;
            }

            const label = moduleFromHref(href);
            const text  = label ? ('Cargando ' + label + '...') : 'Cargando...';
            showLoaderThenGo(text, href);

        }, true); // capture phase para interceptar antes de cualquier otro handler

        // ── Ocultar al volver con el botón atrás (bfcache iOS/Android) ──
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                loader.style.opacity     = '0';
                loader.style.visibility  = 'hidden';
                loader.style.pointerEvents = 'none';
            }
        });
    })();

    </script>
</body>
</html>