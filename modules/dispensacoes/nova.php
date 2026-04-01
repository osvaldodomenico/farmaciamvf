<?php
require_once __DIR__ . '/../../templates/auth.php';

// Processar POST ANTES de qualquer saída HTML para evitar erro de Header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDb();
        $pdo->beginTransaction();
        
        // Calcular quantidade total
        $itens = json_decode($_POST['itens'], true);
        if (empty($itens)) throw new Exception("Adicione ao menos um item.");
        $quantidade_total = array_sum(array_column($itens, 'quantidade'));
        
        // Gerar número da dispensação no momento da gravação para evitar duplicidade
        $data_disp = date('Ymd');
        $count = fetchColumn("SELECT COUNT(*) + 1 FROM " . tableName('dispensacoes') . " WHERE DATE(data_dispensacao) = CURDATE()");
        $novo_numero = 'DISP-' . $data_disp . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        
        // Verificar se esse número foi gerado no meio tempo por outro usuário
        $existe = fetchColumn("SELECT id FROM " . tableName('dispensacoes') . " WHERE numero_dispensacao = ?", [$novo_numero]);
        if ($existe) {
            // Tenta o próximo se houver colisão
            $count++;
            $novo_numero = 'DISP-' . $data_disp . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        // Inserir dispensação
        $stmt = $pdo->prepare("INSERT INTO " . tableName('dispensacoes') . " (
            numero_dispensacao, cliente_id, data_dispensacao, usuario_id, 
            quantidade_total, receita_medica, numero_receita, medico_responsavel, observacoes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Gerar número da receita médica vinculado ao cliente: ID sequencial do dia + DDMMYYYY (sempre gerado no backend)
        $numero_receita = null;
        if (isset($_POST['receita_medica'])) {
            $clienteIdGen = (int)($_POST['cliente_id'] ?? 0);
            $dataRef = $_POST['data_dispensacao'];
            $diaMesAno = date('dmY', strtotime($dataRef));
            $seq = fetchColumn("
                SELECT COUNT(*) + 1 
                FROM " . tableName('dispensacoes') . " 
                WHERE cliente_id = ? 
                  AND DATE(data_dispensacao) = DATE(?) 
                  AND numero_receita IS NOT NULL
            ", [$clienteIdGen, $dataRef]);
            $numero_receita = 'RM-' . $clienteIdGen . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT) . '-' . $diaMesAno;
        }
        
        $stmt->execute([
            $novo_numero,
            $_POST['cliente_id'] ?: null,
            $_POST['data_dispensacao'],
            $_SESSION['usuario_id'],
            $quantidade_total,
            isset($_POST['receita_medica']) ? 1 : 0,
            $numero_receita,
            $_POST['medico_responsavel'] ?: null,
            $_POST['observacoes'] ?: null
        ]);
        
        $dispensacao_id = $pdo->lastInsertId();
        $numero_final_salvo = $novo_numero;
        
        // Inserir itens e baixar estoque
        foreach ($itens as $item) {
            // Inserir item
            $stmt = $pdo->prepare("INSERT INTO " . tableName('dispensacoes_itens') . " (
                dispensacao_id, medicamento_id, estoque_id, quantidade, posologia
            ) VALUES (?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $dispensacao_id,
                $item['medicamento_id'],
                $item['estoque_id'],
                $item['quantidade'],
                $item['posologia'] ?? null
            ]);
            
            // Baixar do estoque
            execute("UPDATE " . tableName('estoque') . " SET quantidade_atual = quantidade_atual - ? WHERE id = ?",
                [$item['quantidade'], $item['estoque_id']]);
        }
        
        $pdo->commit();
        
        registrarLog($_SESSION['usuario_id'], 'registrou_dispensacao', tableName('dispensacoes'), $dispensacao_id, null, ['numero' => $numero_final_salvo]);
        
        $_SESSION['success'] = 'Dispensação registrada com sucesso! Estoque atualizado.';
        header('Location: listar.php');
        exit;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error'] = 'Erro ao registrar dispensação: ' . $e->getMessage();
        // Permanece na mesma página para mostrar o erro
    }
}

require_once __DIR__ . '/../../templates/header.php';

// Gerar número da dispensação (apenas para exibição inicial no form)
$data = date('Ymd');
$count = fetchColumn("SELECT COUNT(*) + 1 FROM " . tableName('dispensacoes') . " WHERE DATE(data_dispensacao) = CURDATE()");
$numero_dispensacao = 'DISP-' . $data . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

// Buscar clientes
$clientes = fetchAll("SELECT id, nome_completo, cpf FROM " . tableName('clientes') . " WHERE ativo = 1 ORDER BY nome_completo");
?>

<script>document.getElementById('page-title').textContent = 'Nova Dispensação';</script>

<div class="page-title">
    <h1><i class="bi bi-prescription2"></i> Nova Dispensação</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="listar.php">Dispensações</a></li>
            <li class="breadcrumb-item active">Nova</li>
        </ol>
    </nav>
</div>

<form method="POST" id="formDispensacao">
    <input type="hidden" name="itens" id="itens_json" value="[]">
    
    <div class="row">
        <!-- Dados da Dispensação -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-info-circle"></i> Dados da Dispensação</h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Número</label>
                            <input type="text" class="form-control" name="numero_dispensacao" 
                                   value="<?php echo $numero_dispensacao; ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data/Hora *</label>
                            <input type="datetime-local" class="form-control" name="data_dispensacao" 
                                   value="<?php echo date('Y-m-d\TH:i'); ?>" readonly required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cliente</label>
                        <select class="form-select" name="cliente_id" id="cliente_id">
                            <option value="">Não identificado</option>
                            <?php foreach ($clientes as $pac): ?>
                                <option value="<?php echo $pac['id']; ?>">
                                    <?php echo htmlspecialchars($pac['nome_completo']); ?>
                                    <?php echo $pac['cpf'] ? " - CPF: {$pac['cpf']}" : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            <button type="button" class="btn btn-link btn-sm p-0 m-0 align-baseline" data-bs-toggle="modal" data-bs-target="#modalNovoCliente">
                                <i class="bi bi-plus-circle"></i> + CADASTRAR NOVO CLIENTE
                            </button>
                        </small>
                    </div>
                    
                    <hr>
                    <h6 class="text-primary"><i class="bi bi-file-medical"></i> Receita Médica</h6>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="receita_medica" id="receita_medica">
                        <label class="form-check-label" for="receita_medica">
                            Com receita médica
                        </label>
                    </div>
                    
                    <div id="campos_receita" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Número da Receita</label>
                            <input type="text" class="form-control" name="numero_receita" maxlength="100" disabled readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Médico Responsável</label>
                            <input type="text" class="form-control" name="medico_responsavel" maxlength="255">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Adicionar Itens -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-capsule"></i> Adicionar Medicamentos</h5></div>
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Medicamento *</label>
                            <div class="input-group">
                                <select class="form-select flex-grow-1" id="medicamento_id">
                                    <option value="">Digite o nome ou escaneie o código...</option>
                                </select>
                                <button class="btn btn-outline-secondary" type="button" id="btnScanBarcodeDisp" title="Escanear Código">
                                    <i class="bi bi-upc-scan"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Quantidade *</label>
                            <input type="number" class="form-control" id="quantidade" min="1" disabled>
                            <small class="inline-help" id="estoque_disponivel"></small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label d-none d-md-block">&nbsp;</label>
                            <button type="button" class="btn btn-success w-100" onclick="adicionarItem()" id="btnAdicionar" disabled>
                                <i class="bi bi-plus"></i> Adicionar Item
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Posologia</label>
                        <input type="text" class="form-control" id="posologia" placeholder="Ex: 1 comprimido a cada 8 horas">
                    </div>
                </div>
            </div>
            
            <!-- Lista de Itens -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-list-check"></i> Itens para Dispensar 
                        <span class="badge bg-primary" id="total-itens">0</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tabela-itens">
                            <thead>
                                <tr>
                                    <th>Medicamento</th>
                                    <th>Lote</th>
                                    <th>Validade</th>
                                    <th>Qtd</th>
                                    <th width="80">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="linha-vazia">
                                    <td colspan="5" class="text-center text-muted">
                                        Nenhum item adicionado
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="btnSalvar" disabled>
                            <i class="bi bi-check-circle"></i> Registrar Dispensação
                        </button>
                        <a href="listar.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>


<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<script>
// Prevenir duplo clique no envio
let formSubmitted = false;
$('#formDispensacao').on('submit', function(e) {
    if (formSubmitted) {
        e.preventDefault();
        return false;
    }
    formSubmitted = true;
    $('#btnSalvar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Registrando...');
    // Desabilitar também o botão cancelar para evitar navegação durante envio
    $(this).find('a.btn-secondary').addClass('disabled').css('pointer-events', 'none');
});

let itens = [];
let lotes_disponiveis = [];

// Toggle campos de receita
$('#receita_medica').on('change', function() {
    $('#campos_receita').toggle(this.checked);
    if (this.checked) {
        gerarNumeroReceitaUI();
    }
});

$('#cliente_id').on('change', function() {
    if ($('#receita_medica').prop('checked')) {
        gerarNumeroReceitaUI();
    }
});

function gerarNumeroReceitaUI() {
    const clienteId = $('#cliente_id').val();
    const dataDisp = $('input[name="data_dispensacao"]').val();
    if (!clienteId) {
        toastr.warning('Selecione o cliente para gerar o número da receita.');
        return;
    }
    $.getJSON(`dispensacoes_api.php?action=gerar_numero_receita&cliente_id=${clienteId}&data=${encodeURIComponent(dataDisp)}`, function(r) {
        if (r && r.numero) {
            $('input[name="numero_receita"]').val(r.numero);
        }
    });
}

// Carregar lotes ao selecionar medicamento
$('#medicamento_id').on('select2:select', function(e) {
    const data = e.params.data;
    const medicamento_id = data.id;
    const controlado = data.controlado;
    
    $('#quantidade').prop('disabled', true).val('');
    $('#estoque_disponivel').text('');
    $('#btnAdicionar').prop('disabled', true);
    
    if (!medicamento_id) return;
    
    // Se controlado, exigir receita
    if (controlado == 1 && !$('#receita_medica').prop('checked')) {
        toastr.warning('Este medicamento é controlado. Marque que há receita médica.');
        $('#receita_medica').prop('checked', true).trigger('change');
    }
    
    $.getJSON(`dispensacoes_api.php?action=buscar_estoque&medicamento_id=${medicamento_id}`, function(data) {
        lotes_disponiveis = data;
        const totalDisponivel = (data || []).reduce((sum, l) => sum + (parseInt(l.quantidade_atual) || 0), 0);
        const lotesCount = (data || []).length;
        if (totalDisponivel > 0) {
            $('#quantidade').prop('disabled', true).val('');
            $('#quantidade').attr('max', totalDisponivel);
            $('#estoque_disponivel').text(`Disponível: ${totalDisponivel} un. (em ${lotesCount} lote${lotesCount>1?'s':''})`);
            $('#quantidade').prop('disabled', false);
            $('#btnAdicionar').prop('disabled', false);
        } else {
            $('#estoque_disponivel').text('Sem estoque disponível');
            $('#quantidade').prop('disabled', true);
            $('#btnAdicionar').prop('disabled', true);
        }
    });
});

function adicionarItem() {
    const selectData = $('#medicamento_id').select2('data')[0];
    if (!selectData) return;

    const medicamento_id = selectData.id;
    const medicamento_nome = selectData.nome;
    const quantidade = parseInt($('#quantidade').val());
    
    const totalDisponivel = (lotes_disponiveis || []).reduce((sum, l) => sum + (parseInt(l.quantidade_atual) || 0), 0);
    if (quantidade > totalDisponivel) {
        toastr.error(`Quantidade indisponível. Máximo total: ${totalDisponivel}`);
        return;
    }
    
    // Escolher automaticamente um lote que tenha quantidade suficiente (prioriza menor validade)
    const candidato = (lotes_disponiveis || [])
        .sort((a,b) => new Date(a.data_validade) - new Date(b.data_validade))
        .find(l => parseInt(l.quantidade_atual) >= quantidade);
    if (!candidato) {
        toastr.error('Quantidade maior que disponível em um único lote. Ajuste a quantidade.');
        return;
    }
    
    const estoque_id = candidato.id;
    const lote = candidato.lote;
    const data_validade = candidato.data_validade;
    const quantidade_disponivel = candidato.quantidade_atual;
    const posologia = $('#posologia').val().trim();
    
    if (!medicamento_id || !quantidade) {
        toastr.warning('Preencha todos os campos do item');
        return;
    }
    
    if (quantidade <= 0) {
        toastr.warning('Quantidade deve ser maior que zero');
        return;
    }
    
    if (quantidade > quantidade_disponivel) {
        toastr.error(`Quantidade indisponível. Máximo: ${quantidade_disponivel}`);
        return;
    }
    
    const item = {
        medicamento_id,
        medicamento_nome,
        estoque_id,
        lote,
        data_validade,
        quantidade,
        posologia
    };
    
    itens.push(item);
    atualizarTabela();
    limparCamposItem();
    toastr.success('Item adicionado!');
}

function removerItem(index) {
    itens.splice(index, 1);
    atualizarTabela();
    toastr.info('Item removido');
}

function atualizarTabela() {
    const tbody = $('#tabela-itens tbody');
    tbody.empty();
    
    if (itens.length === 0) {
        tbody.html('<tr id="linha-vazia"><td colspan="5" class="text-center text-muted">Nenhum item adicionado</td></tr>');
        $('#btnSalvar').prop('disabled', true);
    } else {
        itens.forEach((item, index) => {
            const validade = new Date(item.data_validade + 'T00:00:00').toLocaleDateString('pt-BR');
            tbody.append(`
                <tr>
                    <td><strong>${item.medicamento_nome}</strong></td>
                    <td>${item.lote}</td>
                    <td>${validade}</td>
                    <td><span class="badge bg-info">${item.quantidade}</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removerItem(${index})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
        $('#btnSalvar').prop('disabled', false);
    }
    
    $('#total-itens').text(itens.length);
    $('#itens_json').val(JSON.stringify(itens));
}

function limparCamposItem() {
    $('#medicamento_id').val('').trigger('change.select2');
    $('#quantidade').prop('disabled', true).val('');
    $('#posologia').val('');
    $('#estoque_disponivel').text('');
    $('#btnAdicionar').prop('disabled', true);
}

$(document).ready(function() {
    $('#cliente_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Selecione ou busque...',
        allowClear: true
    });
    
    $('#medicamento_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Pesquise pelo nome ou princípio ativo...',
        allowClear: true,
        ajax: {
            url: '../medicamentos/medicamentos_api.php',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    action: 'search',
                    q: params.term,
                    com_estoque: 1
                };
            },
            processResults: function (data) {
                if (data.error) {
                    if (window.toastr) toastr.error(data.error);
                    return { results: [] };
                }
                return {
                    results: (data.results || []).map(function(item) {
                        let conc = item.dosagem_concentracao || '';
                        if (item.unidade_medida) {
                            conc += (item.unidade_medida === 'PORCENTAGEM' ? '%' : ' ' + item.unidade_medida);
                        }
                        return {
                            id: item.id,
                            text: item.nome + (conc ? ' - ' + conc : '') + ' (' + item.estoque_total + ' un.)',
                            nome: item.nome,
                            controlado: item.controlado,
                            estoque_total: item.estoque_total
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 2,
        language: {
            inputTooShort: function() { return "Digite pelo menos 2 caracteres..."; },
            searching: function() { return "Buscando..."; },
            noResults: function() { return "Nenhum medicamento encontrado com estoque."; }
        }
    });
});

// Modal Novo Cliente
function salvarNovoCliente() {
    const form = document.getElementById('formNovoClienteRapido');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const btn = document.getElementById('btnSalvarModalCliente');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';

    $.ajax({
        url: '../clientes/clientes_api.php?action=add',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Adicionar ao Select2 e selecionar
                const newOption = new Option(
                    response.nome_completo + (response.cpf ? ' - CPF: ' + response.cpf : ''), 
                    response.id, 
                    true, 
                    true
                );
                $('#cliente_id').append(newOption).trigger('change');
                
                // Fechar modal e limpar form
                bootstrap.Modal.getInstance(document.getElementById('modalNovoCliente')).hide();
                form.reset();
                toastr.success('Cliente cadastrado e selecionado!');
            } else {
                toastr.error(response.error || 'Erro ao salvar cliente');
            }
        },
        error: function() {
            toastr.error('Erro de comunicação com o servidor');
        },
        complete: function() {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
}
</script>

<!-- Modal Novo Cliente -->
<div class="modal fade" id="modalNovoCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus"></i> Novo Cliente (Cadastro Rápido)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoClienteRapido">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control input-uppercase" name="nome_completo" required maxlength="255">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Data de Nascimento *</label>
                            <input type="date" class="form-control" name="data_nascimento" required>
                            <div class="inline-help" id="idade_label"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Sexo *</label>
                            <select class="form-select" name="sexo" required>
                                <option value="outro">Não informado</option>
                                <option value="M">Masculino</option>
                                <option value="F">Feminino</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Celular</label>
                            <input type="text" class="form-control celular" name="celular" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control input-lowercase" name="email">
                        </div>
                    </div>
                    <hr>
                    <h6 class="text-primary mb-3"><i class="bi bi-geo-alt"></i> Endereço</h6>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">CEP</label>
                            <input type="text" class="form-control cep cep-lookup" name="cep" id="cep">
                        </div>
                        <div class="col-md-9 mb-3">
                            <label class="form-label">Logradouro</label>
                            <input type="text" class="form-control input-uppercase" name="logradouro" id="logradouro_modal">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Número</label>
                            <input type="text" class="form-control input-uppercase" name="numero" id="numero_modal">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Bairro</label>
                            <input type="text" class="form-control input-uppercase" name="bairro" id="bairro_modal">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Complemento</label>
                            <input type="text" class="form-control input-uppercase" name="complemento" id="complemento_modal">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-10 mb-3">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control input-uppercase" name="cidade" id="cidade_modal">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">UF</label>
                            <select class="form-select" name="estado" id="estado_modal">
                                <option value="">Selecione</option>
                                <option value="AC">AC</option>
                                <option value="AL">AL</option>
                                <option value="AP">AP</option>
                                <option value="AM">AM</option>
                                <option value="BA">BA</option>
                                <option value="CE">CE</option>
                                <option value="DF">DF</option>
                                <option value="ES">ES</option>
                                <option value="GO">GO</option>
                                <option value="MA">MA</option>
                                <option value="MT">MT</option>
                                <option value="MS">MS</option>
                                <option value="MG">MG</option>
                                <option value="PA">PA</option>
                                <option value="PB">PB</option>
                                <option value="PR">PR</option>
                                <option value="PE">PE</option>
                                <option value="PI">PI</option>
                                <option value="RJ">RJ</option>
                                <option value="RN">RN</option>
                                <option value="RS">RS</option>
                                <option value="RO">RO</option>
                                <option value="RR">RR</option>
                                <option value="SC">SC</option>
                                <option value="SP">SP</option>
                                <option value="SE">SE</option>
                                <option value="TO">TO</option>
                            </select>
                        </div>
                        <input type="hidden" name="latitude" id="latitude_modal">
                        <input type="hidden" name="longitude" id="longitude_modal">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarModalCliente" onclick="salvarNovoCliente()">
                    <i class="bi bi-check-circle"></i> Salvar e Selecionar
                </button>
            </div>
        </div>
    </div>
</div>
<?php 
// Injetar script para ajustar IDs do CEP lookup para o modal
?>
<script>
$(document).ready(function() {
    // Configurar o input de CEP do modal para carregar nos campos do modal
    $('#cep').data('logradouro', '#logradouro_modal');
    $('#cep').data('bairro', '#bairro_modal');
    $('#cep').data('cidade', '#cidade_modal');
    $('#cep').data('numero', '#numero_modal');
    $('#cep').data('estado', '#estado_modal');
    
    // Busca coordenadas ao alterar endereço
    function buscarCoordenadasModal() {
        const logradouro = $('#logradouro_modal').val().trim();
        const numero = $('#numero_modal').val().trim();
        const bairro = $('#bairro_modal').val().trim();
        const cidade = $('#cidade_modal').val().trim();
        const estado = $('#estado_modal').val().trim();
        
        if (!logradouro || !cidade) {
            $('#latitude_modal, #longitude_modal').val('');
            return;
        }
        
        const endereco = `${logradouro}, ${numero} - ${bairro}, ${cidade} - ${estado}, Brasil`;
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(endereco)}&limit=1`;
        
        fetch(url, { headers: { 'Accept': 'application/json' }})
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    $('#latitude_modal').val(data[0].lat);
                    $('#longitude_modal').val(data[0].lon);
                } else {
                    // Tentativa simplificada
                    const enderecoSimples = `${logradouro}, ${cidade} - ${estado}, Brasil`;
                    const urlSimples = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(enderecoSimples)}&limit=1`;
                    return fetch(urlSimples)
                        .then(r => r.json())
                        .then(ds => {
                            if (ds && ds.length > 0) {
                                $('#latitude_modal').val(ds[0].lat);
                                $('#longitude_modal').val(ds[0].lon);
                            } else {
                                $('#latitude_modal, #longitude_modal').val('');
                            }
                        });
                }
            })
            .catch(() => {
                $('#latitude_modal, #longitude_modal').val('');
            });
    }
    
    $('#logradouro_modal, #numero_modal, #bairro_modal, #cidade_modal').on('blur change input', buscarCoordenadasModal);
    
    function atualizarIdade() {
        const v = $('input[name="data_nascimento"]').val();
        if (!v) { $('#idade_label').text(''); return; }
        const hoje = new Date();
        const nasc = new Date(v);
        let idade = hoje.getFullYear() - nasc.getFullYear();
        const m = hoje.getMonth() - nasc.getMonth();
        if (m < 0 || (m === 0 && hoje.getDate() < nasc.getDate())) idade--;
        if (!isFinite(idade) || idade < 0) { $('#idade_label').text(''); return; }
        $('#idade_label').text(`Idade: ${idade} anos`);
    }
    $('input[name="data_nascimento"]').on('change input', atualizarIdade);
    atualizarIdade();
});
</script>

<!-- HTML5-QRCode Library -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<!-- Modal Leitor de Código de Barras -->
<div class="modal fade" id="barcodeScannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upc-scan"></i> Escanear Medicamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="reader" style="width: 100%;"></div>
            </div>
            <div class="modal-footer justify-content-center">
                <small class="text-muted">Aponte a câmera para o código de barras do medicamento</small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let html5QrcodeScanner = null;
    const scanButton = document.getElementById('btnScanBarcodeDisp');
    const scannerModal = new bootstrap.Modal(document.getElementById('barcodeScannerModal'));
    const modalElement = document.getElementById('barcodeScannerModal');

    // Som de beep (opcional, curto e sutil)
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    function playBeep() {
        if (audioContext.state === 'suspended') audioContext.resume();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        oscillator.type = 'square';
        oscillator.frequency.value = 1500; // Hz
        gainNode.gain.value = 0.1;
        oscillator.start();
        setTimeout(() => oscillator.stop(), 100);
    }

    scanButton.addEventListener('click', function() {
        scannerModal.show();
    });

    modalElement.addEventListener('shown.bs.modal', function () {
        // Configurar formatos suportados (Prioridade para EAN/Barras)
        const formatsToSupport = [
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E
        ];

        // Inicializar scanner com formatos específicos
        html5QrcodeScanner = new Html5Qrcode("reader", { formatsToSupport: formatsToSupport });
        
        // Configurações - Box mais largo e baixo para código de barras
        const config = { 
            fps: 15, // Aumentado para leitura mais fluida
            qrbox: { width: 280, height: 100 }, // Retangular
            aspectRatio: 1.0, 
            experimentalFeatures: { useBarCodeDetectorIfSupported: true }
        };
        
        // Preferir câmera traseira
        html5QrcodeScanner.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure)
        .catch(err => {
            console.error("Erro ao iniciar scanner:", err);
            alert("Erro ao acessar a câmera. Verifique as permissões. " + err);
            scannerModal.hide();
        });
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
            }).catch(err => console.error("Erro ao parar scanner:", err));
        }
    });

    function onScanSuccess(decodedText, decodedResult) {
        // Tocar som
        playBeep();
        
        scannerModal.hide();
        
        if (window.toastr) toastr.info('Código lido: ' + decodedText + '. Buscando...');

        // Disparar busca no Select2
        // A lógica do Select2 já busca o termo digitado.
        // Vamos simular uma busca abrindo o select2 e definindo o termo de busca.
        
        const $select = $('#medicamento_id');
        $select.select2('open'); // Abrir
        
        // Buscar o campo de input do Select2 que acabou de abrir
        const $search = $('.select2-search__field').last();
        $search.val(decodedText);
        $search.trigger('input'); // Disparar input para iniciar busca AJAX
        $search.trigger('keyup'); // Garantir

    }

    function onScanFailure(error) {
        // console.warn(`Code scan error = ${error}`);
    }
});
</script>
