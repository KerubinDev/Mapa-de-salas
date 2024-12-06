/**
 * @fileoverview Gerenciamento do dashboard administrativo
 */

class GerenciadorDashboard {
    constructor() {
        this._token = localStorage.getItem('token');
        this._init();
    }

    async _init() {
        try {
            await this._carregarResumos();
            await this._carregarReservasRecentes();
            this._configurarEventos();
        } catch (erro) {
            console.error('Erro ao inicializar dashboard:', erro);
        }
    }

    async _carregarResumos() {
        try {
            const [salas, reservas, turmas, usuarios] = await Promise.all([
                this._buscarDados('/api/sala'),
                this._buscarDados('/api/reserva'),
                this._buscarDados('/api/turma'),
                this._buscarDados('/api/usuario')
            ]);

            document.getElementById('totalSalas').textContent = salas.length;
            document.getElementById('totalReservas').textContent = reservas.length;
            document.getElementById('totalTurmas').textContent = turmas.length;
            document.getElementById('totalUsuarios').textContent = usuarios.length;
        } catch (erro) {
            console.error('Erro ao carregar resumos:', erro);
        }
    }

    async _buscarDados(url) {
        const resposta = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${this._token}`
            }
        });

        if (!resposta.ok) {
            throw new Error(`Erro ao buscar dados de ${url}`);
        }

        const dados = await resposta.json();
        return dados.dados || [];
    }

    _configurarEventos() {
        document.getElementById('btnSair').addEventListener('click', () => {
            localStorage.clear();
            window.location.href = '/login';
        });
    }
}

// Inicializa o gerenciador quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    new GerenciadorDashboard();
}); 