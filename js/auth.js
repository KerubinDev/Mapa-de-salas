/**
 * Gerenciador de Autenticação Global
 */
class Auth {
    constructor() {
        this._usuario = JSON.parse(localStorage.getItem('usuario')) || 
                       JSON.parse(sessionStorage.getItem('usuario'));
    }

    /**
     * Verifica se o usuário está autenticado
     */
    verificarAutenticacao() {
        if (!this._usuario) {
            window.location.href = '/login.html';
            return false;
        }
        return true;
    }

    /**
     * Retorna o usuário atual
     */
    getUsuario() {
        return this._usuario;
    }

    /**
     * Realiza o logout
     */
    async logout() {
        try {
            const resposta = await fetch('/api/auth/logout.php', {
                method: 'POST'
            });

            if (!resposta.ok) {
                throw new Error('Erro ao realizar logout');
            }

            localStorage.removeItem('usuario');
            sessionStorage.removeItem('usuario');
            window.location.href = '/login.html';
        } catch (erro) {
            console.error('Erro ao fazer logout:', erro);
            alert('Erro ao realizar logout');
        }
    }
}

// Instancia o gerenciador de autenticação globalmente
window.auth = new Auth(); 