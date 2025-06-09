<?php
// Função para obter o nome do usuário (verifica se já existe)
if (!function_exists('getUsuarioNome')) {
    function getUsuarioNome() {
        // Tenta usar Auth::getUserName() primeiro
        if (method_exists('Auth', 'getUserName')) {
            return Auth::getUserName() ?? 'Usuário';
        }
        // Fallback para sessão direta
        return $_SESSION['user_nome'] ?? $_SESSION['user_name'] ?? 'Usuário';
    }
}

// Função para obter notificações financeiras
function getNotificacoesFinanceiras() {
    try {
        $db = Database::getInstance();
        $notificacoes = [];

        // Contas a pagar vencendo em 7 dias
        $contasVencendo = $db->fetchAll("
            SELECT COUNT(*) as total
            FROM contas_pagar
            WHERE status = 'pendente'
            AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ");

        if ($contasVencendo && $contasVencendo[0]['total'] > 0) {
            $notificacoes[] = [
                'tipo' => 'warning',
                'titulo' => 'Contas a Pagar',
                'mensagem' => $contasVencendo[0]['total'] . ' conta(s) vencendo em 7 dias',
                'link' => 'contas_pagar.php?filtro=vencendo'
            ];
        }

        // Contas vencidas
        $contasVencidas = $db->fetchAll("
            SELECT COUNT(*) as total
            FROM contas_pagar
            WHERE status = 'pendente'
            AND data_vencimento < CURDATE()
        ");

        if ($contasVencidas && $contasVencidas[0]['total'] > 0) {
            $notificacoes[] = [
                'tipo' => 'error',
                'titulo' => 'Contas Vencidas',
                'mensagem' => $contasVencidas[0]['total'] . ' conta(s) em atraso',
                'link' => 'contas_pagar.php?filtro=vencidas'
            ];
        }

        return $notificacoes;
    } catch (Exception) {
        return [];
    }
}

$notificacoes = getNotificacoesFinanceiras();
$totalNotificacoes = count($notificacoes);
?>

<header class="bg-white shadow-sm h-16 flex items-center justify-between px-6">
    <div class="flex items-center">
        <!-- Toggle sidebar para mobile -->
        <button id="mobile-sidebar-toggle" class="lg:hidden text-gray-600 hover:text-gray-900 mr-4">
            <i class="fas fa-bars"></i>
        </button>

        <div class="flex items-center">
            <div class="w-8 h-8 bg-green-600 rounded-md flex items-center justify-center mr-3">
                <i class="fas fa-dollar-sign text-white text-sm"></i>
            </div>
            <h1 class="text-xl font-semibold text-gray-800">Módulo Financeiro</h1>
        </div>
    </div>

    <div class="flex items-center space-x-4">
        <!-- Indicadores rápidos -->
        <div class="hidden md:flex items-center space-x-4 mr-4">
            <!-- Saldo do dia -->
            <div class="text-center">
                <p class="text-xs text-gray-500">Saldo Hoje</p>
                <p class="text-sm font-semibold text-green-600" id="saldo-hoje">R$ 0,00</p>
            </div>

            <!-- Separador -->
            <div class="w-px h-8 bg-gray-300"></div>

            <!-- Contas pendentes -->
            <div class="text-center">
                <p class="text-xs text-gray-500">Pendentes</p>
                <p class="text-sm font-semibold text-orange-600" id="contas-pendentes">0</p>
            </div>
        </div>

        <!-- Notificações -->
        <div class="relative">
            <button id="notifications-button" class="text-gray-500 hover:text-gray-700 focus:outline-none relative">
                <i class="fas fa-bell text-lg"></i>
                <?php if ($totalNotificacoes > 0): ?>
                <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform bg-red-500 rounded-full">
                    <?php echo min($totalNotificacoes, 9); ?><?php echo $totalNotificacoes > 9 ? '+' : ''; ?>
                </span>
                <?php endif; ?>
            </button>

            <!-- Dropdown de notificações -->
            <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg py-1 z-50 border">
                <div class="px-4 py-2 border-b">
                    <h3 class="text-sm font-semibold text-gray-900">Notificações Financeiras</h3>
                </div>

                <?php if (empty($notificacoes)): ?>
                <div class="px-4 py-3 text-center text-gray-500">
                    <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
                    <p class="text-sm">Nenhuma notificação pendente</p>
                </div>
                <?php else: ?>
                <div class="max-h-64 overflow-y-auto">
                    <?php foreach ($notificacoes as $notificacao): ?>
                    <a href="<?php echo $notificacao['link']; ?>" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <?php if ($notificacao['tipo'] == 'warning'): ?>
                                <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                                <?php elseif ($notificacao['tipo'] == 'error'): ?>
                                <i class="fas fa-exclamation-circle text-red-500"></i>
                                <?php else: ?>
                                <i class="fas fa-info-circle text-blue-500"></i>
                                <?php endif; ?>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900"><?php echo $notificacao['titulo']; ?></p>
                                <p class="text-xs text-gray-600"><?php echo $notificacao['mensagem']; ?></p>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <div class="px-4 py-2 border-t">
                    <a href="relatorios.php" class="text-xs text-green-600 hover:text-green-800">Ver todos os relatórios</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Menu do usuário -->
        <div class="relative">
            <button id="user-menu-button" class="flex items-center text-gray-700 hover:text-gray-900 focus:outline-none">
                <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center mr-2">
                    <i class="fas fa-user text-white text-sm"></i>
                </div>
                <span class="hidden md:block font-medium"><?php echo getUsuarioNome(); ?></span>
                <i class="fas fa-chevron-down ml-2 text-xs"></i>
            </button>

            <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border">
                <div class="px-4 py-2 border-b">
                    <p class="text-sm font-medium text-gray-900"><?php echo getUsuarioNome(); ?></p>
                    <p class="text-xs text-gray-600">Módulo Financeiro</p>
                </div>
                <a href="perfil.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-user mr-2"></i>Meu Perfil
                </a>
                <a href="configuracoes.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-cog mr-2"></i>Configurações
                </a>
                <div class="border-t border-gray-100"></div>
                <a href="../secretaria/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-home mr-2"></i>Sistema Principal
                </a>
                <a href="../logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-sign-out-alt mr-2"></i>Sair
                </a>
            </div>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle do menu de usuário
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenu = document.getElementById('user-menu');

    if (userMenuButton && userMenu) {
        userMenuButton.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('hidden');

            // Fecha o menu de notificações se estiver aberto
            const notificationsDropdown = document.getElementById('notifications-dropdown');
            if (notificationsDropdown) {
                notificationsDropdown.classList.add('hidden');
            }
        });
    }

    // Toggle do menu de notificações
    const notificationsButton = document.getElementById('notifications-button');
    const notificationsDropdown = document.getElementById('notifications-dropdown');

    if (notificationsButton && notificationsDropdown) {
        notificationsButton.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationsDropdown.classList.toggle('hidden');

            // Fecha o menu de usuário se estiver aberto
            if (userMenu) {
                userMenu.classList.add('hidden');
            }
        });
    }

    // Toggle do sidebar mobile
    const mobileSidebarToggle = document.getElementById('mobile-sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    if (mobileSidebarToggle && sidebar) {
        mobileSidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-expanded');
            sidebar.classList.toggle('sidebar-collapsed');

            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('hidden');
            }
        });
    }

    // Fecha menus ao clicar fora
    document.addEventListener('click', function() {
        if (userMenu) userMenu.classList.add('hidden');
        if (notificationsDropdown) notificationsDropdown.classList.add('hidden');
    });

    // Carrega indicadores rápidos
    carregarIndicadoresRapidos();
});

function carregarIndicadoresRapidos() {
    // Aqui você pode fazer uma requisição AJAX para buscar os dados atualizados
    // Por enquanto, vamos simular com dados estáticos

    fetch('ajax/indicadores_rapidos.php')
        .then(response => response.json())
        .then(data => {
            const saldoHoje = document.getElementById('saldo-hoje');
            const contasPendentes = document.getElementById('contas-pendentes');

            if (saldoHoje && data.saldo_hoje !== undefined) {
                saldoHoje.textContent = 'R$ ' + data.saldo_hoje.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                // Muda a cor baseado no valor
                if (data.saldo_hoje >= 0) {
                    saldoHoje.className = 'text-sm font-semibold text-green-600';
                } else {
                    saldoHoje.className = 'text-sm font-semibold text-red-600';
                }
            }

            if (contasPendentes && data.contas_pendentes !== undefined) {
                contasPendentes.textContent = data.contas_pendentes;
            }
        })
        .catch(error => {
            console.log('Erro ao carregar indicadores:', error);
        });
}

// Atualiza indicadores a cada 5 minutos
setInterval(carregarIndicadoresRapidos, 300000);
</script>
