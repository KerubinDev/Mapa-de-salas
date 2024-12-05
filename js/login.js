/**
 * Gerenciador de Login
 */
class GerenciadorLogin {
    constructor() {
        this.apiUrl = '/api/auth/login';
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

            console.log('Iniciando tentativa de login:', {
                email,
                urlApi: this.apiUrl,
                temSenha: !!senha
            });

            const dadosRequisicao = {
                email: email.trim(),
                senha: senha,
                timestamp: new Date().getTime()
            };

            console.log('Payload da requisição:', 
                JSON.stringify(dadosRequisicao, null, 2));

            const resposta = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(dadosRequisicao),
                // Adiciona credentials para garantir envio de cookies
                credentials: 'include'
            });

            console.log('Resposta do servidor:', {
                status: resposta.status,
                headers: Object.fromEntries(resposta.headers.entries())
            });

            const dados = await resposta.json();
            console.log('Dados da resposta:', dados);

            if (!resposta.ok || !dados.sucesso) {
                throw new Error(
                    dados.erro?.mensagem || 
                    `Falha na autenticação (${resposta.status})`
                );
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