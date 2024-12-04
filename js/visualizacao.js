/**
 * Gerenciador de Visualização
 * Responsável por exibir as salas e seus horários
 */
class GerenciadorVisualizacao {
    constructor() {
        this._salas = [];
        this._reservas = [];
        this._turmas = [];
        this.inicializar();
    }

    /**
     * Inicializa o gerenciador
     */
    async inicializar() {
        await this.carregarDados();
        this.configurarEventos();
        this.atualizarVisualizacao();
    }

    /**
     * Carrega os dados necessários
     */
    async carregarDados() {
        try {
            const [salasResp, reservasResp, turmasResp] = await Promise.all([
                fetch('api/sala.php'),
                fetch('api/reserva.php'),
                fetch('api/turma.php')
            ]);

            if (!salasResp.ok || !reservasResp.ok || !turmasResp.ok) {
                throw new Error('Erro ao carregar dados');
            }

            this._salas = await salasResp.json();
            this._reservas = await reservasResp.json();
            this._turmas = await turmasResp.json();
        } catch (erro) {
            console.error('Erro ao carregar dados:', erro);
            this.mostrarErro('Não foi possível carregar os dados');
        }
    }

    /**
     * Configura os eventos da interface
     */
    configurarEventos() {
        document.getElementById('pesquisaSala')
            .addEventListener('input', () => this.aplicarFiltros());
        document.getElementById('filtroDiaSemana')
            .addEventListener('change', () => this.aplicarFiltros());
        document.getElementById('filtroHorario')
            .addEventListener('change', () => this.aplicarFiltros());
    }

    /**
     * Atualiza a visualização das salas
     */
    atualizarVisualizacao(salas = this._salas) {
        const container = document.getElementById('listaSalas');
        const template = document.getElementById('templateSala');
        container.innerHTML = '';

        salas.forEach(sala => {
            const reservasSala = this.agruparReservasSala(sala.id);
            if (!this.passaNosFiltos(sala, reservasSala)) return;

            const elemento = template.content.cloneNode(true);
            
            // Atualiza informações da sala
            elemento.querySelector('h2').textContent = sala.nome;
            elemento.querySelector('p').textContent = 
                `Capacidade: ${sala.capacidade} pessoas`;

            // Atualiza lista de reservas
            const listaReservas = elemento.querySelector('.reservas-lista');
            listaReservas.innerHTML = '';

            reservasSala.forEach(grupo => {
                const reservaEl = document.createElement('div');
                reservaEl.className = 'p-2 bg-gray-50 rounded';
                
                const turma = this._turmas.find(t => t.id === grupo.turmaId);
                if (!turma) return;

                reservaEl.innerHTML = `
                    <div class="font-medium">${turma.nome}</div>
                    <div class="text-sm text-gray-600">
                        <span class="dias-semana">${this.formatarDiasSemana(grupo.diasSemana)}</span> •
                        <span class="horario">${grupo.horarioInicio} - ${grupo.horarioFim}</span>
                    </div>
                    <div class="text-sm text-gray-500">
                        <span class="periodo">
                            ${this.formatarData(grupo.dataInicio)} a ${this.formatarData(grupo.dataFim)}
                        </span>
                    </div>
                `;

                listaReservas.appendChild(reservaEl);
            });

            if (listaReservas.children.length === 0) {
                listaReservas.innerHTML = `
                    <div class="text-gray-500 italic">
                        Nenhuma reserva encontrada
                    </div>
                `;
            }

            container.appendChild(elemento);
        });

        if (container.children.length === 0) {
            container.innerHTML = `
                <div class="col-span-full bg-white p-8 rounded-lg shadow text-center">
                    <p class="text-gray-500">Nenhuma sala encontrada</p>
                </div>
            `;
        }
    }

    /**
     * Agrupa as reservas de uma sala por período e horário
     */
    agruparReservasSala(salaId) {
        const grupos = [];
        const reservasSala = this._reservas.filter(r => r.salaId === salaId);

        // Agrupa reservas por turma e horário
        const reservasPorGrupo = {};
        reservasSala.forEach(reserva => {
            const chave = `${reserva.turmaId}-${reserva.horarioInicio}-${reserva.horarioFim}`;
            if (!reservasPorGrupo[chave]) {
                reservasPorGrupo[chave] = [];
            }
            reservasPorGrupo[chave].push(reserva);
        });

        // Para cada grupo, determina o período e dias da semana
        Object.values(reservasPorGrupo).forEach(reservas => {
            if (reservas.length === 0) return;

            const datas = reservas.map(r => new Date(r.data));
            const dataInicio = new Date(Math.min(...datas));
            const dataFim = new Date(Math.max(...datas));
            
            const diasSemana = [...new Set(
                reservas.map(r => new Date(r.data).getDay())
            )].sort();

            grupos.push({
                turmaId: reservas[0].turmaId,
                horarioInicio: reservas[0].horarioInicio,
                horarioFim: reservas[0].horarioFim,
                dataInicio: dataInicio.toISOString().split('T')[0],
                dataFim: dataFim.toISOString().split('T')[0],
                diasSemana: diasSemana
            });
        });

        return grupos;
    }

    /**
     * Verifica se a sala passa nos filtros atuais
     */
    passaNosFiltos(sala, reservas) {
        const termo = document.getElementById('pesquisaSala').value.toLowerCase();
        const diaSemana = document.getElementById('filtroDiaSemana').value;
        const periodo = document.getElementById('filtroHorario').value;

        // Filtro de pesquisa
        if (termo && !sala.nome.toLowerCase().includes(termo)) {
            return false;
        }

        // Se não há filtros de dia ou período, mostra todas as salas
        if (!diaSemana && !periodo) return true;

        // Verifica se alguma reserva passa nos filtros
        return reservas.some(grupo => {
            // Filtro de dia da semana
            if (diaSemana && !grupo.diasSemana.includes(parseInt(diaSemana))) {
                return false;
            }

            // Filtro de período
            if (periodo) {
                const hora = parseInt(grupo.horarioInicio.split(':')[0]);
                switch (periodo) {
                    case 'manha':
                        return hora >= 7 && hora < 12;
                    case 'tarde':
                        return hora >= 13 && hora < 18;
                    case 'noite':
                        return hora >= 19 && hora <= 22;
                }
            }

            return true;
        });
    }

    /**
     * Formata os dias da semana para exibição
     */
    formatarDiasSemana(dias) {
        const nomes = {
            0: 'Dom',
            1: 'Seg',
            2: 'Ter',
            3: 'Qua',
            4: 'Qui',
            5: 'Sex',
            6: 'Sáb'
        };
        return dias.map(d => nomes[d]).join(', ');
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