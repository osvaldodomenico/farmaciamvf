<?php
require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Doações';</script>

<div class="page-title">
    <h1><i class="bi bi-box-arrow-in-down"></i> Doações Recebidas</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Doações</li>
        </ol>
    </nav>
</div>

<!-- Cards de estatísticas -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Doações do Mês</h6>
                        <h2 class="mb-0" id="doacoes-mes">0</h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-box-arrow-in-down text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Itens Recebidos</h6>
                        <h2 class="mb-0" id="itens-mes">0</h2>
                    </div>
                    <div class="bg-info bg-opacity-10 rounded-circle p-3">
                        <i class="bi bi-capsule text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Lista de Doações</h5>
        <a href="nova.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nova Doação
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="doacoesTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Data</th>
                        <th>Doador</th>
                        <th>Tipo</th>
                        <th>Itens</th>
                        <th>Quantidade</th>
                        <th>Recebido por</th>
                        <th width="100">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Detalhes -->
<div class="modal fade" id="modalDetalhes" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-box-arrow-in-down"></i> Detalhes da Doação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalhes-content">
                <!-- Conteúdo dinâmico -->
            </div>
        </div>
    </div>
</div>


<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // Carregar estatísticas
    $.getJSON('doacoes_api.php?action=estatisticas', function(data) {
        $('#doacoes-mes').text(data.doacoes_mes);
        $('#itens-mes').text(data.itens_recebidos_mes);
    });
    
    const table = $('#doacoesTable').DataTable({
        ajax: {
            url: 'doacoes_api.php?action=list',
            dataSrc: function(json) {
                if (!json.data) {
                    console.error('API Response missing data:', json);
                    return [];
                }
                return json.data;
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables Error:', error, thrown);
                // alert('Erro ao carregar doações. Verifique o console.');
            }
        },
        pageLength: 10,
        columns: [
            { 
                data: 'numero_doacao',
                render: data => `<strong>${data || '-'}</strong>`
            },
            { 
                data: 'data_recebimento',
                render: data => data ? new Date(data).toLocaleString('pt-BR') : '-'
            },
            { 
                data: 'doador_nome',
                render: data => data || '<em class="text-muted">Anônimo</em>'
            },
            { 
                data: 'doador_tipo',
                render: data => {
                    if (!data) return '-';
                    return data === 'pessoa_juridica' ? 
                        '<span class="badge bg-primary">PJ</span>' : 
                        '<span class="badge bg-info">PF</span>';
                }
            },
            { 
                data: 'total_itens',
                render: data => `<span class="badge bg-secondary">${data || 0}</span>`
            },
                { 
                    data: 'quantidade_total',
                    render: data => `<span class="badge bg-success badge-quantidade">${data || 0} un.</span>`
                },
            { data: 'usuario_nome' },
            {
                data: null,
                orderable: false,
                render: (data, type, row) => `
                    <div class="btn-group-actions">
                        <button onclick="verDetalhes(${row.id})" class="btn btn-sm btn-info" title="Ver Detalhes">
                            <i class="bi bi-eye"></i>
                        </button>
                        <a href="imprimir.php?id=${row.id}" class="btn btn-sm btn-secondary" title="Imprimir" target="_blank">
                            <i class="bi bi-printer"></i>
                        </a>
                    </div>
                `
            }
        ],
        language: {
            emptyTable: 'Nenhuma doação encontrada'
        },
        order: [[1, 'desc']]
    });
});

function verDetalhes(id) {
    $.getJSON(`doacoes_api.php?action=get&id=${id}`, function(doacao) {
        let html = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Número:</strong> ${doacao.numero_doacao}<br>
                    <strong>Data:</strong> ${new Date(doacao.data_recebimento).toLocaleString('pt-BR')}<br>
                    <strong>Doador:</strong> ${doacao.doador_nome || 'Anônimo'}
                </div>
                <div class="col-md-6">
                    <strong>Recebido por:</strong> ${doacao.usuario_nome}<br>
                    <strong>Observações:</strong> ${doacao.observacoes || '-'}
                </div>
            </div>
            <hr>
            <h6><i class="bi bi-capsule"></i> Itens Recebidos</h6>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Lote</th>
                        <th>Validade</th>
                        <th>Qtd</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        doacao.itens.forEach(item => {
            html += `
                <tr>
                    <td>${item.medicamento_nome} ${item.principio_ativo ? `(${item.principio_ativo})` : ''}</td>
                    <td>${item.lote}</td>
                    <td>${new Date(item.data_validade).toLocaleDateString('pt-BR')}</td>
                    <td><span class="badge bg-success">${item.quantidade}</span></td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        
        $('#detalhes-content').html(html);
        new bootstrap.Modal(document.getElementById('modalDetalhes')).show();
    });
}
</script>
