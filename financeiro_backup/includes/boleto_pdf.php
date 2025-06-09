<?php
/**
 * Classe para gerar o HTML do boleto no padrão Itaú
 */

class BoletoPDF {
    // Propriedades do boleto
    private $boleto;
    
    /**
     * Construtor
     * @param array $boleto Dados do boleto
     */
    public function __construct($boleto) {
        $this->boleto = $boleto;
    }
    
    /**
     * Gera o HTML do boleto
     * @return string HTML do boleto
     */
    public function gerarHTML() {
        // Verifica se o código de barras tem o tamanho correto
        $codigo_barras = $this->boleto['codigo_barras'];
        if (!empty($codigo_barras) && strlen($codigo_barras) != 44) {
            // Completa o código de barras ou trunca para 44 caracteres
            if (strlen($codigo_barras) < 44) {
                $codigo_barras = str_pad($codigo_barras, 44, '0', STR_PAD_RIGHT);
            } else {
                $codigo_barras = substr($codigo_barras, 0, 44);
            }
        }
        
        // Verifica se a linha digitável tem o tamanho correto
        $linha_digitavel = $this->boleto['linha_digitavel'];
        if (empty($linha_digitavel) && !empty($codigo_barras)) {
            // Gera a linha digitável a partir do código de barras
            $linha_digitavel = $this->gerarLinhaDigitavel($codigo_barras);
        }
        
        // Formata os valores
        $valor = number_format($this->boleto['valor'], 2, ',', '.');
        $data_vencimento = date('d/m/Y', strtotime($this->boleto['data_vencimento']));
        $data_emissao = date('d/m/Y');
        
        // Gera o HTML
        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Boleto Bancário - Itaú</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
            size: A4;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 10px;
            font-size: 11px;
            color: #000;
            background-color: #fff;
        }
        
        /* Container principal */
        .boleto-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ccc;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            background-color: #fff;
            padding: 5px;
        }
        
        /* Cabeçalho do boleto */
        .boleto-header {
            border-bottom: 2px solid #000;
            overflow: hidden;
            margin-bottom: 5px;
            height: 30px;
            padding-bottom: 5px;
        }
        
        /* Logo do banco */
        .banco-logo {
            float: left;
            width: 130px;
            height: 30px;
            padding: 3px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .banco-logo img {
            max-height: 30px;
            max-width: 100%;
        }
        
        /* Código do banco */
        .banco-codigo {
            float: left;
            width: 60px;
            height: 30px;
            border-left: 2px solid #000;
            border-right: 2px solid #000;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            line-height: 30px;
            margin: 0 4px;
        }
        
        /* Linha digitável */
        .linha-digitavel {
            float: right;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1px;
            line-height: 30px;
            font-family: "Courier New", monospace;
        }
        
        /* Tabela de informações */
        .boleto-info {
            border: 1px solid #000;
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 3px;
        }
        
        .boleto-info td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 11px;
            vertical-align: top;
        }
        
        .boleto-info .label {
            font-size: 8px;
            color: #333;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .boleto-info .value {
            font-size: 12px;
            font-weight: normal;
            color: #000;
            min-height: 14px;
            margin-top: 2px;
        }
        
        /* Seção do recibo */
        .recibo {
            margin-bottom: 20px;
            position: relative;
        }
        
        .recibo::after {
            content: "RECIBO DO PAGADOR";
            position: absolute;
            top: 0;
            right: 10px;
            font-size: 10px;
            font-weight: bold;
            color: #666;
            transform: rotate(-90deg);
            transform-origin: right top;
        }
        
        /* Ficha de compensação */
        .ficha-compensacao {
            position: relative;
        }
        
        .ficha-compensacao::after {
            content: "FICHA DE COMPENSAÇÃO";
            position: absolute;
            top: 0;
            right: 10px;
            font-size: 10px;
            font-weight: bold;
            color: #666;
            transform: rotate(-90deg);
            transform-origin: right top;
        }
        
        /* Linha de corte */
        .corte {
            border-top: 1px dashed #000;
            margin: 15px 0;
            position: relative;
            text-align: center;
            padding: 3px 0;
        }
        
        .corte::before {
            content: "✂";
            position: absolute;
            left: -15px;
            top: -10px;
            font-size: 16px;
        }
        
        .corte-texto {
            font-size: 9px;
            color: #666;
            background: #fff;
            padding: 0 10px;
            display: inline-block;
            position: relative;
            top: -10px;
        }
        
        /* Código de barras */
        .barcode {
            margin: 15px 0 20px 0;
            text-align: left;
            padding-left: 10px;
        }
        
        .barcode img {
            height: 50px;
            width: 100%;
            max-width: 420px;
        }
        
        /* Instruções */
        .instrucoes {
            min-height: 70px;
        }
        
        /* Fundo cinza para destaque */
        .fundo-cinza {
            background-color: #f0f0f0;
        }
        
        /* Marca d'água do Itaú */
        .marca-dagua {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            font-size: 150px;
            color: #EC7000;
            font-weight: bold;
            z-index: 0;
            user-select: none;
            pointer-events: none;
        }
        
        /* Cabeçalhos de seção */
        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin: 5px 0;
            color: #EC7000;
        }
        
        /* Cores específicas do Itaú */
        .itau-blue {
            color: #003087;
        }
        
        .itau-orange {
            color: #EC7000;
        }
        
        /* Informações do autenticador */
        .autenticacao {
            position: absolute;
            bottom: 10px;
            right: 10px;
            font-size: 8px;
            color: #666;
            text-align: right;
        }
        
        /* Ícone de segurança */
        .seguranca-icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            background-color: #EC7000;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
            position: relative;
        }
        
        .seguranca-icon::after {
            content: "✓";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 10px;
            font-weight: bold;
        }
        
        /* Banner de segurança */
        .banner-seguranca {
            margin-top: 10px;
            padding: 5px;
            background-color: #f9f9f9;
            border: 1px dashed #ccc;
            font-size: 9px;
            color: #555;
            text-align: center;
        }
        
        /* Aviso legal em microletras */
        .micro-text {
            font-size: 6px;
            color: #999;
            margin-top: 5px;
            line-height: 1.2;
        }
    </style>
</head>
<body>
    <div class="boleto-container">
        <!-- Recibo do Sacado -->
        <div class="recibo">
            <div class="boleto-header">
                <div class="banco-logo">
                    <span style="color: #EC7000; font-size: 28px; font-weight: bold;">itaú</span>
                </div>
                <div class="banco-codigo">341-7</div>
                <div class="linha-digitavel" style="font-size: 13px;">{$linha_digitavel}</div>
            </div>
            
            <table class="boleto-info">
                <tr>
                    <td colspan="2">
                        <div class="label">Local de Pagamento</div>
                        <div class="value">PAGÁVEL PREFERENCIALMENTE NAS AGÊNCIAS ITAÚ OU PELO INTERNET BANKING</div>
                    </td>
                    <td style="width: 25%;" class="fundo-cinza">
                        <div class="label">Vencimento</div>
                        <div class="value" style="font-weight: bold;">{$data_vencimento}</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="label">Beneficiário</div>
                        <div class="value">FACIÊNCIA - CNPJ: 09.038.742.0001-80</div>
                    </td>
                    <td class="fundo-cinza">
                        <div class="label">Agência/Código Beneficiário</div>
                        <div class="value">0978 / 27155-1</div>
                    </td>
                </tr>
                <tr>
                    <td style="width: 15%;">
                        <div class="label">Data do Documento</div>
                        <div class="value">{$data_emissao}</div>
                    </td>
                    <td style="width: 25%;">
                        <div class="label">Número do Documento</div>
                        <div class="value">{$this->boleto['id']}</div>
                    </td>
                    <td style="width: 20%;">
                        <div class="label">Nosso Número</div>
                        <div class="value" style="font-weight: bold;">109/{$this->boleto['nosso_numero']}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="label">Espécie</div>
                        <div class="value">R$</div>
                    </td>
                    <td>
                        <div class="label">Quantidade</div>
                        <div class="value">1</div>
                    </td>
                    <td style="width: 25%;" class="fundo-cinza">
                        <div class="label">(=) Valor do Documento</div>
                        <div class="value" style="font-size: 14px; font-weight: bold;">R$ {$valor}</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <div class="label">Demonstrativo</div>
                        <div class="value">{$this->boleto['descricao']}</div>
                        <div class="seguranca-icon"></div><span style="font-size: 9px; color: #666;">Documento gerado eletronicamente</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <div class="label">Pagador</div>
                        <div class="value">
                            {$this->boleto['nome_pagador']} - CPF/CNPJ: {$this->boleto['cpf_pagador']}<br>
                            {$this->boleto['endereco']} - {$this->boleto['bairro']}<br>
                            {$this->boleto['cidade']}/{$this->boleto['uf']} - CEP: {$this->boleto['cep']}
                        </div>
                    </td>
                </tr>
            </table>
            
            <div class="micro-text">
                Este recibo não quita o débito. A baixa do título ocorrerá após o processamento bancário. Em caso de dúvida, entre em contato com o beneficiário.
            </div>
        </div>
        
        <div class="corte">
            <span class="corte-texto">CORTE AQUI</span>
        </div>
        
        <!-- Ficha de Compensação -->
        <div class="ficha-compensacao">
            <div class="marca-dagua">itaú</div>
            
            <div class="boleto-header">
                <div class="banco-logo">
                    <span style="color: #EC7000; font-size: 28px; font-weight: bold;">itaú</span>
                </div>
                <div class="banco-codigo">341-7</div>
                <div class="linha-digitavel">{$linha_digitavel}</div>
            </div>
            
            <table class="boleto-info">
                <tr>
                    <td colspan="5">
                        <div class="label">Local de Pagamento</div>
                        <div class="value">PAGÁVEL PREFERENCIALMENTE NAS AGÊNCIAS ITAÚ OU PELO INTERNET BANKING</div>
                    </td>
                    <td style="width: 20%;" class="fundo-cinza">
                        <div class="label">Vencimento</div>
                        <div class="value" style="font-weight: bold;">{$data_vencimento}</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="5">
                        <div class="label">Beneficiário</div>
                        <div class="value">FACIÊNCIA - CNPJ: 09.038.742.0001-80</div>
                    </td>
                    <td class="fundo-cinza">
                        <div class="label">Agência/Código Beneficiário</div>
                        <div class="value">0978 / 27155-1</div>
                    </td>
                </tr>
                <tr>
                    <td style="width: 15%;">
                        <div class="label">Data do Documento</div>
                        <div class="value">{$data_emissao}</div>
                    </td>
                    <td style="width: 15%;">
                        <div class="label">Nº do Documento</div>
                        <div class="value">{$this->boleto['id']}</div>
                    </td>
                    <td style="width: 10%;">
                        <div class="label">Espécie Doc.</div>
                        <div class="value">DM</div>
                    </td>
                    <td style="width: 10%;">
                        <div class="label">Aceite</div>
                        <div class="value">N</div>
                    </td>
                    <td style="width: 15%;">
                        <div class="label">Data Processamento</div>
                        <div class="value">{$data_emissao}</div>
                    </td>
                    <td style="width: 15%;" class="fundo-cinza">
                        <div class="label">Nosso Número</div>
                        <div class="value" style="font-weight: bold;">109/{$this->boleto['nosso_numero']}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="label">Uso do Banco</div>
                        <div class="value"></div>
                    </td>
                    <td>
                        <div class="label">Carteira</div>
                        <div class="value">109</div>
                    </td>
                    <td>
                        <div class="label">Espécie</div>
                        <div class="value">R$</div>
                    </td>
                    <td>
                        <div class="label">Quantidade</div>
                        <div class="value">1</div>
                    </td>
                    <td>
                        <div class="label">Valor</div>
                        <div class="value"></div>
                    </td>
                    <td class="fundo-cinza">
                        <div class="label">(=) Valor do Documento</div>
                        <div class="value" style="font-size: 14px; font-weight: bold;">R$ {$valor}</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="5" rowspan="5" class="instrucoes">
                        <div class="label">Instruções (Texto de responsabilidade do beneficiário)</div>
                        <div class="value" style="font-size: 11px; line-height: 1.4;">
                            <b>ATENÇÃO:</b><br>
                            - NÃO RECEBER APÓS O VENCIMENTO<br>
                            - MULTA DE 2% APÓS O VENCIMENTO<br>
                            - JUROS DE 1% AO MÊS<br>
                            - SAC Itaú: 0800 728 0728<br>
                            - Em caso de dúvidas, entre em contato com o beneficiário
                        </div>
                    </td>
                    <td>
                        <div class="label">(-) Desconto/Abatimento</div>
                        <div class="value"></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="label">(-) Outras Deduções</div>
                        <div class="value"></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="label">(+) Mora/Multa</div>
                        <div class="value"></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="label">(+) Outros Acréscimos</div>
                        <div class="value"></div>
                    </td>
                </tr>
                <tr>
                    <td class="fundo-cinza">
                        <div class="label">(=) Valor Cobrado</div>
                        <div class="value"></div>
                    </td>
                </tr>
                <tr>
                    <td colspan="6">
                        <div class="label">Pagador</div>
                        <div class="value">
                            <b>{$this->boleto['nome_pagador']}</b> - CPF/CNPJ: {$this->boleto['cpf_pagador']}<br>
                            {$this->boleto['endereco']} - {$this->boleto['bairro']}<br>
                            {$this->boleto['cidade']}/{$this->boleto['uf']} - CEP: {$this->boleto['cep']}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="6">
                        <div class="label">Sacador/Avalista</div>
                        <div class="value"></div>
                        <div class="micro-text">Autenticação mecânica - Ficha de Compensação</div>
                    </td>
                </tr>
            </table>
            
            <div class="barcode">
                {$this->gerarImagemCodigoBarras($codigo_barras)}
            </div>
            
            <div class="banner-seguranca">
                <span class="seguranca-icon"></span> DOCUMENTO VÁLIDO - Autenticado digitalmente conforme MP 2.200-2/2001
            </div>
            
            <div class="autenticacao">
                Este documento foi gerado em {$data_emissao} às <?php echo date('H:i:s'); ?>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
        
        return $html;
    }
    
    /**
     * Gera a linha digitável a partir do código de barras
     * @param string $codigo_barras O código de barras do boleto
     * @return string A linha digitável gerada
     */
    private function gerarLinhaDigitavel($codigo_barras) {
        // Verifica se o código de barras tem 44 dígitos
        if (strlen($codigo_barras) != 44) {
            return '';
        }
        
        // Extrai os campos do código de barras
        // Campo 1: Banco (3) + Moeda (1) + Campo Livre 1 (5)
        $campo1 = substr($codigo_barras, 0, 4) . substr($codigo_barras, 19, 5);
        
        // Campo 2: Campo Livre 2 (10)
        $campo2 = substr($codigo_barras, 24, 10);
        
        // Campo 3: Campo Livre 3 (10)
        $campo3 = substr($codigo_barras, 34, 10);
        
        // Campo 4: DV Geral (1)
        $campo4 = substr($codigo_barras, 4, 1);
        
        // Campo 5: Fator Vencimento (4) + Valor (10)
        $campo5 = substr($codigo_barras, 5, 14);
        
        // Calcula os dígitos verificadores de cada campo
        $dv1 = $this->calcularModulo10($campo1);
        $dv2 = $this->calcularModulo10($campo2);
        $dv3 = $this->calcularModulo10($campo3);
        
        // Formata a linha digitável
        $linha_digitavel = 
            substr($campo1, 0, 5) . '.' . substr($campo1, 5) . $dv1 . ' ' .
            substr($campo2, 0, 5) . '.' . substr($campo2, 5) . $dv2 . ' ' .
            substr($campo3, 0, 5) . '.' . substr($campo3, 5) . $dv3 . ' ' .
            $campo4 . ' ' .
            $campo5;
        
        return $linha_digitavel;
    }
    
    /**
     * Calcula o dígito verificador usando o módulo 10
     * @param string $num O número para calcular o dígito verificador
     * @return int O dígito verificador calculado
     */
    private function calcularModulo10($num) {
        $soma = 0;
        $peso = 2;
        
        // Percorre o número da direita para a esquerda
        for ($i = strlen($num) - 1; $i >= 0; $i--) {
            $resultado = (int)$num[$i] * $peso;
            
            // Se o resultado for maior que 9, soma os algarismos
            if ($resultado > 9) {
                $resultado = (int)($resultado / 10) + ($resultado % 10);
            }
            
            $soma += $resultado;
            $peso = $peso == 2 ? 1 : 2;
        }
        
        $resto = $soma % 10;
        return $resto == 0 ? 0 : 10 - $resto;
    }
    
    /**
     * Gera a imagem do código de barras em base64
     * @param string $codigo_barras O código de barras do boleto
     * @return string HTML com a imagem do código de barras
     */
    private function gerarImagemCodigoBarras($codigo_barras) {
        // Dimensões da imagem
        $largura = 420;
        $altura = 50;
        $barra_fina = 1;
        $barra_grossa = 3;
        
        // Cria a imagem
        $img = imagecreatetruecolor($largura, $altura);
        
        // Cores
        $branco = imagecolorallocate($img, 255, 255, 255);
        $preto = imagecolorallocate($img, 0, 0, 0);
        
        // Preenche o fundo com branco
        imagefilledrectangle($img, 0, 0, $largura, $altura, $branco);
        
        // Define o padrão de barras para cada dígito (Interleaved 2 of 5)
        $padrao = array(
            '0' => '00110',
            '1' => '10001',
            '2' => '01001',
            '3' => '11000',
            '4' => '00101',
            '5' => '10100',
            '6' => '01100',
            '7' => '00011',
            '8' => '10010',
            '9' => '01010'
        );
        
        // Barra inicial (start)
        $sequencia = '0000';
        
        // Codifica pares de dígitos
        for ($i = 0; $i < strlen($codigo_barras); $i += 2) {
            $digito1 = $codigo_barras[$i];
            $digito2 = ($i + 1 < strlen($codigo_barras)) ? $codigo_barras[$i + 1] : '0';
            
            $padrao1 = $padrao[$digito1];
            $padrao2 = $padrao[$digito2];
            
            // Intercala os padrões (Interleaved 2 of 5)
            for ($j = 0; $j < 5; $j++) {
                $sequencia .= $padrao1[$j] . $padrao2[$j];
            }
        }
        
        // Barra final (stop)
        $sequencia .= '100';
        
        // Desenha as barras
        $x = 10; // Margem inicial
        for ($i = 0; $i < strlen($sequencia); $i++) {
            $largura_barra = ($sequencia[$i] == '1') ? $barra_grossa : $barra_fina;
            $cor = ($i % 2 == 0) ? $preto : $branco;
            
            imagefilledrectangle($img, $x, 0, $x + $largura_barra - 1, $altura, $cor);
            $x += $largura_barra;
        }
        
        // Converte para base64
        ob_start();
        imagepng($img);
        $imagem_dados = ob_get_clean();
        imagedestroy($img);
        
        // Retorna a imagem em formato base64
        return '<img src="data:image/png;base64,' . base64_encode($imagem_dados) . '" alt="Código de Barras" style="height: 50px; width: 100%; max-width: 420px;">';
    }
}