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
            // Validação básica dos inputs
            if (!email || !senha) {
                throw new Error('Email e senha são obrigatórios');
            }

            // Gera o hash da senha localmente para comparação
            const hashLocal = await this._gerarHash(senha);
            console.log('DEBUG - Comparação de hashes:', {
                hashGerado: hashLocal,
                hashEsperado: '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9',
                senhaDigitada: senha,
                email: email
            });

            const dadosRequisicao = {
                email: email.trim(),
                senha: senha,
                hashSenha: hashLocal,  // Envia o hash também para debug
                timestamp: new Date().getTime()
            };

            const resposta = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(dadosRequisicao),
                credentials: 'include'
            });

            const dados = await resposta.json();

            // Log detalhado da resposta
            console.log('DEBUG - Resposta completa:', {
                status: resposta.status,
                dados: dados,
                headers: Object.fromEntries(resposta.headers.entries())
            });

            if (!resposta.ok) {
                throw new Error(dados.erro?.mensagem || this.MENSAGENS.ERRO_SERVIDOR);
            }

            if (!dados.sucesso) {
                throw new Error(dados.erro?.mensagem || this.MENSAGENS.SENHA_INCORRETA);
            }

            return dados.dados;

        } catch (erro) {
            console.error('Erro detalhado:', {
                mensagem: erro.message,
                tipo: erro.name,
                stack: erro.stack,
                timestamp: new Date().toISOString()
            });
            throw erro;
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