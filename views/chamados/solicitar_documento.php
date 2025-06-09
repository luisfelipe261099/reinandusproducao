<?php
// Verifica se o usuário é um polo
if (!$is_polo) {
    $_SESSION['mensagem'] = 'Apenas polos podem solicitar documentos.';
    $_SESSION['mensagem_tipo'] = 'erro';
    header('Location: chamados.php');
    exit;
}

// Obtém os tipos de documentos disponíveis
$sql_tipos_documentos = "SELECT * FROM tipos_documentos WHERE status = 'ativo' ORDER BY nome ASC";
$tipos_documentos = $db->fetchAll($sql_tipos_documentos);

// Obtém os alunos do polo
$sql_alunos = "SELECT a.id, a.nome, a.id_legado, m.id as matricula_id, c.nome as curso_nome 
               FROM alunos a 
               JOIN matriculas m ON a.id = m.aluno_id 
               JOIN cursos c ON m.curso_id = c.id
               WHERE m.polo_id = ? AND a.status = 'ativo' AND m.status IN ('ativo', 'concluído')
               ORDER BY a.nome ASC";
$alunos = $db->fetchAll($sql_alunos, [$polo_id]);

// Obtém os limites de documentos do polo
$sql_limites = "SELECT pf.*, tp.nome as tipo_polo_nome, tp.descricao as tipo_polo_descricao
                FROM polos_financeiro pf
                JOIN tipos_polos tp ON pf.tipo_polo_id = tp.id
                WHERE pf.polo_id = ?";
$limites = $db->fetchAll($sql_limites, [$polo_id]);

// Obtém o total de documentos disponíveis
$total_documentos_disponiveis = 0;
foreach ($limites as $limite) {
    $total_documentos_disponiveis += $limite['documentos_disponiveis'];
}
?>

<div class="bg-white shadow-md rounded-lg p-6">
    <h2 class="text-lg font-semibold mb-4">Solicitar Documentos</h2>
    
    <?php if ($total_documentos_disponiveis <= 0): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
        <p class="font-bold">Limite de documentos atingido!</p>
        <p>Você não possui documentos disponíveis para solicitação. Entre em contato com a administração para adquirir mais documentos.</p>
    </div>
    <?php else: ?>
    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
        <p class="font-bold">Documentos disponíveis: <?php echo $total_documentos_disponiveis; ?></p>
        <p>Você pode solicitar até <?php echo $total_documentos_disponiveis; ?> documentos.</p>
    </div>
    
    <form action="chamados.php?action=salvar_documento" method="POST">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Documento *</label>
                <select name="tipo_documento_id" id="tipo_documento_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                    <option value="">Selecione um tipo de documento</option>
                    <?php foreach ($tipos_documentos as $tipo): ?>
                    <option value="<?php echo $tipo['id']; ?>" data-valor="<?php echo $tipo['valor']; ?>">
                        <?php echo htmlspecialchars($tipo['nome']); ?> (R$ <?php echo number_format($tipo['valor'], 2, ',', '.'); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Aluno *</label>
                <select name="aluno_id" id="aluno_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                    <option value="">Selecione um aluno</option>
                    <?php foreach ($alunos as $aluno): ?>
                    <option value="<?php echo $aluno['id']; ?>" data-matricula="<?php echo $aluno['matricula_id']; ?>">
                        <?php echo htmlspecialchars($aluno['nome']); ?> 
                        <?php if (!empty($aluno['id_legado'])): ?>
                        (<?php echo htmlspecialchars($aluno['id_legado']); ?>)
                        <?php endif; ?> - 
                        <?php echo htmlspecialchars($aluno['curso_nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade *</label>
                <input type="number" name="quantidade" id="quantidade" min="1" max="<?php echo $total_documentos_disponiveis; ?>" value="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valor Total</label>
                <div class="bg-gray-100 p-2 rounded-md">
                    <span id="valor_total">R$ 0,00</span>
                </div>
            </div>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Finalidade</label>
            <textarea name="finalidade" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
            <p class="text-xs text-gray-500 mt-1">Descreva a finalidade para a qual o documento será utilizado (opcional).</p>
        </div>
        
        <div class="flex justify-end">
            <a href="chamados.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded mr-2">
                <i class="fas fa-times mr-2"></i> Cancelar
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-paper-plane mr-2"></i> Solicitar
            </button>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
    // Script para calcular o valor total
    document.addEventListener('DOMContentLoaded', function() {
        const tipoDocumentoSelect = document.getElementById('tipo_documento_id');
        const quantidadeInput = document.getElementById('quantidade');
        const valorTotalSpan = document.getElementById('valor_total');
        
        function calcularValorTotal() {
            const tipoDocumentoOption = tipoDocumentoSelect.options[tipoDocumentoSelect.selectedIndex];
            const valor = tipoDocumentoOption ? parseFloat(tipoDocumentoOption.dataset.valor || 0) : 0;
            const quantidade = parseInt(quantidadeInput.value || 0);
            
            const valorTotal = valor * quantidade;
            valorTotalSpan.textContent = 'R$ ' + valorTotal.toFixed(2).replace('.', ',');
        }
        
        tipoDocumentoSelect.addEventListener('change', calcularValorTotal);
        quantidadeInput.addEventListener('change', calcularValorTotal);
        quantidadeInput.addEventListener('input', calcularValorTotal);
    });
</script>
