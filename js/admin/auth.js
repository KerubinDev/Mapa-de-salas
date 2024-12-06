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
        console.log('Token para teste:', token);

        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '/api/auth/perfil');
            
            // Adiciona headers
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('Authorization', `Bearer ${token}`);
            xhr.setRequestHeader('Cache-Control', 'no-cache');
            
            // Log dos headers
            console.log('Headers enviados:', {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
                'Cache-Control': 'no-cache'
            });

            xhr.onload = function() {
                console.log('Resposta XHR:', {
                    status: xhr.status,
                    response: xhr.responseText,
                    headers: xhr.getAllResponseHeaders()
                });

                if (xhr.status === 200) {
                    resolve(JSON.parse(xhr.responseText));
                } else {
                    reject(new Error('Falha na autenticação'));
                }
            };

            xhr.onerror = function() {
                console.error('Erro na requisição XHR:', xhr.statusText);
                reject(new Error('Erro na requisição'));
            };

            xhr.send();
        });
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