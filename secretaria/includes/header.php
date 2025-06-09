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

// Função para obter notificações da secretaria
function getNotificacoesSecretaria() {
    try {
        $db = Database::getInstance();
        $notificacoes = [];

        // Matrículas pendentes
        $matriculasPendentes = $db->fetchOne("SELECT COUNT(*) as total FROM matriculas WHERE status = 'pendente'");
        if ($matriculasPendentes['total'] > 0) {
            $notificacoes[] = [
                'tipo' => 'matriculas',
                'titulo' => 'Matrículas Pendentes',
                'mensagem' => $matriculasPendentes['total'] . ' matrícula(s) aguardando aprovação',
                'icone' => 'fas fa-file-signature',
                'cor' => 'text-yellow-600',
                'link' => 'matriculas.php?status=pendente'
            ];
        }

        // Documentos vencendo
        $documentosVencendo = $db->fetchOne("
            SELECT COUNT(*) as total FROM documentos
            WHERE data_validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            AND data_validade >= CURDATE()
        ");
        if ($documentosVencendo['total'] > 0) {
            $notificacoes[] = [
                'tipo' => 'documentos',
                'titulo' => 'Documentos Vencendo',
                'mensagem' => $documentosVencendo['total'] . ' documento(s) vencem em 30 dias',
                'icone' => 'fas fa-exclamation-triangle',
                'cor' => 'text-orange-600',
                'link' => 'documentos.php?filtro=vencendo'
            ];
        }

        // Chamados em aberto
        $chamadosAbertos = $db->fetchOne("SELECT COUNT(*) as total FROM chamados WHERE status = 'aberto'");
        if ($chamadosAbertos['total'] > 0) {
            $notificacoes[] = [
                'tipo' => 'chamados',
                'titulo' => 'Chamados em Aberto',
                'mensagem' => $chamadosAbertos['total'] . ' chamado(s) aguardando atendimento',
                'icone' => 'fas fa-headset',
                'cor' => 'text-red-600',
                'link' => 'chamados/index.php?status=aberto'
            ];
        }

        return $notificacoes;
    } catch (Exception) {
        return [];
    }
}

$notificacoes = getNotificacoesSecretaria();
$totalNotificacoes = count($notificacoes);
?>

<!-- Header Moderno - Módulo Secretaria -->
<header class="bg-white border-b border-gray-200 shadow-sm">
    <div class="px-6 py-4">
        <div class="flex items-center justify-between">
            <!-- Título da Página -->
            <div class="flex items-center">
                <!-- Botão de menu para mobile -->
                <button id="mobile-menu-button" class="lg:hidden mr-4 p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                    <i class="fas fa-bars text-lg"></i>
                </button>

                <div class="hidden md:block">
                    <h1 class="text-2xl font-bold text-gray-900">
                        <?php
                        $pageTitles = [
                            'secretaria_dashboard.php' => 'Dashboard',
                            'alunos.php' => 'Gestão de Alunos',
                            'matriculas.php' => 'Matrículas',
                            'notas.php' => 'Notas e Frequências',
                            'documentos.php' => 'Documentos',
                            'declaracoes.php' => 'Declarações de Matrícula',
                            'historicos.php' => 'Históricos Escolares',
                            'polos.php' => 'Polos',
                            'cursos.php' => 'Cursos',
                            'turmas.php' => 'Turmas',
                            'disciplinas.php' => 'Disciplinas'
                        ];

                        $currentPage = basename($_SERVER['PHP_SELF']);
                        echo $pageTitles[$currentPage] ?? 'Secretaria Acadêmica';
                        ?>
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">Módulo Secretaria - Faciência ERP</p>
                </div>
                <div class="md:hidden">
                    <h1 class="text-lg font-semibold text-gray-900">Secretaria</h1>
                </div>
            </div>

            <!-- Ações do Header -->
            <div class="flex items-center space-x-4">
                <!-- Indicadores Rápidos -->
                <div class="hidden lg:flex items-center space-x-6 mr-6">
                    <div class="text-center">
                        <div class="text-lg font-bold text-blue-600" id="total-alunos">-</div>
                        <div class="text-xs text-gray-500">Alunos</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold text-green-600" id="matriculas-ativas">-</div>
                        <div class="text-xs text-gray-500">Matrículas</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold text-purple-600" id="total-cursos">-</div>
                        <div class="text-xs text-gray-500">Cursos</div>
                    </div>
                </div>

                <!-- Notificações -->
                <div class="relative">
                    <button onclick="toggleNotificacoes()" class="relative p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                        <i class="fas fa-bell text-lg"></i>
                        <?php if ($totalNotificacoes > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            <?php echo min($totalNotificacoes, 9); ?><?php echo $totalNotificacoes > 9 ? '+' : ''; ?>
                        </span>
                        <?php endif; ?>
                    </button>

                    <!-- Dropdown de Notificações -->
                    <div id="notificacoes-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-900">Notificações</h3>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <?php if (empty($notificacoes)): ?>
                            <div class="p-4 text-center text-gray-500">
                                <i class="fas fa-check-circle text-2xl mb-2"></i>
                                <p class="text-sm">Nenhuma notificação</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($notificacoes as $notificacao): ?>
                            <a href="<?php echo $notificacao['link']; ?>" class="block p-4 hover:bg-gray-50 border-b border-gray-100">
                                <div class="flex items-start">
                                    <i class="<?php echo $notificacao['icone']; ?> <?php echo $notificacao['cor']; ?> mt-1 mr-3"></i>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900"><?php echo $notificacao['titulo']; ?></p>
                                        <p class="text-xs text-gray-600"><?php echo $notificacao['mensagem']; ?></p>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Menu do Usuário -->
                <div class="relative">
                    <button onclick="toggleUserMenu()" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-50 transition-colors">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode(getUsuarioNome()); ?>&background=3b82f6&color=fff"
                             alt="Avatar" class="w-8 h-8 rounded-full">
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-medium text-gray-900"><?php echo getUsuarioNome(); ?></p>
                            <p class="text-xs text-gray-500">Secretaria Acadêmica</p>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                    </button>

                    <!-- Dropdown do Usuário -->
                    <div id="user-menu-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                        <div class="py-1">
                            <a href="perfil.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-user mr-3 text-gray-400"></i>
                                Meu Perfil
                            </a>
                            <a href="configuracoes.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-cog mr-3 text-gray-400"></i>
                                Configurações
                            </a>
                            <div class="border-t border-gray-100"></div>
                            <a href="logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-3"></i>
                                Sair
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
// Funções para toggle dos dropdowns
function toggleNotificacoes() {
    const dropdown = document.getElementById('notificacoes-dropdown');
    const userDropdown = document.getElementById('user-menu-dropdown');

    dropdown.classList.toggle('hidden');
    userDropdown.classList.add('hidden');
}

function toggleUserMenu() {
    const dropdown = document.getElementById('user-menu-dropdown');
    const notifDropdown = document.getElementById('notificacoes-dropdown');

    dropdown.classList.toggle('hidden');
    notifDropdown.classList.add('hidden');
}

// Fecha dropdowns ao clicar fora
document.addEventListener('click', function(event) {
    const notifButton = event.target.closest('[onclick="toggleNotificacoes()"]');
    const userButton = event.target.closest('[onclick="toggleUserMenu()"]');
    const notifDropdown = document.getElementById('notificacoes-dropdown');
    const userDropdown = document.getElementById('user-menu-dropdown');

    if (!notifButton && !notifDropdown.contains(event.target)) {
        notifDropdown.classList.add('hidden');
    }

    if (!userButton && !userDropdown.contains(event.target)) {
        userDropdown.classList.add('hidden');
    }
});

// Controle do menu mobile
const mobileMenuButton = document.getElementById('mobile-menu-button');
if (mobileMenuButton) {
    mobileMenuButton.addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        if (sidebar && overlay) {
            sidebar.classList.add('sidebar-expanded');
            overlay.classList.remove('hidden');
        }
    });
}

// Carrega indicadores rápidos
fetch('ajax/indicadores_rapidos.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('total-alunos').textContent = data.total_alunos || '0';
            document.getElementById('matriculas-ativas').textContent = data.matriculas_ativas || '0';
            document.getElementById('total-cursos').textContent = data.total_cursos || '0';
        }
    })
    .catch(error => console.log('Erro ao carregar indicadores:', error));
</script>
