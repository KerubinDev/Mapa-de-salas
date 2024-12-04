/**
 * Gerenciador de Login
 * Responsável por gerenciar a autenticação do usuário
 */
class GerenciadorLogin {
    constructor() {
        this.configurarFormulario();
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

            const resposta = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados)
            });

            const resultado = await resposta.json();

            if (!resposta.ok) {
                throw new Error(resultado.erro || 'Erro ao realizar login');
            }

            // Armazena os dados do usuário
            if (dados.lembrar) {
                localStorage.setItem('usuario', JSON.stringify(resultado));
            } else {
                sessionStorage.setItem('usuario', JSON.stringify(resultado));
            }
            
            // Redireciona para o painel administrativo
            window.location.href = 'admin/adminpanel.html';
        } catch (erro) {
            console.error('Erro no login:', erro);
            this.mostrarErro(erro.message);
        }
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
document.addEventListener('DOMContentLoaded', () => {
    new GerenciadorLogin();
}); 