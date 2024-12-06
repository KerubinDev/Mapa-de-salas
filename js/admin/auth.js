class GerenciadorAdmin {
    constructor() {
        console.log('Iniciando GerenciadorAdmin...');
        this._init();
    }

    async _init() {
        try {
            await this.verificarAutenticacao();
        } catch (erro) {
            console.error('Erro na inicialização:', erro);
            this.redirecionarParaLogin();
        }
    }

    async verificarAutenticacao() {
        const token = localStorage.getItem('token');
        const usuarioStr = localStorage.getItem('usuario');

        console.log('Dados de autenticação:', {
            token: token ? 'presente' : 'ausente',
            usuario: usuarioStr ? 'presente' : 'ausente'
        });

        if (!token || !usuarioStr) {
            throw new Error('Dados de autenticação não encontrados');
        }

        // Configura o interceptador global
        this._configurarInterceptador(token);

        // Testa a autenticação
        await this._testarAutenticacao();
    }

    _configurarInterceptador(token) {
        if (!token) {
            console.error('Tentativa de configurar interceptador sem token');
            return;
        }

        console.log('Configurando interceptador com token');
        
        const self = this;
        const originalFetch = window.fetch;
        
        window.fetch = function(url, options = {}) {
            options.headers = options.headers || {};
            options.headers['Authorization'] = `Bearer ${token}`;
            
            console.log('Requisição interceptada:', {
                url,
                method: options.method || 'GET',
                headers: options.headers
            });

            return originalFetch(url, options)
                .then(async response => {
                    if (response.status === 401) {
                        console.error('Erro de autenticação na requisição');
                        self.redirecionarParaLogin();
                    }
                    return response;
                });
        };
    }

    async _testarAutenticacao() {
        console.log('Testando autenticação...');
        
        const token = localStorage.getItem('token');
        console.log('Token atual:', token);

        const resposta = await fetch('/api/auth/perfil', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
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
    }

    redirecionarParaLogin() {
        console.log('Redirecionando para login...');
        localStorage.clear();
        window.location.href = '/login.html';
    }
}

// Inicializa o gerenciador
document.addEventListener('DOMContentLoaded', () => {
    console.log('Página carregada, iniciando gerenciador...');
    window.gerenciadorAdmin = new GerenciadorAdmin();
}); 