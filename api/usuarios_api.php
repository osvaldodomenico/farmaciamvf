<?php
session_start();
require_once '../config/database.php';

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

// Verificar permissão
if (!in_array($_SESSION['nivel_acesso'], ['admin', 'gerente'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Sem permissão']);
    exit();
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Listar todos os usuários
            $usuarios = fetchAll("
                SELECT 
                    id, 
                    nome_completo, 
                    cpf, 
                    login, 
                    email, 
                    telefone, 
                    nivel_acesso, 
                    crf,
                    ativo,
                    created_at
                FROM " . tableName('usuarios') . "
                ORDER BY nome_completo ASC
            ");
            echo json_encode(['success' => true, 'data' => $usuarios]);
            break;
            
        case 'get':
            // Buscar um usuário específico
            $id = $_GET['id'] ?? 0;
            $usuario = fetchOne("SELECT * FROM " . tableName('usuarios') . " WHERE id = ?", [$id]);
            
            if ($usuario) {
                // Remover senha do retorno
                unset($usuario['senha']);
                echo json_encode(['success' => true, 'data' => $usuario]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Usuário não encontrado']);
            }
            break;
            
        case 'check_login':
            // Verificar se login está disponível
            $login = $_GET['login'] ?? '';
            $id = $_GET['id'] ?? 0;
            
            if ($id > 0) {
                $existe = fetchOne("SELECT id FROM " . tableName('usuarios') . " WHERE login = ? AND id != ?", [$login, $id]);
            } else {
                $existe = fetchOne("SELECT id FROM " . tableName('usuarios') . " WHERE login = ?", [$login]);
            }
            
            echo json_encode(['disponivel' => !$existe]);
            break;
            
        case 'check_cpf':
            // Verificar se CPF está disponível
            $cpf = $_GET['cpf'] ?? '';
            $id = $_GET['id'] ?? 0;
            
            if ($id > 0) {
                $existe = fetchOne("SELECT id FROM " . tableName('usuarios') . " WHERE cpf = ? AND id != ?", [$cpf, $id]);
            } else {
                $existe = fetchOne("SELECT id FROM " . tableName('usuarios') . " WHERE cpf = ?", [$cpf]);
            }
            
            echo json_encode(['disponivel' => !$existe]);
            break;
            
        case 'toggle_status':
            // Ativar/Desativar usuário
            if ($_SESSION['nivel_acesso'] != 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Apenas administradores podem alterar status']);
                exit();
            }
            
            $id = $_POST['id'] ?? 0;
            $usuario = fetchOne("SELECT * FROM " . tableName('usuarios') . " WHERE id = ?", [$id]);
            
            if (!$usuario) {
                http_response_code(404);
                echo json_encode(['error' => 'Usuário não encontrado']);
                exit();
            }
            
            if ($id == $_SESSION['usuario_id']) {
                http_response_code(400);
                echo json_encode(['error' => 'Você não pode alterar seu próprio status']);
                exit();
            }
            
            $novo_status = $usuario['ativo'] ? 0 : 1;
            update(tableName('usuarios'), ['ativo' => $novo_status], 'id = ?', [$id]);
            
            registrarLog(
                $_SESSION['usuario_id'],
                $novo_status ? 'Ativou usuário' : 'Desativou usuário',
                'usuarios',
                $id,
                ['ativo' => $usuario['ativo']],
                ['ativo' => $novo_status]
            );
            
            echo json_encode([
                'success' => true, 
                'message' => 'Status atualizado com sucesso',
                'novo_status' => $novo_status
            ]);
            break;
            
        case 'estatisticas':
            // Estatísticas gerais de usuários
            $stats = [
                'total' => fetchColumn("SELECT COUNT(*) FROM " . tableName('usuarios')),
                'ativos' => fetchColumn("SELECT COUNT(*) FROM " . tableName('usuarios') . " WHERE ativo = 1"),
                'inativos' => fetchColumn("SELECT COUNT(*) FROM " . tableName('usuarios') . " WHERE ativo = 0"),
                'por_nivel' => fetchAll("
                    SELECT 
                        nivel_acesso,
                        COUNT(*) as total
                    FROM " . tableName('usuarios') . "
                    WHERE ativo = 1
                    GROUP BY nivel_acesso
                ")
            ];
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'ultimos_acessos':
            // Últimos acessos dos usuários
            $limite = $_GET['limite'] ?? 10;
            $acessos = fetchAll("
                SELECT 
                    u.nome_completo,
                    u.ultimo_acesso,
                    u.nivel_acesso
                FROM " . tableName('usuarios') . " u
                WHERE u.ativo = 1
                AND u.ultimo_acesso IS NOT NULL
                ORDER BY u.ultimo_acesso DESC
                LIMIT ?
            ", [$limite]);
            
            echo json_encode(['success' => true, 'data' => $acessos]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Erro na API de usuários: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
}
