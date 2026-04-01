<?php
require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Entrada de Estoque';</script>

<div class="page-title">
    <h1><i class="bi bi-box-seam"></i> Gestão de Estoque</h1>
</div>

<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link active" href="entrada.php">
                    <i class="bi bi-plus-circle me-1"></i> Registrar Estoque
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="listar.php">
                    <i class="bi bi-box-seam me-1"></i> Estoque Atual
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nova Entrada de Estoque</h5>
            </div>
            <div class="card-body">
                <form id="formEntrada" novalidate>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Origem <span class="text-danger">*</span></label>
                            <select class="form-select" id="origem" name="origem" required>
                                <option value="">Selecione...</option>
                                <option value="doacao">Doação Recebida</option>
                                <option value="compra">Compra</option>
                                <option value="devolucao">Devolução</option>
                                <option value="ajuste">Ajuste de Inventário</option>
                                <option value="transferencia">Transferência</option>
                            </select>
                            <div class="invalid-feedback">Selecione a origem</div>
                        </div>
                    </div>
                    
                    <div class="row mb-4 align-items-end" id="row-med-qtd">
                        <div class="col-md-8">
                            <label class="form-label">Medicamento <span class="text-danger">*</span></label>
                            <select class="form-select" id="medicamento_id" name="medicamento_id" required>
                                <option value="">Selecione o medicamento...</option>
                            </select>
                            <div class="invalid-feedback">Selecione um medicamento</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quantidade <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantidade" name="quantidade" min="1" required>
                            <div class="invalid-feedback">Informe a quantidade</div>
                        </div>
                    </div>
                    
                    <!-- Campos para vincular doação -->
                    <div class="row mb-4" id="campos-doacao" style="display: none;">
                        <div class="col-md-12">
                            <label class="form-label">Vincular à Doação</label>
                            <select class="form-select" id="doacao_id" name="doacao_id">
                                <option value="">Selecione a doação (opcional)...</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Campos de transferência (saída) -->
                    <div class="row mb-4" id="campos-transferencia" style="display: none;">
                        <div class="col-md-6">
                            <label class="form-label">Lote para saída <span class="text-danger">*</span></label>
                            <select class="form-select" id="estoque_id" name="estoque_id">
                                <option value="">Selecione o lote...</option>
                            </select>
                            <div class="invalid-feedback">Selecione o lote</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Destino <span class="text-danger">*</span></label>
                            <select class="form-select" id="destino_tipo" name="destino_tipo">
                                <option value="">Selecione...</option>
                                <option value="doador">Doador</option>
                                <option value="instituicao">Instituição</option>
                                <option value="outro">Outro</option>
                            </select>
                            <div class="invalid-feedback">Informe o destino</div>
                        </div>
                        <div class="col-md-12 mt-3" id="destino-doador-wrapper" style="display: none;">
                            <label class="form-label">Selecionar doador</label>
                            <select class="form-select" id="destino_id" name="destino_id"></select>
                        </div>
                        <div class="col-md-12 mt-3" id="destino-nome-wrapper" style="display: none;">
                            <label class="form-label">Nome do destino</label>
                            <input type="text" class="form-control" id="destino_nome" name="destino_nome" placeholder="Ex: ONG X, Hospital Y">
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Validade <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="data_validade" name="data_validade" required>
                            <div class="invalid-feedback">Informe a data de validade</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data de Fabricação</label>
                            <input type="date" class="form-control" id="data_fabricacao" name="data_fabricacao">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Localização</label>
                            <input type="text" class="form-control" id="localizacao" name="localizacao" placeholder="Ex: Prateleira A3">
                        </div>
                    </div>
                    
                    
                    
                    <div class="mb-4">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Registrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="limparFormulario()">
                            <i class="bi bi-eraser"></i> Limpar
                        </button>
                        <a href="listar.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Info do medicamento selecionado -->
        <div class="card mb-4" id="cardMedicamento" style="display: none;">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-capsule"></i> Medicamento Selecionado</h5>
            </div>
            <div class="card-body">
                <h5 id="med-nome" class="mb-1"></h5>
                <p id="med-principio" class="text-muted mb-3"></p>
                
                <div class="d-flex justify-content-between mb-2">
                    <span>Estoque Atual:</span>
                    <strong id="med-estoque">0</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Estoque Mínimo:</span>
                    <span id="med-minimo">0</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Lotes Ativos:</span>
                    <span id="med-lotes">0</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Próx. Vencimento:</span>
                    <span id="med-validade" class="badge bg-secondary">-</span>
                </div>
            </div>
        </div>
        
        <!-- Últimas entradas -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Últimas Entradas</h5>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <div id="ultimas-entradas"></div>
            </div>
        </div>
    </div>
</div>


<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // Inicializar Select2 para medicamento
    $('#medicamento_id').select2({
        theme: 'bootstrap-5',
        ajax: {
            url: '../medicamentos/medicamentos_api.php',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    action: 'search',
                    q: params.term
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
                            text: item.nome + (conc ? ' - ' + conc : '') + (item.principio_ativo ? ' (' + item.principio_ativo + ')' : ''),
                            data: item
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 2,
        placeholder: 'Digite para buscar...',
        language: {
            inputTooShort: function() { return 'Digite ao menos 2 caracteres...'; },
            noResults: function() { return 'Nenhum medicamento encontrado'; },
            searching: function() { return 'Buscando...'; }
        }
    }).on('select2:select', function(e) {
        exibirInfoMedicamento(e.params.data.data);
    });
    
    // Mostrar/ocultar campos conforme origem
    const $rowMedQtd = $('#row-med-qtd');
    const $origemRow = $('#origem').closest('.row');
    $('#origem').change(function() {
        const v = $(this).val();
        if (v === 'doacao') {
            $('#campos-doacao').slideDown();
            carregarDoacoes();
            $rowMedQtd.insertAfter($('#campos-doacao'));
        } else {
            $('#campos-doacao').slideUp();
            $rowMedQtd.insertAfter($origemRow);
        }
        if (v === 'transferencia') {
            $('#campos-transferencia').slideDown();
            $('#data_validade').closest('.col-md-4').hide();
            $('#data_fabricacao').closest('.col-md-4').hide();
            $('#localizacao').closest('.col-md-4').hide();
            $('#data_validade').prop('required', false);
            $('#estoque_id').prop('required', true);
            $('#destino_tipo').prop('required', true);
            carregarLotes();
            $rowMedQtd.insertAfter($('#campos-transferencia'));
        } else {
            $('#campos-transferencia').slideUp();
            $('#data_validade').closest('.col-md-4').show();
            $('#data_fabricacao').closest('.col-md-4').show();
            $('#localizacao').closest('.col-md-4').show();
            $('#data_validade').prop('required', true);
            $('#estoque_id').prop('required', false);
            $('#destino_tipo').prop('required', false);
            if (v !== 'doacao') $rowMedQtd.insertAfter($origemRow);
        }
    });
    
    $('#destino_tipo').change(function() {
        const tipo = $(this).val();
        if (tipo === 'doador') {
            $('#destino-doador-wrapper').slideDown();
            $('#destino-nome-wrapper').slideUp();
            initSelectDoador();
        } else if (tipo) {
            $('#destino-doador-wrapper').slideUp();
            $('#destino-nome-wrapper').slideDown();
        } else {
            $('#destino-doador-wrapper').slideUp();
            $('#destino-nome-wrapper').slideUp();
        }
    });
    
    // Carregar últimas entradas
    carregarUltimasEntradas();
    
    // Submit do formulário
    $('#formEntrada').submit(function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            $(this).addClass('was-validated');
            return;
        }
        
        const formData = new FormData(this);
        const origem = $('#origem').val();
        formData.append('action', origem === 'transferencia' ? 'transferencia' : 'entrada');
        
        $.ajax({
            url: 'estoque_api.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(origem === 'transferencia' ? 'Transferência registrada com sucesso!' : 'Entrada registrada com sucesso!');
                    limparFormulario();
                    carregarUltimasEntradas();
                    // Atualizar info do medicamento
                    if ($('#medicamento_id').val()) {
                        const medId = $('#medicamento_id').val();
                        buscarInfoMedicamento(medId);
                    }
                } else {
                    toastr.error(response.error || 'Erro ao registrar entrada');
                }
            },
            error: function() {
                toastr.error('Erro ao comunicar com o servidor');
            }
        });
    });
});

function exibirInfoMedicamento(med) {
    $('#med-nome').text(med.nome);
    $('#med-principio').text(med.principio_ativo || '-');
    $('#med-minimo').text(med.estoque_minimo || 0);
    $('#cardMedicamento').slideDown();
    
    // Buscar estoque atual
    buscarInfoMedicamento(med.id);
    if ($('#origem').val() === 'transferencia') carregarLotes();
}

function buscarInfoMedicamento(medicamentoId) {
    $.getJSON('estoque_api.php?action=info_medicamento&medicamento_id=' + medicamentoId, function(data) {
        $('#med-estoque').text(data.quantidade_total || 0);
        $('#med-lotes').text(data.total_lotes || 0);
        
        if (data.proxima_validade) {
            const dataVal = new Date(data.proxima_validade);
            const hoje = new Date();
            const diffDias = Math.ceil((dataVal - hoje) / (1000 * 60 * 60 * 24));
            
            let badgeClass = 'bg-success';
            if (diffDias < 0) badgeClass = 'bg-danger';
            else if (diffDias <= 30) badgeClass = 'bg-warning text-dark';
            else if (diffDias <= 90) badgeClass = 'bg-info';
            
            $('#med-validade').removeClass().addClass('badge ' + badgeClass)
                .text(dataVal.toLocaleDateString('pt-BR'));
        } else {
            $('#med-validade').removeClass().addClass('badge bg-secondary').text('-');
        }
    });
}

function carregarLotes() {
    const medId = $('#medicamento_id').val();
    if (!medId) return;
    $.getJSON('estoque_api.php?action=get_lotes&medicamento_id=' + medId, function(lotes) {
        let options = '<option value="">Selecione o lote...</option>';
        lotes.forEach(function(l) {
            const val = l.lote || '-';
            options += `<option value="${l.id}">Lote: ${val} | Val.: ${l.data_validade} | Qtde: ${l.quantidade_atual}</option>`;
        });
        $('#estoque_id').html(options);
    });
}

function carregarDoacoes() {
    $.getJSON('../doacoes/doacoes_api.php?action=listar_pendentes', function(data) {
        let options = '<option value="">Selecione a doação (opcional)...</option>';
        data.forEach(function(d) {
            const dataFmt = new Date(d.data_doacao).toLocaleDateString('pt-BR');
            options += `<option value="${d.id}">${d.numero_doacao || d.id} - ${d.doador_nome || 'Anônimo'} (${dataFmt})</option>`;
        });
        $('#doacao_id').html(options);
    });
}

function carregarUltimasEntradas() {
    $.getJSON('estoque_api.php?action=ultimas_entradas', function(data) {
        if (data.length === 0) {
            $('#ultimas-entradas').html('<p class="text-muted text-center mb-0">Nenhuma entrada recente</p>');
            return;
        }
        
        let html = '';
        data.forEach(function(entrada) {
            html += `
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                    <div>
                        <strong class="d-block">${entrada.medicamento_nome}</strong>
                        <small class="text-muted">Lote: ${entrada.lote}</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-success">+${entrada.quantidade}</span>
                        <small class="d-block text-muted">${entrada.data_formatada}</small>
                    </div>
                </div>
            `;
        });
        $('#ultimas-entradas').html(html);
    });
}

function initSelectDoador() {
    $('#destino_id').select2({
        theme: 'bootstrap-5',
        ajax: {
            url: '../doadores/doadores_api.php?action=search',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { termo: params.term }; },
            processResults: function(data) {
                return {
                    results: data.map(function(d) {
                        return { id: d.id, text: d.nome_completo + (d.cpf_cnpj ? ' (' + d.cpf_cnpj + ')' : '') };
                    })
                };
            }
        },
        minimumInputLength: 2,
        placeholder: 'Digite o nome do doador...',
        language: {
            inputTooShort: function() { return 'Digite ao menos 2 caracteres...'; },
            noResults: function() { return 'Nenhum doador encontrado'; },
            searching: function() { return 'Buscando...'; }
        }
    });
}

function limparFormulario() {
    $('#formEntrada')[0].reset();
    $('#formEntrada').removeClass('was-validated');
    $('#medicamento_id').val(null).trigger('change');
    $('#cardMedicamento').slideUp();
    $('#campos-doacao').slideUp();
    $('#campos-transferencia').slideUp();
}
</script>
