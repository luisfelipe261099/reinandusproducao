/**
 * ============================================================================
 * JAVASCRIPT MÓDULO ADMINISTRADOR - FACIÊNCIA ERP
 * ============================================================================
 * 
 * Funções JavaScript para o módulo administrador do sistema Faciência ERP.
 * Inclui utilitários, validações, e funcionalidades interativas.
 * 
 * @author Sistema Faciência ERP
 * @version 1.0
 * @since 2025-06-10
 * ============================================================================
 */

// ============================================================================
// CONFIGURAÇÕES E CONSTANTES
// ============================================================================
const ADMIN = {
    config: {
        refreshInterval: 30000, // 30 segundos
        notificationDuration: 5000, // 5 segundos
        loadingDelay: 500, // 0.5 segundos
        animationDuration: 300 // 0.3 segundos
    },
    
    elements: {
        body: document.body,
        modals: {},
        notifications: null
    },
    
    state: {
        isLoading: false,
        activeModal: null,
        autoRefresh: true
    }
};

// ============================================================================
// INICIALIZAÇÃO
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    initializeAdmin();
});

function initializeAdmin() {
    console.log('🔧 Inicializando módulo administrador...');
    
    // Configurar elementos base
    setupNotificationContainer();
    setupModalHandlers();
    setupFormValidation();
    setupAutoRefresh();
    setupKeyboardShortcuts();
    
    // Registrar ação de acesso
    registrarAcao('admin', 'acesso_pagina', `Página acessada: ${getCurrentPageName()}`);
    
    console.log('✅ Módulo administrador inicializado com sucesso');
}

// ============================================================================
// SISTEMA DE NOTIFICAÇÕES
// ============================================================================
function setupNotificationContainer() {
    const container = document.createElement('div');
    container.id = 'admin-notifications';
    container.className = 'fixed top-4 right-4 z-50 space-y-2';
    document.body.appendChild(container);
    ADMIN.elements.notifications = container;
}

function showNotification(message, type = 'info', duration = ADMIN.config.notificationDuration) {
    const notification = document.createElement('div');
    const iconMap = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    
    const colorMap = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    notification.className = `
        ${colorMap[type]} text-white px-4 py-3 rounded-lg shadow-lg
        flex items-center space-x-3 transform translate-x-full
        transition-transform duration-300 max-w-md
    `;
    
    notification.innerHTML = `
        <i class="${iconMap[type]}"></i>
        <span class="flex-1">${message}</span>
        <button onclick="removeNotification(this)" class="text-white hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    ADMIN.elements.notifications.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Auto remover
    if (duration > 0) {
        setTimeout(() => {
            removeNotification(notification);
        }, duration);
    }
    
    return notification;
}

function removeNotification(element) {
    if (typeof element === 'string') {
        element = element.parentElement;
    }
    
    element.style.transform = 'translateX(100%)';
    setTimeout(() => {
        if (element.parentNode) {
            element.parentNode.removeChild(element);
        }
    }, 300);
}

// ============================================================================
// SISTEMA DE MODAIS
// ============================================================================
function setupModalHandlers() {
    // Fechar modal ao clicar fora
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('fixed') && e.target.classList.contains('inset-0')) {
            const modalId = e.target.id;
            if (modalId && modalId.includes('modal')) {
                fecharModal(modalId);
            }
        }
    });
    
    // Fechar modal com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && ADMIN.state.activeModal) {
            fecharModal(ADMIN.state.activeModal);
        }
    });
}

function abrirModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        ADMIN.state.activeModal = modalId;
        document.body.style.overflow = 'hidden';
        
        // Focar no primeiro input
        const firstInput = modal.querySelector('input, select, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

function fecharModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        ADMIN.state.activeModal = null;
        document.body.style.overflow = '';
        
        // Limpar formulários
        const forms = modal.querySelectorAll('form');
        forms.forEach(form => {
            if (form.dataset.keepData !== 'true') {
                form.reset();
            }
        });
    }
}

// ============================================================================
// VALIDAÇÃO DE FORMULÁRIOS
// ============================================================================
function setupFormValidation() {
    const forms = document.querySelectorAll('form[data-validate="true"]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
                showNotification('Por favor, corrija os erros no formulário', 'error');
            }
        });
        
        // Validação em tempo real
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => clearFieldError(input));
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const required = field.hasAttribute('required');
    
    // Limpar erros anteriores
    clearFieldError(field);
    
    // Campo obrigatório vazio
    if (required && !value) {
        showFieldError(field, 'Este campo é obrigatório');
        return false;
    }
    
    // Validações específicas por tipo
    if (value) {
        switch (type) {
            case 'email':
                if (!isValidEmail(value)) {
                    showFieldError(field, 'Email inválido');
                    return false;
                }
                break;
                
            case 'password':
                const minLength = field.getAttribute('minlength') || 6;
                if (value.length < minLength) {
                    showFieldError(field, `Senha deve ter pelo menos ${minLength} caracteres`);
                    return false;
                }
                break;
                
            case 'number':
                const min = field.getAttribute('min');
                const max = field.getAttribute('max');
                const numValue = parseFloat(value);
                
                if (isNaN(numValue)) {
                    showFieldError(field, 'Valor deve ser um número');
                    return false;
                }
                
                if (min && numValue < parseFloat(min)) {
                    showFieldError(field, `Valor mínimo: ${min}`);
                    return false;
                }
                
                if (max && numValue > parseFloat(max)) {
                    showFieldError(field, `Valor máximo: ${max}`);
                    return false;
                }
                break;
        }
    }
    
    return true;
}

function showFieldError(field, message) {
    field.classList.add('border-red-500');
    
    let errorElement = field.parentNode.querySelector('.field-error');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'field-error text-red-500 text-sm mt-1';
        field.parentNode.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
}

function clearFieldError(field) {
    field.classList.remove('border-red-500');
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
}

// ============================================================================
// UTILITÁRIOS DE VALIDAÇÃO
// ============================================================================
function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function isValidCPF(cpf) {
    cpf = cpf.replace(/[^\d]/g, '');
    
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
        return false;
    }
    
    let sum = 0;
    for (let i = 0; i < 9; i++) {
        sum += parseInt(cpf.charAt(i)) * (10 - i);
    }
    
    let digit = 11 - (sum % 11);
    if (digit === 10 || digit === 11) digit = 0;
    if (digit !== parseInt(cpf.charAt(9))) return false;
    
    sum = 0;
    for (let i = 0; i < 10; i++) {
        sum += parseInt(cpf.charAt(i)) * (11 - i);
    }
    
    digit = 11 - (sum % 11);
    if (digit === 10 || digit === 11) digit = 0;
    if (digit !== parseInt(cpf.charAt(10))) return false;
    
    return true;
}

function isValidCNPJ(cnpj) {
    cnpj = cnpj.replace(/[^\d]/g, '');
    
    if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) {
        return false;
    }
    
    const weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    const weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    
    let sum = 0;
    for (let i = 0; i < 12; i++) {
        sum += parseInt(cnpj.charAt(i)) * weights1[i];
    }
    
    let digit = 11 - (sum % 11);
    if (digit === 10 || digit === 11) digit = 0;
    if (digit !== parseInt(cnpj.charAt(12))) return false;
    
    sum = 0;
    for (let i = 0; i < 13; i++) {
        sum += parseInt(cnpj.charAt(i)) * weights2[i];
    }
    
    digit = 11 - (sum % 11);
    if (digit === 10 || digit === 11) digit = 0;
    if (digit !== parseInt(cnpj.charAt(13))) return false;
    
    return true;
}

// ============================================================================
// SISTEMA DE LOADING
// ============================================================================
function showLoading(element, text = 'Carregando...') {
    if (typeof element === 'string') {
        element = document.getElementById(element);
    }
    
    if (!element) return;
    
    ADMIN.state.isLoading = true;
    
    const originalContent = element.innerHTML;
    element.dataset.originalContent = originalContent;
    
    element.innerHTML = `
        <div class="flex items-center justify-center space-x-2">
            <div class="loading-spinner"></div>
            <span>${text}</span>
        </div>
    `;
    
    element.disabled = true;
}

function hideLoading(element) {
    if (typeof element === 'string') {
        element = document.getElementById(element);
    }
    
    if (!element) return;
    
    ADMIN.state.isLoading = false;
    
    const originalContent = element.dataset.originalContent;
    if (originalContent) {
        element.innerHTML = originalContent;
        delete element.dataset.originalContent;
    }
    
    element.disabled = false;
}

// ============================================================================
// AUTO REFRESH
// ============================================================================
function setupAutoRefresh() {
    if (!ADMIN.state.autoRefresh) return;
    
    setInterval(() => {
        if (!document.hidden && !ADMIN.state.isLoading) {
            refreshPageData();
        }
    }, ADMIN.config.refreshInterval);
}

async function refreshPageData() {
    try {
        // Atualizar estatísticas se existirem na página
        const statsElements = document.querySelectorAll('[data-stat]');
        if (statsElements.length > 0) {
            await updateStatistics();
        }
        
        // Atualizar dados de tabelas se existirem
        const tables = document.querySelectorAll('[data-auto-refresh="true"]');
        tables.forEach(updateTableData);
        
    } catch (error) {
        console.error('Erro no auto refresh:', error);
    }
}

async function updateStatistics() {
    try {
        const response = await fetch('includes/ajax.php?acao=estatisticas_modulos');
        const data = await response.json();
        
        if (data.success) {
            // Atualizar elementos com os novos dados
            Object.keys(data).forEach(key => {
                const element = document.querySelector(`[data-stat="${key}"]`);
                if (element && key !== 'success') {
                    animateNumberChange(element, data[key]);
                }
            });
        }
    } catch (error) {
        console.error('Erro ao atualizar estatísticas:', error);
    }
}

function animateNumberChange(element, newValue) {
    const currentValue = parseInt(element.textContent.replace(/[^\d]/g, '')) || 0;
    const increment = (newValue - currentValue) / 20;
    let current = currentValue;
    
    const interval = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= newValue) || (increment < 0 && current <= newValue)) {
            element.textContent = formatNumber(newValue);
            clearInterval(interval);
        } else {
            element.textContent = formatNumber(Math.round(current));
        }
    }, 50);
}

// ============================================================================
// ATALHOS DE TECLADO
// ============================================================================
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K: Busca rápida
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            openQuickSearch();
        }
        
        // Ctrl/Cmd + N: Novo item (contexto dependente)
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            handleNewItemShortcut();
        }
        
        // Ctrl/Cmd + S: Salvar formulário ativo
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            const activeForm = document.querySelector('form:focus-within');
            if (activeForm) {
                e.preventDefault();
                activeForm.requestSubmit();
            }
        }
    });
}

function openQuickSearch() {
    // Implementar busca rápida se necessário
    showNotification('Busca rápida não implementada ainda', 'info');
}

function handleNewItemShortcut() {
    const currentPage = getCurrentPageName();
    
    switch (currentPage) {
        case 'usuarios':
            if (typeof abrirModalNovoUsuario === 'function') {
                abrirModalNovoUsuario();
            }
            break;
        default:
            showNotification('Atalho não disponível nesta página', 'info');
    }
}

// ============================================================================
// UTILITÁRIOS GERAIS
// ============================================================================
function getCurrentPageName() {
    const path = window.location.pathname;
    const filename = path.split('/').pop();
    return filename.replace('.php', '') || 'index';
}

function formatNumber(num) {
    return new Intl.NumberFormat('pt-BR').format(num);
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

function formatDate(date, format = 'short') {
    const options = {
        short: { day: '2-digit', month: '2-digit', year: 'numeric' },
        long: { day: 'numeric', month: 'long', year: 'numeric' },
        datetime: { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }
    };
    
    return new Intl.DateTimeFormat('pt-BR', options[format]).format(new Date(date));
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// ============================================================================
// REGISTRO DE AÇÕES
// ============================================================================
async function registrarAcao(modulo, acao, descricao, dados = {}) {
    try {
        const formData = new FormData();
        formData.append('acao', 'registrar_acao');
        formData.append('modulo', modulo);
        formData.append('acao_usuario', acao);
        formData.append('descricao', descricao);
        formData.append('dados', JSON.stringify(dados));
        
        await fetch('includes/ajax.php', {
            method: 'POST',
            body: formData
        });
    } catch (error) {
        console.error('Erro ao registrar ação:', error);
    }
}

// ============================================================================
// EXPORTAÇÃO DE FUNÇÕES GLOBAIS
// ============================================================================
window.ADMIN = ADMIN;
window.showNotification = showNotification;
window.removeNotification = removeNotification;
window.abrirModal = abrirModal;
window.fecharModal = fecharModal;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.registrarAcao = registrarAcao;
window.formatNumber = formatNumber;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.isValidEmail = isValidEmail;
window.isValidCPF = isValidCPF;
window.isValidCNPJ = isValidCNPJ;

// ============================================================================
// CONSOLE LOG DE INICIALIZAÇÃO
// ============================================================================
console.log(`
🔧 FACIÊNCIA ERP - MÓDULO ADMINISTRADOR
====================================
Versão: 1.0
Data: ${new Date().toLocaleDateString('pt-BR')}
Página: ${getCurrentPageName()}
====================================
✅ Sistema inicializado com sucesso
`);

// ============================================================================
// FIM DO ARQUIVO
// ============================================================================
