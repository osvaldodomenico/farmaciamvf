<?php
require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Movimentações de Estoque';</script>

<div class="page-title">
    <h1><i class="bi bi-arrow-left-right"></i> Movimentações de Estoque</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="listar.php">Estoque</a></li>
            <li class="breadcrumb-item active">Movimentações</li>
        </ol>
    </nav>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form id="formFiltros" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Período</label>
                <select class="form-select" id="periodo" name="periodo">
                    <option value="7">Últimos 7 dias</option>
                    <option value="30" selected>Últimos 30 dias</option>
                    <option value="90">Últimos 90 dias</option>
                    <option value="365">Último ano</option>
                    <option value="custom">Personalizado</option>
                </select>
            </div>
            <div class="col-md-2" id="dataInicioWrapper" style="display: none;">
                <label class="form-label">Data Início</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio">
            </div>
            <div class="col-md-2" id="dataFimWrapper" style="display: none;">
                <label class="form-label">Data Fim</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tipo</label>
                <select class="form-select" id="tipo" name="tipo">
                    <option value="">Todos</option>
                    <option value="entrada">Entradas</option>
                    <option value="saida">Saídas</option>
                    <option value="ajuste">Ajustes</option>
                    <option value="devolucao">Devoluções</option>
                    <option value="perda">Perdas</option>
                    <option value="vencimento">Vencimentos</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Medicamento</label>
                <select class="form-select" id="medicamento_id" name="medicamento_id">
                    <option value="">Todos</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cards de resumo -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <h3 class="mb-0 text-success" id="total-entradas">0</h3>
                <small class="text-muted">Entradas</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h3 class="mb-0 text-danger" id="total-saidas">0</h3>
                <small class="text-muted">Saídas</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h3 class="mb-0 text-warning" id="total-ajustes">0</h3>
                <small class="text-muted">Ajustes</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h3 class="mb-0 text-info" id="saldo">0</h3>
                <small class="text-muted">Saldo</small>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de movimentações -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Histórico de Movimentações</h5>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarExcel()">
            <i class="bi bi-file-earmark-excel"></i> Exportar
        </button>
    </div>
    <div class="card-body">
        <table id="movimentacoesTable" class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Medicamento</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Lote</th>
                    <th>Motivo</th>
                    <th>Usuário</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<script>
let dataTable;

$(document).ready(function() {
    // Toggle datas personalizadas
    $('#periodo').change(function() {
        if ($(this).val() === 'custom') {
            $('#dataInicioWrapper, #dataFimWrapper').slideDown();
        } else {
            $('#dataInicioWrapper, #dataFimWrapper').slideUp();
        }
    });
    
    // Inicializar Select2 para medicamento
    $('#medicamento_id').select2({
        theme: 'bootstrap-5',
        ajax: {
            url: '../medicamentos/medicamentos_api.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { action: 'search', q: params.term };
            },
            processResults: function(data) {
                return {
                    results: data.map(item => ({
                        id: item.id,
                        text: item.nome
                    }))
                };
            }
        },
        minimumInputLength: 2,
        placeholder: 'Todos medicamentos',
        allowClear: true
    });
    
    // Inicializar DataTable
    initDataTable();
    
    // Submit do formulário de filtros
    $('#formFiltros').submit(function(e) {
        e.preventDefault();
        dataTable.ajax.reload();
        carregarResumo();
    });
    
    // Carregar resumo inicial
    carregarResumo();
});

function initDataTable() {
    dataTable = $('#movimentacoesTable').DataTable({
        ajax: {
            url: 'estoque_api.php',
            dataSrc: 'data',
            data: function(d) {
                d.action = 'movimentacoes';
                d.periodo = $('#periodo').val();
                d.data_inicio = $('#data_inicio').val();
                d.data_fim = $('#data_fim').val();
                d.tipo = $('#tipo').val();
                d.medicamento_id = $('#medicamento_id').val();
            }
        },
        columns: [
            { 
                data: 'data_movimentacao',
                render: data => {
                    const dt = new Date(data);
                    return dt.toLocaleDateString('pt-BR') + ' ' + dt.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
                }
            },
            { 
                data: 'medicamento_nome',
                render: (data, type, row) => {
                    return `<strong>${data}</strong><br><small class="text-muted">${row.principio_ativo || ''}</small>`;
                }
            },
            { 
                data: 'tipo_movimentacao',
                render: data => {
                    const badges = {
                        'entrada': 'bg-success',
                        'saida': 'bg-danger',
                        'ajuste': 'bg-warning text-dark',
                        'devolucao': 'bg-info',
                        'perda': 'bg-secondary',
                        'vencimento': 'bg-dark',
                        'transferencia': 'bg-primary'
                    };
                    return `<span class="badge ${badges[data] || 'bg-secondary'}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                }
            },
            { 
                data: 'quantidade',
                render: (data, type, row) => {
                    const isPositive = ['entrada', 'devolucao', 'ajuste_positivo'].includes(row.tipo_movimentacao) || 
                                       (row.tipo_movimentacao === 'ajuste' && data > 0);
                    const prefix = isPositive ? '+' : '-';
                    const color = isPositive ? 'text-success' : 'text-danger';
                    return `<span class="${color} fw-bold">${prefix}${Math.abs(data)}</span>`;
                }
            },
            { data: 'lote', defaultContent: '-' },
            { data: 'motivo', defaultContent: '-' },
            { data: 'usuario_nome', defaultContent: 'Sistema' }
        ],
        order: [[0, 'desc']],
        responsive: true,
        pageLength: 25
    });
}

function carregarResumo() {
    const params = new URLSearchParams({
        action: 'resumo_movimentacoes',
        periodo: $('#periodo').val(),
        data_inicio: $('#data_inicio').val(),
        data_fim: $('#data_fim').val(),
        tipo: $('#tipo').val(),
        medicamento_id: $('#medicamento_id').val() || ''
    });
    
    $.getJSON('estoque_api.php?' + params.toString(), function(data) {
        $('#total-entradas').text(data.entradas || 0);
        $('#total-saidas').text(data.saidas || 0);
        $('#total-ajustes').text(data.ajustes || 0);
        
        const saldo = (data.entradas || 0) - (data.saidas || 0);
        $('#saldo').text(saldo >= 0 ? '+' + saldo : saldo)
            .removeClass('text-success text-danger')
            .addClass(saldo >= 0 ? 'text-success' : 'text-danger');
    });
}

function exportarExcel() {
    const params = new URLSearchParams({
        action: 'exportar_movimentacoes',
        periodo: $('#periodo').val(),
        data_inicio: $('#data_inicio').val(),
        data_fim: $('#data_fim').val(),
        tipo: $('#tipo').val(),
        medicamento_id: $('#medicamento_id').val() || ''
    });
    
    window.location.href = 'estoque_api.php?' + params.toString();
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
