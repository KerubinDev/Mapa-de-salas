/**
 * Gerenciador de Login
 */
class GerenciadorLogin {
    constructor() {
        this.apiUrl = '/api/auth/login';
        // Constantes para mensagens de erro
        this.MENSAGENS = {
            SENHA_INCORRETA: 'Senha incorreta',
            ERRO_SERVIDOR: 'Erro ao conectar com o servidor',
            USUARIO_NAO_ENCONTRADO: 'Usuário não encontrado'
        };
    }
    
    /**
     * Realiza o login do usuário
     * @param {string} email Email do usuário
     * @param {string} senha Senha do usuário
     * @returns {Promise<Object>} Dados do usuário logado
     */
    async realizarLogin(email, senha) {
        try {
            console.log('Iniciando login...');
            
            // Converte a senha para SHA-256
            const senhaHash = await this._gerarHashSenha(senha);
            console.log('Hash gerado para a senha');

            const resposta = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: email,
                    senha: senhaHash
                })
            });

            const dados = await resposta.json();
            console.log('Resposta do login:', dados);

            if (!resposta.ok || !dados.sucesso) {
                throw new Error(dados.erro?.mensagem || 'Erro no login');
            }

            // Armazena o token e dados do usuário
            localStorage.setItem('token', dados.dados.token);
            localStorage.setItem('usuario', JSON.stringify(dados.dados.usuario));

            // Aguarda um momento para garantir que os dados foram salvos
            await new Promise(resolve => setTimeout(resolve, 100));

            // Verifica se os dados foram salvos corretamente
            const tokenSalvo = localStorage.getItem('token');
            const usuarioSalvo = localStorage.getItem('usuario');

            if (!tokenSalvo || !usuarioSalvo) {
                throw new Error('Erro ao salvar dados de autenticação');
            }

            console.log('Login bem-sucedido, redirecionando...');
            window.location.href = '/admin';

        } catch (erro) {
            console.error('Erro no login:', erro);
            throw erro;
        }
    }

    _configurarHeadersAutenticacao(token) {
        // Configura o header de autorização para todas as requisições futuras
        if (window.fetch) {
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                options.headers = options.headers || {};
                options.headers['Authorization'] = `Bearer ${token}`;
                return originalFetch(url, options);
            };
        }
    }

    /**
     * Verifica se a senha fornecida corresponde à senha armazenada
     * @param {string} senhaFornecida Senha fornecida pelo usuário
     * @param {string} senhaArmazenada Hash da senha armazenada
     * @returns {Promise<boolean>} True se a senha estiver correta
     */
    async verificarSenha(senhaFornecida, senhaArmazenada) {
        const resposta = await fetch(`${this.apiUrl}/verificar-senha`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                senha: senhaFornecida,
                hash: senhaArmazenada
            })
        });

        const dados = await resposta.json();
        return dados.sucesso;
    }

    // Função auxiliar para gerar hash (apenas para debug)
    async _gerarHash(senha) {
        const encoder = new TextEncoder();
        const data = encoder.encode(senha);
        const hashBuffer = await crypto.subtle.digest('SHA-256', data);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        
        // Log para debug
        console.log('DEBUG - Hash gerado:', {
            senha: senha,
            hash: hashHex,
            hashEsperado: '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9'
        });
        
        return hashHex;
    }

    async _gerarHashSenha(senha) {
        // Converte a string para bytes
        const encoder = new TextEncoder();
        const data = encoder.encode(senha);
        
        // Gera o hash SHA-256
        const hashBuffer = await crypto.subtle.digest('SHA-256', data);
        
        // Converte para string hexadecimal
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        
        return hashHex;
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    const gerenciador = new GerenciadorLogin();
    const form = document.querySelector('form');
    const mensagemErro = document.getElementById('mensagemErro');
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Limpa mensagem de erro anterior
        if (mensagemErro) {
            mensagemErro.textContent = '';
            mensagemErro.style.display = 'none';
        }
        
        const email = form.querySelector('[name="email"]').value;
        const senha = form.querySelector('[name="senha"]').value;
        
        try {
            const resultado = await gerenciador.realizarLogin(email, senha);
            
            // Salva o token e dados do usuário
            localStorage.setItem('token', resultado.token);
            localStorage.setItem('usuario', JSON.stringify(resultado.usuario));
            
            // Redireciona baseado no tipo de usuário
            if (resultado.usuario.tipo === 'admin') {
                window.location.href = '/admin';
            } else {
                window.location.href = '/';
            }
        } catch (erro) {
            console.error('Erro no login:', erro);
            if (mensagemErro) {
                mensagemErro.textContent = erro.message;
                mensagemErro.style.display = 'block';
            } else {
                alert(erro.message);
            }
        }
    });
});