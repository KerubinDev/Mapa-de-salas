class GerenciadorAdmin {
    constructor() {
        this.verificarAutenticacao();
    }

    verificarAutenticacao() {
        const token = localStorage.getItem('token');
        const usuario = JSON.parse(localStorage.getItem('usuario') || '{}');

        // Se não houver token ou usuário, redireciona para login
        if (!token || !usuario) {
            this.redirecionarParaLogin();
            return;
        }

        // Verifica se o usuário é admin
        if (usuario.tipo !== 'admin') {
            this.redirecionarParaLogin();
            return;
        }

        // Configura o header de autorização para todas as requisições
        this._configurarHeadersAutenticacao(token);
    }

    _configurarHeadersAutenticacao(token) {
        if (window.fetch) {
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                options.headers = options.headers || {};
                options.headers['Authorization'] = `Bearer ${token}`;
                return originalFetch(url, options);
            };
        }
    }

    redirecionarParaLogin() {
        localStorage.removeItem('token');
        localStorage.removeItem('usuario');
        window.location.href = '/login.html';
    }
}

// Inicializa o gerenciador
document.addEventListener('DOMContentLoaded', () => {
    const gerenciador = new GerenciadorAdmin();
}); 