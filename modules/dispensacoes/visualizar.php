<?php
require_once __DIR__ . '/../../templates/header.php';

$id = $_GET['id'] ?? 0;
$tDispensacoes = tableName('dispensacoes');
$tClientes = tableName('clientes');
$tUsuarios = tableName('usuarios');
$dispensacao = fetchOne("
    SELECT 
        d.*,
        p.nome_completo as cliente_nome,
        p.cpf as cliente_cpf,
        p.telefone as cliente_telefone,
        u.nome_completo as usuario_nome
    FROM {$tDispensacoes} d
    LEFT JOIN {$tClientes} p ON d.cliente_id = p.id
    LEFT JOIN {$tUsuarios} u ON d.usuario_id = u.id
    WHERE d.id = ?
", [$id]);

if (!$dispensacao) {
    $_SESSION['error'] = 'Dispensação não encontrada.';
    header('Location: listar.php');
    exit;
}

// Buscar itens
$tDispensacoesItens = tableName('dispensacoes_itens');
$tMedicamentos = tableName('medicamentos');
$tEstoque = tableName('estoque');
$itens = fetchAll("
    SELECT 
        di.*,
        m.nome as medicamento_nome,
        m.principio_ativo,
        m.dosagem_concentracao,
        m.unidade_medida,
        m.forma_farmaceutica,
        e.lote,
        e.data_validade
    FROM {$tDispensacoesItens} di
    LEFT JOIN {$tMedicamentos} m ON di.medicamento_id = m.id
    LEFT JOIN {$tEstoque} e ON di.estoque_id = e.id
    WHERE di.dispensacao_id = ?
", [$id]);
?>

<script>document.getElementById('page-title').textContent = 'Visualizar Dispensação';</script>

<div class="page-title">
    <h1><i class="bi bi-clipboard2-pulse"></i> Detalhes da Dispensação</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="listar.php">Dispensações</a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informações</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted">Número</label>
                    <h5><?php echo $dispensacao['numero_dispensacao']; ?></h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Data/Hora</label>
                    <p class="mb-0"><?php echo date('d/m/Y H:i', strtotime($dispensacao['data_dispensacao'])); ?></p>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Atendente</label>
                    <p class="mb-0"><?php echo htmlspecialchars($dispensacao['usuario_nome']); ?></p>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Quantidade Total</label>
                    <p class="mb-0"><span class="badge bg-info fs-6"><?php echo $dispensacao['quantidade_total']; ?> unidades</span></p>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person"></i> Cliente</h5>
            </div>
            <div class="card-body">
                <?php if ($dispensacao['cliente_nome']): ?>
                    <h6><?php echo htmlspecialchars($dispensacao['cliente_nome']); ?></h6>
                    <?php if ($dispensacao['cliente_cpf']): ?>
                        <p class="text-muted mb-1">CPF: <?php echo $dispensacao['cliente_cpf']; ?></p>
                    <?php endif; ?>
                    <?php if ($dispensacao['cliente_telefone']): ?>
                        <p class="mb-0"><i class="bi bi-telephone"></i> <?php echo $dispensacao['cliente_telefone']; ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">Cliente não identificado</p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($dispensacao['receita_medica']): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-file-medical"></i> Receita Médica</h5>
            </div>
            <div class="card-body">
                <?php if ($dispensacao['numero_receita']): ?>
                    <p class="mb-1"><strong>Número:</strong> <?php echo htmlspecialchars($dispensacao['numero_receita']); ?></p>
                <?php endif; ?>
                <?php if ($dispensacao['medico_responsavel']): ?>
                    <p class="mb-0"><strong>Médico:</strong> <?php echo htmlspecialchars($dispensacao['medico_responsavel']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-capsule"></i> Itens Dispensados</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Medicamento</th>
                                <th>Apresentação</th>
                                <th>Lote</th>
                                <th>Validade</th>
                                <th>Qtd</th>
                                <th>Posologia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['medicamento_nome']); ?></strong>
                                        <?php if ($item['principio_ativo']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($item['principio_ativo']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $conc = $item['dosagem_concentracao'] ?? '';
                                            if ($item['unidade_medida']) {
                                                $conc .= ($item['unidade_medida'] === 'PORCENTAGEM' ? '%' : ' ' . $item['unidade_medida']);
                                            }
                                            echo htmlspecialchars($conc ?: '-');
                                        ?>
                                        <?php if ($item['forma_farmaceutica']): ?>
                                            <br><small><?php echo ucfirst($item['forma_farmaceutica']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['lote']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($item['data_validade'])); ?></td>
                                    <td><span class="badge bg-info"><?php echo $item['quantidade']; ?></span></td>
                                    <td><?php echo htmlspecialchars($item['posologia'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php if ($dispensacao['observacoes']): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-chat-text"></i> Observações</h5>
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($dispensacao['observacoes'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <a href="listar.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
    <a href="imprimir.php?id=<?php echo $id; ?>" class="btn btn-outline-primary" target="_blank">
        <i class="bi bi-printer"></i> Imprimir
    </a>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
