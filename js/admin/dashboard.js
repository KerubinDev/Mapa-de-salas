/**
 * @fileoverview Gerenciamento do dashboard administrativo
 * @author Seu Nome
 */

class GerenciadorDashboard {
    constructor() {
        this._nomeUsuario = document.getElementById('nomeUsuario');
        this._btnSair = document.getElementById('btnSair');
        this._totalSalas = document.getElementById('totalSalas');
        this._totalReservas = document.getElementById('totalReservas');
        this._totalUsuarios = document.getElementById('totalUsuarios');

        this._inicializarEventos();
        this._verificarAutenticacao();
        this._carregarDados();
    }

    /**
     * Inicializa os eventos da página
     * @private
     */
    _inicializarEventos() {
        this._btnSair.addEventListener('click', () => this._realizarLogout());
    }

    /**
     * Verifica se o usuário está autenticado
     * @private
     */
    async _verificarAutenticacao() {
        try {
            const resposta = await fetch('/api/auth/perfil');
            if (!resposta.ok) {
                window.location.href = '/login.html';
                return;
            }
            
            const dados = await resposta.json();
            this._nomeUsuario.textContent = dados.nome;
        } catch (erro) {
            console.error('Erro ao verificar autenticação:', erro);
            window.location.href = '/login.html';
        }
    }

    /**
     * Realiza o logout do usuário
     * @private
     */
    async _realizarLogout() {
        try {
            const resposta = await fetch('/api/auth/logout', {
                method: 'POST'
            });
            
            if (resposta.ok) {
                window.location.href = '/login.html';
            }
        } catch (erro) {
            console.error('Erro ao realizar logout:', erro);
        }
    }

    /**
     * Carrega os dados do dashboard
     * @private
     */
    async _carregarDados() {
        try {
            const [salas, reservas, usuarios] = await Promise.all([
                fetch('/api/sala').then(r => r.json()),
                fetch('/api/reserva').then(r => r.json()),
                fetch('/api/usuarios').then(r => r.json())
            ]);

            this._totalSalas.textContent = salas.length;
            this._totalReservas.textContent = reservas.length;
            this._totalUsuarios.textContent = usuarios.length;
        } catch (erro) {
            console.error('Erro ao carregar dados:', erro);
        }
    }
}

// Inicializa o gerenciador do dashboard
const gerenciadorDashboard = new GerenciadorDashboard(); 