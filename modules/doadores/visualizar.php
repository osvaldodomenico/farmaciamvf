<?php
require_once __DIR__ . '/../../templates/header.php';

$id = $_GET['id'] ?? 0;
$doador = fetchOne("SELECT * FROM " . tableName('doadores') . " WHERE id = ?", [$id]);

if (!$doador) {
    $_SESSION['error'] = 'Doador não encontrado.';
    header('Location: listar.php');
    exit;
}

// Buscar histórico de doações
$doacoes = fetchAll("
    SELECT d.*, u.nome_completo as atendente,
           (SELECT COUNT(*) FROM " . tableName('itens_doacao') . " WHERE doacao_id = d.id) as total_itens,
           (SELECT SUM(quantidade) FROM " . tableName('itens_doacao') . " WHERE doacao_id = d.id) as total_quantidade
    FROM " . tableName('doacoes') . " d
    LEFT JOIN " . tableName('usuarios') . " u ON d.usuario_id = u.id
    WHERE d.doador_id = ?
    ORDER BY d.data_recebimento DESC
", [$id]);

// Estatísticas do doador
$total_doacoes = count($doacoes);
$total_itens = array_sum(array_column($doacoes, 'total_quantidade'));
?>

<script>document.getElementById('page-title').textContent = 'Visualizar Doador';</script>

<div class="page-title">
    <h1><i class="bi bi-heart"></i> Detalhes do Doador</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="listar.php">Doadores</a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-heart-fill"></i> Informações</h5>
            </div>
            <div class="card-body text-center">
                <div class="<?php echo $doador['tipo'] === 'pessoa_juridica' ? 'bg-primary' : 'bg-danger'; ?> bg-opacity-10 rounded-circle p-4 d-inline-block mb-3">
                    <i class="bi <?php echo $doador['tipo'] === 'pessoa_juridica' ? 'bi-building' : 'bi-heart'; ?> <?php echo $doador['tipo'] === 'pessoa_juridica' ? 'text-primary' : 'text-danger'; ?>" style="font-size: 4rem;"></i>
                </div>
                <h4><?php echo htmlspecialchars($doador['nome_completo']); ?></h4>
                <span class="badge <?php echo $doador['tipo'] === 'pessoa_juridica' ? 'bg-primary' : 'bg-info'; ?> mb-2">
                    <?php echo $doador['tipo'] === 'pessoa_juridica' ? 'Pessoa Jurídica' : 'Pessoa Física'; ?>
                </span>
                <?php if ($doador['cpf_cnpj']): ?>
                    <p class="text-muted mb-1">
                        <?php echo $doador['tipo'] === 'pessoa_juridica' ? 'CNPJ' : 'CPF'; ?>: 
                        <?php echo $doador['cpf_cnpj']; ?>
                    </p>
                <?php endif; ?>
                <?php if ($doador['celular'] || $doador['telefone']): ?>
                    <p class="mb-0">
                        <i class="bi bi-telephone"></i> 
                        <?php echo $doador['celular'] ?: $doador['telefone']; ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary w-100">
                    <i class="bi bi-pencil"></i> Editar
                </a>
            </div>
        </div>
        
        <!-- Estatísticas -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Estatísticas</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total de Doações:</span>
                    <strong><?php echo $total_doacoes; ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Itens Doados:</span>
                    <strong><?php echo $total_itens; ?> unidades</strong>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Dados de Contato</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted">E-mail</label>
                        <p class="mb-0"><?php echo htmlspecialchars($doador['email'] ?? '-'); ?></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted">Telefone</label>
                        <p class="mb-0"><?php echo htmlspecialchars($doador['telefone'] ?? '-'); ?></p>
                    </div>
                </div>
                
                <hr>
                <h6 class="text-primary"><i class="bi bi-geo-alt"></i> Endereço</h6>
                
                <?php if ($doador['endereco']): ?>
                    <p class="mb-0">
                        <?php echo htmlspecialchars($doador['endereco']); ?>
                        <?php echo $doador['numero'] ? ", {$doador['numero']}" : ''; ?>
                        <?php echo $doador['complemento'] ? " - {$doador['complemento']}" : ''; ?>
                        <br>
                        <?php echo htmlspecialchars($doador['bairro'] ?? ''); ?>
                        <?php echo $doador['cidade'] ? " - {$doador['cidade']}" : ''; ?>
                        <?php echo $doador['estado'] ? "/{$doador['estado']}" : ''; ?>
                        <?php echo $doador['cep'] ? " - CEP: {$doador['cep']}" : ''; ?>
                    </p>
                <?php else: ?>
                    <p class="text-muted">Endereço não informado</p>
                <?php endif; ?>
                
                <?php if ($doador['observacoes']): ?>
                    <hr>
                    <h6 class="text-primary"><i class="bi bi-chat-text"></i> Observações</h6>
                    <p><?php echo nl2br(htmlspecialchars($doador['observacoes'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-box-arrow-in-down"></i> Histórico de Doações</h5>
                <span class="badge bg-success"><?php echo $total_doacoes; ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($doacoes)): ?>
                    <p class="text-muted text-center mb-0">Nenhuma doação registrada deste doador.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Data</th>
                                    <th>Itens</th>
                                    <th>Quantidade</th>
                                    <th>Recebido por</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doacoes as $d): ?>
                                    <tr>
                                        <td><strong><?php echo $d['numero_doacao']; ?></strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($d['data_recebimento'])); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $d['total_itens'] ?? 0; ?></span></td>
                                        <td><span class="badge bg-success"><?php echo $d['total_quantidade'] ?? 0; ?> un.</span></td>
                                        <td><?php echo htmlspecialchars($d['atendente'] ?? '-'); ?></td>
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
