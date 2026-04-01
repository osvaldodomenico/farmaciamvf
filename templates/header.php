<?php
require_once __DIR__ . '/auth.php';

// Determinar página atual para marcar menu ativo
$pagina_atual = basename($_SERVER['PHP_SELF'], '.php');

// Detectar o caminho base para links relativos
// Se estiver em modules/*/arquivo.php, base_path = ../../
// Se estiver em dashboard.php, base_path = ""
$current_path = $_SERVER['PHP_SELF'];
$depth = substr_count($current_path, '/') - 1; // -1 porque a raiz já conta como 1
$base_path = str_repeat('../', $depth);
if ($depth == 0) $base_path = '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME ?? 'Farmácia Popular'; ?> - Sistema de Gestão</title>
    
    <!-- Preconnect para CDNs críticos -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.datatables.net" crossorigin>
    <link rel="dns-prefetch" href="https://code.jquery.com">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet" crossorigin="anonymous">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" crossorigin="anonymous">

    <!-- Toastr CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" crossorigin="anonymous">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"></noscript>
    
    <!-- Premium Design System (Layout Base) -->
    <link href="<?php echo $base_path; ?>assets/css/premium-system.css?v=<?php echo APP_VERSION; ?>" rel="stylesheet">
    <!-- New Premium Aesthetic (Overrides) -->
    <link href="<?php echo $base_path; ?>assets/css/premium.css?v=<?php echo APP_VERSION; ?>" rel="stylesheet">
    
    <!-- Custom Overrides -->
    <style>
        /* Base Overrides for Premium Layout */
        body {
            background-color: var(--premium-bg, #f4f7fa);
            font-family: 'Inter', -apple-system, sans-serif !important;
        }

        .main-content {
            padding: 2rem !important;
            padding-bottom: 72px !important;
        }

        /* Sidebar Refinements */
        .sidebar {
            background: #fff;
            border-right: 1px solid var(--premium-border);
            box-shadow: 2px 0 10px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
        }

        .sidebar-logo {
            border-bottom: 1px solid var(--premium-border);
            padding: 1.5rem;
        }

        .sidebar-menu {
            flex: 1;
            overflow-y: auto;
        }

        .menu-label {
            color: var(--premium-text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .sidebar-menu a {
            color: var(--premium-text-secondary);
            border-radius: 8px;
            margin: 0.25rem 0.75rem;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(15, 76, 117, 0.08);
            color: var(--premium-blue);
        }

        .sidebar-menu a.active {
            font-weight: 600;
        }

        /* Header Refinements */
        .main-header {
            background: #fff;
            border-bottom: 1px solid var(--premium-border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            padding: 1rem 2rem;
            height: var(--header-height);
        }

        .user-info strong {
            color: var(--premium-text-primary);
        }

        .user-info small {
            color: var(--premium-text-muted);
        }

        .user-avatar {
            background: linear-gradient(135deg, var(--premium-blue), var(--premium-teal-dark));
            color: white;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(15, 76, 117, 0.2);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Input Text Transformations */
        .input-uppercase {
            text-transform: uppercase;
        }
        
        .input-lowercase {
            text-transform: lowercase;
        }
        
        /* Sticky Footer */
        .app-footer {
            position: fixed;
            left: var(--sidebar-width);
            bottom: 0;
            width: calc(100% - var(--sidebar-width));
            background: linear-gradient(135deg, #0f4c75, #1a2b3c);
            color: #fff;
            padding: 10px 18px;
            font-size: 12px;
            border-top: 1px solid rgba(255,255,255,0.08);
            z-index: 999;
        }
        @media (max-width: 992px) {
            .app-footer {
                left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- Mobile Menu Toggle -->
<button class="mobile-menu-toggle" id="mobileMenuToggle">
    <i class="bi bi-list"></i>
</button>

<!-- Menu Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo">
        <img src="<?php echo $base_path; ?>assets/images/logo_maoamiga.png" 
             alt="Mão Amiga" 
             style="max-width: 140px; height: auto;">
    </div>
    <div class="sidebar-menu">
        <!-- 1. Dashboard (D) -->
        <a href="<?php echo $base_path; ?>dashboard.php" class="menu-dashboard <?php echo $pagina_atual == 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-house-door"></i>
            <span>Dashboard</span>
        </a>

        <!-- 2. Nova Dispensação — CTA fixo sempre visível -->
        <?php if (in_array($usuario_nivel, ['admin', 'farmaceutico', 'atendente'])): ?>
        <div style="padding: 0.5rem 0.75rem;">
            <a href="<?php echo $base_path; ?>modules/dispensacoes/nova.php"
               class="d-flex align-items-center justify-content-center gap-2"
               style="
                   background: linear-gradient(135deg, #0f4c75, #3282b8);
                   color: white;
                   border-radius: 10px;
                   padding: 0.65rem 1rem;
                   font-weight: 600;
                   font-size: 0.875rem;
                   text-decoration: none;
                   box-shadow: 0 3px 12px rgba(15,76,117,0.35);
                   transition: all 0.2s;
               "
               onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 5px 18px rgba(15,76,117,0.45)'"
               onmouseout="this.style.transform='';this.style.boxShadow='0 3px 12px rgba(15,76,117,0.35)'"
               title="Nova Dispensação (atalho: Alt+N)">
                <i class="bi bi-prescription2" style="font-size:1rem;"></i>
                <span>Nova Dispensação</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- 3. Cadastros (C) -->
        <div class="menu-group">
            <div class="menu-label" data-toggle="collapse">
                <span>Cadastros</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="menu-items">
                <a href="<?php echo $base_path; ?>modules/clientes/listar.php" class="<?php echo $pagina_atual == 'listar' && strpos($_SERVER['PHP_SELF'], 'clientes') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-person-badge"></i>
                    <span>Clientes</span>
                </a>
                
                <a href="<?php echo $base_path; ?>modules/doadores/listar.php" class="<?php echo $pagina_atual == 'listar' && strpos($_SERVER['PHP_SELF'], 'doadores') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-heart"></i>
                    <span>Doadores</span>
                </a>
                
                <a href="<?php echo $base_path; ?>modules/medicamentos/listar.php" class="<?php echo $pagina_atual == 'listar' && strpos($_SERVER['PHP_SELF'], 'medicamentos') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-capsule"></i>
                    <span>Medicamentos</span>
                </a>

                
            </div>
        </div>


        <!-- 4. Operações (O) -->
        <div class="menu-group">
            <div class="menu-label" data-toggle="collapse">
                <span>Operações</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="menu-items">
                <a href="<?php echo $base_path; ?>modules/dispensacoes/listar.php" class="<?php echo $pagina_atual == 'listar' && strpos($_SERVER['PHP_SELF'], 'dispensacoes') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-clipboard2-pulse"></i>
                    <span>Dispensações</span>
                </a>
                
                <a href="<?php echo $base_path; ?>modules/doacoes/listar.php" class="<?php echo $pagina_atual == 'listar' && strpos($_SERVER['PHP_SELF'], 'doacoes') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-box-arrow-in-down"></i>
                    <span>Doações Recebidas</span>
                </a>
                
                <a href="<?php echo $base_path; ?>modules/estoque/listar.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'estoque') !== false && $pagina_atual == 'listar' ? 'active' : ''; ?>">
                    <i class="bi bi-box-seam"></i>
                    <span>Estoque</span>
                </a>
            </div>
        </div>
        
        <?php if (in_array($usuario_nivel, ['admin', 'gerente'])): ?>
        <!-- 5. Relatórios (R) -->
        <div class="menu-group">
            <div class="menu-label" data-toggle="collapse">
                <span>Relatórios</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="menu-items">
                <a href="<?php echo $base_path; ?>modules/relatorios/dispensacoes.php">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Dispensações</span>
                </a>
                
                <a href="<?php echo $base_path; ?>modules/relatorios/doacoes.php">
                    <i class="bi bi-graph-up"></i>
                    <span>Doações</span>
                </a>
                
                <a href="<?php echo $base_path; ?>modules/relatorios/estoque.php">
                    <i class="bi bi-boxes"></i>
                    <span>Estoque</span>
                </a>
                
                <a href="<?php echo $base_path; ?>modules/relatorios/vigilancia.php">
                    <i class="bi bi-shield-check"></i>
                    <span>Vigilância Sanitária</span>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 6. Sistema (S) -->
        <div class="menu-group">
            <div class="menu-label" data-toggle="collapse">
                <span>Sistema</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="menu-items">
                <a href="<?php echo $base_path; ?>modules/perfil/index.php">
                    <i class="bi bi-person-circle"></i>
                    <span>Meu Perfil</span>
                </a>

                <?php if (in_array($usuario_nivel, ['admin', 'gerente'])): ?>
                <a href="<?php echo $base_path; ?>modules/usuarios/listar.php" class="<?php echo $pagina_atual == 'listar' && strpos($_SERVER['PHP_SELF'], 'usuarios') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>Usuários</span>
                </a>
                <?php endif; ?>

                <a href="<?php echo $base_path; ?>logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Sair</span>
                </a>
            </div>
        </div>
    </div>
    
</div>

<!-- Main Header -->
<div class="main-header">
    <div class="header-title">
        <h2 id="page-title">Dashboard</h2>
    </div>
    
    <div class="user-menu d-flex align-items-center gap-2">
        <div class="user-info text-end">
            <strong><?php echo htmlspecialchars($usuario_nome); ?></strong>
            <small class="d-block"><?php echo ucfirst($usuario_nivel); ?></small>
        </div>
        <div class="user-avatar">
            <?php
                $avatarPathJpg = $base_path . "assets/images/avatar/" . $usuario_id . ".jpg";
                $avatarPathPng = $base_path . "assets/images/avatar/" . $usuario_id . ".png";
                $fsJpg = __DIR__ . "/../assets/images/avatar/" . $usuario_id . ".jpg";
                $fsPng = __DIR__ . "/../assets/images/avatar/" . $usuario_id . ".png";
                if (file_exists($fsJpg)) {
                    echo '<img src="' . htmlspecialchars($avatarPathJpg) . '" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">';
                } elseif (file_exists($fsPng)) {
                    echo '<img src="' . htmlspecialchars($avatarPathPng) . '" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">';
                } else {
                    echo strtoupper(substr($usuario_nome, 0, 1));
                }
            ?>
        </div>
        <div class="ms-1 border-start ps-2">
            <a href="<?php echo $base_path; ?>logout.php" class="text-danger" title="Sair do Sistema" style="font-size: 1.2rem;">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content d-flex flex-column">
    <!-- Alerts -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4 mt-2 mx-4" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4 mt-2 mx-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
