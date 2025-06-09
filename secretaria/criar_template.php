<?php
/**
 * Script para criar o modelo de planilha para importação de alunos
 */

// Carrega a biblioteca PHPSpreadsheet
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Cria uma nova planilha
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Importação de Alunos');

// Define os cabeçalhos
$cabecalhos = [
    'Nome',
    'CPF',
    'RG',
    'Orgão expedidor',
    'Nacionalidade',
    'Estado Civil',
    'Sexo',
    'Nascimento',
    'Naturalidade',
    'Curso id',
    'Curso inicio',
    'Curso fim',
    'Situação',
    'Email',
    'Endereço',
    'Complemento',
    'Cidade',
    'Cep',
    'Nome Social',
    'Celular',
    'Bairro',
    'Data Ingresso',
    'Previsão Conclusão',
    'Mono Título',
    'Mono Data',
    'Mono Nota',
    'Mono Prazo',
    'Bolsa',
    'Desconto'
];

// Adiciona os cabeçalhos
foreach ($cabecalhos as $coluna => $cabecalho) {
    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($coluna + 1);
    $sheet->setCellValue($colLetter . '1', $cabecalho);
}

// Adiciona alguns exemplos
$exemplos = [
    [
        'João da Silva',
        '123.456.789-00',
        '12.345.678-9',
        'SSP/SP',
        'Brasileira',
        'Solteiro(a)',
        'M',
        '01/01/1990',
        'São Paulo',
        '1',
        '01/01/2023',
        '31/12/2023',
        'Ativo',
        'joao.silva@email.com',
        'Rua das Flores, 123',
        'Apto 101',
        'São Paulo',
        '01234-567',
        '', // Nome Social
        '(11) 98765-4321', // Celular
        'Centro', // Bairro
        '01/01/2023', // Data Ingresso
        '31/12/2024', // Previsão Conclusão
        '', // Mono Título
        '', // Mono Data
        '', // Mono Nota
        '', // Mono Prazo
        '', // Bolsa
        '' // Desconto
    ],
    [
        'Maria Oliveira',
        '987.654.321-00',
        '98.765.432-1',
        'SSP/RJ',
        'Brasileira',
        'Casado(a)',
        'F',
        '15/05/1985',
        'Rio de Janeiro',
        '2',
        '01/02/2023',
        '28/02/2024',
        'Ativo',
        'maria.oliveira@email.com',
        'Av. Principal, 456',
        'Bloco B',
        'Rio de Janeiro',
        '20000-000',
        '', // Nome Social
        '(21) 98765-4321', // Celular
        'Copacabana', // Bairro
        '01/02/2023', // Data Ingresso
        '28/02/2025', // Previsão Conclusão
        'Estudo sobre Educação', // Mono Título
        '15/12/2024', // Mono Data
        '9.5', // Mono Nota
        '01/12/2024', // Mono Prazo
        '500.00', // Bolsa
        '100.00' // Desconto
    ]
];

// Adiciona os exemplos
foreach ($exemplos as $linha => $exemplo) {
    foreach ($exemplo as $coluna => $valor) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($coluna + 1);
        $sheet->setCellValue($colLetter . ($linha + 2), $valor);
    }
}

// Estiliza os cabeçalhos
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4'],
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];

// Determina a última coluna com base no número de cabeçalhos
$ultima_coluna = chr(65 + count($cabecalhos) - 1); // Converte o número para letra (A, B, C, etc.)

// Aplica estilo aos cabeçalhos
$sheet->getStyle("A1:{$ultima_coluna}1")->applyFromArray($headerStyle);

// Estiliza os exemplos
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
];

// Aplica estilo aos exemplos
$sheet->getStyle("A2:{$ultima_coluna}3")->applyFromArray($dataStyle);

// Ajusta a largura das colunas
foreach (range('A', $ultima_coluna) as $coluna) {
    $sheet->getColumnDimension($coluna)->setAutoSize(true);
}

// Cria o diretório de templates se não existir
if (!is_dir('templates')) {
    mkdir('templates', 0755, true);
}

// Salva o arquivo
$writer = new Xlsx($spreadsheet);
$writer->save('templates/modelo_importacao_alunos.xlsx');

echo "Modelo de planilha criado com sucesso em templates/modelo_importacao_alunos.xlsx\n";
