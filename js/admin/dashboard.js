/**
 * Gerenciador do Dashboard
 * Responsável por gerenciar o painel administrativo
 */
class GerenciadorDashboard {
    constructor() {
        this._dados = {
            salas: [],
            turmas: [],
            reservas: [],
            usuarios: []
        };
        this.inicializar();
    }

    /**
     * Inicializa o dashboard
     */
    async inicializar() {
        await this.carregarDados();
        this.configurarEventos();
        this.atualizarInterface();
    }

    /**
     * Carrega os dados necessários
     */
    async carregarDados() {
        try {
            const [salasResp, turmasResp, reservasResp, usuariosResp] = await Promise.all([
                fetch('/api/sala.php'),
                fetch('/api/turma.php'),
                fetch('/api/reserva.php'),
                fetch('/api/auth/usuarios.php')
            ]);

            // Verifica se as respostas são válidas
            if (!salasResp.ok || !turmasResp.ok || !reservasResp.ok || !usuariosResp.ok) {
                throw new Error('Erro ao carregar dados');
            }

            this._dados.salas = await salasResp.json();
            this._dados.turmas = await turmasResp.json();
            this._dados.reservas = await reservasResp.json();
            this._dados.usuarios = await usuariosResp.json();
        } catch (erro) {
            console.error('Erro ao carregar dados:', erro);
            this.mostrarErro('Erro ao carregar dados do dashboard');
        }
    }

    /**
     * Configura os eventos da interface
     */
    configurarEventos() {
        // Botão de logout
        document.getElementById('btnSair').addEventListener('click', () => this.realizarLogout());
    }

    /**
     * Atualiza a interface com os dados
     */
    atualizarInterface() {
        // Atualiza os totais
        document.getElementById('totalSalas').textContent = this._dados.salas.length;
        document.getElementById('totalReservas').textContent = this._dados.reservas.length;
        document.getElementById('totalTurmas').textContent = this._dados.turmas.length;
        document.getElementById('totalUsuarios').textContent = this._dados.usuarios.length;

        // Atualiza a lista de reservas recentes
        this.atualizarReservasRecentes();
    }

    /**
     * Atualiza a lista de reservas recentes
     */
    atualizarReservasRecentes() {
        const container = document.getElementById('listaReservasRecentes');
        const reservasRecentes = this._dados.reservas
            .sort((a, b) => new Date(b.dataCriacao) - new Date(a.dataCriacao))
            .slice(0, 5);

        container.innerHTML = reservasRecentes.length ? '' : `
            <p class="text-gray-500 italic">Nenhuma reserva encontrada</p>
        `;

        reservasRecentes.forEach(reserva => {
            const sala = this._dados.salas.find(s => s.id === reserva.salaId);
            const turma = this._dados.turmas.find(t => t.id === reserva.turmaId);

            if (!sala || !turma) return;

            const div = document.createElement('div');
            div.className = 'flex justify-between items-center p-3 bg-gray-50 rounded';
            
            div.innerHTML = `
                <div>
                    <p class="font-medium">${sala.nome}</p>
                    <p class="text-sm text-gray-600">${turma.nome}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium">${this.formatarData(reserva.data)}</p>
                    <p class="text-sm text-gray-600">${reserva.horario}</p>
                </div>
            `;

            container.appendChild(div);
        });
    }

    /**
     * Realiza o logout do usuário
     */
    async realizarLogout() {
        try {
            const resposta = await fetch('/api/auth/logout.php', {
                method: 'POST'
            });

            if (!resposta.ok) {
                throw new Error('Erro ao realizar logout');
            }

            // Limpa os dados do usuário
            localStorage.removeItem('usuario');
            sessionStorage.removeItem('usuario');

            // Redireciona para a página de login
            window.location.href = '/login.html';
        } catch (erro) {
            console.error('Erro ao fazer logout:', erro);
            this.mostrarErro('Erro ao realizar logout');
        }
    }

    /**
     * Formata uma data para exibição
     */
    formatarData(data) {
        return new Date(data).toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    /**
     * Mostra uma mensagem de erro
     */
    mostrarErro(mensagem) {
        alert(mensagem); // Podemos melhorar isso com um componente de toast
    }
}

// Inicializa o gerenciador quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    new GerenciadorDashboard();
}); 