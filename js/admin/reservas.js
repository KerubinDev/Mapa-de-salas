/**
 * Gerenciador de Reservas
 * Responsável por gerenciar o CRUD de reservas
 */
class GerenciadorReservas {
    constructor() {
        this._reservas = [];
        this._salas = [];
        this._turmas = [];
        this._usuarioAtual = JSON.parse(localStorage.getItem('usuario')) || 
                            JSON.parse(sessionStorage.getItem('usuario'));
        
        // Verifica se está autenticado
        if (!this._usuarioAtual) {
            window.location.href = '/login.html';
            return;
        }

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
            .addEventListener('click', () => window.auth.logout());

        // Botão de selecionar todos os dias
        document.getElementById('btnSelecionarTodos')
            .addEventListener('click', () => this.selecionarTodosDias());

        // Validação de datas
        document.querySelector('input[name="dataFim"]')
            .addEventListener('change', (e) => this.validarDatas(e));
        
        // Validação de horários
        document.querySelector('input[name="horarioFim"]')
            .addEventListener('change', (e) => this.validarHorarios(e));
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
        const diasSemana = Array.from(form.querySelectorAll('input[name="diasSemana"]:checked'))
            .map(cb => parseInt(cb.value));

        if (diasSemana.length === 0) {
            this.mostrarErro('Selecione pelo menos um dia da semana');
            return;
        }

        const dadosBase = {
            salaId: form.salaId.value,
            turmaId: form.turmaId.value,
            horarioInicio: form.horarioInicio.value,
            horarioFim: form.horarioFim.value,
            dataInicio: form.dataInicio.value,
            dataFim: form.dataFim.value,
            diasSemana: diasSemana
        };

        // Gera todas as datas de reserva
        const datas = this.gerarDatasReserva(
            dadosBase.dataInicio,
            dadosBase.dataFim,
            diasSemana
        );

        try {
            // Verifica conflitos para todas as datas
            for (const data of datas) {
                const temConflito = await this.verificarConflito({
                    ...dadosBase,
                    data: data
                });

                if (temConflito) {
                    throw new Error(`Existe conflito de horário para o dia ${this.formatarData(data)}`);
                }
            }

            // Cria as reservas para todas as datas
            const promessas = datas.map(data => 
                fetch('../api/reserva.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        ...dadosBase,
                        data: data
                    })
                })
            );

            await Promise.all(promessas);
            await this.carregarDados();
            this.fecharModal();
        } catch (erro) {
            console.error('Erro ao salvar reservas:', erro);
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

    /**
     * Seleciona ou desmarca todos os dias da semana
     */
    selecionarTodosDias() {
        const checkboxes = document.querySelectorAll('input[name="diasSemana"]');
        const todosChecados = Array.from(checkboxes).every(cb => cb.checked);
        
        checkboxes.forEach(cb => {
            cb.checked = !todosChecados;
        });
    }

    /**
     * Valida as datas de início e fim
     */
    validarDatas(evento) {
        const dataInicio = document.querySelector('input[name="dataInicio"]').value;
        const dataFim = evento.target.value;

        if (dataInicio && dataFim && dataFim < dataInicio) {
            this.mostrarErro('A data de término deve ser posterior à data de início');
            evento.target.value = '';
        }
    }

    /**
     * Valida os horários de início e fim
     */
    validarHorarios(evento) {
        const horarioInicio = document.querySelector('input[name="horarioInicio"]').value;
        const horarioFim = evento.target.value;

        if (horarioInicio && horarioFim && horarioFim <= horarioInicio) {
            this.mostrarErro('O horário de término deve ser posterior ao horário de início');
            evento.target.value = '';
        }
    }

    /**
     * Gera todas as datas de reserva baseado no período e dias da semana
     */
    gerarDatasReserva(dataInicio, dataFim, diasSemana) {
        const datas = [];
        const inicio = new Date(dataInicio);
        const fim = new Date(dataFim);
        
        for (let data = inicio; data <= fim; data.setDate(data.getDate() + 1)) {
            if (diasSemana.includes(data.getDay())) {
                datas.push(new Date(data).toISOString().split('T')[0]);
            }
        }
        
        return datas;
    }
}

// Inicializa o gerenciador quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    window.gerenciadorReservas = new GerenciadorReservas();
}); 