<?php
/**
 * Página para editar o campo MEC dos polos
 * Este campo é usado nas declarações para exibir o nome oficial do polo registrado no MEC
 */
?>

<div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-semibold text-gray-800">Editar Nome MEC do Polo</h3>
        <p class="mt-2 text-gray-600">Este nome será exibido nas declarações como "Polo de Apoio Presencial".</p>
    </div>

    <div class="p-6">
        <form action="polos.php" method="post" class="space-y-6">
            <input type="hidden" name="action" value="salvar_mec">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($polo['id'] ?? ''); ?>">

            <div>
                <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome do Polo</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($polo['nome'] ?? ''); ?>" readonly
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-sm text-gray-500">Este é o nome atual do polo no sistema.</p>
            </div>

            <div>
                <label for="mec" class="block text-sm font-medium text-gray-700 mb-1">Nome MEC do Polo</label>
                <input type="text" id="mec" name="mec" value="<?php echo htmlspecialchars($polo['mec'] ?? ''); ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-sm text-gray-500">Este nome será exibido nas declarações como "Polo de Apoio Presencial".</p>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <a href="polos.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>
