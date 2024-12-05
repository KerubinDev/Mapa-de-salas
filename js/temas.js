/**
 * @fileoverview Gerenciamento de temas da aplicação
 * @author Seu Nome
 */

class GerenciadorTemas {
    /**
     * @typedef {Object} ConfiguracaoTema
     * @property {string} corPrimaria - Cor principal do tema
     * @property {string} corSecundaria - Cor secundária do tema
     * @property {string} corFundo - Cor de fundo
     * @property {string} corTexto - Cor do texto
     */

    /**
     * Inicializa o gerenciador de temas
     */
    constructor() {
        this._temasPredefinidos = {
            claro: {
                corPrimaria: '#1d4ed8',
                corSecundaria: '#60a5fa',
                corFundo: '#f9fafb',
                corTexto: '#111827'
            },
            escuro: {
                corPrimaria: '#1e40af',
                corSecundaria: '#3b82f6',
                corFundo: '#111827',
                corTexto: '#f9fafb'
            }
        };
        
        this._configuracaoAtual = this._carregarTema();
        this._aplicarTema(this._configuracaoAtual);
    }

    /**
     * Carrega o tema salvo ou retorna o tema padrão
     * @returns {ConfiguracaoTema}
     * @private
     */
    _carregarTema() {
        try {
            const temaSalvo = localStorage.getItem('tema');
            return temaSalvo ? JSON.parse(temaSalvo) : this._temasPredefinidos.claro;
        } catch (erro) {
            console.error('Erro ao carregar tema:', erro);
            return this._temasPredefinidos.claro;
        }
    }

    /**
     * Aplica o tema na página
     * @param {ConfiguracaoTema} configuracao
     * @private
     */
    _aplicarTema(configuracao) {
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: configuracao.corPrimaria,
                        secondary: configuracao.corSecundaria,
                        background: configuracao.corFundo,
                        textColor: configuracao.corTexto
                    }
                }
            }
        };

        document.body.classList.remove('bg-white');
        document.body.classList.add('bg-background');
    }

    /**
     * Salva e aplica um novo tema
     * @param {ConfiguracaoTema} novaConfiguracao
     */
    atualizarTema(novaConfiguracao) {
        try {
            localStorage.setItem('tema', JSON.stringify(novaConfiguracao));
            this._configuracaoAtual = novaConfiguracao;
            this._aplicarTema(novaConfiguracao);
        } catch (erro) {
            console.error('Erro ao salvar tema:', erro);
        }
    }
}

// Inicializa o gerenciador de temas
const gerenciadorTemas = new GerenciadorTemas(); 