<?php
/**
 * Exibe mensagens de sucesso, erro ou aviso
 */

// Verifica se há mensagens na sessão
if (isset($_SESSION['mensagens']) && !empty($_SESSION['mensagens'])) {
    foreach ($_SESSION['mensagens'] as $tipo => $mensagem) {
        // Define as classes CSS com base no tipo de mensagem
        $classes = '';
        $icone = '';
        
        switch ($tipo) {
            case 'sucesso':
                $classes = 'bg-green-100 border-l-4 border-green-500 text-green-700';
                $icone = '<i class="fas fa-check-circle mr-2"></i>';
                break;
            case 'erro':
                $classes = 'bg-red-100 border-l-4 border-red-500 text-red-700';
                $icone = '<i class="fas fa-exclamation-circle mr-2"></i>';
                break;
            case 'aviso':
                $classes = 'bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700';
                $icone = '<i class="fas fa-exclamation-triangle mr-2"></i>';
                break;
            case 'info':
                $classes = 'bg-blue-100 border-l-4 border-blue-500 text-blue-700';
                $icone = '<i class="fas fa-info-circle mr-2"></i>';
                break;
            default:
                $classes = 'bg-gray-100 border-l-4 border-gray-500 text-gray-700';
                $icone = '<i class="fas fa-info-circle mr-2"></i>';
                break;
        }
        
        // Exibe a mensagem
        echo '<div class="' . $classes . ' p-4 mb-6">';
        echo '<div class="flex">';
        echo '<div class="flex-shrink-0">' . $icone . '</div>';
        echo '<div class="ml-3">';
        echo '<p class="text-sm">' . $mensagem . '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    // Limpa as mensagens da sessão
    unset($_SESSION['mensagens']);
}
?>
