/**
 * Gerenciador do Dashboard
 * Responsável por gerenciar o painel administrativo
 */
class DashboardAdmin {
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
            await this.carregarEstatisticas();
            await this.carregarReservasRecentes();
            this.configurarEventos();
        } catch (erro) {
            console.error('Erro ao inicializar dashboard:', erro);
            if (erro.status === 401) {
                window.location.href = '/login.html';
            }
        }
    }
    
    async carregarEstatisticas() {
        try {
            const [salas, turmas, reservas, usuarios] = await Promise.all([
                this.buscarDados('/api/sala'),
                this.buscarDados('/api/turma'),
                this.buscarDados('/api/reserva'),
                this.buscarDados('/api/usuario')
            ]);
            
            document.getElementById('totalSalas').textContent = salas.length;
            document.getElementById('totalTurmas').textContent = turmas.length;
            document.getElementById('totalReservas').textContent = reservas.length;
            document.getElementById('totalUsuarios').textContent = usuarios.length;
        } catch (erro) {
            console.error('Erro ao carregar estatísticas:', erro);
        }
    }
    
    async carregarReservasRecentes() {
        try {
            const reservas = await this.buscarDados('/api/reserva');
            const recentes = reservas
                .sort((a, b) => new Date(b.dataCriacao) - new Date(a.dataCriacao))
                .slice(0, 5);
                
            const lista = document.getElementById('listaReservasRecentes');
            lista.innerHTML = recentes.map(reserva => `
                <div class="p-4 border rounded-lg">
                    <p class="font-bold">${reserva.sala.nome}</p>
                    <p class="text-sm text-gray-600">
                        ${new Date(reserva.data).toLocaleDateString()} - 
                        ${reserva.horarioInicio} às ${reserva.horarioFim}
                    </p>
                </div>
            `).join('');
        } catch (erro) {
            console.error('Erro ao carregar reservas recentes:', erro);
        }
    }
    
    async buscarDados(url) {
        const resposta = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${this.token}`
            }
        });
        
        if (!resposta.ok) {
            throw { status: resposta.status, message: 'Erro ao buscar dados' };
        }
        
        const dados = await resposta.json();
        return dados.dados;
    }
    
    configurarEventos() {
        document.getElementById('btnSair').addEventListener('click', () => {
            localStorage.removeItem('token');
            localStorage.removeItem('usuario');
            window.location.href = '/login.html';
        });
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    new DashboardAdmin();
}); 