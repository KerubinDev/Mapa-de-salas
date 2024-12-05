/**
 * Gerenciador de Visualização
 * Responsável por exibir as salas e seus horários
 */
class GerenciadorVisualizacao {
    constructor() {
        this.token = localStorage.getItem('token');
        this.carregarDados();
    }

    /**
     * Carrega os dados necessários
     */
    async carregarDados() {
        try {
            const [salas, turmas, reservas] = await Promise.all([
                this.buscarSalas(),
                this.buscarTurmas(),
                this.buscarReservas()
            ]);
            
            // Processa os dados...
        } catch (erro) {
            console.error('Erro ao carregar dados:', erro);
            if (erro.status === 401) {
                window.location.href = '/login.html';
            }
        }
    }

    /**
     * Busca as salas
     */
    async buscarSalas() {
        const resposta = await fetch('/api/sala', {
            headers: {
                'Authorization': `Bearer ${this.token}`
            }
        });
        
        if (!resposta.ok) {
            throw { status: resposta.status, message: 'Erro ao buscar salas' };
        }
        
        const dados = await resposta.json();
        return dados.dados;
    }

    /**
     * Busca as turmas
     */
    async buscarTurmas() {
        const resposta = await fetch('/api/turma', {
            headers: {
                'Authorization': `Bearer ${this.token}`
            }
        });
        
        if (!resposta.ok) {
            throw { status: resposta.status, message: 'Erro ao buscar turmas' };
        }
        
        const dados = await resposta.json();
        return dados.dados;
    }

    /**
     * Busca as reservas
     */
    async buscarReservas() {
        const resposta = await fetch('/api/reserva', {
            headers: {
                'Authorization': `Bearer ${this.token}`
            }
        });
        
        if (!resposta.ok) {
            throw { status: resposta.status, message: 'Erro ao buscar reservas' };
        }
        
        const dados = await resposta.json();
        return dados.dados;
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    const token = localStorage.getItem('token');
    if (!token) {
        window.location.href = '/login.html';
        return;
    }
    
    new GerenciadorVisualizacao();
}); 