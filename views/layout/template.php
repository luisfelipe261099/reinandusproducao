<?php
/**
 * Layout Template para o Sistema de Documentos
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo_pagina ?? 'Sistema de Documentos'); ?></title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/custom.css">
</head>
<body>
    <div class="container-fluid">
        <header>
            <!-- Cabeçalho do sistema -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <a class="navbar-brand" href="index.php">Sistema de Documentos</a>
                <!-- Adicione menu de navegação aqui -->
            </nav>
        </header>

        <main>
            <div class="row">
                <!-- Menu lateral, se aplicável -->
                <?php if (isset($mostrar_menu) && $mostrar_menu): ?>
                <div class="col-md-3">
                    <!-- Inclua o menu lateral -->
                    <?php include 'menu_lateral.php'; ?>
                </div>
                <div class="col-md-9">
                <?php else: ?>
                <div class="col-md-12">
                <?php endif; ?>
                    <!-- Título da página -->
                    <h1 class="my-4"><?php echo htmlspecialchars($titulo_pagina ?? 'Página sem Título'); ?></h1>

                    <!-- Área de mensagens -->
                    <?php if (!empty($mensagens_erro)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($mensagens_erro as $erro): ?>
                                <p><?php echo htmlspecialchars($erro); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['mensagem'])): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['mensagem_tipo'] ?? 'info'); ?>">
                            <?php 
                            echo htmlspecialchars($_SESSION['mensagem']); 
                            unset($_SESSION['mensagem'], $_SESSION['mensagem_tipo']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Conteúdo principal -->
                    <?php
                    // Inclui a view específica baseada na ação
                    if (!empty($view)) {
                        $view_path = "views/documentos/{$view}.php";
                        if (file_exists($view_path)) {
                            include $view_path;
                        } else {
                            echo "<div class='alert alert-warning'>View não encontrada: " . htmlspecialchars($view_path) . "</div>";
                        }
                    }
                    ?>
                </div>
            </div>
        </main>

        <footer class="mt-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Sistema de Documentos</p>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/custom.js"></script>
</body>
</html>