<?php
session_start(); // Necessário para acessar e destruir a sessão

// Desfaz todas as variáveis de sessão
$_SESSION = array();

// Se é desejado destruir a sessão completamente, apague também o cookie de sessão.
// Nota: Isso destruirá a sessão, e não apenas os dados de sessão!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destrói a sessão
session_destroy();

// Redirecionar para a página de login com uma mensagem (opcional)
header("Location: login.php?logout=success");
exit();
?>