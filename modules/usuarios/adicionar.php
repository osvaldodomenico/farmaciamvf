<?php
// Processar POST e verificação de permissão ANTES de incluir o header
session_start();
require_once '../../templates/auth.php';

// Verificar permissão
if (!in_array($usuario_nivel, ['admin', 'gerente'])) {
    $_SESSION['error'] = 'Você não tem permissão para acessar esta página.';
    header('Location: ../../dashboard.php');
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../../config/database.php';
    
    // Validações
    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $nivel_acesso = $_POST['nivel_acesso'] ?? '';
    $crf = trim($_POST['crf'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Validar campos obrigatórios
    if (empty($nome_completo)) $errors[] = 'Nome completo é obrigatório';
    if (empty($login)) $errors[] = 'Login é obrigatório';
    if (empty($senha)) $errors[] = 'Senha é obrigatória';
    if (empty($nivel_acesso)) $errors[] = 'Nível de acesso é obrigatório';
    
    // Validar CPF se fornecido
    if (!empty($cpf) && !validarCPF($cpf)) {
        $errors[] = 'CPF inválido';
    }
    
    // Validar email se fornecido
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }
    
    // Validar senhas
    if ($senha !== $confirmar_senha) {
        $errors[] = 'As senhas não conferem';
    }
    
    if (strlen($senha) < 6) {
        $errors[] = 'A senha deve ter no mínimo 6 caracteres';
    }
    
    // Verificar se login já existe
    if (!empty($login)) {
        $login_existe = fetchOne("SELECT id FROM " . tableName('usuarios') . " WHERE login = ?", [$login]);
        if ($login_existe) {
            $errors[] = 'Este login já está em uso';
        }
    }
    
    // Verificar se CPF já existe
    if (!empty($cpf)) {
        $cpf_existe = fetchOne("SELECT id FROM " . tableName('usuarios') . " WHERE cpf = ?", [$cpf]);
        if ($cpf_existe) {
            $errors[] = 'Este CPF já está cadastrado';
        }
    }
    
    // Se não houver erros, inserir
    if (empty($errors)) {
        try {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            $usuario_id = insert(tableName('usuarios'), [
                'nome_completo' => $nome_completo,
                'cpf' => $cpf ?: null,
                'login' => $login,
                'senha' => $senha_hash,
                'email' => $email ?: null,
                'telefone' => $telefone ?: null,
                'nivel_acesso' => $nivel_acesso,
                'crf' => $crf ?: null,
                'ativo' => $ativo
            ]);
            
            // Registrar log
            registrarLog($_SESSION['usuario_id'], 'Criou usuário', 'usuarios', $usuario_id, null, [
                'nome_completo' => $nome_completo,
                'login' => $login,
                'nivel_acesso' => $nivel_acesso
            ]);
            
            $_SESSION['success'] = 'Usuário cadastrado com sucesso!';
            header('Location: listar.php');
            exit();
            
        } catch (Exception $e) {
            error_log("Erro ao criar usuário: " . $e->getMessage());
            $errors[] = 'Erro ao cadastrar usuário. Tente novamente.';
        }
    }
}

require_once '../../templates/header.php';
?>

<script>
    document.getElementById('page-title').textContent = 'Novo Usuário';
</script>

<div class="page-title">
    <h1><i class="bi bi-person-plus"></i> Novo Usuário</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="listar.php">Usuários</a></li>
            <li class="breadcrumb-item active">Novo</li>
        </ol>
    </nav>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger" style="font-size: 1rem;">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>Erros encontrados:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="bi bi-clipboard"></i> Informações do Usuário
    </div>
    <div class="card-body">
        <form method="POST" action="" id="formUsuario">
            <div class="row">
                <!-- Nome Completo -->
                <div class="col-md-6 mb-3">
                    <label for="nome_completo" class="form-label" style="font-size: 1rem;">
                        Nome Completo <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control input-uppercase" 
                           id="nome_completo" 
                           name="nome_completo" 
                           value="<?php echo htmlspecialchars($_POST['nome_completo'] ?? ''); ?>"
                           required
                           style="font-size: 1rem; padding: 0.75rem;">
                </div>
                
                <!-- CPF -->
                <div class="col-md-6 mb-3">
                    <label for="cpf" class="form-label" style="font-size: 1rem;">CPF</label>
                    <input type="text" 
                           class="form-control cpf" 
                           id="cpf" 
                           name="cpf" 
                           value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>"
                           placeholder="000.000.000-00"
                           style="font-size: 1rem; padding: 0.75rem;">
                </div>
                
                <!-- Login -->
                <div class="col-md-6 mb-3">
                    <label for="login" class="form-label" style="font-size: 1rem;">
                        Login <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control input-lowercase" 
                           id="login" 
                           name="login" 
                           value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>"
                           required
                           style="font-size: 1rem; padding: 0.75rem;">
                    <small class="text-muted" style="font-size: 0.9375rem;">Nome de usuário para acesso ao sistema</small>
                </div>
                
                <!-- Email -->
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label" style="font-size: 1rem;">Email</label>
                    <input type="email" 
                           class="form-control input-lowercase" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           style="font-size: 1rem; padding: 0.75rem;">
                </div>
                
                <!-- Telefone -->
                <div class="col-md-6 mb-3">
                    <label for="telefone" class="form-label" style="font-size: 1rem;">Telefone</label>
                    <input type="text" 
                           class="form-control celular" 
                           id="telefone" 
                           name="telefone" 
                           value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>"
                           placeholder="(00) 00000-0000"
                           style="font-size: 1rem; padding: 0.75rem;">
                </div>
                
                <!-- Nível de Acesso -->
                <div class="col-md-6 mb-3">
                    <label for="nivel_acesso" class="form-label" style="font-size: 1rem;">
                        Nível de Acesso <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" 
                            id="nivel_acesso" 
                            name="nivel_acesso" 
                            required
                            style="font-size: 1rem; padding: 0.75rem;">
                        <option value="">Selecione...</option>
                        <option value="admin" <?php echo ($_POST['nivel_acesso'] ?? '') == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                        <option value="gerente" <?php echo ($_POST['nivel_acesso'] ?? '') == 'gerente' ? 'selected' : ''; ?>>Gerente</option>
                        <option value="farmaceutico" <?php echo ($_POST['nivel_acesso'] ?? '') == 'farmaceutico' ? 'selected' : ''; ?>>Farmacêutico</option>
                        <option value="atendente" <?php echo ($_POST['nivel_acesso'] ?? '') == 'atendente' ? 'selected' : ''; ?>>Atendente</option>
                        <option value="caixa" <?php echo ($_POST['nivel_acesso'] ?? '') == 'caixa' ? 'selected' : ''; ?>>Caixa</option>
                    </select>
                </div>
                
                <!-- CRF (para farmacêuticos) -->
                <div class="col-md-6 mb-3">
                    <label for="crf" class="form-label" style="font-size: 1rem;">CRF</label>
                    <input type="text" 
                           class="form-control input-uppercase" 
                           id="crf" 
                           name="crf" 
                           value="<?php echo htmlspecialchars($_POST['crf'] ?? ''); ?>"
                           placeholder="CRF-UF 12345"
                           style="font-size: 1rem; padding: 0.75rem;">
                    <small class="text-muted" style="font-size: 0.9375rem;">Obrigatório para farmacêuticos</small>
                </div>
                
                <!-- Senha -->
                <div class="col-md-6 mb-3">
                    <label for="senha" class="form-label" style="font-size: 1rem;">
                        Senha <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               id="senha" 
                               name="senha" 
                               required
                               minlength="6"
                               style="font-size: 1rem; padding: 0.75rem;">
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <small class="text-muted" style="font-size: 0.9375rem;">Mínimo 6 caracteres</small>
                </div>
                
                <!-- Confirmar Senha -->
                <div class="col-md-6 mb-3">
                    <label for="confirmar_senha" class="form-label" style="font-size: 1rem;">
                        Confirmar Senha <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               id="confirmar_senha" 
                               name="confirmar_senha" 
                               required
                               minlength="6"
                               style="font-size: 1rem; padding: 0.75rem;">
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Status Ativo -->
                <div class="col-md-12 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="ativo" 
                               name="ativo" 
                               value="1" 
                               checked
                               style="width: 20px; height: 20px;">
                        <label class="form-check-label" for="ativo" style="font-size: 1rem; margin-left: 0.5rem;">
                            Usuário Ativo
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2 justify-content-end mt-4">
                <a href="listar.php" class="btn btn-secondary" style="font-size: 1rem;">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary" style="font-size: 1rem;">
                    <i class="bi bi-check-circle"></i> Salvar Usuário
                </button>
            </div>
        </form>
    </div>
</div>



<?php require_once '../../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // Alternar visibilidade da senha
    $('.toggle-password').on('click', function() {
        const input = $(this).siblings('input');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // Validar senhas iguais
    $('#formUsuario').on('submit', function(e) {
        var senha = $('#senha').val();
        var confirmar = $('#confirmar_senha').val();
        
        if (senha !== confirmar) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'As senhas não conferem!',
                confirmButtonColor: '#4361ee'
            });
            return false;
        }
    });
});
</script>
