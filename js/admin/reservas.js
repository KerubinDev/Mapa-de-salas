/**
 * Gerenciador de Reservas
 * Responsável por gerenciar o CRUD de reservas
 */
class GerenciadorReservas {
    constructor() {
        // Verifica autenticação
        if (!window.gerenciadorAuth.verificarAutenticacao()) return;

        this._reservas = [];
        this._salas = [];
        this._turmas = [];
        this.inicializar();
    }

    /**
     * Inicializa o gerenciador
     */
    async inicializar() {
        await this.carregarDados();
        this.configurarEventos();
        this.preencherSelects();
        this.atualizarTabela();
    }

    /**
     * Carrega todos os dados necessários
     */
    async carregarDados() {
        try {
            const [reservasResp, salasResp, turmasResp] = await Promise.all([
                fetch('../api/reserva.php'),
                fetch('../api/sala.php'),
                fetch('../api/turma.php')
            ]);

            if (!reservasResp.ok || !salasResp.ok || !turmasResp.ok) {
                throw new Error('Erro ao carregar dados');
            }

            this._reservas = await reservasResp.json();
            this._salas = await salasResp.json();
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
        // Botão de nova reserva
        document.getElementById('btnNovaReserva')
            .addEventListener('click', () => this.abrirModal());

        // Formulário de reserva
        document.getElementById('formReserva')
            .addEventListener('submit', (e) => this.salvarReserva(e));

        // Filtros
        document.getElementById('pesquisaReserva')
            .addEventListener('input', () => this.filtrarReservas());
        document.getElementById('filtroData')
            .addEventListener('change', () => this.filtrarReservas());
        document.getElementById('filtroSala')
            .addEventListener('change', () => this.filtrarReservas());

        // Botão de logout
        document.getElementById('btnSair')
            .addEventListener('click', () => window.gerenciadorAuth.encerrarSessao());
    }

    /**
     * Preenche os selects com as opções disponíveis
     */
    preencherSelects() {
        // Select de salas no filtro
        const selectFiltroSala = document.getElementById('filtroSala');
        selectFiltroSala.innerHTML = '<option value="">Todas as Salas</option>';
        this._salas.forEach(sala => {
            selectFiltroSala.innerHTML += `
                <option value="${sala.id}">
                    ${sala.nome} (${sala.capacidade} pessoas)
                </option>
            `;
        });

        // Selects do formulário
        const selectSala = document.querySelector('select[name="salaId"]');
        const selectTurma = document.querySelector('select[name="turmaId"]');

        selectSala.innerHTML = '<option value="">Selecione uma sala</option>';
        selectTurma.innerHTML = '<option value="">Selecione uma turma</option>';

        this._salas.forEach(sala => {
            selectSala.innerHTML += `
                <option value="${sala.id}">
                    ${sala.nome} (${sala.capacidade} pessoas)
                </option>
            `;
        });

        this._turmas.forEach(turma => {
            selectTurma.innerHTML += `
                <option value="${turma.id}">
                    ${turma.nome} - ${turma.professor}
                </option>
            `;
        });
    }

    /**
     * Atualiza a tabela de reservas
     */
    atualizarTabela(reservas = this._reservas) {
        const tbody = document.getElementById('listaReservas');
        tbody.innerHTML = '';

        reservas.forEach(reserva => {
            const sala = this._salas.find(s => s.id === reserva.salaId);
            const turma = this._turmas.find(t => t.id === reserva.turmaId);

            if (!sala || !turma) return;

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50';
            
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    ${this.formatarData(reserva.data)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${reserva.horario}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${sala.nome}
                </td>
                <td class="px-6 py-4">
                    ${turma.nome}<br>
                    <span class="text-sm text-gray-500">
                        Prof. ${turma.professor}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                    <button onclick="gerenciadorReservas.editarReserva('${reserva.id}')"
                            class="text-blue-600 hover:text-blue-900">
                        Editar
                    </button>
                    <button onclick="gerenciadorReservas.excluirReserva('${reserva.id}')"
                            class="ml-4 text-red-600 hover:text-red-900">
                        Excluir
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
        });
    }

    /**
     * Filtra as reservas com base nos critérios
     */
    filtrarReservas() {
        const termo = document.getElementById('pesquisaReserva').value.toLowerCase();
        const data = document.getElementById('filtroData').value;
        const salaId = document.getElementById('filtroSala').value;

        const reservasFiltradas = this._reservas.filter(reserva => {
            const sala = this._salas.find(s => s.id === reserva.salaId);
            const turma = this._turmas.find(t => t.id === reserva.turmaId);

            if (!sala || !turma) return false;

            const matchTermo = sala.nome.toLowerCase().includes(termo) ||
                             turma.nome.toLowerCase().includes(termo) ||
                             turma.professor.toLowerCase().includes(termo);
            
            const matchData = !data || reserva.data === data;
            const matchSala = !salaId || reserva.salaId === salaId;

            return matchTermo && matchData && matchSala;
        });

        this.atualizarTabela(reservasFiltradas);
    }

    /**
     * Abre o modal de reserva
     */
    abrirModal(reserva = null) {
        const modal = document.getElementById('modalReserva');
        const form = document.getElementById('formReserva');
        const titulo = document.getElementById('tituloModal');

        // Limpa o formulário
        form.reset();
        form.id.value = '';

        // Define data mínima como hoje
        const hoje = new Date().toISOString().split('T')[0];
        form.data.min = hoje;

        // Se for edição, preenche os dados
        if (reserva) {
            titulo.textContent = 'Editar Reserva';
            form.id.value = reserva.id;
            form.data.value = reserva.data;
            form.horario.value = reserva.horario;
            form.salaId.value = reserva.salaId;
            form.turmaId.value = reserva.turmaId;
        } else {
            titulo.textContent = 'Nova Reserva';
            form.data.value = hoje;
        }

        modal.classList.remove('hidden');
    }

    /**
     * Fecha o modal de reserva
     */
    fecharModal() {
        document.getElementById('modalReserva').classList.add('hidden');
    }

    /**
     * Salva uma reserva (criar ou atualizar)
     */
    async salvarReserva(evento) {
        evento.preventDefault();
        
        const form = evento.target;
        const dados = {
            data: form.data.value,
            horario: form.horario.value,
            salaId: form.salaId.value,
            turmaId: form.turmaId.value
        };

        // Validações adicionais
        if (!this.validarHorario(dados.horario)) {
            this.mostrarErro('Horário inválido');
            return;
        }

        if (await this.verificarConflito(dados, form.id.value)) {
            this.mostrarErro('Já existe uma reserva para este horário');
            return;
        }

        try {
            const url = '../api/reserva.php' + (form.id.value ? `?id=${form.id.value}` : '');
            const metodo = form.id.value ? 'PUT' : 'POST';

            const resposta = await fetch(url, {
                method: metodo,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados)
            });

            if (!resposta.ok) {
                const erro = await resposta.json();
                throw new Error(erro.erro || 'Erro ao salvar reserva');
            }

            await this.carregarDados();
            this.fecharModal();
        } catch (erro) {
            console.error('Erro ao salvar reserva:', erro);
            this.mostrarErro(erro.message);
        }
    }

    /**
     * Edita uma reserva existente
     */
    editarReserva(id) {
        const reserva = this._reservas.find(r => r.id === id);
        if (reserva) {
            this.abrirModal(reserva);
        }
    }

    /**
     * Exclui uma reserva
     */
    async excluirReserva(id) {
        if (!confirm('Tem certeza que deseja excluir esta reserva?')) return;

        try {
            const resposta = await fetch(`../api/reserva.php?id=${id}`, {
                method: 'DELETE'
            });

            if (!resposta.ok) {
                const erro = await resposta.json();
                throw new Error(erro.erro || 'Erro ao excluir reserva');
            }

            await this.carregarDados();
        } catch (erro) {
            console.error('Erro ao excluir reserva:', erro);
            this.mostrarErro(erro.message);
        }
    }

    /**
     * Verifica se existe conflito de horário
     */
    async verificarConflito(dados, reservaId = null) {
        // Verifica localmente primeiro
        const conflito = this._reservas.some(reserva => {
            if (reservaId && reserva.id === reservaId) return false;

            return reserva.data === dados.data &&
                   reserva.horario === dados.horario &&
                   (reserva.salaId === dados.salaId || 
                    reserva.turmaId === dados.turmaId);
        });

        if (conflito) return true;

        // Verifica também no servidor
        try {
            const params = new URLSearchParams({
                data: dados.data,
                horario: dados.horario,
                salaId: dados.salaId,
                turmaId: dados.turmaId
            });

            if (reservaId) {
                params.append('excluirId', reservaId);
            }

            const resposta = await fetch(`../api/reserva.php/verificar?${params}`);
            const resultado = await resposta.json();

            return resultado.conflito;
        } catch (erro) {
            console.error('Erro ao verificar conflito:', erro);
            return true; // Por segurança, considera que há conflito
        }
    }

    /**
     * Valida o formato e intervalo do horário
     */
    validarHorario(horario) {
        const [hora, minuto] = horario.split(':').map(Number);
        
        // Verifica se está dentro do horário de funcionamento (7h às 22h)
        if (hora < 7 || hora > 22) return false;
        
        // Verifica se os minutos são múltiplos de 15
        if (minuto % 15 !== 0) return false;

        return true;
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
    window.gerenciadorReservas = new GerenciadorReservas();
}); 