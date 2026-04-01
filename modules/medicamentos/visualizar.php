<?php
require_once __DIR__ . '/../../templates/header.php';

$id = $_GET['id'] ?? 0;
$medicamento = fetchOne("
    SELECT m.*, c.nome as categoria_nome
    FROM " . tableName('medicamentos') . " m
    LEFT JOIN " . tableName('categorias_medicamentos') . " c ON m.categoria_id = c.id
    WHERE m.id = ?
", [$id]);

if (!$medicamento) {
    $_SESSION['error'] = 'Medicamento não encontrado.';
    header('Location: listar.php');
    exit;
}

// Buscar estoque do medicamento
$lotes = fetchAll("
    SELECT * FROM " . tableName('estoque') . "
    WHERE medicamento_id = ? AND quantidade_atual > 0
    ORDER BY data_validade ASC
", [$id]);

$estoque_total = array_sum(array_column($lotes, 'quantidade_atual'));

// Últimas dispensações deste medicamento
$dispensacoes = fetchAll("
    SELECT 
        di.*,
        d.numero_dispensacao,
        d.data_dispensacao,
        p.nome_completo as cliente
    FROM " . tableName('dispensacoes_itens') . " di
    INNER JOIN " . tableName('dispensacoes') . " d ON di.dispensacao_id = d.id
    LEFT JOIN " . tableName('clientes') . " p ON d.cliente_id = p.id
    WHERE di.medicamento_id = ?
    ORDER BY d.data_dispensacao DESC
    LIMIT 10
", [$id]);
?>

<script>document.getElementById('page-title').textContent = 'Visualizar Medicamento';</script>

<div class="page-title">
    <h1><i class="bi bi-capsule"></i> Detalhes do Medicamento</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="listar.php">Medicamentos</a></li>
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
            <div class="card-body text-center">
                <div class="bg-primary bg-opacity-10 rounded-circle p-4 d-inline-block mb-3">
                    <i class="bi bi-capsule text-primary" style="font-size: 4rem;"></i>
                </div>
                <h4><?php echo htmlspecialchars($medicamento['nome']); ?></h4>
                <?php if ($medicamento['principio_ativo']): ?>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($medicamento['principio_ativo']); ?></p>
                <?php endif; ?>
                <?php if ($medicamento['dosagem_concentracao']): ?>
                    <p class="mb-2"><span class="badge bg-secondary"><?php echo htmlspecialchars($medicamento['dosagem_concentracao']); ?></span></p>
                <?php endif; ?>
                
                <div class="d-flex justify-content-center gap-2 flex-wrap mb-3">
                    <?php if ($medicamento['controlado']): ?>
                        <span class="badge bg-warning"><i class="bi bi-shield-exclamation"></i> Controlado</span>
                    <?php endif; ?>
                    <?php if (($medicamento['temperatura_armazenamento'] ?? '') === 'refrigerado'): ?>
                        <span class="badge bg-info"><i class="bi bi-thermometer-snow"></i> Refrigerado</span>
                    <?php endif; ?>
                </div>
                
                <hr>
                
                <div class="row text-start">
                    <div class="col-6 mb-2">
                        <small class="text-muted">Estoque Atual</small>
                        <h5 class="<?php echo $estoque_total <= $medicamento['estoque_minimo'] ? 'text-danger' : 'text-success'; ?>">
                            <?php echo $estoque_total; ?> un.
                        </h5>
                    </div>
                    <div class="col-6 mb-2">
                        <small class="text-muted">Estoque Mínimo</small>
                        <h5><?php echo $medicamento['estoque_minimo']; ?> un.</h5>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary w-100">
                    <i class="bi bi-pencil"></i> Editar Medicamento
                </a>
            </div>
        </div>
        
        <!-- Status do Estoque -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-box-seam"></i> Status do Estoque</h5>
            </div>
            <div class="card-body">
                <?php 
                $status_classe = 'success';
                $status_texto = 'Normal';
                if ($estoque_total == 0) {
                    $status_classe = 'dark';
                    $status_texto = 'Sem Estoque';
                } elseif ($estoque_total <= $medicamento['estoque_minimo']) {
                    $status_classe = 'danger';
                    $status_texto = 'Crítico';
                } elseif ($estoque_total <= $medicamento['estoque_minimo'] * 1.5) {
                    $status_classe = 'warning';
                    $status_texto = 'Baixo';
                }
                ?>
                <div class="text-center">
                    <span class="badge bg-<?php echo $status_classe; ?> fs-5 px-4 py-2">
                        <?php echo $status_texto; ?>
                    </span>
                </div>
                
                <div class="progress mt-3" style="height: 20px;">
                    <?php 
                    $percent = min(100, ($estoque_total / max(1, $medicamento['estoque_minimo'] * 2)) * 100);
                    ?>
                    <div class="progress-bar bg-<?php echo $status_classe; ?>" 
                         style="width: <?php echo $percent; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-file-text"></i> Dados Completos</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="text-muted">Forma Farmacêutica</label>
                        <p class="mb-0"><?php echo ucfirst($medicamento['forma_farmaceutica'] ?? '-'); ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted">Fabricante</label>
                        <p class="mb-0"><?php echo htmlspecialchars($medicamento['fabricante'] ?? $medicamento['fabricante_laboratorio'] ?? '-'); ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted">Categoria</label>
                        <p class="mb-0"><?php echo htmlspecialchars($medicamento['categoria_nome'] ?? '-'); ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="text-muted">Código de Barras</label>
                        <p class="mb-0"><?php echo htmlspecialchars($medicamento['codigo_barras'] ?? '-'); ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted">Registro ANVISA</label>
                        <p class="mb-0"><?php echo htmlspecialchars($medicamento['registro_anvisa'] ?? $medicamento['registro_ms'] ?? '-'); ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted">Status</label>
                        <p class="mb-0">
                            <?php echo $medicamento['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>'; ?>
                        </p>
                    </div>
                </div>
                
                <?php if ($medicamento['observacoes']): ?>
                    <hr>
                    <h6 class="text-primary"><i class="bi bi-chat-text"></i> Observações</h6>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($medicamento['observacoes'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Lotes em Estoque -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-boxes"></i> Lotes em Estoque</h5>
                <span class="badge bg-secondary"><?php echo count($lotes); ?> lotes</span>
            </div>
            <div class="card-body">
                <?php if (empty($lotes)): ?>
                    <p class="text-muted text-center mb-0">Nenhum lote disponível em estoque.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Lote</th>
                                    <th>Validade</th>
                                    <th>Quantidade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lotes as $lote): 
                                    $validade = new DateTime($lote['data_validade']);
                                    $hoje = new DateTime();
                                    $dias = $hoje->diff($validade)->days;
                                    $vencido = $validade < $hoje;
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($lote['lote']); ?></strong></td>
                                        <td><?php echo date('d/m/Y', strtotime($lote['data_validade'])); ?></td>
                                        <td><span class="badge bg-info"><?php echo $lote['quantidade_atual']; ?></span></td>
                                        <td>
                                            <?php if ($vencido): ?>
                                                <span class="badge bg-danger">Vencido</span>
                                            <?php elseif ($dias <= 30): ?>
                                                <span class="badge bg-warning"><?php echo $dias; ?> dias</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">OK</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Últimas Dispensações -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clipboard2-pulse"></i> Últimas Dispensações</h5>
            </div>
            <div class="card-body">
                <?php if (empty($dispensacoes)): ?>
                    <p class="text-muted text-center mb-0">Nenhuma dispensação registrada para este medicamento.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Qtd</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dispensacoes as $d): ?>
                                    <tr>
                                        <td><?php echo $d['numero_dispensacao']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($d['data_dispensacao'])); ?></td>
                                        <td><?php echo htmlspecialchars($d['cliente'] ?? 'Não identificado'); ?></td>
                                        <td><span class="badge bg-info"><?php echo $d['quantidade']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="listar.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
