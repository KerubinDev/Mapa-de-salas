/**
 * Gerenciador de Login
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
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.realizarLogin(form);
        });
    }
    
    /**
     * Realiza o login
     */
    async realizarLogin(form) {
        try {
            // Remove mensagens de erro anteriores
            const erroAnterior = form.querySelector('.bg-red-50');
            if (erroAnterior) erroAnterior.remove();
            
            // Obtém os dados do formulário
            const dados = {
                email: form.email.value,
                senha: form.senha.value
            };
            
            // Envia a requisição
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(dados)
            });
            
            // Verifica se houve erro na requisição
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Resposta inválida do servidor');
            }
            
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.erro || 'Erro ao realizar login');
            }
            
            // Salva os dados do usuário
            if (form.lembrar.checked) {
                localStorage.setItem('usuario', JSON.stringify(data));
            } else {
                sessionStorage.setItem('usuario', JSON.stringify(data));
            }
            
            // Redireciona para a página inicial
            window.location.href = '/admin/';
            
        } catch (erro) {
            console.error('Erro no login:', erro);
            this.mostrarErro(form, erro.message);
        }
    }
    
    /**
     * Mostra uma mensagem de erro no formulário
     */
    mostrarErro(form, mensagem) {
        const erro = document.createElement('div');
        erro.className = 'bg-red-50 text-red-600 p-4 rounded-lg mb-4';
        erro.textContent = mensagem;
        
        form.insertBefore(erro, form.firstChild);
    }
}

// Inicializa o gerenciador quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    new GerenciadorLogin();
}); 