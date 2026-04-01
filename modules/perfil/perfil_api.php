<?php
session_start();
ob_start();
require_once __DIR__ . '/../../config/database.php';

ob_end_clean(); // descartar qualquer saída capturada (notices/warnings) antes do JSON
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$usuario_id = $_SESSION['usuario_id'];

try {
    switch ($action) {
        case 'atualizar_dados':
            atualizarDados($usuario_id);
            break;
        case 'alterar_senha':
            alterarSenha($usuario_id);
            break;
        case 'estatisticas':
            getEstatisticas($usuario_id);
            break;
        case 'upload_avatar':
            uploadAvatar($usuario_id);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function atualizarDados($usuario_id) {
    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $crf = trim($_POST['crf'] ?? '');
    
    if (empty($nome_completo)) {
        echo json_encode(['success' => false, 'error' => 'Nome completo é obrigatório']);
        return;
    }
    
    // Validar email se fornecido
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'E-mail inválido']);
        return;
    }
    
    // Verificar se email já está em uso por outro usuário
    if (!empty($email)) {
        $emailExiste = fetchColumn("SELECT COUNT(*) FROM " . tableName('usuarios') . " WHERE email = ? AND id != ?", [$email, $usuario_id]);
        if ($emailExiste > 0) {
            echo json_encode(['success' => false, 'error' => 'E-mail já está em uso']);
            return;
        }
    }
    
    // Atualizar dados
    $sql = "UPDATE " . tableName('usuarios') . " SET 
            nome_completo = ?, 
            email = ?, 
            telefone = ?, 
            crf = ?,
            updated_at = NOW()
            WHERE id = ?";
    
    execute($sql, [$nome_completo, $email ?: null, $telefone ?: null, $crf ?: null, $usuario_id]);
    
    // Atualizar sessão
    $_SESSION['nome_completo'] = $nome_completo;
    
    // Log
    logAction('ATUALIZAR_PERFIL', 'usuarios', $usuario_id, "Dados pessoais atualizados");
    
    echo json_encode(['success' => true, 'message' => 'Dados atualizados com sucesso']);
}

function alterarSenha($usuario_id) {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $senha_nova = $_POST['senha_nova'] ?? '';
    
    if (empty($senha_atual) || empty($senha_nova)) {
        echo json_encode(['success' => false, 'error' => 'Preencha todos os campos']);
        return;
    }
    
    if (strlen($senha_nova) < 6) {
        echo json_encode(['success' => false, 'error' => 'A nova senha deve ter no mínimo 6 caracteres']);
        return;
    }
    
    // Verificar senha atual
    $usuario = fetchOne("SELECT senha FROM " . tableName('usuarios') . " WHERE id = ?", [$usuario_id]);
    
    if (!password_verify($senha_atual, $usuario['senha'])) {
        echo json_encode(['success' => false, 'error' => 'Senha atual incorreta']);
        return;
    }
    
    // Atualizar senha
    $nova_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
    execute("UPDATE " . tableName('usuarios') . " SET senha = ?, updated_at = NOW() WHERE id = ?", [$nova_hash, $usuario_id]);
    
    // Log
    logAction('ALTERAR_SENHA', 'usuarios', $usuario_id, "Senha alterada");
    
    echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso']);
}

function getEstatisticas($usuario_id) {
    $stats = [
        'dispensacoes' => fetchColumn("SELECT COUNT(*) FROM " . tableName('dispensacoes') . " WHERE usuario_id = ?", [$usuario_id]) ?: 0,
        'doacoes' => fetchColumn("SELECT COUNT(*) FROM " . tableName('doacoes') . " WHERE usuario_id = ?", [$usuario_id]) ?: 0,
        'membro_desde' => fetchColumn("SELECT DATE_FORMAT(created_at, '%d/%m/%Y') FROM " . tableName('usuarios') . " WHERE id = ?", [$usuario_id])
    ];
    
    echo json_encode($stats);
}

function uploadAvatar($usuario_id) {
    if (!isset($_FILES['avatar'])) {
        echo json_encode(['success' => false, 'error' => 'Arquivo não enviado']);
        return;
    }
    $file = $_FILES['avatar'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Falha no upload (código '.$file['error'].')']);
        return;
    }
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        echo json_encode(['success' => false, 'error' => 'Arquivo excede 5MB']);
        return;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png'])) {
        echo json_encode(['success' => false, 'error' => 'Formato inválido. Use JPG ou PNG']);
        return;
    }
    $targetDir = __DIR__ . '/../../assets/images/avatar/';
    if (!is_dir($targetDir)) {
        // tentar criar diretório se não existir
        @mkdir($targetDir, 0775, true);
        if (!is_dir($targetDir)) {
            echo json_encode(['success' => false, 'error' => 'Diretório de upload indisponível']);
            return;
        }
    }
    $dest = $targetDir . $usuario_id . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
    // remover outros formatos antigos para evitar duplicidade
    @unlink($targetDir . $usuario_id . '.jpg');
    @unlink($targetDir . $usuario_id . '.png');
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['success' => false, 'error' => 'Não foi possível salvar o arquivo']);
        return;
    }
    // Log
    logAction('UPLOAD_AVATAR', 'usuarios', $usuario_id, 'Avatar atualizado');
    echo json_encode(['success' => true]);
}

function logAction($acao, $tabela, $registro_id, $detalhes = null) {
    try {
        $sql = "INSERT INTO " . tableName('logs_sistema') . " (usuario_id, acao, tabela, registro_id, dados_novos, ip_address, data_log)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        execute($sql, [
            $_SESSION['usuario_id'],
            $acao,
            $tabela,
            $registro_id,
            $detalhes,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Exception $e) {
        // Silently fail on log errors
    }
}
?>
