<?php
require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Relatório de Doações';</script>

<div class="page-title">
    <h1><i class="bi bi-graph-up"></i> Relatório de Doações</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Relatório de Doações</li>
        </ol>
    </nav>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form id="formFiltros" class="row g-3 align-items-end">
            <div class="col-md-2">
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
            <div class="col-md-3">
                <label class="form-label">Doador</label>
                <select class="form-select" id="doador_id" name="doador_id">
                    <option value="">Todos</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-secondary w-100" onclick="exportarPDF()">
                    <i class="bi bi-file-pdf"></i> Exportar PDF
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Cards de resumo -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-primary" id="total-doacoes">0</h2>
                <small class="text-muted">Total de Doações</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-primary" id="total-itens">0</h2>
                <small class="text-muted">Itens Recebidos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-info" id="total-doadores">0</h2>
                <small class="text-muted">Doadores Ativos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-warning" id="media-mensal">0</h2>
                <small class="text-muted">Média Mensal</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Gráfico de evolução -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Evolução das Doações</h5>
            </div>
            <div class="card-body">
                <canvas id="chartEvolucao" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top doadores -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-trophy"></i> Top Doadores</h5>
            </div>
            <div class="card-body" id="topDoadores"></div>
        </div>
    </div>
</div>

<!-- Tabela de doações -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-table"></i> Detalhamento das Doações</h5>
    </div>
    <div class="card-body">
        <table id="doacoesTable" class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Doador</th>
                    <th>Total Itens</th>
                    <th>Medicamentos</th>
                    <th>Registrado por</th>
                    <th>Ações</th>
                </tr>
            </thead>
        </table>
    </div>
</div>


<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>
let chartEvolucao;

$(document).ready(function() {
    // Toggle datas personalizadas
    $('#periodo').change(function() {
        if ($(this).val() === 'custom') {
            $('#dataInicioWrapper, #dataFimWrapper').slideDown();
        } else {
            $('#dataInicioWrapper, #dataFimWrapper').slideUp();
        }
    });
    
    // Inicializar Select2 para doador
    $('#doador_id').select2({
        theme: 'bootstrap-5',
        ajax: {
            url: '../doadores/doadores_api.php',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { action: 'search', q: params.term }; },
            processResults: function(data) {
                return {
                    results: data.map(item => ({ id: item.id, text: item.nome_completo || item.nome }))
                };
            }
        },
        minimumInputLength: 2,
        placeholder: 'Todos doadores',
        allowClear: true
    });
    
    // Inicializar DataTable
    initDataTable();
    
    // Carregar dados (apenas gráficos e resumo, pois a tabela carrega sozinha no init)
    carregarDados(false);
    
    // Submit do formulário
    $('#formFiltros').submit(function(e) {
        e.preventDefault();
        carregarDados(true);
    });
});

function getParams() {
    return {
        periodo: $('#periodo').val(),
        data_inicio: $('#data_inicio').val(),
        data_fim: $('#data_fim').val(),
        doador_id: $('#doador_id').val() || ''
    };
}

function carregarDados(reloadTable = true) {
    const params = new URLSearchParams(getParams());
    
    // Carregar resumo
    $.getJSON('relatorios_api.php?action=resumo_doacoes&' + params.toString(), function(data) {
        $('#total-doacoes').text(data.total_doacoes || 0);
        $('#total-itens').text(data.total_itens || 0);
        $('#total-doadores').text(data.total_doadores || 0);
        $('#media-mensal').text(Math.round(data.media_mensal || 0));
    }).fail(function() {
        toastr.error('Erro ao carregar resumo de doações');
    });
    
    // Carregar gráfico
    $.getJSON('relatorios_api.php?action=grafico_doacoes&' + params.toString(), function(data) {
        renderizarGrafico(data);
    }).fail(function() {
        $('#chartEvolucao').parent().html('<p class="text-danger text-center">Falha ao carregar gráfico</p>');
    });
    
    // Carregar top doadores
    $.getJSON('relatorios_api.php?action=top_doadores&' + params.toString(), function(data) {
        renderizarTopDoadores(data);
    }).fail(function() {
        $('#topDoadores').html('<p class="text-danger text-center">Falha ao carregar top doadores</p>');
    });
    
    // Recarregar tabela se solicitado
    if (reloadTable && $.fn.DataTable.isDataTable('#doacoesTable')) {
        $('#doacoesTable').DataTable().ajax.reload();
    }
}

function initDataTable() {
    $('#doacoesTable').DataTable({
        ajax: {
            url: 'relatorios_api.php',
            dataSrc: 'data',
            data: function(d) {
                d.action = 'listar_doacoes';
                Object.assign(d, getParams());
            }
        },
        columns: [
            { data: 'id' },
            { 
                data: 'data_doacao',
                render: data => {
                    const dt = new Date(data);
                    return dt.toLocaleDateString('pt-BR');
                }
            },
            { data: 'doador_nome' },
            { data: 'total_itens' },
            { 
                data: 'medicamentos',
                render: data => data ? data.substring(0, 50) + (data.length > 50 ? '...' : '') : '-'
            },
            { data: 'usuario_nome' },
            { 
                data: 'id',
                render: id => `
                    <a href="../doacoes/visualizar.php?id=${id}" class="btn btn-sm btn-outline-primary" title="Visualizar">
                        <i class="bi bi-eye"></i>
                    </a>
                `
            }
        ],
        order: [[1, 'desc']]
    });
}

function renderizarGrafico(data) {
    const ctx = document.getElementById('chartEvolucao').getContext('2d');
    
    if (chartEvolucao) {
        chartEvolucao.destroy();
    }
    
    chartEvolucao = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Doações',
                data: data.valores || [],
                backgroundColor: 'rgba(67, 97, 238, 0.7)',
                borderColor: 'rgba(67, 97, 238, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
}

function renderizarTopDoadores(data) {
    if (!data || data.length === 0) {
        $('#topDoadores').html('<p class="text-muted text-center mb-0">Nenhum doador no período</p>');
        return;
    }
    
    let html = '';
    data.forEach((doador, index) => {
        const medalha = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `${index + 1}.`;
        html += `
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                <div>
                    <span class="me-2">${medalha}</span>
                    <strong>${doador.nome}</strong>
                </div>
                <span class="badge bg-primary">${doador.total_doacoes} doações</span>
            </div>
        `;
    });
    
    $('#topDoadores').html(html);
}

function exportarPDF() {
    const params = new URLSearchParams(getParams());
    params.append('action', 'exportar_doacoes_pdf');
    window.open('relatorios_api.php?' + params.toString(), '_blank');
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
