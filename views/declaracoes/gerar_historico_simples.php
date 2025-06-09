<?php
/**
 * Função para gerar o HTML do histórico escolar (versão simplificada)
 */
function gerarHistoricoSimples($dados) {
    // Cor principal da FaCiência (roxo)
    $cor_principal = '#6a1b9a';

    // Links atualizados para logo e assinatura
    $logo_url = 'https://www.faciencia.edu.br/logo.png?v=1745601920310';
    $assinatura_url = 'assinatura.png';

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

    // Gera o HTML do histórico
    $html = '
    <div style="position: relative; font-family: Arial, sans-serif; line-height: 1.5; color: #333;">
        <!-- Estilo para ocultar URLs na impressão -->
        <style>
            @media print {
                @page { size: A4; margin: 1.5cm; }
                body:after { display: none; content: none !important; }
                body { -webkit-print-color-adjust: exact; }
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
                font-size: 9pt;
            }
            table, th, td {
                border: 1px solid #ddd;
            }
            th, td {
                padding: 6px;
                text-align: left;
            }
            th {
                background-color: '.$cor_principal.';
                color: white;
                font-weight: bold;
            }
            /* Otimizações para caber em uma página */
            .compact-text {
                font-size: 10pt;
                line-height: 1.3;
                margin: 4px 0;
            }
            .compact-section {
                margin: 15px 0;
            }
            .compact-heading {
                font-size: 12pt;
                margin-bottom: 8px;
                padding-bottom: 3px;
            }
        </style>
        <!-- Marca d\'agua -->
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; font-size: 80px; color: #f0f0f0; opacity: 0.1; transform: rotate(-45deg); z-index: -1;">FACIÊNCIA</div>

        <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid '.$cor_principal.'; padding-bottom: 20px;">
            <img src="'.$logo_url.'" alt="Logo da Instituição" style="max-height: 80px; margin-bottom: 15px;">
            <h1 style="color: '.$cor_principal.'; font-size: 22pt; font-weight: bold; margin: 0 0 5px 0; text-transform: uppercase;">'.htmlspecialchars($dados['instituicao'] ?? 'Faculdade FaCiência').'</h1>
            <p style="color: #555; font-size: 10pt; margin: 5px 0;">Credenciada pelo MEC - Portaria nº 147 de 08/03/2022</p>
            <p style="color: #555; font-size: 10pt; margin: 5px 0;">Departamento de Pós-Graduação</p>
            <p style="color: #555; font-size: 10pt; margin: 5px 0;">CNPJ: 09.038.742/0001-80</p>
            <h2 style="font-size: 18pt; font-weight: bold; margin: 25px 0 0 0; color: #333; text-transform: uppercase; letter-spacing: 2px; border-top: 1px solid #ddd; padding-top: 15px;">HISTÓRICO ESCOLAR</h2>
        </div>

        <div style="margin: 20px 0; line-height: 1.5; font-size: 11pt;">
            <div class="compact-section" style="margin-bottom: 20px;">
                <h3 class="compact-heading" style="color: '.$cor_principal.'; font-size: 12pt; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 3px;">Informações do Aluno</h3>
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between;">
                    <p class="compact-text" style="margin: 4px 0; width: 48%;"><strong style="color: '.$cor_principal.';">Nome:</strong> '.htmlspecialchars($dados['aluno_nome'] ?? '').'</p>
                    <p class="compact-text" style="margin: 4px 0; width: 48%;"><strong style="color: '.$cor_principal.';">CPF:</strong> '.htmlspecialchars($dados['aluno_cpf'] ?? '').'</p>
                    <p class="compact-text" style="margin: 4px 0; width: 48%;"><strong style="color: '.$cor_principal.';">Matrícula:</strong> '.htmlspecialchars($dados['matricula_numero'] ?? '').'</p>
                    <p class="compact-text" style="margin: 4px 0; width: 48%;"><strong style="color: '.$cor_principal.';">Curso:</strong> '.htmlspecialchars($dados['curso_nome'] ?? '').'</p>
                    <p class="compact-text" style="margin: 4px 0; width: 48%;"><strong style="color: '.$cor_principal.';">Polo:</strong> '.htmlspecialchars($dados['polo_nome'] ?? '').'</p>
                    <p class="compact-text" style="margin: 4px 0; width: 48%;"><strong style="color: '.$cor_principal.';">Data de Início:</strong> '.htmlspecialchars($dados['data_inicio'] ?? '').'</p>
                    <p class="compact-text" style="margin: 4px 0; width: 48%;"><strong style="color: '.$cor_principal.';">Situação:</strong> '.htmlspecialchars($dados['situacao'] ?? '').'</p>
                </div>
            </div>

            <div class="compact-section" style="margin-bottom: 20px;">
                <h3 class="compact-heading" style="color: '.$cor_principal.'; font-size: 12pt; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 3px;">Disciplinas Cursadas</h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 5px;">
                    <thead>
                        <tr>
                            <th style="background-color: '.$cor_principal.'; color: white; padding: 6px; text-align: left; border: 1px solid #ddd; font-size: 9pt;">Disciplina</th>
                            <th style="background-color: '.$cor_principal.'; color: white; padding: 6px; text-align: center; border: 1px solid #ddd; font-size: 9pt; width: 15%;">Carga Horária</th>
                            <th style="background-color: '.$cor_principal.'; color: white; padding: 6px; text-align: center; border: 1px solid #ddd; font-size: 9pt; width: 10%;">Nota</th>
                            <th style="background-color: '.$cor_principal.'; color: white; padding: 6px; text-align: center; border: 1px solid #ddd; font-size: 9pt; width: 12%;">Frequência</th>
                            <th style="background-color: '.$cor_principal.'; color: white; padding: 6px; text-align: center; border: 1px solid #ddd; font-size: 9pt; width: 15%;">Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        '.($dados['disciplinas'] ?? '<tr><td colspan="5" style="text-align: center; padding: 10px; border: 1px solid #ddd; font-size: 9pt;">Não há disciplinas cursadas até o momento.</td></tr>').'
                    </tbody>
                </table>
            </div>';

    if (!empty($dados['observacoes'])) {
        $html .= '<div class="compact-section" style="margin: 10px 0;">
            <h3 class="compact-heading" style="color: '.$cor_principal.'; font-size: 12pt; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 3px;">Observações</h3>
            <p class="compact-text" style="margin: 4px 0; font-size: 9pt;">'.nl2br(htmlspecialchars($dados['observacoes'])).'</p>
        </div>';
    }

    $html .= '</div>

        <div style="text-align: center; margin-top: 30px; font-size: 10pt; color: #555; border-top: 1px solid #ddd; padding-top: 15px;">
            <p style="margin: 5px 0;">'.htmlspecialchars($dados['cidade'] ?? 'Curitiba/PR').', '.$data_extenso.'.</p>

            <div style="margin-top: 20px; text-align: center;">
                <img src="'.$assinatura_url.'" alt="Assinatura Digital" style="max-height: 50px; margin-bottom: 5px;">
                <div style="width: 200px; border-bottom: 1px solid #333; margin: 5px auto;"></div>
                <p style="margin: 3px 0; font-size: 9pt;"><strong>'.htmlspecialchars($dados['responsavel'] ?? 'Guindani Instituto de Ensino Pesquisa e Gestão S/S Ltda - ME').'</strong></p>
                <p style="margin: 3px 0; font-size: 9pt;">Faculdade FaCiência</p>
                <p style="margin: 3px 0; font-size: 9pt;">Departamento de Pós-Graduação</p>
            </div>

            <div style="text-align: center; margin-top: 15px;">
                <p style="font-size: 8pt; color: #666; margin-top: 3px;">Código de Verificação: '.htmlspecialchars($dados['codigo_verificacao'] ?? '').'</p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 10px; font-size: 7pt; color: #666;">
            Rua Visconde de Nácar, 1510 - 10º andar - Centro, Curitiba/PR - CEP 80410-201 - (41) 99256-2500<br>
            www.faciencia.edu.br - contato@faciencia.edu.br
        </div>

        <!-- Script para remover o caminho do arquivo na impressão -->
        <script>
            window.onload = function() {
                // Remover qualquer texto indesejado que possa aparecer na impressão
                document.title = "Histórico Escolar";

                // Adiciona um estilo para ocultar o caminho do arquivo
                var style = document.createElement("style");
                style.innerHTML = "@media print { body::after { content: none !important; } body::before { content: none !important; } } a[href]:after { content: none !important; } abbr[title]:after { content: none !important; }";
                document.head.appendChild(style);

                // Remove qualquer texto que contenha caminhos de arquivo
                var content = document.body.innerHTML;
                content = content.replace(/file:\/\/\/[^\s<>"\']+/g, "");
                document.body.innerHTML = content;
            }
        </script>
    </div>';

    return $html;
}
?>
