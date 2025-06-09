/**
 * JavaScript principal do módulo financeiro
 */

// Configurações globais
const FinanceiroConfig = {
    baseUrl: window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/'),
    ajaxUrl: 'ajax/',
    currency: 'BRL',
    locale: 'pt-BR'
};

// Utilitários
const FinanceiroUtils = {
    // Formatar moeda
    formatCurrency: function(value) {
        return new Intl.NumberFormat(FinanceiroConfig.locale, {
            style: 'currency',
            currency: FinanceiroConfig.currency
        }).format(value);
    },

    // Formatar data
    formatDate: function(date) {
        return new Intl.DateTimeFormat(FinanceiroConfig.locale).format(new Date(date));
    },

    // Formatar data e hora
    formatDateTime: function(date) {
        return new Intl.DateTimeFormat(FinanceiroConfig.locale, {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(date));
    },

    // Converter string de moeda para número
    parseCurrency: function(value) {
        if (typeof value === 'number') return value;
        return parseFloat(value.replace(/[^\d,-]/g, '').replace(',', '.')) || 0;
    },

    // Debounce para pesquisas
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Mostrar loading
    showLoading: function(element) {
        if (element) {
            element.innerHTML = '<div class="flex items-center justify-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i>Carregando...</div>';
        }
    },

    // Esconder loading
    hideLoading: function(element) {
        if (element) {
            element.innerHTML = '';
        }
    }
};

// Notificações
const FinanceiroNotifications = {
    show: function(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all duration-300 transform translate-x-full`;

        const colors = {
            success: 'bg-green-500 text-white',
            error: 'bg-red-500 text-white',
            warning: 'bg-yellow-500 text-white',
            info: 'bg-blue-500 text-white'
        };

        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };

        notification.className += ` ${colors[type] || colors.info}`;

        notification.innerHTML = `
            <div class="flex items-center">
                <i class="${icons[type] || icons.info} mr-3"></i>
                <span class="flex-1">${message}</span>
                <button class="ml-3 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        // Animar entrada
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remover
        if (duration > 0) {
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 300);
            }, duration);
        }
    },

    success: function(message, duration = 5000) {
        this.show(message, 'success', duration);
    },

    error: function(message, duration = 7000) {
        this.show(message, 'error', duration);
    },

    warning: function(message, duration = 6000) {
        this.show(message, 'warning', duration);
    },

    info: function(message, duration = 5000) {
        this.show(message, 'info', duration);
    }
};

// Modais
const FinanceiroModal = {
    show: function(title, content, options = {}) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-50 overflow-y-auto';
        modal.innerHTML = `
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="this.parentElement.parentElement.remove()"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">${title}</h3>
                        <button class="text-gray-400 hover:text-gray-600" onclick="this.closest('.fixed').remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="mb-4">
                        ${content}
                    </div>
                    <div class="flex justify-end space-x-3">
                        ${options.showCancel !== false ? '<button class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200" onclick="this.closest(\'.fixed\').remove()">Cancelar</button>' : ''}
                        ${options.confirmText ? `<button class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700" onclick="${options.onConfirm || ''}">${options.confirmText}</button>` : ''}
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        return modal;
    },

    confirm: function(title, message, onConfirm) {
        return this.show(title, `<p class="text-gray-600">${message}</p>`, {
            confirmText: 'Confirmar',
            onConfirm: `(${onConfirm.toString()})(); this.closest('.fixed').remove();`
        });
    }
};

// AJAX Helper
const FinanceiroAjax = {
    request: function(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const config = { ...defaultOptions, ...options };

        return fetch(FinanceiroConfig.ajaxUrl + url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                FinanceiroNotifications.error('Erro na comunicação com o servidor');
                throw error;
            });
    },

    get: function(url, params = {}) {
        const urlParams = new URLSearchParams(params);
        const fullUrl = urlParams.toString() ? `${url}?${urlParams}` : url;
        return this.request(fullUrl);
    },

    post: function(url, data = {}) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    put: function(url, data = {}) {
        return this.request(url, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    delete: function(url) {
        return this.request(url, {
            method: 'DELETE'
        });
    }
};

// Formulários
const FinanceiroForms = {
    // Máscara para campos de moeda
    maskCurrency: function(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (value / 100).toFixed(2);
            value = value.replace('.', ',');
            value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            e.target.value = 'R$ ' + value;
        });
    },

    // Máscara para CPF
    maskCPF: function(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });
    },

    // Validação de formulário
    validate: function(form) {
        const errors = [];
        const requiredFields = form.querySelectorAll('[required]');

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                errors.push(`O campo ${field.getAttribute('data-label') || field.name} é obrigatório`);
                field.classList.add('border-red-500');
            } else {
                field.classList.remove('border-red-500');
            }
        });

        if (errors.length > 0) {
            FinanceiroNotifications.error(errors.join('<br>'));
            return false;
        }

        return true;
    }
};

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Controle do sidebar
    const sidebar = document.getElementById('sidebar');
    const toggleButton = document.getElementById('toggle-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const mobileToggle = document.getElementById('mobile-sidebar-toggle');

    // Toggle sidebar no mobile (botão X no sidebar)
    if (toggleButton) {
        toggleButton.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-expanded');
            overlay.classList.toggle('hidden');
        });
    }

    // Toggle sidebar no mobile (botão hambúrguer no header)
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.add('sidebar-expanded');
            overlay.classList.remove('hidden');
        });
    }

    // Fecha sidebar ao clicar no overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('sidebar-expanded');
            overlay.classList.add('hidden');
        });
    }

    // Fecha sidebar ao redimensionar para desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            sidebar.classList.remove('sidebar-expanded');
            overlay.classList.add('hidden');
        }
    });

    // Aplicar máscaras automaticamente
    document.querySelectorAll('[data-mask="currency"]').forEach(input => {
        FinanceiroForms.maskCurrency(input);
    });

    document.querySelectorAll('[data-mask="cpf"]').forEach(input => {
        FinanceiroForms.maskCPF(input);
    });

    // Tooltips
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'absolute z-50 px-2 py-1 text-sm text-white bg-gray-900 rounded shadow-lg';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.top = this.offsetTop - 30 + 'px';
            tooltip.style.left = this.offsetLeft + 'px';
            this.parentElement.appendChild(tooltip);
        });

        element.addEventListener('mouseleave', function() {
            const tooltip = this.parentElement.querySelector('.absolute.z-50');
            if (tooltip) tooltip.remove();
        });
    });

    // Auto-save para formulários
    document.querySelectorAll('[data-autosave]').forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', FinanceiroUtils.debounce(() => {
                // Implementar auto-save aqui
                console.log('Auto-saving form data...');
            }, 1000));
        });
    });
});

// Exportar para uso global
window.Financeiro = {
    Config: FinanceiroConfig,
    Utils: FinanceiroUtils,
    Notifications: FinanceiroNotifications,
    Modal: FinanceiroModal,
    Ajax: FinanceiroAjax,
    Forms: FinanceiroForms
};
