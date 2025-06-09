<?php
/**
 * Página de opções para geração de declaração
 * Permite escolher se o polo deve aparecer ou não na declaração
 */
?>

<div class="bg-white shadow-md rounded-lg p-6 mb-6">
    <h2 class="text-xl font-semibold mb-4">Opções de Declaração para <?php echo htmlspecialchars($aluno['nome'] ?? ''); ?></h2>
    
    <div class="mb-4">
        <p class="text-gray-700 mb-2">
            <strong>Aluno:</strong> <?php echo htmlspecialchars($aluno['nome'] ?? ''); ?>
        </p>
        <p class="text-gray-700 mb-2">
            <strong>CPF:</strong> <?php echo htmlspecialchars($aluno['cpf'] ?? ''); ?>
        </p>
        <p class="text-gray-700 mb-2">
            <strong>Curso:</strong> <?php echo htmlspecialchars($aluno['curso_nome'] ?? ''); ?>
        </p>
        <p class="text-gray-700 mb-2">
            <strong>Polo:</strong> <?php echo htmlspecialchars($aluno['polo_razao_social'] ?? $aluno['polo_nome'] ?? ''); ?>
        </p>
    </div>

    <form action="documentos.php" method="get" class="mt-6">
        <input type="hidden" name="action" value="gerar_declaracao">
        <input type="hidden" name="aluno_id" value="<?php echo htmlspecialchars($aluno['id'] ?? ''); ?>">
        <input type="hidden" name="confirmar" value="1">
        <?php if (isset($_GET['solicitacao_id'])): ?>
        <input type="hidden" name="solicitacao_id" value="<?php echo htmlspecialchars($_GET['solicitacao_id'] ?? ''); ?>">
        <?php endif; ?>
        
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Exibir polo na declaração:</label>
            <div class="mt-2">
                <div class="flex items-center mb-2">
                    <input type="radio" id="exibir_polo_sim" name="exibir_polo" value="1" checked 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="exibir_polo_sim" class="ml-2 block text-gray-700">
                        Sim, exibir o polo na declaração
                    </label>
                </div>
                <div class="flex items-center">
                    <input type="radio" id="exibir_polo_nao" name="exibir_polo" value="0" 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="exibir_polo_nao" class="ml-2 block text-gray-700">
                        Não, não exibir o polo na declaração
                    </label>
                </div>
            </div>
        </div>
        
        <div class="flex items-center justify-between mt-6">
            <a href="documentos.php?action=selecionar_aluno" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Cancelar
            </a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Gerar Declaração
            </button>
        </div>
    </form>
</div>
