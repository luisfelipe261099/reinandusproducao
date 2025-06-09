<?php
// Obtém as categorias de chamados
$sql_categorias = "SELECT * FROM categorias_chamados WHERE status = 'ativo'";
if ($is_polo) {
    $sql_categorias .= " AND tipo = 'polo'";
} else {
    $sql_categorias .= " AND tipo = 'interno'";
}
$sql_categorias .= " ORDER BY ordem ASC";
$categorias = $db->fetchAll($sql_categorias);

// Obtém a lista de departamentos
$departamentos = ['secretaria', 'financeiro', 'suporte', 'diretoria'];

// Obtém a lista de polos (se não for um polo)
$polos = [];
if (!$is_polo) {
    $sql_polos = "SELECT id, nome FROM polos WHERE status = 'ativo' ORDER BY nome ASC";
    $polos = $db->fetchAll($sql_polos);
}

// Obtém a lista de alunos (se for um polo)
$alunos = [];
if ($is_polo) {
    $sql_alunos = "SELECT a.id, a.nome, a.id_legado FROM alunos a 
                   JOIN matriculas m ON a.id = m.aluno_id 
                   WHERE m.polo_id = ? AND a.status = 'ativo' 
                   GROUP BY a.id 
                   ORDER BY a.nome ASC";
    $alunos = $db->fetchAll($sql_alunos, [$polo_id]);
}
?>

<div class="bg-white shadow-md rounded-lg p-6">
    <h2 class="text-lg font-semibold mb-4">Novo Chamado</h2>
    
    <form action="chamados.php?action=salvar" method="POST" enctype="multipart/form-data">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Categoria *</label>
                <select name="categoria_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                    <option value="">Selecione uma categoria</option>
                    <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>">
                        <?php echo htmlspecialchars($categoria['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prioridade *</label>
                <select name="prioridade" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                    <option value="baixa">Baixa</option>
                    <option value="media" selected>Média</option>
                    <option value="alta">Alta</option>
                    <option value="urgente">Urgente</option>
                </select>
            </div>
            
            <?php if (!$is_polo): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Departamento *</label>
                <select name="departamento" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                    <option value="">Selecione um departamento</option>
                    <?php foreach ($departamentos as $departamento): ?>
                    <option value="<?php echo $departamento; ?>">
                        <?php echo ucfirst($departamento); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Polo</label>
                <select name="polo_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Selecione um polo (opcional)</option>
                    <?php foreach ($polos as $polo): ?>
                    <option value="<?php echo $polo['id']; ?>">
                        <?php echo htmlspecialchars($polo['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="polo_id" value="<?php echo $polo_id; ?>">
            <input type="hidden" name="tipo" value="polo">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Aluno</label>
                <select name="aluno_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <option value="">Selecione um aluno (opcional)</option>
                    <?php foreach ($alunos as $aluno): ?>
                    <option value="<?php echo $aluno['id']; ?>">
                        <?php echo htmlspecialchars($aluno['nome']); ?> 
                        <?php if (!empty($aluno['id_legado'])): ?>
                        (<?php echo htmlspecialchars($aluno['id_legado']); ?>)
                        <?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
            <input type="text" name="titulo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição *</label>
            <textarea name="descricao" rows="6" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required></textarea>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Anexos</label>
            <input type="file" name="anexos[]" multiple class="w-full border border-gray-300 rounded-md p-2">
            <p class="text-xs text-gray-500 mt-1">Você pode anexar até 5 arquivos (máximo 5MB cada). Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG.</p>
        </div>
        
        <div class="flex justify-end">
            <a href="chamados.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded mr-2">
                <i class="fas fa-times mr-2"></i> Cancelar
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-save mr-2"></i> Salvar
            </button>
        </div>
    </form>
</div>
