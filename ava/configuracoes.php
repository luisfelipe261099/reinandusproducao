<?php
/**
 * Página de configurações do AVA
 */

// Inicializa o sistema
require_once '../includes/init.php';

// Verifica se o usuário está autenticado
exigirLogin();

// Define o título da página
$page_title = 'Configurações';

// Inclui o início do layout
include 'includes/layout_start.php';
?>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Configurações do Sistema</h1>
    <p class="text-gray-600 mb-4">Gerencie as configurações do Ambiente Virtual de Aprendizagem.</p>
    
    <div class="bg-yellow-50 p-4 rounded-lg mb-4">
        <div class="flex items-center mb-2">
            <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
            <h2 class="text-lg font-semibold text-yellow-800">Página em Desenvolvimento</h2>
        </div>
        <p class="text-yellow-600">Esta página está em desenvolvimento e será implementada em breve.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Configurações Gerais -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Configurações Gerais</h2>
        
        <form action="#" method="post" class="space-y-4">
            <div class="form-group">
                <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">Nome do Site</label>
                <input type="text" id="site_name" name="site_name" class="form-input" value="Faciência ERP" disabled>
            </div>
            
            <div class="form-group">
                <label for="site_description" class="block text-sm font-medium text-gray-700 mb-1">Descrição do Site</label>
                <textarea id="site_description" name="site_description" class="form-textarea" disabled>Sistema de gestão acadêmica e ambiente virtual de aprendizagem.</textarea>
            </div>
            
            <div class="form-group">
                <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">E-mail de Contato</label>
                <input type="email" id="contact_email" name="contact_email" class="form-input" value="contato@faciencia.com.br" disabled>
            </div>
            
            <button type="button" class="btn-primary opacity-50 cursor-not-allowed" disabled>
                <i class="fas fa-save mr-2"></i> Salvar Configurações
            </button>
        </form>
    </div>
    
    <!-- Configurações do AVA -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Configurações do AVA</h2>
        
        <form action="#" method="post" class="space-y-4">
            <div class="form-group">
                <label for="default_course_status" class="block text-sm font-medium text-gray-700 mb-1">Status Padrão de Novos Cursos</label>
                <select id="default_course_status" name="default_course_status" class="form-select" disabled>
                    <option value="draft">Rascunho</option>
                    <option value="published" selected>Publicado</option>
                    <option value="archived">Arquivado</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="items_per_page" class="block text-sm font-medium text-gray-700 mb-1">Itens por Página</label>
                <input type="number" id="items_per_page" name="items_per_page" class="form-input" value="10" min="5" max="50" disabled>
            </div>
            
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Opções de Exibição</label>
                <div class="space-y-2">
                    <div class="flex items-center">
                        <input type="checkbox" id="show_progress" name="show_progress" class="form-checkbox" checked disabled>
                        <label for="show_progress" class="ml-2 text-sm text-gray-700">Mostrar progresso do aluno</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="show_certificates" name="show_certificates" class="form-checkbox" checked disabled>
                        <label for="show_certificates" class="ml-2 text-sm text-gray-700">Mostrar certificados disponíveis</label>
                    </div>
                </div>
            </div>
            
            <button type="button" class="btn-primary opacity-50 cursor-not-allowed" disabled>
                <i class="fas fa-save mr-2"></i> Salvar Configurações
            </button>
        </form>
    </div>
</div>

<?php
// Inclui o fim do layout
include 'includes/layout_end.php';
?>
