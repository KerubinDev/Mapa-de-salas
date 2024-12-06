/**
 * Gerenciador de Login
 */
class GerenciadorLogin {
    constructor() {
        this.apiUrl = '/api/auth/login';
    }
    
    /**
     * Realiza o login
     */
    async realizarLogin(email, senha) {
        try {
            // Gera o hash da senha
            const senhaHash = await this._gerarHashSenha(senha);
            
            const resposta = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, senha: senhaHash })
            });

            const dados = await resposta.json();
            
            if (!resposta.ok) {
                throw new Error(dados.erro?.mensagem || 'Erro no login');
            }

            if (!dados.sucesso) {
                throw new Error(dados.erro?.mensagem || 'Erro no login');
            }

            // Configura o token no gerenciador de autenticação
            window.authManager.setToken(dados.dados.token);
            
            // Salva os dados do usuário
            localStorage.setItem('usuario', JSON.stringify(dados.dados.usuario));

            return dados.dados;
        } catch (erro) {
            console.error('Erro no login:', erro);
            throw erro;
        }
    }

    /**
     * Gera o hash SHA-256 da senha
     * @private
     */
    async _gerarHashSenha(senha) {
        const encoder = new TextEncoder();
        const data = encoder.encode(senha);
        const hashBuffer = await crypto.subtle.digest('SHA-256', data);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
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