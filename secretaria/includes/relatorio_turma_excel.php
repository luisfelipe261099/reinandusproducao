<?php
/**
 * Gerador de relatório Excel para turmas
 */

function gerarRelatorioTurmaExcel($turma, $alunos) {
    // Verifica se a biblioteca PhpSpreadsheet está disponível
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        // Fallback: gera CSV se PhpSpreadsheet não estiver disponível
        gerarRelatorioTurmaCSV($turma, $alunos);
        return;
    }

    require_once 'vendor/autoload.php';

    // Importa as classes necessárias
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Configurações da planilha
    $sheet->setTitle('Relatório da Turma');

    // Cabeçalho do relatório
    $linha = 1;

    // Título principal
    $sheet->setCellValue('A' . $linha, 'RELATÓRIO DA TURMA');
    $sheet->mergeCells('A' . $linha . ':L' . $linha);
    $sheet->getStyle('A' . $linha)->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A' . $linha)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $linha++;

    // Informações da turma
    $linha++;
    $sheet->setCellValue('A' . $linha, 'INFORMAÇÕES DA TURMA');
    $sheet->getStyle('A' . $linha)->getFont()->setBold(true)->setSize(12);
    $linha++;

    $sheet->setCellValue('A' . $linha, 'Nome da Turma:');
    $sheet->setCellValue('B' . $linha, $turma['nome']);
    $sheet->getStyle('A' . $linha)->getFont()->setBold(true);
    $linha++;

    $sheet->setCellValue('A' . $linha, 'Curso:');
    $curso_texto = $turma['curso_nome'];
    if (!empty($turma['curso_codigo'])) {
        $curso_texto .= ' (' . $turma['curso_codigo'] . ')';
    }
    $sheet->setCellValue('B' . $linha, $curso_texto);
    $sheet->getStyle('A' . $linha)->getFont()->setBold(true);
    $linha++;

    $sheet->setCellValue('A' . $linha, 'Polo:');
    $sheet->setCellValue('B' . $linha, $turma['polo_nome'] . ' - ' . $turma['polo_cidade']);
    $sheet->getStyle('A' . $linha)->getFont()->setBold(true);
    $linha++;

    if (!empty($turma['professor_nome'])) {
        $sheet->setCellValue('A' . $linha, 'Professor Coordenador:');
        $sheet->setCellValue('B' . $linha, $turma['professor_nome']);
        $sheet->getStyle('A' . $linha)->getFont()->setBold(true);
        $linha++;
    }

    $sheet->setCellValue('A' . $linha, 'Status:');
    $sheet->setCellValue('B' . $linha, ucfirst(str_replace('_', ' ', $turma['status'])));
    $sheet->getStyle('A' . $linha)->getFont()->setBold(true);
    $linha++;

    $sheet->setCellValue('A' . $linha, 'Turno:');
    $sheet->setCellValue('B' . $linha, ucfirst($turma['turno']));
    $sheet->getStyle('A' . $linha)->getFont()->setBold(true);
    $linha++;

    if (!empty($turma['data_inicio'])) {
        $sheet->setCellValue('A' . $linha, 'Data de Início:');
        $sheet->setCellValue('B' . $linha, date('d/m/Y', strtotime($turma['data_inicio'])));
        $sheet->getStyle('A' . $linha)->getFont()->setBold(true);
        $linha++;
    }

    if (!empty($turma['data_fim'])) {
        $sheet->setCellValue('A' . $linha, 'Data de Fim:');
        $sheet->setCellValue('B' . $linha, date('d/m/Y', strtotime($turma['data_fim'])));
        $sheet->getStyle('A' . $linha)->getFont()->setBold(true);
        $linha++;
    }

    $sheet->setCellValue('A' . $linha, 'Total de Alunos:');
    $sheet->setCellValue('B' . $linha, count($alunos));
    $sheet->getStyle('A' . $linha)->getFont()->setBold(true);
    $linha++;

    // Espaço
    $linha += 2;

    // Cabeçalho da tabela de alunos
    $sheet->setCellValue('A' . $linha, 'LISTA DE ALUNOS MATRICULADOS');
    $sheet->getStyle('A' . $linha)->getFont()->setBold(true)->setSize(12);
    $linha++;

    // Cabeçalhos das colunas
    $colunas = [
        'A' => 'Nº',
        'B' => 'Nome',
        'C' => 'CPF',
        'D' => 'RG',
        'E' => 'Email',
        'F' => 'Telefone',
        'G' => 'Celular',
        'H' => 'Data Nascimento',
        'I' => 'Sexo',
        'J' => 'Status Matrícula',
        'K' => 'Data Matrícula',
        'L' => 'Nº Matrícula'
    ];

    foreach ($colunas as $coluna => $titulo) {
        $sheet->setCellValue($coluna . $linha, $titulo);
        $sheet->getStyle($coluna . $linha)->getFont()->setBold(true);
        $sheet->getStyle($coluna . $linha)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2E8F0');
    }

    // Bordas no cabeçalho
    $sheet->getStyle('A' . $linha . ':L' . $linha)->getBorders()->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN);

    $linha++;

    // Dados dos alunos
    $contador = 1;
    foreach ($alunos as $aluno) {
        $sheet->setCellValue('A' . $linha, $contador);
        $sheet->setCellValue('B' . $linha, $aluno['aluno_nome']);
        $sheet->setCellValue('C' . $linha, $aluno['aluno_cpf']);
        $sheet->setCellValue('D' . $linha, $aluno['aluno_rg'] ?? '');
        $sheet->setCellValue('E' . $linha, $aluno['aluno_email']);
        $sheet->setCellValue('F' . $linha, $aluno['telefone'] ?? '');
        $sheet->setCellValue('G' . $linha, $aluno['celular'] ?? '');

        if (!empty($aluno['data_nascimento'])) {
            $sheet->setCellValue('H' . $linha, date('d/m/Y', strtotime($aluno['data_nascimento'])));
        }

        $sheet->setCellValue('I' . $linha, $aluno['sexo'] ?? '');
        $sheet->setCellValue('J' . $linha, ucfirst(str_replace('_', ' ', $aluno['matricula_status'])));

        if (!empty($aluno['data_matricula'])) {
            $sheet->setCellValue('K' . $linha, date('d/m/Y', strtotime($aluno['data_matricula'])));
        }

        $sheet->setCellValue('L' . $linha, $aluno['numero_matricula'] ?? '');

        // Bordas nas células
        $sheet->getStyle('A' . $linha . ':L' . $linha)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $linha++;
        $contador++;
    }

    // Ajusta largura das colunas
    $sheet->getColumnDimension('A')->setWidth(5);
    $sheet->getColumnDimension('B')->setWidth(30);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(25);
    $sheet->getColumnDimension('F')->setWidth(15);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->getColumnDimension('H')->setWidth(15);
    $sheet->getColumnDimension('I')->setWidth(10);
    $sheet->getColumnDimension('J')->setWidth(15);
    $sheet->getColumnDimension('K')->setWidth(15);
    $sheet->getColumnDimension('L')->setWidth(15);

    // Nome do arquivo
    $nomeArquivo = 'Relatorio_Turma_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $turma['nome']) . '_' . date('Y-m-d_H-i-s') . '.xlsx';

    // Headers para download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $nomeArquivo . '"');
    header('Cache-Control: max-age=0');

    // Salva o arquivo
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}

function gerarRelatorioTurmaCSV($turma, $alunos) {
    // Fallback: gera CSV se PhpSpreadsheet não estiver disponível
    $nomeArquivo = 'Relatorio_Turma_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $turma['nome']) . '_' . date('Y-m-d_H-i-s') . '.csv';

    // Headers para download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment;filename="' . $nomeArquivo . '"');
    header('Cache-Control: max-age=0');

    // Abre o output
    $output = fopen('php://output', 'w');

    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Cabeçalho do relatório
    fputcsv($output, ['RELATÓRIO DA TURMA'], ';');
    fputcsv($output, [''], ';'); // Linha vazia

    // Informações da turma
    fputcsv($output, ['INFORMAÇÕES DA TURMA'], ';');
    fputcsv($output, ['Nome da Turma:', $turma['nome']], ';');
    fputcsv($output, ['Curso:', $turma['curso_nome'] . ' (' . $turma['curso_codigo'] . ')'], ';');
    fputcsv($output, ['Polo:', $turma['polo_nome'] . ' - ' . $turma['polo_cidade']], ';');

    if (!empty($turma['professor_nome'])) {
        fputcsv($output, ['Professor Coordenador:', $turma['professor_nome']], ';');
    }

    fputcsv($output, ['Status:', ucfirst(str_replace('_', ' ', $turma['status']))], ';');
    fputcsv($output, ['Turno:', ucfirst($turma['turno'])], ';');

    if (!empty($turma['data_inicio'])) {
        fputcsv($output, ['Data de Início:', date('d/m/Y', strtotime($turma['data_inicio']))], ';');
    }

    if (!empty($turma['data_fim'])) {
        fputcsv($output, ['Data de Fim:', date('d/m/Y', strtotime($turma['data_fim']))], ';');
    }

    fputcsv($output, ['Total de Alunos:', count($alunos)], ';');
    fputcsv($output, [''], ';'); // Linha vazia

    // Cabeçalho da tabela de alunos
    fputcsv($output, ['LISTA DE ALUNOS MATRICULADOS'], ';');

    // Cabeçalhos das colunas
    fputcsv($output, [
        'Nº', 'Nome', 'CPF', 'RG', 'Email', 'Telefone', 'Celular',
        'Data Nascimento', 'Sexo', 'Status Matrícula', 'Data Matrícula', 'Nº Matrícula'
    ], ';');

    // Dados dos alunos
    $contador = 1;
    foreach ($alunos as $aluno) {
        $dataNascimento = !empty($aluno['data_nascimento']) ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '';
        $dataMatricula = !empty($aluno['data_matricula']) ? date('d/m/Y', strtotime($aluno['data_matricula'])) : '';

        fputcsv($output, [
            $contador,
            $aluno['aluno_nome'],
            $aluno['aluno_cpf'],
            $aluno['aluno_rg'] ?? '',
            $aluno['aluno_email'],
            $aluno['telefone'] ?? '',
            $aluno['celular'] ?? '',
            $dataNascimento,
            $aluno['sexo'] ?? '',
            ucfirst(str_replace('_', ' ', $aluno['matricula_status'])),
            $dataMatricula,
            $aluno['numero_matricula'] ?? ''
        ], ';');

        $contador++;
    }

    fclose($output);
}
?>
