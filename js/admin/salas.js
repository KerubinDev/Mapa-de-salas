/**
 * Gerenciador de Salas
 * Responsável por gerenciar o CRUD de salas
 */
class GerenciadorSalas {
    constructor() {
        // Verifica autenticação
        if (!window.gerenciadorAuth.verificarAutenticacao()) return;

        this._salas = [];
        this.inicializar();
    }

    /**
     * Inicializa o gerenciador
     */
    inicializar() {
        this.configurarEventos();
        this.carregarSalas();
    }

    /**
     * Configura os eventos da interface
     */
    configurarEventos() {
        // Botão de nova sala
        document.getElementById('btnNovaSala')
            .addEventListener('click', () => this.abrirModal());

        // Formulário de sala
        document.getElementById('formSala')
            .addEventListener('submit', (e) => this.salvarSala(e));

        // Pesquisa e filtros
        document.getElementById('pesquisaSala')
            .addEventListener('input', () => this.filtrarSalas());
        document.getElementById('filtroCapacidade')
            .addEventListener('change', () => this.filtrarSalas());

        // Botão de logout
        document.getElementById('btnSair')
            .addEventListener('click', () => window.gerenciadorAuth.encerrarSessao());
    }

    /**
     * Carrega as salas do servidor
     */
    async carregarSalas() {
        try {
            const resposta = await fetch('../api/sala.php');
            if (!resposta.ok) throw new Error('Erro ao carregar salas');

            this._salas = await resposta.json();
            this.atualizarTabela();
        } catch (erro) {
            console.error('Erro ao carregar salas:', erro);
            this.mostrarErro('Não foi possível carregar as salas');
        }
    }

    /**
     * Atualiza a tabela de salas
     */
    atualizarTabela(salas = this._salas) {
        const tbody = document.getElementById('listaSalas');
        tbody.innerHTML = '';

        salas.forEach(sala => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50';
            
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    ${sala.nome}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${sala.capacidade} pessoas
                </td>
                <td class="px-6 py-4">
                    ${sala.descricao || '-'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                    <button onclick="gerenciadorSalas.editarSala('${sala.id}')"
                            class="text-blue-600 hover:text-blue-900">
                        Editar
                    </button>
                    <button onclick="gerenciadorSalas.excluirSala('${sala.id}')"
                            class="ml-4 text-red-600 hover:text-red-900">
                        Excluir
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
        });
    }

    /**
     * Filtra as salas com base nos critérios
     */
    filtrarSalas() {
        const termo = document.getElementById('pesquisaSala').value.toLowerCase();
        const capacidade = parseInt(document.getElementById('filtroCapacidade').value);

        const salasFiltradas = this._salas.filter(sala => {
            const matchTermo = sala.nome.toLowerCase().includes(termo) ||
                             (sala.descricao || '').toLowerCase().includes(termo);
            
            const matchCapacidade = !capacidade || sala.capacidade <= capacidade;

            return matchTermo && matchCapacidade;
        });

        this.atualizarTabela(salasFiltradas);
    }

    /**
     * Abre o modal de sala
     */
    abrirModal(sala = null) {
        const modal = document.getElementById('modalSala');
        const form = document.getElementById('formSala');
        const titulo = document.getElementById('tituloModal');

        // Limpa o formulário
        form.reset();
        form.id.value = '';

        // Se for edição, preenche os dados
        if (sala) {
            titulo.textContent = 'Editar Sala';
            form.id.value = sala.id;
            form.nome.value = sala.nome;
            form.capacidade.value = sala.capacidade;
            form.descricao.value = sala.descricao || '';
        } else {
            titulo.textContent = 'Nova Sala';
        }

        modal.classList.remove('hidden');
    }

    /**
     * Fecha o modal de sala
     */
    fecharModal() {
        document.getElementById('modalSala').classList.add('hidden');
    }

    /**
     * Salva uma sala (criar ou atualizar)
     */
    async salvarSala(evento) {
        evento.preventDefault();
        
        const form = evento.target;
        const dados = {
            nome: form.nome.value,
            capacidade: parseInt(form.capacidade.value),
            descricao: form.descricao.value
        };

        try {
            const url = '../api/sala.php' + (form.id.value ? `?id=${form.id.value}` : '');
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
                throw new Error(erro.erro || 'Erro ao salvar sala');
            }

            await this.carregarSalas();
            this.fecharModal();
        } catch (erro) {
            console.error('Erro ao salvar sala:', erro);
            this.mostrarErro(erro.message);
        }
    }

    /**
     * Edita uma sala existente
     */
    editarSala(id) {
        const sala = this._salas.find(s => s.id === id);
        if (sala) {
            this.abrirModal(sala);
        }
    }

    /**
     * Exclui uma sala
     */
    async excluirSala(id) {
        if (!confirm('Tem certeza que deseja excluir esta sala?')) return;

        try {
            const resposta = await fetch(`../api/sala.php?id=${id}`, {
                method: 'DELETE'
            });

            if (!resposta.ok) {
                const erro = await resposta.json();
                throw new Error(erro.erro || 'Erro ao excluir sala');
            }

            await this.carregarSalas();
        } catch (erro) {
            console.error('Erro ao excluir sala:', erro);
            this.mostrarErro(erro.message);
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
    window.gerenciadorSalas = new GerenciadorSalas();
}); 