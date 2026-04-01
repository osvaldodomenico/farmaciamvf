<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    try {
        execute("UPDATE " . tableName('clientes') . " SET ativo = 0 WHERE id = ?", [$id]);
        registrarLog($_SESSION['usuario_id'], 'desativou_cliente', 'clientes', $id, "Cliente ID: $id");
        $_SESSION['success'] = 'Cliente desativado com sucesso!';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erro ao desativar cliente: ' . $e->getMessage();
    }
}

header('Location: listar.php');
exit;
