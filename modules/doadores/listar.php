<?php
require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Doadores';</script>

<div class="page-title">
    <h1><i class="bi bi-heart"></i> Gerenciar Doadores</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Doadores</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Lista de Doadores</h5>
        <a href="adicionar.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Novo Doador
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="doadoresTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Nome/Razão Social</th>
                        <th>Tipo</th>
                        <th>Celular</th>
                        <th>Cidade</th>
                        <th>Total Doações</th>
                        <th>Última Doação</th>
                        <th width="120">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Confirmação Bootstrap -->
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmActionModalLabel"><i class="bi bi-exclamation-triangle text-danger"></i> Confirmar Ação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirmActionModalBody">
                Deseja realmente realizar esta ação?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a id="btnConfirmAction" href="#" class="btn btn-danger">Confirmar</a>
            </div>
        </div>
    </div>
</div>


<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<script>
function showConfirmModal(url, message) {
    const modal = new bootstrap.Modal(document.getElementById('confirmActionModal'));
    document.getElementById('confirmActionModalBody').textContent = message;
    document.getElementById('btnConfirmAction').href = url;
    modal.show();
}

$(document).ready(function() {
    const table = $('#doadoresTable').DataTable({
        ajax: {
            url: 'doadores_api.php?action=list',
            dataSrc: 'data'
        },
        columns: [
            { data: 'nome_completo' },
            { 
                data: 'tipo',
                render: data => data === 'pessoa_juridica' ? 
                    '<span class="badge bg-primary">Pessoa Jurídica</span>' : 
                    '<span class="badge bg-info">Pessoa Física</span>'
            },
            { 
                data: null,
                render: (data, type, row) => row.celular || row.telefone || '-'
            },
            { 
                data: 'cidade',
                render: data => data || '-'
            },
            { 
                data: 'total_doacoes',
                render: data => `<span class="badge bg-success">${data}</span>`
            },
            { 
                data: 'ultima_doacao',
                render: data => data ? new Date(data).toLocaleDateString('pt-BR') : '-'
            },
            {
                data: null,
                orderable: false,
                render: (data, type, row) => `
                    <div class="btn-group-actions">
                        <a href="visualizar.php?id=${row.id}" class="btn btn-sm btn-info" title="Visualizar">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="editar.php?id=${row.id}" class="btn btn-sm btn-primary" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="javascript:void(0)" class="btn btn-sm btn-danger" 
                           onclick="showConfirmModal('deletar.php?id=${row.id}', 'Deseja realmente desativar este doador?')" title="Desativar">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                `
            }
        ],
        order: [[0, 'asc']]
    });
});
</script>
