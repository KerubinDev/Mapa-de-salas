/**
 * Gerenciador de Visualização
 * Responsável por exibir as salas e seus horários
 */
class GerenciadorVisualizacao {
    constructor() {
        this._salas = [];
        this._reservas = [];
        this.inicializar();
    }

    /**
     * Inicializa o gerenciador
     */
    async inicializar() {
        await this.carregarDados();
        this.configurarEventos();
        this.atualizarInterface();
    }

    /**
     * Carrega os dados da API
     */
    async carregarDados() {
        try {
            const [salasResp, reservasResp] = await Promise.all([
                fetch('api/sala.php'),
                fetch('api/reserva.php')
            ]);

            if (!salasResp.ok || !reservasResp.ok) {
                throw new Error('Erro ao carregar dados');
            }

            this._salas = await salasResp.json();
            this._reservas = await reservasResp.json();
        } catch (erro) {
            console.error('Erro ao carregar dados:', erro);
            this.mostrarErro('Não foi possível carregar os dados. Tente novamente mais tarde.');
        }
    }

    /**
     * Configura os eventos da interface
     */
    configurarEventos() {
        // Pesquisa de sala
        document.getElementById('pesquisaSala').addEventListener('input', 
            () => this.atualizarInterface());

        // Filtro por data
        document.getElementById('pesquisaData').addEventListener('change',
            () => this.atualizarInterface());
    }

    /**
     * Atualiza a interface com os dados filtrados
     */
    atualizarInterface() {
        const termoPesquisa = document.getElementById('pesquisaSala')
            .value.toLowerCase();
        const dataFiltro = document.getElementById('pesquisaData').value;

        // Filtra as salas
        const salasFiltradas = this._salas.filter(sala => 
            sala.nome.toLowerCase().includes(termoPesquisa)
        );

        // Atualiza a lista de salas
        const container = document.getElementById('listaSalas');
        container.innerHTML = '';

        salasFiltradas.forEach(sala => {
            const reservasSala = this._reservas.filter(r => 
                r.salaId === sala.id &&
                (!dataFiltro || this.mesmaData(r.data, dataFiltro))
            );

            container.appendChild(this.criarCardSala(sala, reservasSala));
        });

        if (salasFiltradas.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-8 text-gray-500">
                    Nenhuma sala encontrada
                </div>
            `;
        }
    }

    /**
     * Cria o card de uma sala com seus horários
     */
    criarCardSala(sala, reservas) {
        const div = document.createElement('div');
        div.className = 'bg-white p-6 rounded-lg shadow';
        
        div.innerHTML = `
            <h3 class="text-xl font-bold mb-4">${sala.nome}</h3>
            <p class="text-gray-600 mb-4">
                Capacidade: ${sala.capacidade} pessoas
            </p>
            <div class="space-y-2">
                ${this.gerarListaHorarios(reservas)}
            </div>
        `;

        return div;
    }

    /**
     * Gera a lista de horários de uma sala
     */
    gerarListaHorarios(reservas) {
        if (reservas.length === 0) {
            return `
                <p class="text-gray-500 italic">
                    Nenhum horário reservado
                </p>
            `;
        }

        return reservas
            .sort((a, b) => a.horario.localeCompare(b.horario))
            .map(reserva => `
                <div class="flex justify-between items-center 
                            py-2 px-3 bg-gray-50 rounded">
                    <div>
                        <p class="font-medium">${reserva.horario}</p>
                        <p class="text-sm text-gray-600">
                            ${reserva.turma.nome}
                        </p>
                    </div>
                    <div class="text-sm text-gray-500">
                        ${this.formatarData(reserva.data)}
                    </div>
                </div>
            `).join('');
    }

    /**
     * Funções auxiliares
     */
    mesmaData(data1, data2) {
        return new Date(data1).toDateString() === new Date(data2).toDateString();
    }

    formatarData(data) {
        return new Date(data).toLocaleDateString('pt-BR');
    }

    mostrarErro(mensagem) {
        const container = document.getElementById('listaSalas');
        container.innerHTML = `
            <div class="col-span-full bg-red-50 text-red-600 p-4 rounded-lg">
                ${mensagem}
            </div>
        `;
    }
}

// Inicializa o gerenciador quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    window.gerenciadorVisualizacao = new GerenciadorVisualizacao();
}); 