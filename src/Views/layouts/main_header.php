<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmácia Comunitária</title>
    <!-- Você pode adicionar um link para um arquivo CSS aqui -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; color: #333; }
        .container { width: 90%; margin: auto; overflow: hidden; padding: 20px; background-color: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        header { background: #333; color: #fff; padding: 1rem 0; text-align: center; }
        header h1 { margin: 0; }
        nav ul { padding: 0; list-style: none; text-align: center; background: #444; margin-bottom: 20px;}
        nav ul li { display: inline; }
        nav ul li a { color: #fff; padding: 10px 15px; text-decoration: none; display: inline-block; }
        nav ul li a:hover { background: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .actions a { margin-right: 5px; text-decoration: none; }
        .actions .edit { color: blue; }
        .actions .delete { color: red; }
        .btn {
            display: inline-block;
            background: #5cb85c;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .btn-danger { background: #d9534f; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Para incluir padding e border na largura total */
        }
        footer { text-align: center; padding: 20px; margin-top: 20px; background: #333; color: #fff; }
    </style>
</head>
<body>
    <header>
        <h1>Sistema de Controle de Farmácia Comunitária</h1>
    </header>
    <nav>
        <ul>
            <li><a href="index.php?action=home">Início</a></li>
            <li><a href="index.php?action=listarMedicamentos">Medicamentos</a></li>
            <li><a href="#">Clientes</a></li>
            <li><a href="#">Estoque</a></li>
            <li><a href="#">Saídas</a></li>
            <li><a href="#">Relatórios</a></li>
            <li><a href="#">Usuários</a></li>
        </ul>
    </nav>
    <div class="container">