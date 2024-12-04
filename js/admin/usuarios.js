/**
 * Gerenciador de Usuários
 * Responsável por gerenciar o CRUD de usuários
 */
class GerenciadorUsuarios {
    constructor() {
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
        await this.carregarUsuarios();
        this.configurarEventos();
        this.atualizarTabela();
    }

    /**
     * Carrega os usuários do servidor
     */
    async carregarUsuarios() {
        try {
            const resposta = await fetch('/api/auth/usuarios.php');
            if (!resposta.ok) throw new Error('Erro ao carregar usuários');

            this._usuarios = await resposta.json();
        } catch (erro) {
            console.error('Erro ao carregar usuários:', erro);
            this.mostrarErro('Não foi possível carregar os usuários');
        }
    }

    /**
     * Configura os eventos da interface
     */
    configurarEventos() {
        // Botão de novo usuário
        document.getElementById('btnNovoUsuario')
            .addEventListener('click', () => this.abrirModal());

        // Formulário de usuário
        document.getElementById('formUsuario')
            .addEventListener('submit', (e) => this.salvarUsuario(e));

        // Pesquisa
        document.getElementById('pesquisaUsuario')
            .addEventListener('input', () => this.filtrarUsuarios());

        // Botão de logout
        document.getElementById('btnSair')
            .addEventListener('click', () => this.realizarLogout());
    }

    /**
     * Atualiza a tabela de usuários
     */
    atualizarTabela(usuarios = this._usuarios) {
        const tbody = document.getElementById('listaUsuarios');
        tbody.innerHTML = '';

        usuarios.forEach(usuario => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50';
            
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    ${usuario.nome}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${usuario.email}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                ${usuario.tipo === 'admin' ? 'bg-green-100 text-green-800' : 
                                                           'bg-gray-100 text-gray-800'}">
                        ${usuario.tipo === 'admin' ? 'Administrador' : 'Usuário'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${this.formatarData(usuario.dataCriacao)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                    ${usuario.id !== 'admin' ? `
                        <button onclick="gerenciadorUsuarios.editarUsuario('${usuario.id}')"
                                class="text-blue-600 hover:text-blue-900">
                            Editar
                        </button>
                        <button onclick="gerenciadorUsuarios.excluirUsuario('${usuario.id}')"
                                class="ml-4 text-red-600 hover:text-red-900">
                            Excluir
                        </button>
                    ` : ''}
                </td>
            `;

            tbody.appendChild(tr);
        });
    }

    /**
     * Filtra os usuários
     */
    filtrarUsuarios() {
        const termo = document.getElementById('pesquisaUsuario').value.toLowerCase();

        const usuariosFiltrados = this._usuarios.filter(usuario => 
            usuario.nome.toLowerCase().includes(termo) ||
            usuario.email.toLowerCase().includes(termo)
        );

        this.atualizarTabela(usuariosFiltrados);
    }

    /**
     * Abre o modal de usuário
     */
    abrirModal(usuario = null) {
        const modal = document.getElementById('modalUsuario');
        const form = document.getElementById('formUsuario');
        const titulo = document.getElementById('tituloModal');
        const campoSenha = form.senha;

        // Limpa o formulário
        form.reset();
        form.id.value = '';
        campoSenha.required = true;

        // Se for edição, preenche os dados
        if (usuario) {
            titulo.textContent = 'Editar Usuário';
            form.id.value = usuario.id;
            form.nome.value = usuario.nome;
            form.email.value = usuario.email;
            form.tipo.value = usuario.tipo;
            campoSenha.required = false;
        } else {
            titulo.textContent = 'Novo Usuário';
        }

        modal.classList.remove('hidden');
    }

    /**
     * Fecha o modal de usuário
     */
    fecharModal() {
        document.getElementById('modalUsuario').classList.add('hidden');
    }

    /**
     * Salva um usuário (criar ou atualizar)
     */
    async salvarUsuario(evento) {
        evento.preventDefault();
        
        const form = evento.target;
        const dados = {
            nome: form.nome.value,
            email: form.email.value,
            tipo: form.tipo.value
        };

        // Adiciona senha apenas se fornecida
        if (form.senha.value) {
            dados.senha = form.senha.value;
        }

        try {
            const url = '/api/auth/usuarios.php' + (form.id.value ? `?id=${form.id.value}` : '');
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
                throw new Error(erro.erro || 'Erro ao salvar usuário');
            }

            await this.carregarUsuarios();
            this.fecharModal();
        } catch (erro) {
            console.error('Erro ao salvar usuário:', erro);
            this.mostrarErro(erro.message);
        }
    }

    /**
     * Edita um usuário existente
     */
    editarUsuario(id) {
        const usuario = this._usuarios.find(u => u.id === id);
        if (usuario) {
            this.abrirModal(usuario);
        }
    }

    /**
     * Exclui um usuário
     */
    async excluirUsuario(id) {
        if (!confirm('Tem certeza que deseja excluir este usuário?')) return;

        try {
            const resposta = await fetch(`/api/auth/usuarios.php?id=${id}`, {
                method: 'DELETE'
            });

            if (!resposta.ok) {
                const erro = await resposta.json();
                throw new Error(erro.erro || 'Erro ao excluir usuário');
            }

            await this.carregarUsuarios();
        } catch (erro) {
            console.error('Erro ao excluir usuário:', erro);
            this.mostrarErro(erro.message);
        }
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
    window.gerenciadorUsuarios = new GerenciadorUsuarios();
}); 