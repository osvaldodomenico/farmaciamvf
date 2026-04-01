<?php
// Inicia a sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
$isLoggedIn = isset($_SESSION['usuario_id']);
$currentPage = basename($_SERVER['PHP_SELF']);

// Obter iniciais do nome para o avatar
$userInitials = '';
if ($isLoggedIn && isset($_SESSION['nome_completo'])) {
    $names = explode(' ', $_SESSION['nome_completo']);
    $userInitials = strtoupper(substr($names[0], 0, 1));
    if (count($names) > 1) {
        $userInitials .= strtoupper(substr(end($names), 0, 1));
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmácia Comunitária</title>
    
    <!-- Bootstrap CSS -->
    <link href="assets/css/lib/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom App CSS -->
    <link href="assets/css/app.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/logo.png">
</head>
<body>

<?php if ($isLoggedIn): ?>
<div class="app-wrapper">
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <div class="brand-icon">
                    <i class="bi bi-capsule"></i>
                </div>
                <div class="brand-text">
                    Farmácia Comunitária
                    <span>Sistema de Gestão</span>
                </div>
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Menu Principal</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="bi bi-grid-1x2"></i>
                            Painel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="bi bi-capsule-pill"></i>
                            Medicamentos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="bi bi-box-seam"></i>
                            Estoque
                            <span class="nav-badge">3</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="bi bi-person-lines-fill"></i>
                            Clientes
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Operações</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="bi bi-clipboard2-check"></i>
                            Dispensação
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="bi bi-arrow-left-right"></i>
                            Movimentações
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="bi bi-graph-up"></i>
                            Relatórios
                        </a>
                    </li>
                </ul>
            </div>
            
            <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] === 'administrador'): ?>
            <div class="nav-section">
                <div class="nav-section-title">Administração</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="bi bi-people"></i>
                            Usuários
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="bi bi-gear"></i>
                            Configurações
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo $userInitials; ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['nome_completo'] ?? 'Usuário'); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($_SESSION['nivel_acesso'] ?? ''); ?></div>
                </div>
                <a href="logout.php" class="user-logout" title="Sair do sistema">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title"><?php echo $pageTitle ?? 'Painel'; ?></h1>
            </div>
            <div class="header-right">
                <button class="header-btn" title="Notificações">
                    <i class="bi bi-bell"></i>
                    <span class="badge-dot"></span>
                </button>
                <button class="header-btn" title="Ajuda">
                    <i class="bi bi-question-circle"></i>
                </button>
            </div>
        </header>
        
        <!-- Content Area -->
        <div class="content-area">
<?php else: ?>
<!-- Non-logged in users still get the basic HTML structure -->
<div class="non-auth-wrapper">
<?php endif; ?>