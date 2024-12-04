/**
 * Gerenciador de Configurações
 * Responsável por gerenciar as configurações do sistema
 */
class GerenciadorConfiguracoes {
    constructor() {
        // Verifica autenticação
        if (!window.gerenciadorAuth.verificarAutenticacao()) return;

        this.inicializar();
    }

    /**
     * Inicializa o gerenciador
     */
    inicializar() {
        this.preencherTemasPredefinidos();
        this.preencherTemaAtual();
        this.configurarEventos();
    }

    /**
     * Preenche a seção de temas predefinidos
     */
    preencherTemasPredefinidos() {
        const container = document.getElementById('temasPredefinidos');
        const temas = window.gerenciadorTemas.temasPredefinidos;

        container.innerHTML = temas.map(tema => `
            <button type="button"
                    class="p-4 rounded-lg border-2 border-gray-200 hover:border-primary
                           transition flex flex-col items-center gap-2"
                    onclick='gerenciadorConfiguracoes.aplicarTema(${JSON.stringify(tema)})'>
                <div class="w-full h-20 rounded-lg" 
                     style="background-color: ${tema.corPrimaria}"></div>
                <span class="text-sm font-medium">${tema.nome}</span>
            </button>
        `).join('');
    }

    /**
     * Preenche o formulário com o tema atual
     */
    preencherTemaAtual() {
        const tema = window.gerenciadorTemas.temaAtual;
        const form = document.getElementById('formTema');

        form.corPrimaria.value = tema.corPrimaria;
        form.corSecundaria.value = tema.corSecundaria;
        form.corFundo.value = tema.corFundo;
        form.corTexto.value = tema.corTexto;
    }

    /**
     * Configura os eventos da interface
     */
    configurarEventos() {
        // Formulário de tema personalizado
        document.getElementById('formTema').addEventListener('submit', 
            (evento) => {
                evento.preventDefault();
                this.aplicarTemaPersonalizado(new FormData(evento.target));
            }
        );

        // Botão de logout
        document.getElementById('btnSair').addEventListener('click',
            () => window.gerenciadorAuth.encerrarSessao()
        );
    }

    /**
     * Aplica um tema predefinido
     */
    aplicarTema(tema) {
        window.gerenciadorTemas.aplicarTema(tema);
        this.preencherTemaAtual();
    }

    /**
     * Aplica um tema personalizado
     */
    aplicarTemaPersonalizado(formData) {
        const tema = {
            nome: 'Personalizado',
            corPrimaria: formData.get('corPrimaria'),
            corSecundaria: formData.get('corSecundaria'),
            corFundo: formData.get('corFundo'),
            corTexto: formData.get('corTexto')
        };

        window.gerenciadorTemas.aplicarTema(tema);
    }
}

// Inicializa o gerenciador quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    window.gerenciadorConfiguracoes = new GerenciadorConfiguracoes();
}); 