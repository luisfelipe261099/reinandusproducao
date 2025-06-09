<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferramentas de Correção - Faciência ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #1a202c;
        }
        
        .card-description {
            color: #4a5568;
            margin-bottom: 20px;
        }
        
        .tool-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        @media (min-width: 640px) {
            .tool-list {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .tool-item {
            background-color: #f7fafc;
            border-radius: 6px;
            padding: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .tool-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .tool-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .tool-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #2d3748;
        }
        
        .tool-description {
            font-size: 0.875rem;
            color: #4a5568;
            margin-bottom: 10px;
        }
        
        .tool-link {
            display: inline-block;
            background-color: #4299e1;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.875rem;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .tool-link:hover {
            background-color: #3182ce;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #4299e1;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container">
        <div class="card">
            <div class="card-title">Ferramentas de Correção</div>
            <div class="card-description">
                Estas ferramentas ajudam a corrigir problemas de layout e estrutura no sistema Faciência ERP.
            </div>
            
            <div class="tool-list">
                <div class="tool-item">
                    <div class="tool-icon" style="background-color: #ebf8ff; color: #3182ce;">
                        <i class="fas fa-file-code"></i>
                    </div>
                    <div class="tool-title">Teste de Layout Global</div>
                    <div class="tool-description">Verifica se as correções de CSS foram aplicadas globalmente.</div>
                    <a href="teste_layout_global.php" class="tool-link">Acessar</a>
                </div>
                
                <div class="tool-item">
                    <div class="tool-icon" style="background-color: #e6fffa; color: #319795;">
                        <i class="fas fa-code"></i>
                    </div>
                    <div class="tool-title">Incluir CSS em Todas as Páginas</div>
                    <div class="tool-description">Adiciona os arquivos CSS necessários em todas as páginas do sistema.</div>
                    <a href="include_css.php" class="tool-link">Executar</a>
                </div>
                
                <div class="tool-item">
                    <div class="tool-icon" style="background-color: #faf5ff; color: #805ad5;">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="tool-title">Teste de Menu do Polo</div>
                    <div class="tool-description">Testa o novo layout do menu lateral no módulo Polo.</div>
                    <a href="polo/menu_teste.php" class="tool-link">Acessar</a>
                </div>
                
                <div class="tool-item">
                    <div class="tool-icon" style="background-color: #fff5f5; color: #e53e3e;">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <div class="tool-title">Teste de Menu do AVA</div>
                    <div class="tool-description">Testa o novo layout do menu lateral no módulo AVA.</div>
                    <a href="ava/menu_teste.php" class="tool-link">Acessar</a>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title">Páginas do Sistema</div>
            <div class="card-description">
                Acesse as principais páginas do sistema.
            </div>
            
            <div class="tool-list">
                <div class="tool-item">
                    <div class="tool-icon" style="background-color: #fffaf0; color: #dd6b20;">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="tool-title">Página Inicial</div>
                    <div class="tool-description">Acesse a página inicial do sistema.</div>
                    <a href="index.php" class="tool-link">Acessar</a>
                </div>
                
                <div class="tool-item">
                    <div class="tool-icon" style="background-color: #f0fff4; color: #38a169;">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="tool-title">Módulo Polo</div>
                    <div class="tool-description">Acesse o módulo de gerenciamento de polos.</div>
                    <a href="polo/index.php" class="tool-link">Acessar</a>
                </div>
                
                <div class="tool-item">
                    <div class="tool-icon" style="background-color: #ebf8ff; color: #3182ce;">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="tool-title">Módulo AVA</div>
                    <div class="tool-description">Acesse o ambiente virtual de aprendizagem.</div>
                    <a href="ava/dashboard.php" class="tool-link">Acessar</a>
                </div>
                
                <div class="tool-item">
                    <div class="tool-icon" style="background-color: #fff5f7; color: #d53f8c;">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="tool-title">Módulo Financeiro</div>
                    <div class="tool-description">Acesse o módulo de gerenciamento financeiro.</div>
                    <a href="financeiro/index.php" class="tool-link">Acessar</a>
                </div>
            </div>
        </div>
        
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left mr-1"></i> Voltar para a página inicial
        </a>
    </div>
</body>
</html>
