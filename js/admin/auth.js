class GerenciadorAdmin {
    constructor() {
        console.log('Iniciando GerenciadorAdmin...');
        this.verificarAutenticacao();
    }

    verificarAutenticacao() {
        const token = localStorage.getItem('token');
        const usuario = JSON.parse(localStorage.getItem('usuario') || '{}');

        console.log('Verificando autenticação:', {
            temToken: !!token,
            temUsuario: !!usuario.id
        });

        if (!token || !usuario.id) {
            console.error('Token ou usuário não encontrado');
            this.redirecionarParaLogin();
            return;
        }

        // Configura o interceptador de requisições
        this._configurarInterceptador(token);
        
        // Testa a autenticação
        this._testarAutenticacao();
    }

    _configurarInterceptador(token) {
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            // Garante que options.headers existe
            options.headers = options.headers || {};
            
            // Adiciona o token em todas as requisições
            options.headers['Authorization'] = `Bearer ${token}`;
            
            console.log('Interceptando requisição:', {
                url: url,
                method: options.method || 'GET',
                headers: options.headers
            });

            return originalFetch(url, options);
        };
    }

    async _testarAutenticacao() {
        try {
            console.log('Testando autenticação...');
            const resposta = await fetch('/api/auth/perfil', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Cache-Control': 'no-cache'
                }
            });

            console.log('Resposta do teste:', {
                status: resposta.status,
                headers: Object.fromEntries(resposta.headers)
            });

            if (!resposta.ok) {
                throw new Error('Falha na autenticação');
            }

            const dados = await resposta.json();
            console.log('Perfil autenticado:', dados);

        } catch (erro) {
            console.error('Erro no teste de autenticação:', erro);
            this.redirecionarParaLogin();
        }
    }

    redirecionarParaLogin() {
        console.log('Redirecionando para login...');
        localStorage.removeItem('token');
        localStorage.removeItem('usuario');
        window.location.href = '/login.html';
    }
}

// Inicializa o gerenciador quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    console.log('Página carregada, iniciando gerenciador...');
    window.gerenciadorAdmin = new GerenciadorAdmin();
}); 