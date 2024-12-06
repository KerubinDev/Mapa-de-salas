class GerenciadorAdmin {
    constructor() {
        console.log('Iniciando verificação de autenticação...');
        this.verificarAutenticacao();
    }

    verificarAutenticacao() {
        const token = localStorage.getItem('token');
        const usuario = JSON.parse(localStorage.getItem('usuario') || '{}');

        console.log('Dados de autenticação:', {
            temToken: !!token,
            token: token,
            usuario: usuario,
            pathname: window.location.pathname
        });

        // Se não houver token ou usuário, redireciona para login
        if (!token || !usuario) {
            console.error('Token ou usuário não encontrado');
            this.redirecionarParaLogin();
            return;
        }

        // Verifica se o usuário é admin
        if (usuario.tipo !== 'admin') {
            console.error('Usuário não é admin:', usuario.tipo);
            this.redirecionarParaLogin();
            return;
        }

        console.log('Usuário autenticado com sucesso:', {
            id: usuario.id,
            tipo: usuario.tipo,
            email: usuario.email
        });

        // Configura o header de autorização para todas as requisições
        this._configurarHeadersAutenticacao(token);

        // Teste de autenticação
        this._testarAutenticacao();
    }

    async _testarAutenticacao() {
        try {
            console.log('Testando autenticação com o servidor...');
            const resposta = await fetch('/api/auth/perfil', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            console.log('Resposta do teste de autenticação:', {
                status: resposta.status,
                headers: Object.fromEntries(resposta.headers.entries())
            });

            const dados = await resposta.json();
            console.log('Dados do perfil:', dados);

            if (!resposta.ok) {
                throw new Error('Falha na autenticação');
            }
        } catch (erro) {
            console.error('Erro no teste de autenticação:', erro);
            this.redirecionarParaLogin();
        }
    }

    _configurarHeadersAutenticacao(token) {
        console.log('Configurando headers de autenticação');
        if (window.fetch) {
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                console.log('Requisição interceptada:', {
                    url: url,
                    method: options.method || 'GET',
                    headers: options.headers
                });

                options.headers = options.headers || {};
                options.headers['Authorization'] = `Bearer ${token}`;
                return originalFetch(url, options);
            };
        }
    }

    redirecionarParaLogin() {
        console.log('Redirecionando para login...');
        localStorage.removeItem('token');
        localStorage.removeItem('usuario');
        window.location.href = '/login.html';
    }
}

// Inicializa o gerenciador
document.addEventListener('DOMContentLoaded', () => {
    console.log('Página carregada, iniciando gerenciador...');
    const gerenciador = new GerenciadorAdmin();
}); 