<?php
// Inicializa o sistema
require_once __DIR__ . '/includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Instancia o banco de dados
$db = Database::getInstance();

// Verifica as permissões do usuário
if (!Auth::hasPermission('relatorios', 'visualizar')) {
    $_SESSION['mensagem'] = 'Você não tem permissão para acessar esta página.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: index.php');
    exit;
}

// Obtém o tipo de relatório e o formato de exportação
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$formato = isset($_GET['formato']) ? $_GET['formato'] : '';

// Verifica se os parâmetros são válidos
if (empty($tipo) || empty($formato) || !in_array($formato, ['excel', 'pdf'])) {
    $_SESSION['mensagem'] = 'Parâmetros inválidos para exportação.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: relatorios.php');
    exit;
}

// Prepara os dados para exportação com base no tipo de relatório
$dados = [];
$colunas = [];
$titulo = '';

switch ($tipo) {
    case 'desempenho':
        // Obtém os filtros
        $filtro_curso = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
        $filtro_polo = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : 0;
        $filtro_turma = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
        $filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'ultimo_semestre';
        $filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
        $filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

        // Define as datas com base no período selecionado
        if (empty($filtro_data_inicio) || empty($filtro_data_fim)) {
            switch ($filtro_periodo) {
                case 'ultimo_mes':
                    $filtro_data_inicio = date('Y-m-d', strtotime('-1 month'));
                    $filtro_data_fim = date('Y-m-d');
                    break;
                case 'ultimo_trimestre':
                    $filtro_data_inicio = date('Y-m-d', strtotime('-3 months'));
                    $filtro_data_fim = date('Y-m-d');
                    break;
                case 'ultimo_semestre':
                    $filtro_data_inicio = date('Y-m-d', strtotime('-6 months'));
                    $filtro_data_fim = date('Y-m-d');
                    break;
                case 'ultimo_ano':
                    $filtro_data_inicio = date('Y-m-d', strtotime('-1 year'));
                    $filtro_data_fim = date('Y-m-d');
                    break;
                case 'personalizado':
                    // Mantém as datas informadas
                    break;
            }
        }

        // Constrói a consulta SQL para o relatório de desempenho
        $sql_desempenho = "SELECT 
                            c.nome as curso_nome,
                            p.nome as polo_nome,
                            t.nome as turma_nome,
                            COUNT(DISTINCT m.id) as total_matriculas,
                            COUNT(DISTINCT CASE WHEN m.status = 'ativo' THEN m.id END) as matriculas_ativas,
                            COUNT(DISTINCT CASE WHEN m.status = 'concluído' THEN m.id END) as matriculas_concluidas,
                            COUNT(DISTINCT CASE WHEN m.status = 'trancado' THEN m.id END) as matriculas_trancadas,
                            COUNT(DISTINCT CASE WHEN m.status = 'cancelado' THEN m.id END) as matriculas_canceladas,
                            AVG(nd.nota) as media_notas,
                            AVG(nd.frequencia) as media_frequencia
                        FROM matriculas m
                        JOIN cursos c ON m.curso_id = c.id
                        JOIN polos p ON m.polo_id = p.id
                        LEFT JOIN turmas t ON m.turma_id = t.id
                        LEFT JOIN notas_disciplinas nd ON m.id = nd.matricula_id
                        WHERE m.data_matricula BETWEEN ? AND ?";

        $params_desempenho = [$filtro_data_inicio, $filtro_data_fim];

        // Aplica os filtros
        if ($filtro_curso > 0) {
            $sql_desempenho .= " AND c.id = ?";
            $params_desempenho[] = $filtro_curso;
        }

        if ($filtro_polo > 0) {
            $sql_desempenho .= " AND p.id = ?";
            $params_desempenho[] = $filtro_polo;
        }

        if ($filtro_turma > 0) {
            $sql_desempenho .= " AND t.id = ?";
            $params_desempenho[] = $filtro_turma;
        }

        $sql_desempenho .= " GROUP BY c.id, p.id, t.id ORDER BY c.nome, p.nome, t.nome";

        // Executa a consulta
        $dados_desempenho = $db->fetchAll($sql_desempenho, $params_desempenho);

        // Define as colunas para o relatório
        $colunas = [
            'Curso', 'Polo', 'Turma', 'Total Matrículas', 'Matrículas Ativas', 
            'Matrículas Concluídas', 'Média Notas', 'Média Frequência'
        ];

        // Formata os dados para exportação
        foreach ($dados_desempenho as $dado) {
            $dados[] = [
                $dado['curso_nome'],
                $dado['polo_nome'],
                $dado['turma_nome'] ?: 'N/A',
                $dado['total_matriculas'],
                $dado['matriculas_ativas'] . ' (' . ($dado['total_matriculas'] > 0 ? round(($dado['matriculas_ativas'] / $dado['total_matriculas']) * 100, 1) : 0) . '%)',
                $dado['matriculas_concluidas'] . ' (' . ($dado['total_matriculas'] > 0 ? round(($dado['matriculas_concluidas'] / $dado['total_matriculas']) * 100, 1) : 0) . '%)',
                $dado['media_notas'] !== null ? number_format($dado['media_notas'], 1, ',', '.') : 'N/A',
                $dado['media_frequencia'] !== null ? number_format($dado['media_frequencia'], 1, ',', '.') . '%' : 'N/A'
            ];
        }

        $titulo = 'Relatório de Desempenho Acadêmico';
        break;

    case 'estatisticas':
        // Obtém estatísticas de alunos
        $sql_alunos = "SELECT 
                        COUNT(*) as total_alunos,
                        COUNT(CASE WHEN status = 'ativo' THEN 1 END) as alunos_ativos,
                        COUNT(CASE WHEN status = 'inativo' THEN 1 END) as alunos_inativos,
                        COUNT(CASE WHEN sexo = 'M' THEN 1 END) as alunos_masculino,
                        COUNT(CASE WHEN sexo = 'F' THEN 1 END) as alunos_feminino
                    FROM alunos";
        $estatisticas_alunos = $db->fetchOne($sql_alunos);

        // Obtém estatísticas de matrículas
        $sql_matriculas = "SELECT 
                            COUNT(*) as total_matriculas,
                            COUNT(CASE WHEN status = 'ativo' THEN 1 END) as matriculas_ativas,
                            COUNT(CASE WHEN status = 'concluído' THEN 1 END) as matriculas_concluidas,
                            COUNT(CASE WHEN status = 'trancado' THEN 1 END) as matriculas_trancadas,
                            COUNT(CASE WHEN status = 'cancelado' THEN 1 END) as matriculas_canceladas
                        FROM matriculas";
        $estatisticas_matriculas = $db->fetchOne($sql_matriculas);

        // Define as colunas para o relatório
        $colunas = ['Categoria', 'Total', 'Percentual'];

        // Formata os dados para exportação
        $dados[] = ['ALUNOS', '', ''];
        $dados[] = ['Total de Alunos', $estatisticas_alunos['total_alunos'], '100%'];
        $dados[] = ['Alunos Ativos', $estatisticas_alunos['alunos_ativos'], 
                   ($estatisticas_alunos['total_alunos'] > 0 ? 
                   number_format(($estatisticas_alunos['alunos_ativos'] / $estatisticas_alunos['total_alunos']) * 100, 1) : 0) . '%'];
        $dados[] = ['Alunos Inativos', $estatisticas_alunos['alunos_inativos'], 
                   ($estatisticas_alunos['total_alunos'] > 0 ? 
                   number_format(($estatisticas_alunos['alunos_inativos'] / $estatisticas_alunos['total_alunos']) * 100, 1) : 0) . '%'];
        $dados[] = ['Alunos Masculino', $estatisticas_alunos['alunos_masculino'], 
                   ($estatisticas_alunos['total_alunos'] > 0 ? 
                   number_format(($estatisticas_alunos['alunos_masculino'] / $estatisticas_alunos['total_alunos']) * 100, 1) : 0) . '%'];
        $dados[] = ['Alunos Feminino', $estatisticas_alunos['alunos_feminino'], 
                   ($estatisticas_alunos['total_alunos'] > 0 ? 
                   number_format(($estatisticas_alunos['alunos_feminino'] / $estatisticas_alunos['total_alunos']) * 100, 1) : 0) . '%'];

        $dados[] = ['', '', ''];
        $dados[] = ['MATRÍCULAS', '', ''];
        $dados[] = ['Total de Matrículas', $estatisticas_matriculas['total_matriculas'], '100%'];
        $dados[] = ['Matrículas Ativas', $estatisticas_matriculas['matriculas_ativas'], 
                   ($estatisticas_matriculas['total_matriculas'] > 0 ? 
                   number_format(($estatisticas_matriculas['matriculas_ativas'] / $estatisticas_matriculas['total_matriculas']) * 100, 1) : 0) . '%'];
        $dados[] = ['Matrículas Concluídas', $estatisticas_matriculas['matriculas_concluidas'], 
                   ($estatisticas_matriculas['total_matriculas'] > 0 ? 
                   number_format(($estatisticas_matriculas['matriculas_concluidas'] / $estatisticas_matriculas['total_matriculas']) * 100, 1) : 0) . '%'];
        $dados[] = ['Matrículas Trancadas', $estatisticas_matriculas['matriculas_trancadas'], 
                   ($estatisticas_matriculas['total_matriculas'] > 0 ? 
                   number_format(($estatisticas_matriculas['matriculas_trancadas'] / $estatisticas_matriculas['total_matriculas']) * 100, 1) : 0) . '%'];
        $dados[] = ['Matrículas Canceladas', $estatisticas_matriculas['matriculas_canceladas'], 
                   ($estatisticas_matriculas['total_matriculas'] > 0 ? 
                   number_format(($estatisticas_matriculas['matriculas_canceladas'] / $estatisticas_matriculas['total_matriculas']) * 100, 1) : 0) . '%'];

        $titulo = 'Relatório de Estatísticas';
        break;

    case 'documentos':
        // Obtém os filtros
        $filtro_polo = isset($_GET['polo_id']) ? (int)$_GET['polo_id'] : 0;
        $filtro_tipo_documento = isset($_GET['tipo_documento_id']) ? (int)$_GET['tipo_documento_id'] : 0;
        $filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-6 months'));
        $filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

        // Constrói a consulta SQL para o relatório de documentos
        $sql_documentos = "SELECT 
                            p.nome as polo_nome,
                            td.nome as tipo_documento,
                            COUNT(cd.id) as total_solicitados,
                            COUNT(CASE WHEN cd.status IN ('pronto', 'entregue') THEN cd.id END) as total_emitidos,
                            COUNT(CASE WHEN cd.status IN ('solicitado', 'processando') THEN cd.id END) as total_pendentes,
                            SUM(cd.valor_total) as valor_total
                        FROM chamados_documentos cd
                        JOIN polos p ON cd.polo_id = p.id
                        JOIN tipos_documentos td ON cd.tipo_documento_id = td.id
                        WHERE cd.created_at BETWEEN ? AND ?";

        $params_documentos = [$filtro_data_inicio . ' 00:00:00', $filtro_data_fim . ' 23:59:59'];

        // Aplica os filtros
        if ($filtro_polo > 0) {
            $sql_documentos .= " AND p.id = ?";
            $params_documentos[] = $filtro_polo;
        }

        if ($filtro_tipo_documento > 0) {
            $sql_documentos .= " AND td.id = ?";
            $params_documentos[] = $filtro_tipo_documento;
        }

        $sql_documentos .= " GROUP BY p.id, td.id ORDER BY p.nome, td.nome";

        // Executa a consulta
        $dados_documentos = $db->fetchAll($sql_documentos, $params_documentos);

        // Define as colunas para o relatório
        $colunas = [
            'Polo', 'Tipo de Documento', 'Solicitados', 'Emitidos', 'Pendentes', 'Valor Total'
        ];

        // Formata os dados para exportação
        foreach ($dados_documentos as $dado) {
            $dados[] = [
                $dado['polo_nome'],
                $dado['tipo_documento'],
                $dado['total_solicitados'],
                $dado['total_emitidos'],
                $dado['total_pendentes'],
                'R$ ' . number_format($dado['valor_total'], 2, ',', '.')
            ];
        }

        $titulo = 'Relatório de Documentos';
        break;

    case 'chamados':
        // Obtém os filtros
        $filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
        $filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
        $filtro_departamento = isset($_GET['departamento']) ? $_GET['departamento'] : '';
        $filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-6 months'));
        $filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

        // Constrói a consulta SQL para o relatório de chamados
        $sql_chamados = "SELECT 
                            cc.nome as categoria,
                            COUNT(c.id) as total,
                            COUNT(CASE WHEN c.status = 'aberto' THEN c.id END) as abertos,
                            COUNT(CASE WHEN c.status = 'em_andamento' THEN c.id END) as em_andamento,
                            COUNT(CASE WHEN c.status = 'resolvido' THEN c.id END) as resolvidos,
                            AVG(CASE WHEN c.tempo_resolucao IS NOT NULL THEN c.tempo_resolucao ELSE NULL END) / 60 as tempo_medio
                        FROM chamados c
                        JOIN categorias_chamados cc ON c.categoria_id = cc.id
                        WHERE c.data_abertura BETWEEN ? AND ?";

        $params_chamados = [$filtro_data_inicio . ' 00:00:00', $filtro_data_fim . ' 23:59:59'];

        // Aplica os filtros
        if (!empty($filtro_status)) {
            $sql_chamados .= " AND c.status = ?";
            $params_chamados[] = $filtro_status;
        }

        if (!empty($filtro_tipo)) {
            $sql_chamados .= " AND c.tipo = ?";
            $params_chamados[] = $filtro_tipo;
        }

        if (!empty($filtro_departamento)) {
            $sql_chamados .= " AND c.departamento = ?";
            $params_chamados[] = $filtro_departamento;
        }

        $sql_chamados .= " GROUP BY cc.id ORDER BY total DESC";

        // Executa a consulta
        $dados_chamados = $db->fetchAll($sql_chamados, $params_chamados);

        // Define as colunas para o relatório
        $colunas = [
            'Categoria', 'Total', 'Abertos', 'Em Andamento', 'Resolvidos', 'Tempo Médio (horas)'
        ];

        // Formata os dados para exportação
        foreach ($dados_chamados as $dado) {
            $dados[] = [
                $dado['categoria'],
                $dado['total'],
                $dado['abertos'] . ' (' . ($dado['total'] > 0 ? round(($dado['abertos'] / $dado['total']) * 100, 1) : 0) . '%)',
                $dado['em_andamento'] . ' (' . ($dado['total'] > 0 ? round(($dado['em_andamento'] / $dado['total']) * 100, 1) : 0) . '%)',
                $dado['resolvidos'] . ' (' . ($dado['total'] > 0 ? round(($dado['resolvidos'] / $dado['total']) * 100, 1) : 0) . '%)',
                $dado['tempo_medio'] !== null ? number_format($dado['tempo_medio'], 1, ',', '.') : 'N/A'
            ];
        }

        $titulo = 'Relatório de Chamados';
        break;

    default:
        $_SESSION['mensagem'] = 'Tipo de relatório inválido.';
        $_SESSION['mensagem_tipo'] = 'erro';
        header('Location: relatorios.php');
        exit;
}

// Verifica se há dados para exportar
if (empty($dados)) {
    $_SESSION['mensagem'] = 'Não há dados para exportar.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: relatorios.php?tipo=' . $tipo);
    exit;
}

// Exporta os dados no formato solicitado
if ($formato === 'excel') {
    // Define o tipo de conteúdo e cabeçalhos para download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . str_replace(' ', '_', $titulo) . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Inicia a saída do arquivo
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>';
    echo '<body>';
    echo '<table border="1">';
    
    // Título
    echo '<tr><th colspan="' . count($colunas) . '" style="font-size: 16pt; font-weight: bold; text-align: center;">' . $titulo . '</th></tr>';
    
    // Cabeçalho
    echo '<tr>';
    foreach ($colunas as $coluna) {
        echo '<th style="background-color: #DDDDDD; font-weight: bold;">' . htmlspecialchars($coluna) . '</th>';
    }
    echo '</tr>';
    
    // Dados
    foreach ($dados as $linha) {
        echo '<tr>';
        foreach ($linha as $valor) {
            echo '<td>' . htmlspecialchars($valor) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    exit;
} elseif ($formato === 'pdf') {
    // Gera um PDF simples usando HTML e CSS
    // Define o tipo de conteúdo
    header('Content-Type: text/html; charset=utf-8');
    
    // Estilos CSS
    $css = '
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
        @media print {
            body {
                margin: 0;
                padding: 15mm;
            }
            @page {
                size: landscape;
                margin: 10mm;
            }
        }
    </style>
    ';
    
    // Início do HTML
    echo '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($titulo) . '</title>
        ' . $css . '
        <script>
            window.onload = function() {
                window.print();
                setTimeout(function() {
                    window.close();
                }, 500);
            };
        </script>
    </head>
    <body>
        <h1>' . htmlspecialchars($titulo) . '</h1>
        
        <table>
            <thead>
                <tr>';
    
    // Cabeçalho da tabela
    foreach ($colunas as $coluna) {
        echo '<th>' . htmlspecialchars($coluna) . '</th>';
    }
    
    echo '</tr>
            </thead>
            <tbody>';
    
    // Dados da tabela
    foreach ($dados as $linha) {
        echo '<tr>';
        foreach ($linha as $valor) {
            echo '<td>' . htmlspecialchars($valor) . '</td>';
        }
        echo '</tr>';
    }
    
    // Fim do HTML
    echo '</tbody>
        </table>
        
        <div class="footer">
            Relatório gerado em ' . date('d/m/Y H:i:s') . ' - Faciência ERP
        </div>
    </body>
    </html>';
    
    exit;
}

// Se chegou até aqui, redireciona para a página de relatórios
$_SESSION['mensagem'] = 'Formato de exportação inválido.';
$_SESSION['mensagem_tipo'] = 'erro';
header('Location: relatorios.php?tipo=' . $tipo);
exit;
