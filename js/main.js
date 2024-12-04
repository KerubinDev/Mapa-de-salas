/**
 * Variáveis globais para armazenar o estado da aplicação
 */
const ESTADO = {
    salas: [],
    turmas: [],
    reservas: []
};


/**
 * Funções para manipulação de modais
 */
function mostrarModal(idModal) {
    document.getElementById(idModal).classList.remove('hidden');
}

function fecharModal(idModal) {
    document.getElementById(idModal).classList.add('hidden');
}


/**
 * Funções para manipulação de dados via API
 */
async function buscarDados() {
    try {
        const [salasResp, turmasResp, reservasResp] = await Promise.all([
            fetch('api/sala.php'),
            fetch('api/turma.php'),
            fetch('api/reserva.php')
        ]);

        // Log detalhado dos erros
        if (!salasResp.ok) {
            const erro = await salasResp.text();
            console.error('Resposta da API de salas:', erro);
            throw new Error(`Erro ao buscar salas: ${salasResp.status}`);
        }
        if (!turmasResp.ok) {
            const erro = await turmasResp.text();
            console.error('Resposta da API de turmas:', erro);
            throw new Error(`Erro ao buscar turmas: ${turmasResp.status}`);
        }
        if (!reservasResp.ok) {
            const erro = await reservasResp.text();
            console.error('Resposta da API de reservas:', erro);
            throw new Error(`Erro ao buscar reservas: ${reservasResp.status}`);
        }

        const [salas, turmas, reservas] = await Promise.all([
            salasResp.json(),
            turmasResp.json(),
            reservasResp.json()
        ]);

        ESTADO.salas = salas;
        ESTADO.turmas = turmas;
        ESTADO.reservas = reservas;

        atualizarInterface();
    } catch (erro) {
        console.error('Erro detalhado:', erro);
        alert('Erro ao carregar dados. Verifique o console para mais detalhes.');
    }
}


/**
 * Funções para cadastro de dados
 */
async function cadastrarSala(evento) {
    evento.preventDefault();
    
    const formData = new FormData(evento.target);
    const dadosSala = {
        nome: formData.get('nomeSala'),
        capacidade: parseInt(formData.get('capacidade')),
        descricao: formData.get('descricao')
    };

    try {
        const resposta = await fetch('api/sala.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dadosSala)
        });

        if (!resposta.ok) throw new Error('Erro ao cadastrar sala');

        await buscarDados();
        fecharModal('modalSala');
        evento.target.reset();
    } catch (erro) {
        console.error('Erro ao cadastrar sala:', erro);
        alert('Erro ao cadastrar sala. Tente novamente.');
    }
}

async function cadastrarTurma(evento) {
    evento.preventDefault();
    
    const formData = new FormData(evento.target);
    const dadosTurma = {
        nome: formData.get('nomeTurma'),
        professor: formData.get('professor'),
        numeroAlunos: parseInt(formData.get('numeroAlunos')),
        turno: formData.get('turno')
    };

    try {
        const resposta = await fetch('api/turma.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dadosTurma)
        });

        if (!resposta.ok) throw new Error('Erro ao cadastrar turma');

        await buscarDados();
        fecharModal('modalTurma');
        evento.target.reset();
    } catch (erro) {
        console.error('Erro ao cadastrar turma:', erro);
        alert('Erro ao cadastrar turma. Tente novamente.');
    }
}

async function cadastrarReserva(evento) {
    evento.preventDefault();
    
    const formData = new FormData(evento.target);
    const dadosReserva = {
        salaId: formData.get('salaId'),
        turmaId: formData.get('turmaId'),
        dia: formData.get('dia'),
        horario: formData.get('horario')
    };

    try {
        const resposta = await fetch('api/reserva.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dadosReserva)
        });

        if (!resposta.ok) {
            const erro = await resposta.json();
            throw new Error(erro.erro || 'Erro ao cadastrar reserva');
        }

        await buscarDados();
        fecharModal('modalReserva');
        evento.target.reset();
    } catch (erro) {
        console.error('Erro ao cadastrar reserva:', erro);
        alert(erro.message);
    }
}


/**
 * Funções para atualização da interface
 */
function atualizarInterface() {
    atualizarTabelaHorarios();
    aplicarFiltros();
}

function atualizarTabelaHorarios() {
    const horarios = [
        '07:30 - 08:20', '08:20 - 09:10', '09:10 - 10:00',
        '10:20 - 11:10', '11:10 - 12:00',
        '13:30 - 14:20', '14:20 - 15:10', '15:10 - 16:00',
        '16:20 - 17:10', '17:10 - 18:00',
        '19:00 - 19:50', '19:50 - 20:40', '20:40 - 21:30',
        '21:40 - 22:30'
    ];

    const tbody = document.getElementById('corpoTabela');
    tbody.innerHTML = '';

    horarios.forEach(horario => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="px-4 py-2 border font-medium">${horario}</td>
            ${gerarCelulasDias(horario)}
        `;
        tbody.appendChild(tr);
    });
}

function gerarCelulasDias(horario) {
    const dias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta'];
    return dias.map(dia => {
        const reserva = encontrarReserva(dia, horario);
        if (reserva) {
            return `
                <td class="px-4 py-2 border bg-blue-100">
                    <div class="text-sm">
                        <strong>${reserva.turma.nome}</strong><br>
                        ${reserva.sala.nome}
                    </div>
                </td>
            `;
        }
        return '<td class="px-4 py-2 border"></td>';
    }).join('');
}

function encontrarReserva(dia, horario) {
    return ESTADO.reservas.find(r => 
        r.dia === dia && r.horario === horario
    );
}


/**
 * Funções para filtros e pesquisa
 */
function preencherSelectSalas() {
    const select = document.querySelector('select[name="salaId"]');
    select.innerHTML = '<option value="">Selecione uma sala</option>';
    
    ESTADO.salas.forEach(sala => {
        select.innerHTML += `
            <option value="${sala.id}">
                ${sala.nome} (${sala.capacidade} lugares)
            </option>
        `;
    });
}

function preencherSelectTurmas() {
    const select = document.querySelector('select[name="turmaId"]');
    select.innerHTML = '<option value="">Selecione uma turma</option>';
    
    ESTADO.turmas.forEach(turma => {
        select.innerHTML += `
            <option value="${turma.id}">
                ${turma.nome} - ${turma.professor}
            </option>
        `;
    });
}

function aplicarFiltros() {
    const termoPesquisa = document.getElementById('pesquisa').value.toLowerCase();
    const turnoSelecionado = document.getElementById('filtroTurno').value;
    const capacidadeSelecionada = 
        parseInt(document.getElementById('filtroCapacidade').value);

    // Filtra as reservas baseado nos critérios
    const reservasFiltradas = ESTADO.reservas.filter(reserva => {
        const sala = ESTADO.salas.find(s => s.id === reserva.salaId);
        const turma = ESTADO.turmas.find(t => t.id === reserva.turmaId);
        
        // Filtro de pesquisa
        const matchPesquisa = !termoPesquisa || 
            sala.nome.toLowerCase().includes(termoPesquisa) ||
            turma.nome.toLowerCase().includes(termoPesquisa) ||
            turma.professor.toLowerCase().includes(termoPesquisa);
            
        // Filtro de turno
        const matchTurno = !turnoSelecionado || 
            turma.turno === turnoSelecionado;
            
        // Filtro de capacidade
        const matchCapacidade = !capacidadeSelecionada || 
            sala.capacidade <= capacidadeSelecionada;
            
        return matchPesquisa && matchTurno && matchCapacidade;
    });

    atualizarTabelaHorarios(reservasFiltradas);
}


/**
 * Event Listeners
 */
document.addEventListener('DOMContentLoaded', () => {
    buscarDados().then(() => {
        preencherSelectSalas();
        preencherSelectTurmas();
    });

    // Adicionar listeners para filtros
    document.getElementById('pesquisa')
        .addEventListener('input', aplicarFiltros);
    document.getElementById('filtroTurno')
        .addEventListener('change', aplicarFiltros);
    document.getElementById('filtroCapacidade')
        .addEventListener('change', aplicarFiltros);
}); 