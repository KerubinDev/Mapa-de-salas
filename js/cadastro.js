/**
 * Gerenciador de Cadastro
 * Responsável por gerenciar o cadastro de novos usuários
 */
class GerenciadorCadastro {
    constructor() {
        this.configurarFormulario();
    }

    /**
     * Configura o formulário de cadastro
     */
    configurarFormulario() {
        const form = document.getElementById('formCadastro');
        if (!form) return;

        form.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            
            if (this.validarFormulario(form)) {
                await this.realizarCadastro(new FormData(form));
            }
        });
    }

    /**
     * Valida os dados do formulário
     */
    validarFormulario(form) {
        const senha = form.senha.value;
        const confirmarSenha = form.confirmarSenha.value;

        if (senha !== confirmarSenha) {
            this.mostrarErro('As senhas não coincidem');
            return false;
        }

        if (senha.length < 6) {
            this.mostrarErro('A senha deve ter no mínimo 6 caracteres');
            return false;
        }

        return true;
    }

    /**
     * Realiza o cadastro do usuário
     */
    async realizarCadastro(formData) {
        try {
            const dados = {
                nome: formData.get('nome'),
                email: formData.get('email'),
                senha: formData.get('senha')
            };

            const resposta = await fetch('api/auth/registro.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados)
            });

            if (!resposta.ok) {
                const erro = await resposta.json();
                throw new Error(erro.erro || 'Erro ao realizar cadastro');
            }

            // Redireciona para o login com mensagem de sucesso
            window.location.href = 'login.html?cadastro=sucesso';
        } catch (erro) {
            console.error('Erro no cadastro:', erro);
            this.mostrarErro(erro.message);
        }
    }

    /**
     * Mostra mensagem de erro no formulário
     */
    mostrarErro(mensagem) {
        const form = document.getElementById('formCadastro');
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
    new GerenciadorCadastro();
}); 