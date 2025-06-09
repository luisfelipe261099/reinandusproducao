<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Layout - Faciência ERP</title>
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
        
        .page-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        @media (min-width: 640px) {
            .page-list {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .page-item {
            background-color: #f7fafc;
            border-radius: 6px;
            padding: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .page-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .page-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .page-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #2d3748;
        }
        
        .page-description {
            font-size: 0.875rem;
            color: #4a5568;
            margin-bottom: 10px;
        }
        
        .page-link {
            display: inline-block;
            background-color: #4299e1;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.875rem;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .page-link:hover {
            background-color: #3182ce;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #c6f6d5;
            border-left: 4px solid #38a169;
            color: #2f855a;
        }
        
        .alert-info {
            background-color: #bee3f8;
            border-left: 4px solid #3182ce;
            color: #2c5282;
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
            <div class="card-title">Verificação de Layout</div>
            <div class="alert alert-success">
                <strong>Correção Aplicada!</strong> As correções de layout foram aplicadas diretamente no arquivo CSS principal (styles.css). Todas as páginas que usam este arquivo agora devem exibir o menu lateral corretamente.
            </div>
            <div class="card-description">
                Acesse as páginas abaixo para verificar se o menu lateral está sendo exibido corretamente, sem sobrepor o conteúdo principal.
            </div>
            
            <div class="page-list">
                <div class="page-item">
                    <div class="page-icon" style="background-color: #ebf8ff; color: #3182ce;">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="page-title">Dashboard do Polo</div>
                    <div class="page-description">Página inicial do módulo de Polo.</div>
                    <a href="polo/index.php" class="page-link" target="_blank">Verificar</a>
                </div>
                
                <div class="page-item">
                    <div class="page-icon" style="background-color: #e6fffa; color: #319795;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="page-title">Documentos</div>
                    <div class="page-description">Página de documentos do Polo.</div>
                    <a href="polo/documentos.php" class="page-link" target="_blank">Verificar</a>
                </div>
                
                <div class="page-item">
                    <div class="page-icon" style="background-color: #faf5ff; color: #805ad5;">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="page-title">Matrículas</div>
                    <div class="page-description">Página de matrículas do Polo.</div>
                    <a href="polo/matriculas.php" class="page-link" target="_blank">Verificar</a>
                </div>
                
                <div class="page-item">
                    <div class="page-icon" style="background-color: #fff5f5; color: #e53e3e;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="page-title">Turmas</div>
                    <div class="page-description">Página de turmas do Polo.</div>
                    <a href="polo/turmas.php" class="page-link" target="_blank">Verificar</a>
                </div>
                
                <div class="page-item">
                    <div class="page-icon" style="background-color: #fffaf0; color: #dd6b20;">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="page-title">Cursos</div>
                    <div class="page-description">Página de cursos do Polo.</div>
                    <a href="polo/cursos.php" class="page-link" target="_blank">Verificar</a>
                </div>
                
                <div class="page-item">
                    <div class="page-icon" style="background-color: #f0fff4; color: #38a169;">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="page-title">Dashboard do AVA</div>
                    <div class="page-description">Página inicial do Ambiente Virtual de Aprendizagem.</div>
                    <a href="ava/dashboard.php" class="page-link" target="_blank">Verificar</a>
                </div>
                
                <div class="page-item">
                    <div class="page-icon" style="background-color: #ebf8ff; color: #3182ce;">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="page-title">Cursos do AVA</div>
                    <div class="page-description">Página de cursos do AVA.</div>
                    <a href="ava/cursos.php" class="page-link" target="_blank">Verificar</a>
                </div>
                
                <div class="page-item">
                    <div class="page-icon" style="background-color: #fff5f7; color: #d53f8c;">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="page-title">Novo Curso</div>
                    <div class="page-description">Página de criação de novo curso no AVA.</div>
                    <a href="ava/cursos_novo.php" class="page-link" target="_blank">Verificar</a>
                </div>
                
                <div class="page-item">
                    <div class="page-icon" style="background-color: #e6fffa; color: #319795;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="page-title">Alunos do AVA</div>
                    <div class="page-description">Página de alunos do AVA.</div>
                    <a href="ava/alunos.php" class="page-link" target="_blank">Verificar</a>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title">O que foi corrigido?</div>
            <div class="card-description">
                <p>As seguintes correções foram aplicadas ao arquivo <code>css/styles.css</code>:</p>
                <ul>
                    <li>Ajuste do z-index do menu lateral para ficar atrás do conteúdo principal</li>
                    <li>Definição de margens e larguras corretas para o conteúdo principal</li>
                    <li>Ajuste do posicionamento quando o menu está recolhido</li>
                    <li>Correções para dispositivos móveis</li>
                    <li>Melhoria na estrutura do menu para permitir rolagem adequada</li>
                </ul>
            </div>
            <div class="alert alert-info">
                <strong>Dica:</strong> Se alguma página ainda apresentar problemas, pode ser necessário limpar o cache do navegador (Ctrl+F5) para carregar a versão mais recente do CSS.
            </div>
        </div>
        
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left mr-1"></i> Voltar para a página inicial
        </a>
    </div>
</body>
</html>
