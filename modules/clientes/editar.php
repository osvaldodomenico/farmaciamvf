<?php
require_once __DIR__ . '/../../templates/auth.php';

$id = $_GET['id'] ?? 0;
$cliente = fetchOne("SELECT * FROM " . tableName('clientes') . " WHERE id = ?", [$id]);

if (!$cliente) {
    $_SESSION['error'] = 'Cliente não encontrado.';
    header('Location: listar.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação de campos obrigatórios
        $nome_completo = trim($_POST['nome_completo'] ?? '');
        $data_nascimento = $_POST['data_nascimento'] ?? '';
        $sexo = $_POST['sexo'] ?? '';
        $celular = $_POST['celular'] ?? '';

        if (empty($nome_completo) || empty($data_nascimento) || empty($sexo) || empty($celular)) {
            throw new Exception('Nome completo, data de nascimento, sexo e celular são campos obrigatórios.');
        }

        $dados = [
            $_POST['nome_completo'] ?? '',
            $_POST['cpf'] ?? null ?: null,
            $_POST['rg'] ?? null ?: null,
            $_POST['data_nascimento'] ?? null ?: null,
            $_POST['sexo'] ?? 'outro',
            $_POST['celular'] ?? null ?: null,
            $_POST['email'] ?? null ?: null,
            $_POST['cep'] ?? null ?: null,
            $_POST['logradouro'] ?? null ?: null,
            $_POST['numero'] ?? null ?: null,
            $_POST['complemento'] ?? null ?: null,
            $_POST['bairro'] ?? null ?: null,
            $_POST['cidade'] ?? null ?: null,
            $_POST['estado'] ?? null ?: null,
            $_POST['latitude'] ?? null ?: null,
            $_POST['longitude'] ?? null ?: null,
            $_POST['observacoes'] ?? null ?: null,
            $id
        ];
        
        execute("UPDATE " . tableName('clientes') . " SET 
            nome_completo = ?, cpf = ?, rg = ?, data_nascimento = ?, sexo = ?,
            celular = ?, email = ?, cep = ?, logradouro = ?,
            numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ?,
            latitude = ?, longitude = ?, observacoes = ?
            WHERE id = ?", $dados);
        
        registrarLog($_SESSION['usuario_id'], 'editou_cliente', 'clientes', $id, $_POST['nome_completo'] ?? '');
        
        $_SESSION['success'] = 'Cliente atualizado com sucesso!';
        header('Location: listar.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erro ao atualizar cliente: ' . $e->getMessage();
    }
}

require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Editar Cliente';</script>

<div class="page-title">
    <h1><i class="bi bi-pencil"></i> Editar Cliente</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="listar.php">Clientes</a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Cliente</h5></div>
    <div class="card-body">
        <form method="POST" id="formCliente">
            <h6 class="text-primary mb-3"><i class="bi bi-person"></i> Dados Pessoais</h6>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome Completo *</label>
                    <input type="text" class="form-control input-uppercase" name="nome_completo" required maxlength="255"
                           value="<?php echo htmlspecialchars($cliente['nome_completo']); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">CPF</label>
                    <input type="text" class="form-control cpf" name="cpf" maxlength="14"
                           value="<?php echo htmlspecialchars($cliente['cpf'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">RG</label>
                    <input type="text" class="form-control input-uppercase" name="rg" maxlength="20"
                           value="<?php echo htmlspecialchars($cliente['rg'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Data de Nascimento *</label>
                    <input type="date" class="form-control" name="data_nascimento" required
                           value="<?php echo $cliente['data_nascimento'] ?? ''; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Sexo *</label>
                    <select class="form-select" name="sexo" required>
                        <option value="outro" <?php echo ($cliente['sexo'] ?? '') === 'outro' ? 'selected' : ''; ?>>Não informado</option>
                        <option value="M" <?php echo ($cliente['sexo'] ?? '') === 'M' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo ($cliente['sexo'] ?? '') === 'F' ? 'selected' : ''; ?>>Feminino</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Celular *</label>
                    <input type="text" class="form-control celular" name="celular" required maxlength="20"
                           value="<?php echo htmlspecialchars($cliente['celular'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" class="form-control input-lowercase" name="email" maxlength="255"
                           value="<?php echo htmlspecialchars($cliente['email'] ?? ''); ?>">
                </div>
            </div>
            
            <hr class="my-4">
            <h6 class="text-primary mb-3"><i class="bi bi-geo-alt"></i> Endereço</h6>
            
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label class="form-label">CEP</label>
                    <div class="input-group">
                        <input type="text" class="form-control cep" name="cep" id="cep" maxlength="10"
                               value="<?php echo htmlspecialchars($cliente['cep'] ?? ''); ?>">
                        <span class="input-group-text" id="cep-loading" style="display:none;">
                            <span class="spinner-border spinner-border-sm"></span>
                        </span>
                    </div>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">Endereço</label>
                    <input type="text" class="form-control input-uppercase" name="logradouro" id="logradouro" maxlength="255"
                           value="<?php echo htmlspecialchars($cliente['logradouro'] ?? ''); ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Número</label>
                    <input type="text" class="form-control input-uppercase" name="numero" maxlength="10"
                           value="<?php echo htmlspecialchars($cliente['numero'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Complemento</label>
                    <input type="text" class="form-control input-uppercase" name="complemento" maxlength="100"
                           value="<?php echo htmlspecialchars($cliente['complemento'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Bairro</label>
                    <input type="text" class="form-control input-uppercase" name="bairro" id="bairro" maxlength="100"
                           value="<?php echo htmlspecialchars($cliente['bairro'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Cidade</label>
                    <input type="text" class="form-control input-uppercase" name="cidade" id="cidade" maxlength="100"
                           value="<?php echo htmlspecialchars($cliente['cidade'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="estado" id="estado">
                        <option value="">Selecione...</option>
                        <?php
                        $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                        foreach ($estados as $uf) {
                            $selected = ($cliente['estado'] ?? '') === $uf ? 'selected' : '';
                            echo "<option value=\"$uf\" $selected>$uf</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars($cliente['latitude'] ?? ''); ?>">
            <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($cliente['longitude'] ?? ''); ?>">
            

            
            <div class="mb-3">
                <label class="form-label">Observações</label>
                <textarea class="form-control input-uppercase" name="observacoes" rows="3"><?php echo htmlspecialchars($cliente['observacoes'] ?? ''); ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="btnSalvarCliente">
                    <i class="bi bi-check-circle"></i> Salvar Alterações
                </button>
                <a href="listar.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
            </div>
            
            <script>
            // Proteção contra cliques duplos
            document.getElementById('formCliente').addEventListener('submit', function(e) {
                const btn = document.getElementById('btnSalvarCliente');
                if (btn.disabled) {
                    e.preventDefault();
                    return false;
                }
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Salvando...';
            });
            </script>
        </form>
    </div>
</div>



<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<script>
function buscarCoordenadas() {
    const logradouro = $('#logradouro').val();
    const numero = $('input[name="numero"]').val();
    const bairro = $('#bairro').val();
    const cidade = $('#cidade').val();
    const estado = $('#estado').val();
    
    if (!logradouro || !cidade) {
        toastr.warning('Preencha pelo menos o endereço e a cidade para geolocalizar.');
        return;
    }

    const enderecoCompleto = `${logradouro}, ${numero} - ${bairro}, ${cidade} - ${estado}, Brasil`;
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(enderecoCompleto)}&limit=1`;

    $('#latitude, #longitude').val('Buscando...');

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                $('#latitude').val(data[0].lat);
                $('#longitude').val(data[0].lon);
                toastr.success('Coordenadas encontradas!');
            } else {
                // Tentar busca simplificada se a completa falhar
                const enderecoSimples = `${logradouro}, ${cidade} - ${estado}, Brasil`;
                const urlSimples = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(enderecoSimples)}&limit=1`;
                
                return fetch(urlSimples)
                    .then(response => response.json())
                    .then(dataSimples => {
                        if (dataSimples && dataSimples.length > 0) {
                            $('#latitude').val(dataSimples[0].lat);
                            $('#longitude').val(dataSimples[0].lon);
                            toastr.info('Coordenadas encontradas (aproximadas pelo logradouro).');
                        } else {
                            $('#latitude, #longitude').val('');
                            toastr.error('Não foi possível encontrar as coordenadas para este endereço.');
                        }
                    });
            }
        })
        .catch(error => {
            console.error('Erro no geocoding:', error);
            $('#latitude, #longitude').val('');
            toastr.error('Erro ao consultar serviço de geolocalização.');
        });
}

// Disparar busca ao mudar qualquer campo do endereço
$(document).ready(function() {
    $('input[name="numero"], #cep, #logradouro, #bairro, #cidade, #estado').on('blur change', function() {
        if ($('#logradouro').val() && $('#cidade').val()) {
            // Pequeno delay para evitar múltiplas chamadas seguidas
            clearTimeout(window.geocodingTimeout);
            window.geocodingTimeout = setTimeout(buscarCoordenadas, 1000);
        }
    });
});
</script>
