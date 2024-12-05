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
        console.log('Tentando login com:', { email }); // não logue a senha
        
        try {
            const resposta = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email, senha })
            });

            console.log('Status da resposta:', resposta.status);
            
            const dados = await resposta.json();
            console.log('Dados recebidos:', dados);

            if (!dados.sucesso) {
                throw new Error(dados.erro?.mensagem || 'Erro no login');
            }

            return dados.dados;
        } catch (erro) {
            console.error('Erro detalhado:', erro);
            throw new Error(erro.message || 'Erro no login');
        }
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    const gerenciador = new GerenciadorLogin();
    const form = document.querySelector('form');
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = form.querySelector('[name="email"]').value;
        const senha = form.querySelector('[name="senha"]').value;
        
        try {
            const resultado = await gerenciador.realizarLogin(email, senha);
            console.log('Login bem sucedido:', resultado);
            
            // Salva o token e dados do usuário
            localStorage.setItem('token', resultado.token);
            localStorage.setItem('usuario', JSON.stringify(resultado.usuario));
            
            // Redireciona baseado no tipo de usuário
            if (resultado.usuario.tipo === 'admin') {
                window.location.href = '/admin/';
            } else if (resultado.usuario.tipo === 'coordenador') {
                window.location.href = '/coordenador/';
            } else {
                window.location.href = '/';
            }
        } catch (erro) {
            console.error('Erro no login:', erro);
            alert(erro.message);
        }
    });
}); 