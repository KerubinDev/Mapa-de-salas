/**
 * Gerenciador de Autenticação
 * Responsável por gerenciar o login e sessão do usuário
 */
class GerenciadorAutenticacao {
    constructor() {
        this._usuario = this.carregarSessao();
        this.configurarFormulario();
    }

    /**
     * Carrega dados da sessão do localStorage
     */
    carregarSessao() {
        const sessao = localStorage.getItem('sessao');
        return sessao ? JSON.parse(sessao) : null;
    }

    /**
     * Configura o formulário de login
     */
    configurarFormulario() {
        const form = document.getElementById('formLogin');
        if (!form) return;

        form.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            await this.realizarLogin(new FormData(form));
        });
    }

    /**
     * Realiza o login do usuário
     */
    async realizarLogin(formData) {
        try {
            const dados = {
                email: formData.get('email'),
                senha: formData.get('senha'),
                lembrar: formData.get('lembrar') === 'on'
            };

            const resposta = await fetch('api/auth/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados)
            });

            if (!resposta.ok) {
                const erro = await resposta.json();
                throw new Error(erro.erro || 'Erro ao realizar login');
            }

            const usuario = await resposta.json();
            this.iniciarSessao(usuario, dados.lembrar);
            
            // Redireciona para o painel administrativo
            window.location.href = 'admin/';
        } catch (erro) {
            console.error('Erro no login:', erro);
            this.mostrarErro(erro.message);
        }
    }

    /**
     * Inicia a sessão do usuário
     */
    iniciarSessao(usuario, lembrar) {
        this._usuario = usuario;
        if (lembrar) {
            localStorage.setItem('sessao', JSON.stringify(usuario));
        } else {
            sessionStorage.setItem('sessao', JSON.stringify(usuario));
        }
    }

    /**
     * Encerra a sessão do usuário
     */
    async encerrarSessao() {
        try {
            await fetch('api/auth/logout.php', { method: 'POST' });
        } catch (erro) {
            console.error('Erro ao fazer logout:', erro);
        } finally {
            this._usuario = null;
            localStorage.removeItem('sessao');
            sessionStorage.removeItem('sessao');
            window.location.href = '/login.html';
        }
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
     * Mostra mensagem de erro no formulário
     */
    mostrarErro(mensagem) {
        const form = document.getElementById('formLogin');
        if (!form) return;

        const erro = document.createElement('div');
        erro.className = 'bg-red-50 text-red-600 p-4 rounded-lg mb-4';
        erro.textContent = mensagem;

        const existente = form.querySelector('.bg-red-50');
        if (existente) {
            existente.remove();
        }

        form.insertBefore(erro, form.firstChild);
    }
}

// Inicializa o gerenciador quando a página carregar
window.gerenciadorAuth = new GerenciadorAutenticacao(); 