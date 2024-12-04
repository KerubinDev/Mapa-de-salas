/**
 * Gerenciador de Temas
 * Responsável por gerenciar as cores e aparência do sistema
 */
class GerenciadorTemas {
    constructor() {
        this._temaAtual = this.carregarTema();
        this.aplicarTema(this._temaAtual);
    }

    /**
     * Carrega o tema salvo ou retorna o tema padrão
     */
    carregarTema() {
        const temaSalvo = localStorage.getItem('tema');
        return temaSalvo ? JSON.parse(temaSalvo) : {
            corPrimaria: '#1d4ed8',    // Azul escuro
            corSecundaria: '#60a5fa',  // Azul claro
            corFundo: '#f9fafb',       // Cinza claro
            corTexto: '#111827',       // Quase preto
            nome: 'Padrão'
        };
    }

    /**
     * Aplica o tema atual ao Tailwind e salva no localStorage
     */
    aplicarTema(tema) {
        this._temaAtual = tema;
        localStorage.setItem('tema', JSON.stringify(tema));

        // Atualiza as cores do Tailwind
        window.tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: tema.corPrimaria,
                        secondary: tema.corSecundaria,
                        background: tema.corFundo,
                        textColor: tema.corTexto
                    }
                }
            }
        };

        // Força atualização dos estilos
        document.body.className = document.body.className;
    }

    /**
     * Retorna o tema atual
     */
    get temaAtual() {
        return this._temaAtual;
    }

    /**
     * Lista de temas predefinidos
     */
    get temasPredefinidos() {
        return [
            {
                nome: 'Claro',
                corPrimaria: '#1d4ed8',
                corSecundaria: '#60a5fa',
                corFundo: '#f9fafb',
                corTexto: '#111827'
            },
            {
                nome: 'Escuro',
                corPrimaria: '#60a5fa',
                corSecundaria: '#1d4ed8',
                corFundo: '#111827',
                corTexto: '#f9fafb'
            },
            {
                nome: 'Verde',
                corPrimaria: '#059669',
                corSecundaria: '#34d399',
                corFundo: '#f0fdf4',
                corTexto: '#064e3b'
            },
            {
                nome: 'Roxo',
                corPrimaria: '#7c3aed',
                corSecundaria: '#a78bfa',
                corFundo: '#f5f3ff',
                corTexto: '#4c1d95'
            }
        ];
    }
}

// Instancia o gerenciador de temas globalmente
window.gerenciadorTemas = new GerenciadorTemas(); 