<?php
require_once __DIR__ . '/../../templates/header.php';

$id = $_GET['id'] ?? 0;
// Fetch Donation
$tDoacoes = tableName('doacoes');
$tDoadores = tableName('doadores');
$tUsuarios = tableName('usuarios');
$doacao = fetchOne("
    SELECT 
        d.*,
        do.nome_completo as doador_nome,
        do.cpf_cnpj as doador_cpf_cnpj,
        do.tipo as doador_tipo,
        do.telefone as doador_telefone,
        u.nome_completo as usuario_nome
    FROM {$tDoacoes} d
    LEFT JOIN {$tDoadores} do ON d.doador_id = do.id
    LEFT JOIN {$tUsuarios} u ON d.usuario_id = u.id
    WHERE d.id = ?
", [$id]);

if (!$doacao) {
    $_SESSION['error'] = 'Doação não encontrada.';
    header('Location: listar.php');
    exit;
}

// Fetch Items
$tDoacoesItens = tableName('doacoes_itens');
$tMedicamentos = tableName('medicamentos');
$itens = fetchAll("
    SELECT 
        di.*,
        m.nome as medicamento_nome,
        m.principio_ativo,
        m.dosagem_concentracao,
        m.unidade_medida,
        m.forma_farmaceutica
    FROM {$tDoacoesItens} di
    LEFT JOIN {$tMedicamentos} m ON di.medicamento_id = m.id
    WHERE di.doacao_id = ?
", [$id]);
?>

<script>document.getElementById('page-title').textContent = 'Visualizar Doação';</script>

<div class="page-title">
    <h1><i class="bi bi-box-seam"></i> Detalhes da Doação</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="listar.php">Doações</a></li>
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
                    <label class="text-muted">Número da Doação</label>
                    <h5><?php echo htmlspecialchars($doacao['numero_doacao']); ?></h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Data de Recebimento</label>
                    <p class="mb-0"><?php echo date('d/m/Y H:i', strtotime($doacao['data_recebimento'])); ?></p>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Recebido por</label>
                    <p class="mb-0"><?php echo htmlspecialchars($doacao['usuario_nome']); ?></p>
                </div>
                <?php if ($doacao['valor_estimado'] > 0): ?>
                <div class="mb-3">
                    <label class="text-muted">Valor Estimado</label>
                    <p class="mb-0 text-success fw-bold">R$ <?php echo number_format($doacao['valor_estimado'], 2, ',', '.'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-heart"></i> Doador</h5>
            </div>
            <div class="card-body">
                <?php if ($doacao['doador_nome']): ?>
                    <h6><?php echo htmlspecialchars($doacao['doador_nome']); ?></h6>
                    <span class="badge bg-secondary mb-2">
                        <?php echo $doacao['doador_tipo'] === 'pessoa_juridica' ? 'Pessoa Jurídica' : 'Pessoa Física'; ?>
                    </span>
                    <?php if ($doacao['doador_cpf_cnpj']): ?>
                        <p class="text-muted mb-1">CPF/CNPJ: <?php echo $doacao['doador_cpf_cnpj']; ?></p>
                    <?php endif; ?>
                    <?php if ($doacao['doador_telefone']): ?>
                        <p class="mb-0"><i class="bi bi-telephone"></i> <?php echo $doacao['doador_telefone']; ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">Doador Anônimo / Não Identificado</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-capsule"></i> Itens da Doação</h5>
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
                                    <td><span class="badge bg-primary"><?php echo $item['quantidade']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php if ($doacao['observacoes']): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-chat-text"></i> Observações</h5>
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($doacao['observacoes'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <a href="listar.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
    <!-- Uncomment when print is ready
    <a href="imprimir.php?id=<?php echo $id; ?>" class="btn btn-outline-primary" target="_blank">
        <i class="bi bi-printer"></i> Imprimir
    </a>
    -->
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
