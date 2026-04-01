<?php
require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Estoque';</script>

<div class="page-title">
    <h1><i class="bi bi-box-seam"></i> Gestão de Estoque</h1>
</div>

<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link" href="entrada.php">
                    <i class="bi bi-plus-circle me-1"></i> Registrar Estoque
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="listar.php">
                    <i class="bi bi-box-seam me-1"></i> Estoque Atual
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Cards de estatísticas -->
<div class="row g-4 mb-4">
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-primary" id="total-medicamentos">0</h2>
                <small class="text-muted">Medicamentos</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-success" id="total-itens">0</h2>
                <small class="text-muted">Itens em Estoque</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-info" id="total-lotes">0</h2>
                <small class="text-muted">Lotes Ativos</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-danger" id="total-criticos">0</h2>
                <small class="text-muted">Críticos</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body text-center">
                <h2 class="mb-0 text-warning" id="vencendo-30">0</h2>
                <small class="text-muted">Vencendo 30d</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h2 class="mb-0 text-danger" id="total-vencidos">0</h2>
                <small class="text-muted">Vencidos</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Estoque por Medicamento</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="filtro-criticos">
                    <label class="form-check-label" for="filtro-criticos">Apenas críticos</label>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="estoqueTable" class="table table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Medicamento</th>
                                <th>Apresentação</th>
                                <th>Lotes</th>
                                <th>Estoque</th>
                                <th>Mínimo</th>
                                <th>Status</th>
                                <th>Próx. Venc.</th>
                                <th width="80">Ações</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <!-- Próximos a vencer -->
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-calendar-x text-warning"></i> Próximos a Vencer</h5>
            </div>
            <div class="card-body" id="lista-vencimento" style="max-height: 400px; overflow-y: auto;"></div>
        </div>
    </div>
    
    <div class="col-md-6">
        <!-- Estoque Crítico -->
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle text-danger"></i> Estoque Crítico</h5>
            </div>
            <div class="card-body" id="lista-criticos" style="max-height: 400px; overflow-y: auto;"></div>
        </div>
    </div>
</div>
</div>

<!-- Modal de Lotes -->
<div class="modal fade" id="modalLotes" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-boxes"></i> <span id="modal-medicamento-nome"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-lotes-content">
                <!-- Conteúdo dinâmico -->
            </div>
        </div>
    </div>
</div>


<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // Carregar estatísticas
    $.getJSON('estoque_api.php?action=estatisticas', function(data) {
        $('#total-medicamentos').text(data.total_medicamentos);
        $('#total-itens').text(data.total_itens);
        $('#total-lotes').text(data.total_lotes);
        $('#total-vencidos').text(data.vencidos);
        $('#vencendo-30').text(data.vencendo_30_dias);
        $('#total-criticos').text(data.criticos);
    });
    
    // Carregar próximos a vencer
    $.getJSON('estoque_api.php?action=proximos_vencimento&dias=30', function(data) {
        let html = '';
        if (data.length === 0) {
            html = '<p class="text-muted text-center mb-0">Nenhum item próximo ao vencimento</p>';
        } else {
            data.forEach(item => {
                const validade = new Date(item.data_validade + 'T00:00:00').toLocaleDateString('pt-BR');
                const badgeClass = item.dias_para_vencer < 0 ? 'bg-danger' : 
                                   item.dias_para_vencer <= 7 ? 'bg-warning' : 'bg-info';
                const texto = item.dias_para_vencer < 0 ? 'Vencido' : `${item.dias_para_vencer}d`;
                html += `
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <strong>${item.medicamento_nome}</strong><br>
                            <small class="text-muted">Lote: ${item.lote} | ${item.quantidade_atual} un.</small>
                        </div>
                        <span class="badge ${badgeClass}">${texto}</span>
                    </div>
                `;
            });
        }
        $('#lista-vencimento').html(html);
    });
    
    // Carregar críticos
    $.getJSON('estoque_api.php?action=criticos', function(data) {
        let html = '';
        if (data.length === 0) {
            html = '<p class="text-muted text-center mb-0">Nenhum item em estoque crítico</p>';
        } else {
            data.forEach(item => {
                html += `
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <strong>${item.nome}</strong><br>
                            <small class="text-muted">Mín: ${item.estoque_minimo}</small>
                        </div>
                        <span class="badge bg-danger">${item.quantidade_atual} un.</span>
                    </div>
                `;
            });
        }
        $('#lista-criticos').html(html);
    });
    
    const table = $('#estoqueTable').DataTable({
        ajax: {
            url: 'estoque_api.php?action=list',
            dataSrc: 'data'
        },
        columns: [
            { 
                data: 'nome',
                render: (data, type, row) => {
                    let badges = '';
                    if (row.controlado == 1) badges += ' <span class="badge bg-warning" title="Controlado">C</span>';
                    if (row.refrigerado == 1) badges += ' <span class="badge bg-info" title="Refrigerado">❄</span>';
                    return `<strong>${data}</strong>${badges}`;
                }
            },
            { 
                data: null,
                render: (data, type, row) => {
                    let info = row.dosagem_concentracao || '';
                    if (row.unidade_medida) {
                        info += (row.unidade_medida === 'PORCENTAGEM' ? '%' : ' ' + row.unidade_medida);
                    }
                    if (row.forma_farmaceutica) {
                        info += (info ? ' - ' : '') + row.forma_farmaceutica.charAt(0).toUpperCase() + row.forma_farmaceutica.slice(1);
                    }
                    return info || '-';
                }
            },
            { 
                data: 'total_lotes',
                render: data => `<span class="badge bg-secondary">${data}</span>`
            },
            { 
                data: 'quantidade_total',
                render: (data, type, row) => {
                    const critical = parseInt(data) <= parseInt(row.estoque_minimo);
                    const badge = critical ? 'bg-danger' : 'bg-success';
                    return `<span class="badge ${badge}">${data}</span>`;
                }
            },
            { data: 'estoque_minimo' },
            { 
                data: null,
                render: (data, type, row) => {
                    const qtd = parseInt(row.quantidade_total);
                    const min = parseInt(row.estoque_minimo);
                    if (qtd === 0) return '<span class="badge bg-dark">Sem Estoque</span>';
                    if (qtd <= min) return '<span class="badge bg-danger">Crítico</span>';
                    if (qtd <= min * 1.5) return '<span class="badge bg-warning">Baixo</span>';
                    return '<span class="badge bg-success">Normal</span>';
                }
            },
            { 
                data: 'proxima_validade',
                render: data => data ? new Date(data + 'T00:00:00').toLocaleDateString('pt-BR') : '-'
            },
            {
                data: null,
                orderable: false,
                render: (data, type, row) => `
                    <div class="btn-group-actions">
                        <button onclick="verLotes(${row.medicamento_id}, '${row.nome}')" class="btn btn-sm btn-info" title="Ver Lotes">
                            <i class="bi bi-boxes"></i>
                        </button>
                    </div>
                `
            }
        ],
        order: [[0, 'asc']]
    });
    
    // Filtro de críticos
    $('#filtro-criticos').on('change', function() {
        if (this.checked) {
            table.columns(5).search('Crítico|Sem Estoque', true, false).draw();
        } else {
            table.columns(5).search('').draw();
        }
    });
});

function verLotes(medicamento_id, nome) {
    $('#modal-medicamento-nome').text(nome);
    $.getJSON(`estoque_api.php?action=get_lotes&medicamento_id=${medicamento_id}`, function(lotes) {
        let html = '';
        if (lotes.length === 0) {
            html = '<p class="text-muted text-center">Nenhum lote disponível</p>';
        } else {
            html = `
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Lote</th>
                            <th>Fabricação</th>
                            <th>Validade</th>
                            <th>Quantidade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            lotes.forEach(lote => {
                const validade = new Date(lote.data_validade + 'T00:00:00');
                const hoje = new Date();
                const diasVencer = Math.ceil((validade - hoje) / (1000 * 60 * 60 * 24));
                
                let statusClass = 'success';
                let statusText = 'OK';
                if (diasVencer < 0) { statusClass = 'danger'; statusText = 'Vencido'; }
                else if (diasVencer <= 30) { statusClass = 'warning'; statusText = `${diasVencer}d`; }
                
                html += `
                    <tr>
                        <td><strong>${lote.lote}</strong></td>
                        <td>${lote.data_fabricacao ? new Date(lote.data_fabricacao + 'T00:00:00').toLocaleDateString('pt-BR') : '-'}</td>
                        <td>${validade.toLocaleDateString('pt-BR')}</td>
                        <td><span class="badge bg-info">${lote.quantidade_atual}</span></td>
                        <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
        }
        $('#modal-lotes-content').html(html);
        new bootstrap.Modal('#modalLotes').show();
    });
}
</script>
