class GerenciadorAdmin {
    constructor() {
        this.token = localStorage.getItem('token');
        this.usuario = JSON.parse(localStorage.getItem('usuario'));
        
        if (!this.token || !this.usuario || this.usuario.tipo !== 'admin') {
            window.location.href = '/login.html';
            return;
        }
        
        this.inicializar();
    }
    
    async inicializar() {
        try {
            // Carrega os dados iniciais
            await this.carregarDados();
            
            // Configura os eventos
            this.configurarEventos();
            
        } catch (erro) {
            console.error('Erro ao inicializar:', erro);
            if (erro.status === 401) {
                window.location.href = '/login.html';
            }
        }
    }
    
    async carregarDados() {
        // Implementar carregamento de dados
    }
    
    configurarEventos() {
        // Implementar configuração de eventos
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    new GerenciadorAdmin();
}); 