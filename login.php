<?php
session_start(); // Inicia a sessão

// Se o usuário já estiver logado, redireciona para o dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'config/database.php'; // Conexão PDO
require_once 'config/security.php'; // CSRF + Rate Limiting

$mensagem_erro = '';
$mensagem_bloqueio = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Verificar token CSRF
    $csrf_enviado = $_POST['csrf_token'] ?? '';
    if (empty($csrf_enviado) || !hash_equals(csrfToken(), $csrf_enviado)) {
        $mensagem_erro = "Token de segurança inválido. Recarregue a página e tente novamente.";
    }
    // 2. Verificar rate limit
    elseif (($rateStatus = rateLimitCheck('login'))['blocked']) {
        $mensagem_bloqueio = "Muitas tentativas falhas. Aguarde " . formatRemainingTime($rateStatus['remaining_seconds']) . " antes de tentar novamente.";
    }
    elseif (empty(trim($_POST["login"])) || empty(trim($_POST["senha"]))) {
        $mensagem_erro = "Por favor, preencha o login e a senha.";
    } else {
        $login = trim($_POST["login"]);
        $senha_digitada = trim($_POST["senha"]);

        try {
            $db = getDb();
            $usuarios = tableName('usuarios');
            $sql = "SELECT id, nome_completo, login, senha, nivel_acesso FROM {$usuarios} WHERE login = :login AND ativo = 1 LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':login', $login, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $usuario = $stmt->fetch();
                if (password_verify($senha_digitada, $usuario['senha'])) {
                    // Login bem-sucedido: limpar tentativas e iniciar sessão
                    rateLimitClear('login');

                    // Regenerar ID de sessão para prevenir session fixation
                    session_regenerate_id(true);

                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['nome_completo'] = $usuario['nome_completo'];
                    $_SESSION['login'] = $usuario['login'];
                    $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];

                    // Redirecionar para o painel
                    header("Location: dashboard.php");
                    exit();
                } else {
                    rateLimitRecord('login');
                    $rateAtual = rateLimitCheck('login');
                    $restantes = RATE_LIMIT_MAX - $rateAtual['attempts'];
                    if ($restantes > 0) {
                        $mensagem_erro = "Login ou senha inválidos. Você tem {$restantes} tentativa(s) restante(s).";
                    } else {
                        $mensagem_bloqueio = "Conta temporariamente bloqueada por excesso de tentativas. Aguarde " . formatRemainingTime(RATE_LIMIT_LOCKOUT) . ".";
                    }
                }
            } else {
                rateLimitRecord('login');
                $mensagem_erro = "Login ou senha inválidos.";
            }
        } catch (PDOException $e) {
            error_log("Erro no login: " . $e->getMessage());
            $mensagem_erro = "Ocorreu um erro. Tente novamente mais tarde.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Farmácia Mão Amiga</title>
    <!-- Bootstrap CSS -->
    <link href="assets/css/lib/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Login CSS -->
    <link href="assets/css/login.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/logo.png">
</head>
<body>

    <div class="login-wrapper">
        <!-- Left Side: Image -->
        <div class="login-sidebar"></div>

        <!-- Right Side: Login Form -->
        <div class="login-main">
            <div class="login-form-content">
                <!-- Logo Section -->
                <div class="logo-section">
                    <img src="assets/images/logo_maoamiga.png" 
                         alt="Mão Amiga" 
                         style="max-width: 120px; height: auto;">
                </div>

                <div class="mb-4 text-center">
                    <p class="brand-subtitle">Sistema de Gestão de Medicamentos e Clientes</p>
                </div>

                <?php if (!empty($mensagem_bloqueio)): ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-shield-lock-fill"></i>
                        <span><?php echo htmlspecialchars($mensagem_bloqueio); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($mensagem_erro)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span><?php echo htmlspecialchars($mensagem_erro); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Você saiu do sistema com sucesso!</span>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="loginForm" novalidate>
                    <?php echo csrfField(); ?>
                    <!-- Login Field -->
                    <div class="form-group">
                        <label for="login" class="form-label">
                            Usuário <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="login" 
                            name="login" 
                            placeholder="Digite seu nome de usuário" 
                            required 
                            autofocus 
                            value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>"
                            aria-describedby="loginHelp"
                        >
                        <small id="loginHelp" class="form-text">
                            <i class="bi bi-info-circle"></i>
                            Use seu nome de usuário cadastrado no sistema
                        </small>
                    </div>
                    
                    <!-- Password Field with Toggle -->
                    <div class="form-group">
                        <label for="senha" class="form-label">
                            Senha <span class="required">*</span>
                        </label>
                        <div class="password-wrapper">
                            <input 
                                type="password" 
                                class="form-control" 
                                id="senha" 
                                name="senha" 
                                placeholder="Digite sua senha" 
                                required
                                aria-describedby="senhaHelp"
                            >
                            <button 
                                type="button" 
                                class="password-toggle" 
                                id="togglePassword"
                                aria-label="Mostrar/Ocultar senha"
                            >
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        <small id="senhaHelp" class="form-text">
                            <i class="bi bi-shield-lock"></i>
                            Sua senha deve ter pelo menos 6 caracteres
                        </small>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="form-options">
                        <div class="form-check">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                value="" 
                                id="rememberMe"
                            >
                            <label class="form-check-label" for="rememberMe">
                                Lembrar-me
                            </label>
                        </div>
                        <a href="#" class="forgot-link">Esqueceu a senha?</a>
                    </div>

                    <!-- Submit Button -->
                    <button class="btn-primary-custom" type="submit" id="submitBtn">
                        Entrar no Sistema
                    </button>
                    
                    <!-- Footer -->
                    <div class="footer-text">
                        Sistema Mão Amiga • <?php echo date("Y"); ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Login Page Scripts -->
    <script>
        // Password Toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('senha');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                
                // Toggle icon
                if (type === 'password') {
                    toggleIcon.classList.remove('bi-eye-slash');
                    toggleIcon.classList.add('bi-eye');
                } else {
                    toggleIcon.classList.remove('bi-eye');
                    toggleIcon.classList.add('bi-eye-slash');
                }
            });
        }
        
        // Form Validation
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                const loginInput = document.getElementById('login');
                const senhaInput = document.getElementById('senha');
                
                let isValid = true;
                
                // Reset validation states
                loginInput.classList.remove('is-invalid', 'is-valid');
                senhaInput.classList.remove('is-invalid', 'is-valid');
                
                // Validate login
                if (loginInput.value.trim() === '') {
                    loginInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    loginInput.classList.add('is-valid');
                }
                
                // Validate password
                if (senhaInput.value.trim() === '') {
                    senhaInput.classList.add('is-invalid');
                    isValid = false;
                } else if (senhaInput.value.length < 6) {
                    senhaInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    senhaInput.classList.add('is-valid');
                }
                
                if (!isValid) {
                    e.preventDefault();
                } else {
                    // Add loading state
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                }
            });
            
            // Real-time validation feedback
            const inputs = loginForm.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() !== '') {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                });
                
                input.addEventListener('input', function() {
                    this.classList.remove('is-invalid', 'is-valid');
                });
            });
        }
    </script>
</body>
</html>
