<?php
/**
 * SCRIPT DE MIGRAÇÃO - Farmácia Mão Amiga
 * Execute APENAS UMA VEZ e depois DELETE este arquivo do servidor.
 * Acesse: http://SEU_DOMINIO/migrate_db.php
 * 
 * SEGURO: Usa apenas CREATE TABLE IF NOT EXISTS - NUNCA apaga dados.
 */

// Proteção básica - muda para uma chave secreta de sua preferência
define('MIGRATE_KEY', 'farmacia2026');

if (!isset($_GET['key']) || $_GET['key'] !== MIGRATE_KEY) {
    http_response_code(403);
    die('Acesso negado. Use: migrate_db.php?key=farmacia2026');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config/database.php';

$db = getDb();
$resultados = [];
$erros = [];

function executarSQL($db, $nome, $sql, &$resultados, &$erros) {
    try {
        $db->exec($sql);
        $resultados[] = "✅ $nome";
    } catch (PDOException $e) {
        $erros[] = "❌ $nome: " . $e->getMessage();
    }
}

$tabelas = [

'farmacia_usuarios' => "CREATE TABLE IF NOT EXISTS farmacia_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    login VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    telefone VARCHAR(20),
    nivel_acesso ENUM('admin','gerente','farmaceutico','atendente','caixa') DEFAULT 'atendente',
    crf VARCHAR(20),
    ativo TINYINT(1) DEFAULT 1,
    ultimo_acesso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_login (login),
    INDEX idx_nivel_acesso (nivel_acesso),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_clientes' => "CREATE TABLE IF NOT EXISTS farmacia_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    rg VARCHAR(20),
    data_nascimento DATE,
    sexo ENUM('M','F','Outro'),
    telefone VARCHAR(20),
    celular VARCHAR(20),
    email VARCHAR(255),
    cep VARCHAR(10),
    logradouro VARCHAR(255),
    numero VARCHAR(10),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(2),
    observacoes TEXT,
    cartao_fidelidade VARCHAR(50),
    pontos_fidelidade INT DEFAULT 0,
    alergias_medicamentosas TEXT,
    condicoes_saude TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cpf (cpf),
    INDEX idx_nome (nome_completo),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_fornecedores' => "CREATE TABLE IF NOT EXISTS farmacia_fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    razao_social VARCHAR(255) NOT NULL,
    nome_fantasia VARCHAR(255),
    cnpj VARCHAR(18) UNIQUE NOT NULL,
    inscricao_estadual VARCHAR(20),
    telefone VARCHAR(20),
    email VARCHAR(255),
    cep VARCHAR(10),
    logradouro VARCHAR(255),
    numero VARCHAR(10),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(2),
    contato_nome VARCHAR(255),
    contato_telefone VARCHAR(20),
    observacoes TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cnpj (cnpj),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_categorias_medicamentos' => "CREATE TABLE IF NOT EXISTS farmacia_categorias_medicamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    codigo_anvisa VARCHAR(50),
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_medicamentos' => "CREATE TABLE IF NOT EXISTS farmacia_medicamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT,
    nome VARCHAR(255) NOT NULL,
    nome_generico VARCHAR(255),
    principio_ativo VARCHAR(255),
    forma_farmaceutica VARCHAR(100),
    dosagem_concentracao VARCHAR(100),
    unidade VARCHAR(50),
    apresentacao VARCHAR(255),
    fabricante_laboratorio VARCHAR(255),
    codigo_barras VARCHAR(100) UNIQUE,
    registro_ms VARCHAR(50),
    requer_receita TINYINT(1) DEFAULT 0,
    tipo_receita ENUM('comum','controle_especial','antibiotico','isento') DEFAULT 'comum',
    receita_retida TINYINT(1) DEFAULT 0,
    generico TINYINT(1) DEFAULT 0,
    similar TINYINT(1) DEFAULT 0,
    controlado TINYINT(1) DEFAULT 0,
    psicotropico TINYINT(1) DEFAULT 0,
    estoque_minimo INT DEFAULT 10,
    estoque_maximo INT DEFAULT 1000,
    preco_custo DECIMAL(10,2),
    preco_venda DECIMAL(10,2),
    margem_lucro DECIMAL(5,2),
    desconto_maximo DECIMAL(5,2) DEFAULT 0,
    observacoes TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES farmacia_categorias_medicamentos(id),
    INDEX idx_nome (nome),
    INDEX idx_codigo_barras (codigo_barras),
    INDEX idx_principio_ativo (principio_ativo),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_estoque' => "CREATE TABLE IF NOT EXISTS farmacia_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicamento_id INT NOT NULL,
    lote VARCHAR(50) NOT NULL,
    data_fabricacao DATE,
    data_validade DATE NOT NULL,
    quantidade_atual INT NOT NULL DEFAULT 0,
    quantidade_inicial INT NOT NULL,
    fornecedor_id INT,
    preco_custo_lote DECIMAL(10,2),
    nota_fiscal VARCHAR(50),
    data_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    localizacao VARCHAR(100),
    observacoes TEXT,
    FOREIGN KEY (medicamento_id) REFERENCES farmacia_medicamentos(id),
    FOREIGN KEY (fornecedor_id) REFERENCES farmacia_fornecedores(id),
    INDEX idx_medicamento (medicamento_id),
    INDEX idx_lote (lote),
    INDEX idx_validade (data_validade),
    UNIQUE KEY unique_medicamento_lote (medicamento_id, lote)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_vendas' => "CREATE TABLE IF NOT EXISTS farmacia_vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_venda VARCHAR(50) UNIQUE NOT NULL,
    cliente_id INT,
    usuario_id INT NOT NULL,
    farmaceutico_id INT,
    data_venda TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) NOT NULL,
    desconto DECIMAL(10,2) DEFAULT 0,
    acrescimo DECIMAL(10,2) DEFAULT 0,
    valor_total DECIMAL(10,2) NOT NULL,
    valor_pago DECIMAL(10,2),
    troco DECIMAL(10,2),
    forma_pagamento ENUM('dinheiro','cartao_debito','cartao_credito','pix','boleto','crediario','convenio') NOT NULL,
    parcelas INT DEFAULT 1,
    status ENUM('aberta','finalizada','cancelada','devolvida') DEFAULT 'finalizada',
    tipo_venda ENUM('balcao','delivery','online') DEFAULT 'balcao',
    receita_apresentada TINYINT(1) DEFAULT 0,
    receita_numero VARCHAR(50),
    receita_medico VARCHAR(255),
    receita_crm VARCHAR(20),
    observacoes TEXT,
    motivo_cancelamento TEXT,
    data_cancelamento TIMESTAMP NULL,
    usuario_cancelamento_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES farmacia_clientes(id),
    FOREIGN KEY (usuario_id) REFERENCES farmacia_usuarios(id),
    INDEX idx_numero_venda (numero_venda),
    INDEX idx_data_venda (data_venda),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_itens_venda' => "CREATE TABLE IF NOT EXISTS farmacia_itens_venda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT NOT NULL,
    medicamento_id INT NOT NULL,
    estoque_id INT,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    desconto_unitario DECIMAL(10,2) DEFAULT 0,
    preco_total DECIMAL(10,2) NOT NULL,
    lote VARCHAR(50),
    data_validade DATE,
    FOREIGN KEY (venda_id) REFERENCES farmacia_vendas(id) ON DELETE CASCADE,
    FOREIGN KEY (medicamento_id) REFERENCES farmacia_medicamentos(id),
    FOREIGN KEY (estoque_id) REFERENCES farmacia_estoque(id),
    INDEX idx_venda (venda_id),
    INDEX idx_medicamento (medicamento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_movimentacoes_estoque' => "CREATE TABLE IF NOT EXISTS farmacia_movimentacoes_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicamento_id INT NOT NULL,
    estoque_id INT,
    tipo_movimentacao ENUM('entrada','saida','ajuste','devolucao','perda','vencimento','transferencia') NOT NULL,
    quantidade INT NOT NULL,
    quantidade_anterior INT,
    quantidade_posterior INT,
    motivo VARCHAR(255),
    observacoes TEXT,
    usuario_id INT,
    venda_id INT,
    data_movimentacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicamento_id) REFERENCES farmacia_medicamentos(id),
    FOREIGN KEY (usuario_id) REFERENCES farmacia_usuarios(id),
    INDEX idx_medicamento (medicamento_id),
    INDEX idx_tipo (tipo_movimentacao),
    INDEX idx_data (data_movimentacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_caixa' => "CREATE TABLE IF NOT EXISTS farmacia_caixa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_abertura_id INT NOT NULL,
    usuario_fechamento_id INT,
    data_abertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_fechamento TIMESTAMP NULL,
    valor_abertura DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_entradas DECIMAL(10,2) DEFAULT 0,
    valor_saidas DECIMAL(10,2) DEFAULT 0,
    valor_esperado DECIMAL(10,2),
    valor_contado DECIMAL(10,2),
    diferenca DECIMAL(10,2),
    status ENUM('aberto','fechado') DEFAULT 'aberto',
    observacoes_abertura TEXT,
    observacoes_fechamento TEXT,
    FOREIGN KEY (usuario_abertura_id) REFERENCES farmacia_usuarios(id),
    INDEX idx_status (status),
    INDEX idx_data_abertura (data_abertura)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_movimentacoes_caixa' => "CREATE TABLE IF NOT EXISTS farmacia_movimentacoes_caixa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caixa_id INT NOT NULL,
    venda_id INT,
    tipo ENUM('entrada','saida','sangria','suprimento') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    forma_pagamento ENUM('dinheiro','cartao_debito','cartao_credito','pix','boleto','crediario','convenio'),
    descricao TEXT,
    usuario_id INT NOT NULL,
    data_movimentacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caixa_id) REFERENCES farmacia_caixa(id),
    FOREIGN KEY (usuario_id) REFERENCES farmacia_usuarios(id),
    INDEX idx_caixa (caixa_id),
    INDEX idx_tipo (tipo),
    INDEX idx_data (data_movimentacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_logs_sistema' => "CREATE TABLE IF NOT EXISTS farmacia_logs_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    acao VARCHAR(255) NOT NULL,
    tabela VARCHAR(100),
    registro_id INT,
    dados_anteriores TEXT,
    dados_novos TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    data_log TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES farmacia_usuarios(id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_data (data_log),
    INDEX idx_tabela (tabela)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_configuracoes' => "CREATE TABLE IF NOT EXISTS farmacia_configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo ENUM('texto','numero','boolean','json') DEFAULT 'texto',
    descricao TEXT,
    categoria VARCHAR(50),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_doadores' => "CREATE TABLE IF NOT EXISTS farmacia_doadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(255) NOT NULL,
    cpf VARCHAR(14),
    cnpj VARCHAR(18),
    tipo_pessoa ENUM('fisica','juridica') DEFAULT 'fisica',
    telefone VARCHAR(20),
    celular VARCHAR(20),
    email VARCHAR(255),
    logradouro VARCHAR(255),
    numero VARCHAR(10),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(2),
    cep VARCHAR(10),
    observacoes TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nome (nome_completo),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_doacoes' => "CREATE TABLE IF NOT EXISTS farmacia_doacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doador_id INT,
    usuario_id INT NOT NULL,
    data_doacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    numero_nf VARCHAR(50),
    valor_total DECIMAL(10,2) DEFAULT 0,
    status ENUM('pendente','recebida','cancelada') DEFAULT 'recebida',
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doador_id) REFERENCES farmacia_doadores(id),
    FOREIGN KEY (usuario_id) REFERENCES farmacia_usuarios(id),
    INDEX idx_doador (doador_id),
    INDEX idx_data (data_doacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_itens_doacao' => "CREATE TABLE IF NOT EXISTS farmacia_itens_doacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doacao_id INT NOT NULL,
    medicamento_id INT NOT NULL,
    lote VARCHAR(50),
    data_validade DATE,
    quantidade INT NOT NULL,
    valor_unitario DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (doacao_id) REFERENCES farmacia_doacoes(id) ON DELETE CASCADE,
    FOREIGN KEY (medicamento_id) REFERENCES farmacia_medicamentos(id),
    INDEX idx_doacao (doacao_id),
    INDEX idx_medicamento (medicamento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

'farmacia_dispensacoes' => "CREATE TABLE IF NOT EXISTS farmacia_dispensacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    usuario_id INT NOT NULL,
    medicamento_id INT NOT NULL,
    estoque_id INT,
    quantidade INT NOT NULL,
    data_dispensacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    receita_numero VARCHAR(50),
    receita_medico VARCHAR(255),
    receita_crm VARCHAR(20),
    observacoes TEXT,
    FOREIGN KEY (cliente_id) REFERENCES farmacia_clientes(id),
    FOREIGN KEY (usuario_id) REFERENCES farmacia_usuarios(id),
    FOREIGN KEY (medicamento_id) REFERENCES farmacia_medicamentos(id),
    INDEX idx_cliente (cliente_id),
    INDEX idx_medicamento (medicamento_id),
    INDEX idx_data (data_dispensacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

];

// Criar tabelas
foreach ($tabelas as $nome => $sql) {
    executarSQL($db, "Tabela: $nome", $sql, $resultados, $erros);
}

// Dados iniciais
$seedsSQL = [
    'Usuário admin' => "INSERT INTO farmacia_usuarios (nome_completo, login, senha, nivel_acesso)
        SELECT 'Administrador do Sistema','admin',
            '\$2y\$12\$ZYcbgatDNvL6/eCmF8S7m./DZg0eBcNBP2InAKIKy.tQGh39znzWC','admin'
        WHERE NOT EXISTS (SELECT 1 FROM farmacia_usuarios WHERE login = 'admin')",

    'Categorias' => "INSERT INTO farmacia_categorias_medicamentos (nome, descricao) VALUES
        ('Analgésicos e Antitérmicos','Medicamentos para dor e febre'),
        ('Antibióticos','Medicamentos para infecções bacterianas'),
        ('Anti-inflamatórios','Medicamentos anti-inflamatórios'),
        ('Antiácidos e Antiulcerosos','Medicamentos para problemas gástricos'),
        ('Anti-hipertensivos','Medicamentos para controle da pressão arterial'),
        ('Antidiabéticos','Medicamentos para controle da diabetes'),
        ('Vitaminas e Suplementos','Suplementos vitamínicos e minerais'),
        ('Dermatológicos','Medicamentos de uso tópico'),
        ('Respiratórios','Medicamentos para o sistema respiratório'),
        ('Cardiovasculares','Medicamentos para o sistema cardiovascular')
        ON DUPLICATE KEY UPDATE nome = nome",

    'Configurações' => "INSERT INTO farmacia_configuracoes (chave, valor, tipo, descricao, categoria) VALUES
        ('farmacia_nome','Farmácia Mão Amiga','texto','Nome da farmácia','geral'),
        ('farmacia_cnpj','','texto','CNPJ da farmácia','geral'),
        ('alerta_vencimento_dias','30','numero','Dias para alerta de vencimento','estoque'),
        ('estoque_minimo_padrao','10','numero','Quantidade mínima padrão','estoque'),
        ('permitir_venda_sem_estoque','false','boolean','Permitir venda negativa','vendas'),
        ('desconto_maximo_permitido','20','numero','Desconto máximo em %','vendas')
        ON DUPLICATE KEY UPDATE chave = chave",

    'Medicamento teste: Dipirona 500mg' => "INSERT INTO farmacia_medicamentos
        (nome, principio_ativo, forma_farmaceutica, dosagem_concentracao, apresentacao, categoria_id, preco_custo, preco_venda, estoque_minimo, ativo)
        SELECT 'Dipirona 500mg','Dipirona Sódica','Comprimido','500mg','Cx c/ 20 comp',1,0.50,2.50,20,1
        WHERE NOT EXISTS (SELECT 1 FROM farmacia_medicamentos WHERE nome = 'Dipirona 500mg')",

    'Medicamento teste: Paracetamol 750mg' => "INSERT INTO farmacia_medicamentos
        (nome, principio_ativo, forma_farmaceutica, dosagem_concentracao, apresentacao, categoria_id, preco_custo, preco_venda, estoque_minimo, ativo)
        SELECT 'Paracetamol 750mg','Paracetamol','Comprimido','750mg','Cx c/ 20 comp',1,0.60,2.90,20,1
        WHERE NOT EXISTS (SELECT 1 FROM farmacia_medicamentos WHERE nome = 'Paracetamol 750mg')",

    'Medicamento teste: Amoxicilina 500mg' => "INSERT INTO farmacia_medicamentos
        (nome, principio_ativo, forma_farmaceutica, dosagem_concentracao, apresentacao, categoria_id, preco_custo, preco_venda, estoque_minimo, ativo)
        SELECT 'Amoxicilina 500mg','Amoxicilina','Cápsula','500mg','Cx c/ 15 cáps',2,3.00,12.00,10,1
        WHERE NOT EXISTS (SELECT 1 FROM farmacia_medicamentos WHERE nome = 'Amoxicilina 500mg')",

    'Estoque: Dipirona' => "INSERT INTO farmacia_estoque (medicamento_id, lote, data_validade, quantidade_atual, quantidade_inicial, preco_custo_lote)
        SELECT m.id,'L2025001','2026-12-31',100,100,0.50 FROM farmacia_medicamentos m WHERE m.nome='Dipirona 500mg'
        AND NOT EXISTS (SELECT 1 FROM farmacia_estoque e WHERE e.medicamento_id=m.id AND e.lote='L2025001')",

    'Estoque: Paracetamol' => "INSERT INTO farmacia_estoque (medicamento_id, lote, data_validade, quantidade_atual, quantidade_inicial, preco_custo_lote)
        SELECT m.id,'L2025002','2026-10-31',80,80,0.60 FROM farmacia_medicamentos m WHERE m.nome='Paracetamol 750mg'
        AND NOT EXISTS (SELECT 1 FROM farmacia_estoque e WHERE e.medicamento_id=m.id AND e.lote='L2025002')",

    'Estoque: Amoxicilina' => "INSERT INTO farmacia_estoque (medicamento_id, lote, data_validade, quantidade_atual, quantidade_inicial, preco_custo_lote)
        SELECT m.id,'L2025003','2026-06-30',50,50,3.00 FROM farmacia_medicamentos m WHERE m.nome='Amoxicilina 500mg'
        AND NOT EXISTS (SELECT 1 FROM farmacia_estoque e WHERE e.medicamento_id=m.id AND e.lote='L2025003')",

    'Cliente de teste' => "INSERT INTO farmacia_clientes (nome_completo, cpf, celular, cidade, estado, ativo)
        SELECT 'Maria Silva (Teste)','000.000.000-00','(11) 99999-0000','São Paulo','SP',1
        WHERE NOT EXISTS (SELECT 1 FROM farmacia_clientes WHERE cpf='000.000.000-00')",
];

foreach ($seedsSQL as $nome => $sql) {
    executarSQL($db, "Seed: $nome", $sql, $resultados, $erros);
}

// Contar tabelas criadas
$totalTabelas = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE 'farmacia_%'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Migração DB - Farmácia Mão Amiga</title>
    <style>
        body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 30px; }
        h1 { color: #38bdf8; }
        h2 { color: #94a3b8; border-bottom: 1px solid #334155; padding-bottom: 8px; }
        .ok { color: #4ade80; }
        .err { color: #f87171; }
        .box { background: #1e293b; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .stat { font-size: 2rem; color: #38bdf8; font-weight: bold; }
        .warn { background: #7c2d12; color: #fed7aa; padding: 15px; border-radius: 8px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🏥 Migração DB - Farmácia Mão Amiga</h1>
    <p>Banco: <strong>projetos</strong> | Host: <strong>66.179.191.53</strong> | Prefixo: <strong>farmacia_</strong></p>

    <div class="box">
        <span class="stat"><?= $totalTabelas ?></span>
        <span> tabelas com prefixo farmacia_ no banco</span>
    </div>

    <h2>✅ Operações concluídas (<?= count($resultados) ?>)</h2>
    <div class="box">
        <?php foreach ($resultados as $r): ?>
            <div class="ok"><?= htmlspecialchars($r) ?></div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($erros)): ?>
    <h2>❌ Erros (<?= count($erros) ?>)</h2>
    <div class="box">
        <?php foreach ($erros as $e): ?>
            <div class="err"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="warn">
        ⚠️ <strong>IMPORTANTE:</strong> Delete este arquivo do servidor após confirmar que tudo funcionou!<br>
        <code>rm migrate_db.php</code>
    </div>
</body>
</html>
</php>
