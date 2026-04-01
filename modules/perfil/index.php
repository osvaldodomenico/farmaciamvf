<?php
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../config/database.php';

// Buscar dados do usuário logado
$usuario = fetchOne("SELECT * FROM " . tableName('usuarios') . " WHERE id = ?", [$_SESSION['usuario_id']]);
?>

<script>document.getElementById('page-title').textContent = 'Meu Perfil';</script>

<div class="page-title">
    <h1><i class="bi bi-person-circle"></i> Meu Perfil</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Meu Perfil</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-4">
        <!-- Card de informações -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="avatar-large mx-auto mb-3">
                    <?php
                        $avatarBase = '../../assets/images/avatar/' . $usuario['id'];
                        $avatarFile = null;
                        if (file_exists(__DIR__ . '/../../assets/images/avatar/' . $usuario['id'] . '.jpg')) {
                            $avatarFile = $avatarBase . '.jpg';
                        } elseif (file_exists(__DIR__ . '/../../assets/images/avatar/' . $usuario['id'] . '.png')) {
                            $avatarFile = $avatarBase . '.png';
                        }
                        if ($avatarFile) {
                            echo '<img src="' . htmlspecialchars($avatarFile) . '" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
                        } else {
                            echo strtoupper(substr($usuario['nome_completo'], 0, 1));
                        }
                    ?>
                </div>
                <div class="mt-2">
                    <form id="formAvatar" enctype="multipart/form-data" method="post">
                        <label for="avatar" class="btn btn-outline-primary btn-sm w-100 mb-1" style="cursor:pointer;">
                            <i class="bi bi-camera"></i> Alterar foto
                            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png" class="d-none">
                        </label>
                        <div id="avatarPreviewBar" class="d-none">
                            <button class="btn btn-primary btn-sm w-100" type="submit" id="btnEnviarAvatar">
                                <i class="bi bi-upload"></i> Salvar foto
                            </button>
                            <button type="button" class="btn btn-link btn-sm w-100 text-muted p-0 mt-1" id="btnCancelarAvatar">Cancelar</button>
                        </div>
                        <small class="text-muted d-block mt-1">JPG ou PNG · máx. 2MB</small>
                    </form>
                </div>
                <h4 class="mb-1"><?php echo htmlspecialchars($usuario['nome_completo']); ?></h4>
                <p class="text-muted mb-3">
                    <span class="badge bg-primary"><?php echo ucfirst($usuario['nivel_acesso']); ?></span>
                </p>
                
                <div class="text-start">
                    <p class="mb-2">
                        <i class="bi bi-person me-2 text-muted"></i>
                        <strong>Login:</strong> <?php echo htmlspecialchars($usuario['login']); ?>
                    </p>
                    <?php if ($usuario['email']): ?>
                    <p class="mb-2">
                        <i class="bi bi-envelope me-2 text-muted"></i>
                        <?php echo htmlspecialchars($usuario['email']); ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($usuario['telefone']): ?>
                    <p class="mb-2">
                        <i class="bi bi-telephone me-2 text-muted"></i>
                        <?php echo htmlspecialchars($usuario['telefone']); ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($usuario['crf']): ?>
                    <p class="mb-2">
                        <i class="bi bi-card-text me-2 text-muted"></i>
                        <strong>CRF:</strong> <?php echo htmlspecialchars($usuario['crf']); ?>
                    </p>
                    <?php endif; ?>
                    <p class="mb-0 text-muted">
                        <i class="bi bi-clock me-2"></i>
                        Último acesso: <?php echo $usuario['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) : 'Primeiro acesso'; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Estatísticas do usuário -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Minhas Atividades</h5>
            </div>
            <div class="card-body">
                <div id="estatisticas"></div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="perfilTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button">
                    <i class="bi bi-person-lines-fill"></i> Dados Pessoais
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="senha-tab" data-bs-toggle="tab" data-bs-target="#senha" type="button">
                    <i class="bi bi-key"></i> Alterar Senha
                </button>
            </li>
        </ul>
        
        <div class="tab-content">
            <!-- Tab Dados Pessoais -->
            <div class="tab-pane fade show active" id="dados" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Dados Pessoais</h5>
                    </div>
                    <div class="card-body">
                        <form id="formDados">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nome_completo" name="nome_completo" 
                                           value="<?php echo htmlspecialchars($usuario['nome_completo']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Telefone</label>
                                    <input type="text" class="form-control" id="telefone" name="telefone" 
                                           value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CPF</label>
                                    <input type="text" class="form-control" id="cpf" name="cpf" 
                                           value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>" readonly>
                                    <small class="text-muted">O CPF não pode ser alterado</small>
                                </div>
                            </div>
                            
                            <?php if (in_array($usuario['nivel_acesso'], ['farmaceutico', 'admin'])): ?>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">CRF (Farmacêutico)</label>
                                    <input type="text" class="form-control" id="crf" name="crf" 
                                           value="<?php echo htmlspecialchars($usuario['crf'] ?? ''); ?>">
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Salvar Alterações
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Tab Alterar Senha -->
            <div class="tab-pane fade" id="senha" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Alterar Senha</h5>
                    </div>
                    <div class="card-body">
                        <form id="formSenha">
                            <div class="mb-4">
                                <label class="form-label">Senha Atual <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('senha_atual')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Nova Senha <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="senha_nova" name="senha_nova" required minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('senha_nova')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Mínimo de 6 caracteres</small>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Confirmar Nova Senha <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="senha_confirma" name="senha_confirma" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('senha_confirma')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-key"></i> Alterar Senha
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-large {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), #7c3aed);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 2.5rem;
    font-weight: 600;
}

.nav-tabs .nav-link {
    font-size: 1.1rem;
    padding: 0.875rem 1.25rem;
}

.nav-tabs .nav-link.active {
    font-weight: 600;
}
</style>

<script>
// DOMContentLoaded fires AFTER jQuery (loaded synchronously in footer.php) is available
document.addEventListener('DOMContentLoaded', function() {

    // Salvar referência do avatar original para restaurar ao cancelar
    var originalAvatarHtml = document.querySelector('.avatar-large').innerHTML;

    // Máscaras
    if ($.fn.mask) {
        $('#telefone').mask('(00) 00000-0000');
    }

    // Carregar estatísticas
    carregarEstatisticas();

    // Preview ao selecionar arquivo
    $('#avatar').on('change', function() {
        var file = this.files[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            toastr.error('Arquivo muito grande. Máximo 2MB.');
            this.value = '';
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            document.querySelector('.avatar-large').innerHTML =
                '<img src="' + e.target.result + '" alt="Preview" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
        };
        reader.readAsDataURL(file);
        $('#avatarPreviewBar').removeClass('d-none');
    });

    // Cancelar seleção de avatar
    $('#btnCancelarAvatar').on('click', function() {
        $('#avatar').val('');
        $('#avatarPreviewBar').addClass('d-none');
        document.querySelector('.avatar-large').innerHTML = originalAvatarHtml;
    });

    // Upload de avatar
    $('#formAvatar').on('submit', function(e) {
        e.preventDefault();
        var file = $('#avatar')[0].files[0];
        if (!file) { toastr.info('Selecione uma imagem'); return; }

        var fd = new FormData();
        fd.append('action', 'upload_avatar');
        fd.append('avatar', file);

        var $btn = $('#btnEnviarAvatar');
        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Enviando...');

        $.ajax({
            url: 'perfil_api.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    toastr.success('Avatar atualizado!');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    toastr.error(resp.error || 'Falha ao enviar avatar');
                    $btn.prop('disabled', false).html('<i class="bi bi-upload"></i> Salvar foto');
                }
            },
            error: function() {
                toastr.error('Erro ao comunicar com o servidor');
                $btn.prop('disabled', false).html('<i class="bi bi-upload"></i> Salvar foto');
            }
        });
    });

    // Salvar dados pessoais
    $('#formDados').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: 'perfil_api.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'atualizar_dados',
                nome_completo: $('#nome_completo').val(),
                email: $('#email').val(),
                telefone: $('#telefone').val(),
                crf: $('#crf').val() || ''
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Dados atualizados com sucesso!');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    toastr.error(response.error || 'Erro ao atualizar dados');
                }
            },
            error: function() {
                toastr.error('Erro ao comunicar com o servidor');
            }
        });
    });

    // Alterar senha
    $('#formSenha').on('submit', function(e) {
        e.preventDefault();

        var senhaNova = $('#senha_nova').val();
        var senhaConfirma = $('#senha_confirma').val();

        if (senhaNova !== senhaConfirma) {
            toastr.warning('As senhas não conferem');
            return;
        }

        $.ajax({
            url: 'perfil_api.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'alterar_senha',
                senha_atual: $('#senha_atual').val(),
                senha_nova: senhaNova
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Senha alterada com sucesso!');
                    $('#formSenha')[0].reset();
                } else {
                    toastr.error(response.error || 'Erro ao alterar senha');
                }
            },
            error: function() {
                toastr.error('Erro ao comunicar com o servidor');
            }
        });
    });

});

function carregarEstatisticas() {
    $.getJSON('perfil_api.php?action=estatisticas', function(data) {
        var html = '<div class="d-flex justify-content-between align-items-center mb-2">' +
            '<span><i class="bi bi-prescription2 text-primary me-2"></i> Dispensações</span>' +
            '<strong>' + (data.dispensacoes || 0) + '</strong></div>' +
            '<div class="d-flex justify-content-between align-items-center mb-2">' +
            '<span><i class="bi bi-box-arrow-in-down text-success me-2"></i> Doações Registradas</span>' +
            '<strong>' + (data.doacoes || 0) + '</strong></div>' +
            '<div class="d-flex justify-content-between align-items-center">' +
            '<span><i class="bi bi-calendar-check text-info me-2"></i> Membro desde</span>' +
            '<strong>' + (data.membro_desde || '-') + '</strong></div>';
        $('#estatisticas').html(html);
    });
}

function togglePassword(inputId) {
    var input = document.getElementById(inputId);
    var icon = event.currentTarget.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
