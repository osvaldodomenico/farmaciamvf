<?php
require_once __DIR__ . '/../../templates/auth.php';

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

        // Geocoding Logic (Nominatim)
        $latitude = null;
        $longitude = null;

        $logradouro = $_POST['logradouro'] ?? '';
        $numero = $_POST['numero'] ?? '';
        $bairro = $_POST['bairro'] ?? '';
        $cidade = $_POST['cidade'] ?? '';
        $estado = $_POST['estado'] ?? '';

        if (!empty($logradouro) && !empty($cidade)) {
            $endereco = "{$logradouro}, {$numero} - {$bairro}, {$cidade} - {$estado}, Brasil";
            $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($endereco) . "&limit=1";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, "FarmaciaPopularApp/1.0");
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            curl_close($ch);

            if ($response) {
                $geoData = json_decode($response, true);
                if (!empty($geoData) && isset($geoData[0]['lat'])) {
                    $latitude = $geoData[0]['lat'];
                    $longitude = $geoData[0]['lon'];
                }
            }
        }

        $id = insert('clientes', [
            'nome_completo' => $_POST['nome_completo'] ?? '',
            'cpf' => $_POST['cpf'] ?? null ?: null,
            'rg' => $_POST['rg'] ?? null ?: null,
            'data_nascimento' => $_POST['data_nascimento'] ?? null ?: null,
            'sexo' => $_POST['sexo'] ?? 'outro',
            'celular' => $_POST['celular'] ?? null ?: null,
            'email' => $_POST['email'] ?? null ?: null,
            'cep' => $_POST['cep'] ?? null ?: null,
            'logradouro' => $_POST['logradouro'] ?? null ?: null,
            'numero' => $_POST['numero'] ?? null ?: null,
            'complemento' => $_POST['complemento'] ?? null ?: null,
            'bairro' => $_POST['bairro'] ?? null ?: null,
            'cidade' => $_POST['cidade'] ?? null ?: null,
            'estado' => $_POST['estado'] ?? null ?: null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'observacoes' => $_POST['observacoes'] ?? null ?: null
        ]);
        
        registrarLog($_SESSION['usuario_id'], 'criou_cliente', 'clientes', $id, $_POST['nome_completo'] ?? '');
        
        $_SESSION['success'] = 'Cliente cadastrado com sucesso!';
        header('Location: listar.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erro ao cadastrar cliente: ' . $e->getMessage();
    }
}

require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Novo Cliente';</script>

<div class="page-title">
    <h1><i class="bi bi-plus-circle"></i> Novo Cliente</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="listar.php">Clientes</a></li>
            <li class="breadcrumb-item active">Novo</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-file-earmark-plus"></i> Cadastrar Cliente</h5></div>
    <div class="card-body">
        <form method="POST" id="formCliente">
            <h6 class="text-primary mb-3"><i class="bi bi-person"></i> Dados Pessoais</h6>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome Completo *</label>
                    <input type="text" class="form-control input-uppercase" name="nome_completo" required maxlength="255" value="<?php echo htmlspecialchars($_POST['nome_completo'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">CPF</label>
                    <input type="text" class="form-control cpf" name="cpf" maxlength="14" value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">RG</label>
                    <input type="text" class="form-control input-uppercase" name="rg" maxlength="20" value="<?php echo htmlspecialchars($_POST['rg'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Data de Nascimento *</label>
                    <input type="date" class="form-control" name="data_nascimento" required value="<?php echo htmlspecialchars($_POST['data_nascimento'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Sexo *</label>
                    <select class="form-select" name="sexo" required>
                        <option value="Outro" <?php echo ($_POST['sexo'] ?? '') === 'Outro' ? 'selected' : ''; ?>>Não informado</option>
                        <option value="M" <?php echo ($_POST['sexo'] ?? '') === 'M' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo ($_POST['sexo'] ?? '') === 'F' ? 'selected' : ''; ?>>Feminino</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Celular *</label>
                    <input type="text" class="form-control celular" name="celular" required maxlength="20" value="<?php echo htmlspecialchars($_POST['celular'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" class="form-control input-lowercase" name="email" maxlength="255" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            
            <hr class="my-4">
            <h6 class="text-primary mb-3"><i class="bi bi-geo-alt"></i> Endereço</h6>
            
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label class="form-label">CEP</label>
                    <div class="input-group">
                        <input type="text" class="form-control cep" name="cep" id="cep" maxlength="10" value="<?php echo htmlspecialchars($_POST['cep'] ?? ''); ?>">
                        <span class="input-group-text" id="cep-loading" style="display:none;">
                            <span class="spinner-border spinner-border-sm"></span>
                        </span>
                    </div>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">Endereço</label>
                    <input type="text" class="form-control input-uppercase" name="logradouro" id="logradouro" maxlength="255" value="<?php echo htmlspecialchars($_POST['logradouro'] ?? ''); ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Número</label>
                    <input type="text" class="form-control input-uppercase" name="numero" maxlength="10" value="<?php echo htmlspecialchars($_POST['numero'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Complemento</label>
                    <input type="text" class="form-control input-uppercase" name="complemento" maxlength="100" value="<?php echo htmlspecialchars($_POST['complemento'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Bairro</label>
                    <input type="text" class="form-control input-uppercase" name="bairro" id="bairro" maxlength="100" value="<?php echo htmlspecialchars($_POST['bairro'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Cidade</label>
                    <input type="text" class="form-control input-uppercase" name="cidade" id="cidade" maxlength="100" value="<?php echo htmlspecialchars($_POST['cidade'] ?? ''); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="estado" id="estado">
                        <option value="">Selecione...</option>
                        <?php
                        $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                        foreach ($estados as $uf) {
                            $selected = ($_POST['estado'] ?? '') === $uf ? 'selected' : '';
                            echo "<option value=\"$uf\" $selected>$uf</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>


            

            
            <div class="mb-3">
                <label class="form-label">Observações</label>
                <textarea class="form-control input-uppercase" name="observacoes" rows="3"><?php echo htmlspecialchars($_POST['observacoes'] ?? ''); ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="btnSalvarCliente">
                    <i class="bi bi-check-circle"></i> Salvar Cliente
                </button>
                <a href="listar.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
            </div>
        </form>
        
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
    </div>
</div>



<?php require_once __DIR__ . '/../../templates/footer.php'; ?>


