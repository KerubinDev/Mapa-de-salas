/**
 * @fileoverview Gerenciamento da visualização de salas
 * @author Seu Nome
 */

class GerenciadorVisualizacao {
    constructor() {
        this._listaSalas = document.getElementById('listaSalas');
        this._templateSala = document.getElementById('templateSala');
        this._pesquisaSala = document.getElementById('pesquisaSala');
        this._filtroDiaSemana = document.getElementById('filtroDiaSemana');
        this._filtroHorario = document.getElementById('filtroHorario');

        this._inicializarEventos();
        this._carregarSalas();
    }

    /**
     * Inicializa os eventos dos filtros
     * @private
     */
    _inicializarEventos() {
        this._pesquisaSala.addEventListener('input', () => this._aplicarFiltros());
        this._filtroDiaSemana.addEventListener('change', () => this._aplicarFiltros());
        this._filtroHorario.addEventListener('change', () => this._aplicarFiltros());
    }

    /**
     * Carrega as salas do servidor
     * @private
     */
    async _carregarSalas() {
        try {
            const resposta = await fetch('api/sala.php');
            if (!resposta.ok) throw new Error('Erro ao carregar salas');
            
            const salas = await resposta.json();
            this._renderizarSalas(salas);
        } catch (erro) {
            console.error('Erro ao carregar salas:', erro);
            this._mostrarErro('Não foi possível carregar as salas');
        }
    }

    /**
     * Renderiza as salas na interface
     * @param {Array} salas - Lista de salas para renderizar
     * @private
     */
    _renderizarSalas(salas) {
        this._listaSalas.innerHTML = '';
        
        salas.forEach(sala => {
            const elemento = this._templateSala.content.cloneNode(true);
            
            elemento.querySelector('h2').textContent = sala.nome;
            elemento.querySelector('p').textContent = 
                `Capacidade: ${sala.capacidade} pessoas`;

            const listaReservas = elemento.querySelector('.reservas-lista');
            this._renderizarReservas(sala.reservas, listaReservas);

            this._listaSalas.appendChild(elemento);
        });
    }

    /**
     * Renderiza as reservas de uma sala
     * @param {Array} reservas - Lista de reservas
     * @param {HTMLElement} elemento - Elemento onde renderizar as reservas
     * @private
     */
    _renderizarReservas(reservas, elemento) {
        elemento.innerHTML = '';
        
        reservas.forEach(reserva => {
            const div = document.createElement('div');
            div.className = 'p-2 bg-gray-50 rounded';
            div.innerHTML = `
                <div class="font-medium">${reserva.turma}</div>
                <div class="text-sm text-gray-600">
                    <span>${this._formatarDiasSemana(reserva.diasSemana)}</span> •
                    <span>${reserva.horarioInicio} - ${reserva.horarioFim}</span>
                </div>
                <div class="text-sm text-gray-500">
                    <span>${this._formatarPeriodo(reserva.dataInicio, 
                        reserva.dataFim)}</span>
                </div>
            `;
            elemento.appendChild(div);
        });
    }

    /**
     * Formata os dias da semana para exibição
     * @param {Array} dias - Array com os dias da semana
     * @returns {string} Dias formatados
     * @private
     */
    _formatarDiasSemana(dias) {
        const nomesDias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        return dias.map(dia => nomesDias[dia]).join(', ');
    }

    /**
     * Formata o período para exibição
     * @param {string} inicio - Data de início
     * @param {string} fim - Data de fim
     * @returns {string} Período formatado
     * @private
     */
    _formatarPeriodo(inicio, fim) {
        const dataInicio = new Date(inicio).toLocaleDateString('pt-BR');
        const dataFim = new Date(fim).toLocaleDateString('pt-BR');
        return `${dataInicio} a ${dataFim}`;
    }

    /**
     * Exibe mensagem de erro
     * @param {string} mensagem - Mensagem de erro
     * @private
     */
    _mostrarErro(mensagem) {
        this._listaSalas.innerHTML = `
            <div class="col-span-full text-center text-red-600 p-4">
                ${mensagem}
            </div>
        `;
    }
}

// Inicializa o gerenciador de visualização
const gerenciadorVisualizacao = new GerenciadorVisualizacao(); 