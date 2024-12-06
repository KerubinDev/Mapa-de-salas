/**
 * Gerenciador de Autenticação Global
 */
class AuthManager {
    constructor() {
        this.token = localStorage.getItem('token');
        this.configureAuthHeaders();
    }

    /**
     * Configura os headers de autenticação para todas as requisições
     */
    configureAuthHeaders() {
        if (window.fetch) {
            const originalFetch = window.fetch;
            window.fetch = (url, options = {}) => {
                options.headers = options.headers || {};
                if (this.token) {
                    options.headers['Authorization'] = `Bearer ${this.token}`;
                }
                return originalFetch(url, options);
            };
        }
    }

    /**
     * Atualiza o token de autenticação
     */
    setToken(token) {
        this.token = token;
        if (token) {
            localStorage.setItem('token', token);
        } else {
            localStorage.removeItem('token');
        }
    }

    /**
     * Remove o token e dados do usuário
     */
    clearAuth() {
        this.token = null;
        localStorage.removeItem('token');
        localStorage.removeItem('usuario');
    }

    /**
     * Verifica se o usuário está autenticado
     */
    isAuthenticated() {
        return !!this.token;
    }
}

// Cria uma instância global do gerenciador de autenticação
window.authManager = new AuthManager(); 