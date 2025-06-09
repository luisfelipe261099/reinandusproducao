<?php
/**
 * Função para gerar o HTML da declaração de matrícula (versão simplificada)
 */
function gerarDeclaracaoSimples($dados) {
    // Cor principal da FaCiência (roxo)
    $cor_principal = '#6a0dad';

    // Links externos para logo e assinatura
    $logo_url = 'https://lfmtecnologia.com/reinandus/secretaria/logo.png';
    $assinatura_url = 'https://lfmtecnologia.com/reinandus/secretaria/Imagem3.jpg';

    // Formata a data por extenso
    $data_atual = $dados['data_emissao'] ?? date('d/m/Y');
    $meses = [
        '01' => 'janeiro', '02' => 'fevereiro', '03' => 'março', '04' => 'abril',
        '05' => 'maio', '06' => 'junho', '07' => 'julho', '08' => 'agosto',
        '09' => 'setembro', '10' => 'outubro', '11' => 'novembro', '12' => 'dezembro'
    ];

    $partes_data = explode('/', $data_atual);
    if (count($partes_data) === 3) {
        $dia = $partes_data[0];
        $mes = $meses[$partes_data[1]] ?? $partes_data[1];
        $ano = $partes_data[2];
        $data_extenso = "$dia de $mes de $ano";
    } else {
        $data_extenso = $data_atual;
    }

    // Gera o HTML da declaração
    $html = '
    <div style="position: relative; font-family: Arial, sans-serif; line-height: 1.5; color: #333;">
        <!-- Estilo para ocultar URLs na impressão -->
        <style>
            @media print {
                @page { size: A4; margin: 2cm; }
                body:after { display: none; content: none !important; }
                body { -webkit-print-color-adjust: exact; }
            }
        </style>
        <!-- Marca d\'agua -->
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; font-size: 80px; color: #f0f0f0; opacity: 0.1; transform: rotate(-45deg); z-index: -1;">FACIÊNCIA</div>

        <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid '.$cor_principal.'; padding-bottom: 20px;">
            <img src="'.$logo_url.'" alt="Logo da Instituição" style="max-height: 80px; margin-bottom: 15px;">
            <h1 style="color: '.$cor_principal.'; font-size: 22pt; font-weight: bold; margin: 0 0 5px 0; text-transform: uppercase;">'.htmlspecialchars($dados['instituicao'] ?? 'Faculdade FaCiência').'</h1>
            <p style="color: #555; font-size: 10pt; margin: 5px 0;">Credenciada pelo MEC - Portaria nº 147 de 08/03/2022</p>
            <p style="color: #555; font-size: 10pt; margin: 5px 0;">Departamento de Pós-Graduação</p>
            <p style="color: #555; font-size: 10pt; margin: 5px 0;">CNPJ: 09.038.742.0001-80</p>
            <h2 style="font-size: 18pt; font-weight: bold; margin: 25px 0 0 0; color: #333; text-transform: uppercase; letter-spacing: 2px; border-top: 1px solid #ddd; padding-top: 15px;">DECLARAÇÃO</h2>
        </div>

        <div style="text-align: justify; margin: 30px 0; line-height: 1.8; font-size: 12pt;">';

    // Texto base da declaração
    $texto_declaracao = '<p>Declaramos para os devidos fins que <strong style="color: '.$cor_principal.';">'.htmlspecialchars($dados['aluno_nome'] ?? '').'</strong>,
            inscrito(a) no CPF <strong style="color: '.$cor_principal.';">'.htmlspecialchars($dados['aluno_cpf'] ?? '').'</strong>,
            com número de matrícula <strong style="color: '.$cor_principal.';">'.htmlspecialchars($dados['matricula_numero'] ?? '').'</strong>,
            é aluno(a) regularmente matriculado(a) no Curso de <strong style="color: '.$cor_principal.';">'.htmlspecialchars($dados['curso_nome'] ?? '').'</strong>';

    // Verifica se deve exibir o polo
    if (isset($dados['exibir_polo']) && $dados['exibir_polo'] === false) {
        // Não exibe o polo, mas mantém o layout consistente
        $texto_declaracao .= ' desta instituição de ensino.</p>';
        // Adiciona um espaçador para manter o layout consistente
        $texto_declaracao .= '<div style="height: 20px;"></div>';
    } else {
        // Exibe o polo, usando razao_social se disponível, senão usa polo_nome
        $nome_polo = !empty($dados['polo_razao_social']) ? $dados['polo_razao_social'] : ($dados['polo_nome'] ?? '');
        $texto_declaracao .= ' desta instituição de ensino, no polo <strong style="color: '.$cor_principal.';">'.htmlspecialchars($nome_polo).'</strong>.</p>';
    }

    $html .= $texto_declaracao;

    $html .= '<p>O(A) aluno(a) iniciou o curso em <strong style="color: '.$cor_principal.';">'.htmlspecialchars($dados['data_inicio'] ?? '').'</strong>
            com previsão de término em <strong style="color: '.$cor_principal.';">'.htmlspecialchars($dados['data_previsao_termino'] ?? '').'</strong>.</p>';

        if (!empty($dados['observacoes'])) {
            $html .= '<p><strong style="color: '.$cor_principal.';">Observações:</strong> '.nl2br(htmlspecialchars($dados['observacoes'])).'</p>';
        }

        $html .= '<p>Por ser expressão da verdade, firmamos a presente.</p>
        </div>

        <div style="text-align: center; margin-top: 50px; font-size: 10pt; color: #555; border-top: 1px solid #ddd; padding-top: 20px;">
            <p style="margin: 5px 0;">'.htmlspecialchars($dados['cidade'] ?? 'Curitiba/PR').', '.$data_extenso.'.</p>

            <div style="margin-top: 40px; text-align: center;">
                <img src="'.$assinatura_url.'" alt="Assinatura Digital" style="max-height: 60px; margin-bottom: 10px;">
                <div style="width: 250px; border-bottom: 1px solid #333; margin: 10px auto;"></div>
                <p style="margin: 5px 0;"><strong>'.htmlspecialchars($dados['responsavel'] ?? 'Guindani Instituto de Ensino Pesquisa e Gestão S/S Ltda - ME').'</strong></p>
                <p style="margin: 5px 0;">Faculdade FaCiência</p>
                <p style="margin: 5px 0;">Departamento de Pós-Graduação</p>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <p style="font-size: 8pt; color: #666; margin-top: 5px;">Código de Verificação: '.htmlspecialchars($dados['codigo_verificacao'] ?? '').'</p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 8pt; color: #666;">
            Rua Visconde de Nácar, 1510 - 10º andar - Centro, Curitiba/PR - CEP 80410-201 - (41) 99256-2500<br>
            faciencia.edu.br - contato@faciencia.edu.br
        </div>
        <!-- Div para evitar que o caminho do arquivo apareça na impressão -->
        <div style="height: 0; overflow: hidden; visibility: hidden; display: none;"><!-- --></div>

        <!-- Script para remover o caminho do arquivo na impressão -->
        <script>
            window.onload = function() {
                // Remover qualquer texto indesejado que possa aparecer na impressão
                document.title = "Declaração de Matrícula";

                // Adiciona um estilo para ocultar o caminho do arquivo
                var style = document.createElement("style");
                style.innerHTML = `
                    @media print {
                        body::after { content: none !important; }
                        body::before { content: none !important; }
                    }
                    /* Oculta URLs e caminhos de arquivo */
                    a[href]:after { content: none !important; }
                    abbr[title]:after { content: none !important; }
                `;
                document.head.appendChild(style);

                // Remove qualquer texto que contenha caminhos de arquivo
                document.body.innerHTML = document.body.innerHTML.replace(/file:\/{3}[^\s<>"']+/g, "");
            }
        </script>
    </div>';

    return $html;
}
?>
