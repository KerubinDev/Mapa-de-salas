/**
 * Gerenciador de Logs
 * Responsável por exibir e filtrar os logs do sistema
 */
class GerenciadorLogs {
    constructor() {
        this._logs = [];
        this._usuarios = [];
        this._usuarioAtual = JSON.parse(localStorage.getItem('usuario')) || 
                            JSON.parse(sessionStorage.getItem('usuario'));
        
        // Verifica se é admin
        if (!this._usuarioAtual || this._usuarioAtual.tipo !== 'admin') {
            window.location.href = '/admin/';
            return;
        }

        this.inicializar();
    }

    /**
     * Inicializa o gerenciador
     */
    async inicializar() {
        await Promise.all([
            this.carregarLogs(),
            this.carregarUsuarios()
        ]);
        this.configurarEventos();
        this.preencherSelects();
        this.atualizarTabela();
    }

    /**
     * Carrega os logs do servidor
     */
    async carregarLogs(filtros = {}) {
        try {
            const params = new URLSearchParams(filtros);
            const resposta = await fetch(`/api/auth/logs.php?${params}`);
            if (!resposta.ok) throw new Error('Erro ao carregar logs');

            this._logs = await resposta.json();
        } catch (erro) {
            console.error('Erro ao carregar logs:', erro);
            this.mostrarErro('Não foi possível carregar os logs');
        }
    }

    /**
     * Carrega os usuários do sistema
     */
    async carregarUsuarios() {
        try {
            const resposta = await fetch('/api/auth/usuarios.php');
            if (!resposta.ok) throw new Error('Erro ao carregar usuários');

            this._usuarios = await resposta.json();
        } catch (erro) {
            console.error('Erro ao carregar usuários:', erro);
        }
    }

    /**
     * Configura os eventos da interface
     */
    configurarEventos() {
        // Formulário de filtros
        document.getElementById('formFiltros').addEventListener('change', 
            () => this.aplicarFiltros());

        // Botão de logout
        document.getElementById('btnSair')
            .addEventListener('click', () => this.realizarLogout());
    }

    /**
     * Preenche os selects com as opções disponíveis
     */
    preencherSelects() {
        const selectUsuario = document.querySelector('select[name="usuarioId"]');
        
        this._usuarios.forEach(usuario => {
            selectUsuario.innerHTML += `
                <option value="${usuario.id}">
                    ${usuario.nome} (${usuario.email})
                </option>
            `;
        });
    }

    /**
     * Atualiza a tabela de logs
     */
    atualizarTabela() {
        const tbody = document.getElementById('listaLogs');
        tbody.innerHTML = '';

        this._logs.forEach(log => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50';
            
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    ${this.formatarDataHora(log.data)}
                </td>
                <td class="px-6 py-4">
                    ${log.usuario ? `
                        <div>
                            <p class="font-medium">${log.usuario.nome}</p>
                            <p class="text-sm text-gray-500">${log.usuario.email}</p>
                        </div>
                    ` : 'Usuário não encontrado'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                ${this.getCorAcao(log.acao)}">
                        ${this.traduzirAcao(log.acao)}
                    </span>
                </td>
                <td class="px-6 py-4">
                    ${log.detalhes || '-'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${log.ip}
                </td>
            `;

            tbody.appendChild(tr);
        });
    }

    /**
     * Aplica os filtros selecionados
     */
    async aplicarFiltros() {
        const form = document.getElementById('formFiltros');
        const filtros = {
            dataInicio: form.dataInicio.value,
            dataFim: form.dataFim.value,
            usuarioId: form.usuarioId.value,
            acao: form.acao.value
        };

        // Remove filtros vazios
        Object.keys(filtros).forEach(key => {
            if (!filtros[key]) delete filtros[key];
        });

        await this.carregarLogs(filtros);
        this.atualizarTabela();
    }

    /**
     * Retorna a cor para cada tipo de ação
     */
    getCorAcao(acao) {
        const cores = {
            login: 'bg-green-100 text-green-800',
            logout: 'bg-yellow-100 text-yellow-800',
            criar: 'bg-blue-100 text-blue-800',
            editar: 'bg-purple-100 text-purple-800',
            excluir: 'bg-red-100 text-red-800'
        };
        return cores[acao] || 'bg-gray-100 text-gray-800';
    }

    /**
     * Traduz o tipo de ação
     */
    traduzirAcao(acao) {
        const traducoes = {
            login: 'Login',
            logout: 'Logout',
            criar: 'Criação',
            editar: 'Edição',
            excluir: 'Exclusão'
        };
        return traducoes[acao] || acao;
    }

    /**
     * Formata data e hora para exibição
     */
    formatarDataHora(data) {
        return new Date(data).toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
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

            localStorage.removeItem('usuario');
            sessionStorage.removeItem('usuario');
            window.location.href = '/login.html';
        } catch (erro) {
            console.error('Erro ao fazer logout:', erro);
            this.mostrarErro('Erro ao realizar logout');
        }
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
    window.gerenciadorLogs = new GerenciadorLogs();
}); 