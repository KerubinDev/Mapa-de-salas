/**
 * @fileoverview Gerenciamento do dashboard administrativo
 */

class GerenciadorDashboard {
    constructor() {
        // Verifica se está autenticado
        if (!window.authManager.isAuthenticated()) {
            window.location.href = '/login.html';
            return;
        }

        this._nomeUsuario = document.getElementById('nomeUsuario');
        this._btnSair = document.getElementById('btnSair');
        this._totalSalas = document.getElementById('totalSalas');
        this._totalReservas = document.getElementById('totalReservas');
        this._totalUsuarios = document.getElementById('totalUsuarios');

        this._inicializarEventos();
        this._carregarPerfil();
        this._carregarDados();
    }

    /**
     * Inicializa os eventos da página
     * @private
     */
    _inicializarEventos() {
        this._btnSair.addEventListener('click', async () => {
            try {
                // Faz a requisição de logout
                const resposta = await fetch('/api/auth/logout', {
                    method: 'POST'
                });

                // Limpa os dados de autenticação
                window.authManager.clearAuth();
                
                // Redireciona para a página de login
                window.location.href = '/login.html';
            } catch (erro) {
                console.error('Erro ao fazer logout:', erro);
                window.location.href = '/login.html';
            }
        });
    }

    /**
     * Carrega os dados do perfil do usuário
     * @private
     */
    async _carregarPerfil() {
        try {
            const resposta = await fetch('/api/auth/perfil');
            if (!resposta.ok) {
                throw new Error('Erro ao carregar perfil');
            }
            
            const dados = await resposta.json();
            this._nomeUsuario.textContent = dados.nome;
        } catch (erro) {
            console.error('Erro ao carregar perfil:', erro);
            window.authManager.clearAuth();
            window.location.href = '/login.html';
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

            this._totalSalas.textContent = salas.length || 0;
            this._totalReservas.textContent = reservas.length || 0;
            this._totalUsuarios.textContent = usuarios.length || 0;
        } catch (erro) {
            console.error('Erro ao carregar dados:', erro);
            // Mostra 0 em caso de erro
            this._totalSalas.textContent = '0';
            this._totalReservas.textContent = '0';
            this._totalUsuarios.textContent = '0';
        }
    }
}

// Inicializa o gerenciador do dashboard
document.addEventListener('DOMContentLoaded', () => {
    const gerenciadorDashboard = new GerenciadorDashboard();
}); 