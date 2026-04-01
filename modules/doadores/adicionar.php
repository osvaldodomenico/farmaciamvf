<?php
// Processar POST ANTES de incluir o header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    require_once __DIR__ . '/../../config/database.php';
    
    try {
        $dados = [
            $_POST['tipo'],
            $_POST['nome_completo'],
            $_POST['cpf_cnpj'] ?: null,
            $_POST['telefone'] ?: null,
            $_POST['celular'] ?: null,
            $_POST['email'] ?: null,
            $_POST['cep'] ?: null,
            $_POST['endereco'] ?: null,
            $_POST['numero'] ?: null,
            $_POST['complemento'] ?: null,
            $_POST['bairro'] ?: null,
            $_POST['cidade'] ?: null,
            $_POST['estado'] ?: null,
            $_POST['observacoes'] ?: null
        ];
        
        $id = execute("INSERT INTO " . tableName('doadores') . " (
            tipo, nome_completo, cpf_cnpj, telefone, celular, email,
            cep, endereco, numero, complemento, bairro, cidade, estado, observacoes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $dados);
        
        $id = getDb()->lastInsertId();
        
        registrarLog($_SESSION['usuario_id'], 'criou_doador', 'doadores', $id, $_POST['nome_completo']);
        
        $_SESSION['success'] = 'Doador cadastrado com sucesso!';
        header('Location: listar.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erro ao cadastrar doador: ' . $e->getMessage();
        header('Location: adicionar.php');
        exit;
    }
}

require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Novo Doador';</script>

<div class="page-title">
    <h1><i class="bi bi-plus-circle"></i> Novo Doador</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="listar.php">Doadores</a></li>
            <li class="breadcrumb-item active">Novo</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0"><i class="bi bi-heart-fill"></i> Cadastrar Doador</h5></div>
    <div class="card-body">
        <form method="POST" id="formDoador">
            <h6 class="text-primary mb-3"><i class="bi bi-person"></i> Dados do Doador</h6>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo de Pessoa *</label>
                    <select class="form-select" name="tipo" id="tipo" required>
                        <option value="pessoa_fisica">Pessoa Física</option>
                        <option value="pessoa_juridica">Pessoa Jurídica</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" id="labelNome">Nome Completo *</label>
                    <input type="text" class="form-control input-uppercase" name="nome_completo" required maxlength="255">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label" id="labelDoc">CPF</label>
                    <input type="text" class="form-control" name="cpf_cnpj" id="cpf_cnpj" maxlength="18">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control telefone" name="telefone" maxlength="20">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Celular</label>
                    <input type="text" class="form-control celular" name="celular" maxlength="20">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" class="form-control input-lowercase" name="email" maxlength="255">
                </div>
            </div>
            
            <hr class="my-4">
            <h6 class="text-primary mb-3"><i class="bi bi-geo-alt"></i> Endereço</h6>
            
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label class="form-label">CEP</label>
                    <div class="input-group">
                        <input type="text" class="form-control cep" name="cep" id="cep" maxlength="10" data-logradouro="#endereco">
                        <span class="input-group-text" id="cep-loading" style="display:none;">
                            <span class="spinner-border spinner-border-sm"></span>
                        </span>
                    </div>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">Endereço</label>
                    <input type="text" class="form-control input-uppercase" name="endereco" id="endereco" maxlength="255">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Número</label>
                    <input type="text" class="form-control input-uppercase" name="numero" maxlength="10">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Complemento</label>
                    <input type="text" class="form-control input-uppercase" name="complemento" maxlength="100">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Bairro</label>
                    <input type="text" class="form-control input-uppercase" name="bairro" id="bairro" maxlength="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Cidade</label>
                    <input type="text" class="form-control input-uppercase" name="cidade" id="cidade" maxlength="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="estado" id="estado">
                        <option value="">Selecione...</option>
                        <option value="AC">Acre</option>
                        <option value="AL">Alagoas</option>
                        <option value="AP">Amapá</option>
                        <option value="AM">Amazonas</option>
                        <option value="BA">Bahia</option>
                        <option value="CE">Ceará</option>
                        <option value="DF">Distrito Federal</option>
                        <option value="ES">Espírito Santo</option>
                        <option value="GO">Goiás</option>
                        <option value="MA">Maranhão</option>
                        <option value="MT">Mato Grosso</option>
                        <option value="MS">Mato Grosso do Sul</option>
                        <option value="MG">Minas Gerais</option>
                        <option value="PA">Pará</option>
                        <option value="PB">Paraíba</option>
                        <option value="PR">Paraná</option>
                        <option value="PE">Pernambuco</option>
                        <option value="PI">Piauí</option>
                        <option value="RJ">Rio de Janeiro</option>
                        <option value="RN">Rio Grande do Norte</option>
                        <option value="RS">Rio Grande do Sul</option>
                        <option value="RO">Rondônia</option>
                        <option value="RR">Roraima</option>
                        <option value="SC">Santa Catarina</option>
                        <option value="SP">São Paulo</option>
                        <option value="SE">Sergipe</option>
                        <option value="TO">Tocantins</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Observações</label>
                <textarea class="form-control input-uppercase" name="observacoes" rows="3"></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Salvar Doador
                </button>
                <a href="listar.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>



<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // ===== MÁSCARA DINÂMICA CPF/CNPJ =====
    function aplicarMascaraDoc() {
        const tipo = $('#tipo').val();
        $('#cpf_cnpj').unmask();
        if (tipo === 'pessoa_juridica') {
            $('#cpf_cnpj').mask('00.000.000/0000-00');
            $('#labelDoc').text('CNPJ');
            $('#labelNome').text('Razão Social *');
        } else {
            $('#cpf_cnpj').mask('000.000.000-00');
            $('#labelDoc').text('CPF');
            $('#labelNome').text('Nome Completo *');
        }
    }
    
    aplicarMascaraDoc();
    $('#tipo').on('change', aplicarMascaraDoc);
});
</script>
