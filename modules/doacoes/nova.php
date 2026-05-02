<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDb();
        $pdo->beginTransaction();
        
        // Inserir doação
        $stmt = $pdo->prepare("INSERT INTO " . tableName('doacoes') . " (numero_doacao, doador_id, data_recebimento, usuario_id, valor_estimado, observacoes) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['numero_doacao'],
            $_POST['doador_id'] ?: null,
            $_POST['data_recebimento'],
            $_SESSION['usuario_id'],
            $_POST['valor_estimado'] ?: 0,
            $_POST['observacoes'] ?: null
        ]);
        
        $doacao_id = $pdo->lastInsertId();
        
        // Inserir itens
        $itens = json_decode($_POST['itens'], true);
        
        foreach ($itens as $item) {
            // Inserir item da doação
            $stmt = $pdo->prepare("INSERT INTO " . tableName('itens_doacao') . " (doacao_id, medicamento_id, lote, data_validade, quantidade, valor_unitario) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $doacao_id,
                $item['medicamento_id'],
                $item['lote'],
                $item['data_validade'],
                $item['quantidade'],
                $item['valor_unitario'] ?? 0
            ]);
            
            // Verificar se já existe lote no estoque
            $estoque_existente = fetchOne("SELECT id FROM " . tableName('estoque') . " WHERE medicamento_id = ? AND lote = ?", 
                [$item['medicamento_id'], $item['lote']]);
            
            if ($estoque_existente) {
                // Atualizar quantidade
                execute("UPDATE " . tableName('estoque') . " SET quantidade_atual = quantidade_atual + ?, quantidade_inicial = quantidade_inicial + ? WHERE id = ?",
                    [$item['quantidade'], $item['quantidade'], $estoque_existente['id']]);
            } else {
                // Criar novo lote no estoque
                execute("INSERT INTO " . tableName('estoque') . " (medicamento_id, lote, data_validade, quantidade_inicial, quantidade_atual) VALUES (?, ?, ?, ?, ?)",
                    [$item['medicamento_id'], $item['lote'], $item['data_validade'], $item['quantidade'], $item['quantidade']]);
            }
        }
        
        $pdo->commit();
        
        registrarLog($_SESSION['usuario_id'], 'registrou_doacao', 'doacoes', $doacao_id, $_POST['numero_doacao']);
        
        $_SESSION['success'] = 'Doação registrada com sucesso! Os itens foram adicionados ao estoque.';
        header('Location: listar.php');
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Erro ao registrar doação: ' . $e->getMessage();
    }
}


// Gerar número da doação
$data = date('Ymd');
$count = fetchColumn("SELECT COUNT(*) + 1 FROM " . tableName('doacoes') . " WHERE DATE(data_recebimento) = CURDATE()");
$numero_doacao = 'DOA-' . $data . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

// Buscar medicamentos para o select
$medicamentos = fetchAll("SELECT id, nome, principio_ativo, dosagem_concentracao FROM " . tableName('medicamentos') . " WHERE ativo = 1 ORDER BY nome");

// Buscar doadores para o select
$doadores = fetchAll("SELECT id, nome_completo, tipo, cpf_cnpj FROM " . tableName('doadores') . " WHERE ativo = 1 ORDER BY nome_completo");

// Estatísticas do mês
$stats = [
    'doacoes_mes' => fetchColumn("SELECT COUNT(*) FROM " . tableName('doacoes') . " WHERE MONTH(data_recebimento) = MONTH(CURDATE()) AND YEAR(data_recebimento) = YEAR(CURDATE())") ?? 0,
    'itens_mes' => fetchColumn("SELECT COALESCE(SUM(di.quantidade), 0) FROM " . tableName('itens_doacao') . " di INNER JOIN " . tableName('doacoes') . " d ON di.doacao_id = d.id WHERE MONTH(d.data_recebimento) = MONTH(CURDATE()) AND YEAR(d.data_recebimento) = YEAR(CURDATE())") ?? 0,
    'valor_mes' => fetchColumn("SELECT COALESCE(SUM(valor_estimado), 0) FROM " . tableName('doacoes') . " WHERE MONTH(data_recebimento) = MONTH(CURDATE()) AND YEAR(data_recebimento) = YEAR(CURDATE())") ?? 0,
    'doadores_ativos' => fetchColumn("SELECT COUNT(*) FROM " . tableName('doadores') . " WHERE ativo = 1") ?? 0
];

require_once __DIR__ . '/../../templates/header.php';
?>

<script>document.getElementById('page-title').textContent = 'Nova Doação';</script>

<!-- Premium Design Styles -->
<style>
    /* Premium Color Palette - Inspired by Reference */
    :root {
        --premium-bg: #f4f7fa;
        --premium-card-bg: #ffffff;
        --premium-border: #e8ecf1;
        --premium-text-primary: #1a2b3c;
        --premium-text-secondary: #5a6b7c;
        --premium-text-muted: #8a9aac;
        /* Dashboard Blue Scheme */
        --premium-blue: #1e3a8a;
        --premium-blue-light: #2563eb;
        --premium-teal: #3b82f6; /* Changed to lighter blue */
        --premium-teal-dark: #1d4ed8; /* Changed to dark blue */
        --premium-green: #2563eb; /* Changed to Blue */
        --premium-gold: #f4a261;
        --premium-pink: #e84a5f;
        --premium-purple: #6c5ce7;
        --premium-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        --premium-shadow-hover: 0 8px 30px rgba(0, 0, 0, 0.12);
        --premium-radius: 16px;
        --premium-radius-sm: 10px;
    }

    /* Google Font - Inter */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

    .premium-page {
        font-family: 'Inter', -apple-system, sans-serif;
        background: var(--premium-bg);
        padding: 0;
    }

    /* Page Header Premium */
    .premium-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        margin: -2rem -2rem 2rem -2rem;
        padding: 2rem 2rem 6rem 2rem;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .premium-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }

    .premium-header::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: 20%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
        border-radius: 50%;
    }

    .premium-header-content {
        position: relative;
        z-index: 1;
    }

    .premium-header h1 {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .premium-header h1 i {
        font-size: 1.5rem;
        opacity: 0.9;
    }

    .premium-breadcrumb {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        font-size: 0.875rem;
        opacity: 0.9;
    }

    .premium-breadcrumb a {
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: all 0.2s;
    }

    .premium-breadcrumb a:hover {
        color: white;
    }

    .premium-breadcrumb span {
        opacity: 0.6;
    }

    /* Stats Cards - Inspired by Reference */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.25rem;
        margin-top: -4rem;
        margin-bottom: 2rem;
        position: relative;
        z-index: 10;
    }

    @media (max-width: 1200px) {
        .stats-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 576px) {
        .stats-container {
            grid-template-columns: 1fr;
        }
    }

    .stat-card {
        background: var(--premium-card-bg);
        border-radius: var(--premium-radius);
        padding: 1.5rem;
        box-shadow: var(--premium-shadow);
        border: 1px solid var(--premium-border);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--premium-shadow-hover);
    }

    .stat-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .stat-card-title {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--premium-text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .stat-card-help {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: 1.5px solid var(--premium-text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--premium-text-muted);
        font-size: 0.75rem;
        cursor: help;
        transition: all 0.2s;
    }

    .stat-card-help:hover {
        border-color: var(--premium-blue);
        color: var(--premium-blue);
    }

    .stat-card-value {
        font-size: 2.25rem;
        font-weight: 700;
        color: var(--premium-text-primary);
        line-height: 1.1;
        margin-bottom: 0.75rem;
    }

    .stat-card-subtitle {
        font-size: 0.8125rem;
        color: var(--premium-text-muted);
        margin-bottom: 0.75rem;
    }

    .stat-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .stat-badge-teal {
        background: linear-gradient(135deg, #e6f7f5, #d0f0ed);
        color: var(--premium-teal-dark);
    }

    .stat-badge-green {
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        color: #1e40af;
    }

    .stat-badge-gold {
        background: linear-gradient(135deg, #fef5e7, #fde8c8);
        color: #d68910;
    }

    .stat-badge-pink {
        background: linear-gradient(135deg, #fdeaea, #f8d7da);
        color: var(--premium-pink);
    }

    .stat-badge i {
        font-size: 0.625rem;
    }

    /* Premium Cards */
    .premium-card {
        background: var(--premium-card-bg);
        border-radius: var(--premium-radius);
        box-shadow: var(--premium-shadow);
        border: 1px solid var(--premium-border);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .premium-card:hover {
        box-shadow: var(--premium-shadow-hover);
    }

    .premium-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--premium-border);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .premium-card-header-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--premium-radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.125rem;
    }

    .premium-card-header-icon.blue {
        background: linear-gradient(135deg, var(--premium-blue), var(--premium-blue-light));
        color: white;
    }

    .premium-card-header-icon.teal {
        background: linear-gradient(135deg, var(--premium-teal-dark), var(--premium-teal));
        color: white;
    }

    .premium-card-header-icon.purple {
        background: linear-gradient(135deg, #5b4cdb, var(--premium-purple));
        color: white;
    }

    .premium-card-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--premium-text-primary);
        margin: 0;
    }

    .premium-card-subtitle {
        font-size: 0.8125rem;
        color: var(--premium-text-muted);
        margin: 0;
    }

    .premium-card-body {
        padding: 1.5rem;
    }

    .premium-card-footer {
        padding: 1rem 1.5rem;
        background: #fafbfc;
        border-top: 1px solid var(--premium-border);
    }

    /* Premium Form Elements */
    .premium-form-group {
        margin-bottom: 1.25rem;
    }

    .premium-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--premium-text-secondary);
        margin-bottom: 0.5rem;
    }

    .premium-label .required {
        color: var(--premium-pink);
    }

    .premium-input {
        width: 100%;
        padding: 0.75rem 1rem;
        font-size: 0.9375rem;
        color: var(--premium-text-primary);
        background: #fafbfc;
        border: 1.5px solid var(--premium-border);
        border-radius: var(--premium-radius-sm);
        transition: all 0.2s ease;
    }

    .premium-input:focus {
        outline: none;
        border-color: var(--premium-teal);
        background: white;
        box-shadow: 0 0 0 4px rgba(0, 168, 150, 0.1);
    }

    .premium-input::placeholder {
        color: var(--premium-text-muted);
    }

    .premium-input-readonly {
        background: #f0f3f6;
        color: var(--premium-text-muted);
        cursor: not-allowed;
    }

    .premium-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%235a6b7c' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        padding-right: 2.5rem;
    }

    .premium-textarea {
        min-height: 100px;
        resize: vertical;
    }

    /* Premium Number Display */
    .doacao-numero-display {
        background: linear-gradient(135deg, var(--premium-blue) 0%, var(--premium-teal-dark) 100%);
        color: white;
        padding: 1.25rem;
        border-radius: var(--premium-radius-sm);
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .doacao-numero-display .label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        opacity: 0.9;
        margin-bottom: 0.25rem;
    }

    .doacao-numero-display .numero {
        font-size: 1.375rem;
        font-weight: 700;
        font-family: 'SF Mono', 'Fira Code', monospace;
        letter-spacing: 0.05em;
    }

    /* Premium Add Item Section */
    .add-item-section {
        background: linear-gradient(135deg, #f8fafb, #f0f4f7);
        border-radius: var(--premium-radius-sm);
        padding: 1.5rem;
        border: 1px dashed var(--premium-border);
    }

    .add-item-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        gap: 1rem;
        align-items: end;
    }

    @media (max-width: 1200px) {
        .add-item-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 576px) {
        .add-item-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Premium Button */
    .premium-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        border-radius: var(--premium-radius-sm);
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .premium-btn-primary {
        background: linear-gradient(135deg, var(--premium-teal-dark), var(--premium-teal));
        color: white;
        box-shadow: 0 4px 14px rgba(0, 168, 150, 0.35);
    }

    .premium-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 168, 150, 0.45);
        color: white;
    }

    .premium-btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .premium-btn-success {
        background: linear-gradient(135deg, #16a085, var(--premium-green));
        color: white;
        box-shadow: 0 4px 14px rgba(46, 204, 113, 0.35);
    }

    .premium-btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(46, 204, 113, 0.45);
    }

    .premium-btn-secondary {
        background: white;
        color: var(--premium-text-secondary);
        border: 1.5px solid var(--premium-border);
    }

    .premium-btn-secondary:hover {
        background: #f8fafb;
        border-color: var(--premium-text-muted);
        color: var(--premium-text-primary);
    }

    .premium-btn-danger {
        background: linear-gradient(135deg, #c0392b, var(--premium-pink));
        color: white;
    }

    .premium-btn-danger:hover {
        transform: translateY(-2px);
    }

    .premium-btn-sm {
        padding: 0.5rem 0.875rem;
        font-size: 0.8125rem;
    }

    /* Premium Table */
    .premium-table-container {
        overflow-x: auto;
    }

    .premium-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .premium-table thead th {
        background: #f8fafb;
        padding: 1rem 1.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--premium-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid var(--premium-border);
        text-align: left;
    }

    .premium-table tbody td {
        padding: 1rem 1.25rem;
        font-size: 0.9375rem;
        color: var(--premium-text-primary);
        border-bottom: 1px solid var(--premium-border);
        vertical-align: middle;
    }

    .premium-table tbody tr {
        transition: background 0.2s ease;
    }

    .premium-table tbody tr:hover {
        background: #fafbfc;
    }

    .premium-table tbody tr:last-child td {
        border-bottom: none;
    }

    .item-nome {
        font-weight: 600;
        color: var(--premium-text-primary);
    }

    .item-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.375rem 0.625rem;
        background: linear-gradient(135deg, #e8f8f0, #d4f1e4);
        color: #16a085;
        font-size: 0.8125rem;
        font-weight: 600;
        border-radius: 6px;
    }

    .item-lote {
        font-family: 'SF Mono', 'Fira Code', monospace;
        font-size: 0.875rem;
        color: var(--premium-text-secondary);
        background: #f0f3f6;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--premium-text-muted);
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }

    .empty-state p {
        font-size: 0.9375rem;
        margin: 0;
    }

    /* Items Counter Badge */
    .items-counter {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 24px;
        padding: 0 0.5rem;
        background: linear-gradient(135deg, var(--premium-teal-dark), var(--premium-teal));
        color: white;
        font-size: 0.75rem;
        font-weight: 700;
        border-radius: 50px;
        margin-left: 0.5rem;
    }

    /* Small Link */
    .premium-link {
        font-size: 0.8125rem;
        color: var(--premium-teal-dark);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        transition: all 0.2s;
    }

    .premium-link:hover {
        color: var(--premium-teal);
        text-decoration: underline;
    }

    /* Two Column Layout */
    .premium-layout {
        display: grid;
        grid-template-columns: 380px 1fr;
        gap: 1.5rem;
        align-items: start;
    }

    @media (max-width: 1024px) {
        .premium-layout {
            grid-template-columns: 1fr;
        }
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-in {
        animation: fadeInUp 0.4s ease-out forwards;
    }

    .stat-card:nth-child(1) { animation-delay: 0.1s; }
    .stat-card:nth-child(2) { animation-delay: 0.2s; }
    .stat-card:nth-child(3) { animation-delay: 0.3s; }
    .stat-card:nth-child(4) { animation-delay: 0.4s; }

    /* Tooltip Styling */
    [data-tooltip] {
        position: relative;
    }

    [data-tooltip]::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        padding: 0.5rem 0.75rem;
        background: var(--premium-text-primary);
        color: white;
        font-size: 0.75rem;
        font-weight: 500;
        border-radius: 6px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s;
        margin-bottom: 8px;
        z-index: 100;
    }

    [data-tooltip]:hover::after {
        opacity: 1;
        visibility: visible;
    }

    /* Select2 Premium Override */
    .select2-container--bootstrap-5 .select2-selection {
        border-radius: var(--premium-radius-sm) !important;
        border: 1.5px solid var(--premium-border) !important;
        min-height: 44px !important;
        background: #fafbfc !important;
    }

    .select2-container--bootstrap-5 .select2-selection:focus,
    .select2-container--bootstrap-5.select2-container--focus .select2-selection {
        border-color: var(--premium-teal) !important;
        box-shadow: 0 0 0 4px rgba(0, 168, 150, 0.1) !important;
    }

    /* Button Group */
    .premium-btn-group {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
</style>

<div class="premium-page">
    <!-- Premium Header -->
    <div class="premium-header">
        <div class="premium-header-content">
            <h1><i class="bi bi-gift-fill"></i> Nova Doação</h1>
            <div class="premium-breadcrumb">
                <a href="../../dashboard.php">Dashboard</a>
                <span>/</span>
                <a href="listar.php">Doações</a>
                <span>/</span>
                <span>Nova</span>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-container">
        <div class="stat-card animate-in">
            <div class="stat-card-header">
                <div class="stat-card-title">Doações no Mês</div>
                <div class="stat-card-help" data-tooltip="Total de doações recebidas este mês">?</div>
            </div>
            <div class="stat-card-value"><?php echo number_format($stats['doacoes_mes'], 0, ',', '.'); ?></div>
            <div class="stat-card-subtitle">Registros este mês</div>
            <span class="stat-badge stat-badge-teal">
                <i class="bi bi-circle-fill"></i> Mês Atual
            </span>
        </div>

        <div class="stat-card animate-in">
            <div class="stat-card-header">
                <div class="stat-card-title">Itens Recebidos</div>
                <div class="stat-card-help" data-tooltip="Total de unidades de medicamentos doados">?</div>
            </div>
            <div class="stat-card-value"><?php echo number_format($stats['itens_mes'], 0, ',', '.'); ?></div>
            <div class="stat-card-subtitle">Unidades de medicamentos</div>
            <span class="stat-badge stat-badge-green">
                <i class="bi bi-circle-fill"></i> Estoque Atualizado
            </span>
        </div>

        <div class="stat-card animate-in">
            <div class="stat-card-header">
                <div class="stat-card-title">Valor Estimado</div>
                <div class="stat-card-help" data-tooltip="Valor total estimado das doações do mês">?</div>
            </div>
            <div class="stat-card-value">R$ <?php echo number_format($stats['valor_mes'], 2, ',', '.'); ?></div>
            <div class="stat-card-subtitle">Valor acumulado (estimado)</div>
            <span class="stat-badge stat-badge-gold">
                <i class="bi bi-circle-fill"></i> Estimativa
            </span>
        </div>

        <div class="stat-card animate-in">
            <div class="stat-card-header">
                <div class="stat-card-title">Doadores Ativos</div>
                <div class="stat-card-help" data-tooltip="Número de doadores cadastrados ativos">?</div>
            </div>
            <div class="stat-card-value"><?php echo number_format($stats['doadores_ativos'], 0, ',', '.'); ?></div>
            <div class="stat-card-subtitle">Cadastros ativos</div>
            <span class="stat-badge stat-badge-pink">
                <i class="bi bi-circle-fill"></i> Parceiros
            </span>
        </div>
    </div>

    <!-- Main Form -->
    <form method="POST" id="formDoacao">
        <input type="hidden" name="itens" id="itens_json" value="[]">
        
        <div class="premium-layout">
            <!-- Left Column - Donation Data -->
            <div>
                <div class="premium-card animate-in" style="animation-delay: 0.5s;">
                    <div class="premium-card-header">
                        <div class="premium-card-header-icon blue">
                            <i class="bi bi-info-circle-fill"></i>
                        </div>
                        <div>
                            <h5 class="premium-card-title">Dados da Doação</h5>
                            <p class="premium-card-subtitle">Informações gerais do recebimento</p>
                        </div>
                    </div>
                    <div class="premium-card-body">
                        <!-- Número da Doação Display -->
                        <div class="doacao-numero-display">
                            <div class="label">Número da Doação</div>
                            <div class="numero"><?php echo $numero_doacao; ?></div>
                        </div>
                        <input type="hidden" name="numero_doacao" value="<?php echo $numero_doacao; ?>">

                        <div class="premium-form-group">
                            <label class="premium-label">
                                Data/Hora de Recebimento <span class="required">*</span>
                            </label>
                            <input type="datetime-local" 
                                   class="premium-input" 
                                   name="data_recebimento" 
                                   value="<?php echo date('Y-m-d\TH:i'); ?>" 
                                   required>
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Doador</label>
                            <select class="premium-input premium-select" name="doador_id" id="doador_id">
                                <option value="">Anônimo / Não identificado</option>
                                <?php foreach ($doadores as $doador): ?>
                                    <option value="<?php echo $doador['id']; ?>">
                                        <?php echo htmlspecialchars($doador['nome_completo']); ?>
                                        (<?php echo $doador['tipo'] === 'pessoa_juridica' ? 'PJ' : 'PF'; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <a href="../doadores/adicionar.php" target="_blank" class="premium-link mt-2">
                                <i class="bi bi-plus-circle"></i> Cadastrar novo doador
                            </a>
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Valor Estimado (R$)</label>
                            <input type="number" 
                                   class="premium-input" 
                                   name="valor_estimado" 
                                   step="0.01" 
                                   min="0"
                                   placeholder="0,00">
                        </div>

                        <div class="premium-form-group">
                            <label class="premium-label">Observações</label>
                            <textarea class="premium-input premium-textarea" 
                                      name="observacoes" 
                                      placeholder="Informações adicionais sobre a doação..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Items -->
            <div>
                <!-- Add Items Card -->
                <div class="premium-card animate-in mb-4" style="animation-delay: 0.6s;">
                    <div class="premium-card-header">
                        <div class="premium-card-header-icon teal">
                            <i class="bi bi-capsule-pill"></i>
                        </div>
                        <div>
                            <h5 class="premium-card-title">Adicionar Itens</h5>
                            <p class="premium-card-subtitle">Medicamentos recebidos nesta doação</p>
                        </div>
                    </div>
                    <div class="premium-card-body">
                        <div class="add-item-section">
                            <!-- 1. Linha para Medicamento (Full Width) -->
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <div class="premium-form-group mb-0">
                                        <label class="premium-label">Medicamento <span class="required">*</span></label>
                                        <select class="premium-input premium-select" id="medicamento_id">
                                            <option value="">Selecione o medicamento...</option>
                                            <?php foreach ($medicamentos as $med): ?>
                                                <option value="<?php echo $med['id']; ?>" 
                                                        data-nome="<?php echo htmlspecialchars($med['nome']); ?>">
                                                    <?php echo htmlspecialchars($med['nome']); ?>
                                                    <?php echo $med['dosagem_concentracao'] ? " - {$med['dosagem_concentracao']}" : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Linha para Outros Campos -->
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <div class="premium-form-group mb-0">
                                        <label class="premium-label">Lote <span class="required">*</span></label>
                                        <input type="text" class="premium-input" id="lote" maxlength="100" placeholder="Ex: ABC123">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="premium-form-group mb-0">
                                        <label class="premium-label">Validade <span class="required">*</span></label>
                                        <input type="date" class="premium-input" id="data_validade">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="premium-form-group mb-0">
                                        <label class="premium-label">Qtd <span class="required">*</span></label>
                                        <input type="number" class="premium-input" id="quantidade" min="1" placeholder="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="premium-form-group mb-0">
                                        <button type="button" class="premium-btn premium-btn-success w-100" onclick="adicionarItem()">
                                            <i class="bi bi-plus-lg"></i> Adicionar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items List Card -->
                <div class="premium-card animate-in" style="animation-delay: 0.7s;">
                    <div class="premium-card-header">
                        <div class="premium-card-header-icon purple">
                            <i class="bi bi-list-check"></i>
                        </div>
                        <div>
                            <h5 class="premium-card-title">
                                Itens da Doação
                                <span class="items-counter" id="total-itens">0</span>
                            </h5>
                            <p class="premium-card-subtitle">Lista de medicamentos a serem registrados</p>
                        </div>
                    </div>
                    <div class="premium-card-body p-0">
                        <div class="premium-table-container">
                            <table class="premium-table" id="tabela-itens">
                                <thead>
                                    <tr>
                                        <th>Medicamento</th>
                                        <th>Lote</th>
                                        <th>Validade</th>
                                        <th>Quantidade</th>
                                        <th width="80">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="linha-vazia">
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <i class="bi bi-inbox"></i>
                                                <p>Nenhum item adicionado ainda.<br>Use o formulário acima para adicionar medicamentos.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="premium-card-footer">
                        <div class="premium-btn-group">
                            <button type="submit" class="premium-btn premium-btn-primary" id="btnSalvar" disabled>
                                <i class="bi bi-check-circle-fill"></i> Registrar Doação
                            </button>
                            <a href="listar.php" class="premium-btn premium-btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<script>
let itens = [];

function adicionarItem() {
    const medicamento_id = $('#medicamento_id').val();
    const medicamento_nome = $('#medicamento_id option:selected').data('nome');
    const lote = $('#lote').val().trim();
    const data_validade = $('#data_validade').val();
    const quantidade = parseInt($('#quantidade').val());
    
    if (!medicamento_id || !lote || !data_validade || !quantidade) {
        toastr.warning('Preencha todos os campos do item');
        return;
    }
    
    if (quantidade <= 0) {
        toastr.warning('Quantidade deve ser maior que zero');
        return;
    }
    
    // Verificar duplicata
    const duplicado = itens.find(i => i.medicamento_id == medicamento_id && i.lote.toUpperCase() === lote.toUpperCase());
    if (duplicado) {
        toastr.warning('Este medicamento com este lote já foi adicionado');
        return;
    }
    
    const item = {
        medicamento_id,
        medicamento_nome,
        lote: lote.toUpperCase(),
        data_validade,
        quantidade,
        valor_unitario: 0
    };
    
    itens.push(item);
    atualizarTabela();
    limparCamposItem();
    toastr.success('Item adicionado com sucesso!');
}

function removerItem(index) {
    itens.splice(index, 1);
    atualizarTabela();
    toastr.info('Item removido');
}

function atualizarTabela() {
    const tbody = $('#tabela-itens tbody');
    tbody.empty();
    
    if (itens.length === 0) {
        tbody.html(`
            <tr id="linha-vazia">
                <td colspan="5">
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>Nenhum item adicionado ainda.<br>Use o formulário acima para adicionar medicamentos.</p>
                    </div>
                </td>
            </tr>
        `);
        $('#btnSalvar').prop('disabled', true);
    } else {
        itens.forEach((item, index) => {
            const validade = new Date(item.data_validade + 'T00:00:00').toLocaleDateString('pt-BR');
            tbody.append(`
                <tr>
                    <td><span class="item-nome">${item.medicamento_nome}</span></td>
                    <td><span class="item-lote">${item.lote}</span></td>
                    <td>${validade}</td>
                    <td><span class="item-badge"><i class="bi bi-box-fill"></i> ${item.quantidade}</span></td>
                    <td>
                        <button type="button" class="premium-btn premium-btn-danger premium-btn-sm" onclick="removerItem(${index})">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
        $('#btnSalvar').prop('disabled', false);
    }
    
    $('#total-itens').text(itens.length);
    $('#itens_json').val(JSON.stringify(itens));
}

function limparCamposItem() {
    $('#medicamento_id').val('').trigger('change');
    $('#lote').val('');
    $('#data_validade').val('');
    $('#quantidade').val('');
    $('#medicamento_id').focus();
}

$(document).ready(function() {


    $('#doador_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Selecione ou busque um doador...',
        allowClear: true
    });
    
    $('#medicamento_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Buscar medicamento...'
    });
});
</script>
