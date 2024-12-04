/**
 * Gerenciador de Configurações
 * Responsável por gerenciar as configurações do sistema
 */
class GerenciadorConfiguracoes {
    constructor() {
        this._usuarioAtual = JSON.parse(localStorage.getItem('usuario')) || 
                            JSON.parse(sessionStorage.getItem('usuario'));
        
        // Verifica se é admin
        if (!this._usuarioAtual || this._usuarioAtual.tipo !== 'admin') {
            window.location.href = '/admin/';
            return;
        }

        this._configuracoes = {};
        this._logs = [];
        this.inicializar();
    }

    /**
     * Inicializa o gerenciador
     */
    async inicializar() {
        await this.carregarDados();
        this.configurarEventos();
        this.preencherTemasPredefinidos();
        this.preencherFormularios();
        this.atualizarUltimosLogs();
    }

    /**
     * Carrega os dados necessários
     */
    async carregarDados() {
        try {
            const headers = window.auth.getHeaders();
            
            const [configResp, logsResp] = await Promise.all([
                fetch('../api/configuracoes.php', { headers }),
                fetch('../api/auth/logs.php', { headers })
            ]);

            // Primeiro verifica se as respostas são JSON válido
            const configData = await configResp.text();
            const logsData = await logsResp.text();

            let configJson, logsJson;
            try {
                configJson = JSON.parse(configData);
                logsJson = JSON.parse(logsData);
            } catch (e) {
                console.error('Resposta não é JSON válido:', { configData, logsData });
                throw new Error('Resposta inválida do servidor');
            }

            if (!configResp.ok || !logsResp.ok) {
                throw new Error(configJson.erro || logsJson.erro || 'Erro ao carregar dados');
            }

            this._configuracoes = configJson;
            this._logs = logsJson;
        } catch (erro) {
            console.error('Erro ao carregar dados:', erro);
            
            if (erro.message.includes('Não autorizado') || 
                erro.message.includes('Token') ||
                erro.message.includes('inválido')) {
                window.location.href = '/login.html';
                return;
            }
            
            this.mostrarErro('Não foi possível carregar as configurações');
        }
    }

    /**
     * Configura os eventos da interface
     */
    configurarEventos() {
        // Formulário de tema
        document.getElementById('formTema')
            .addEventListener('submit', (e) => this.salvarTema(e));

        // Formulário de configurações
        document.getElementById('formConfiguracoes')
            .addEventListener('submit', (e) => this.salvarConfiguracoes(e));

        // Botão de backup
        document.getElementById('btnBackup')
            .addEventListener('click', () => this.fazerBackup());

        // Botão de logout
        document.getElementById('btnSair')
            .addEventListener('click', () => window.auth.logout());
    }

    /**
     * Preenche os temas predefinidos
     */
    preencherTemasPredefinidos() {
        const container = document.getElementById('temasPredefinidos');
        const temas = [
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

        container.innerHTML = temas.map(tema => `
            <button type="button" 
                    onclick="gerenciadorConfiguracoes.aplicarTema(${JSON.stringify(tema)})"
                    class="p-4 border-2 rounded-lg hover:border-primary transition">
                <div class="w-full h-8 mb-2 rounded"
                     style="background-color: ${tema.corPrimaria}"></div>
                <span class="text-sm font-medium">${tema.nome}</span>
            </button>
        `).join('');
    }

    /**
     * Preenche os formulários com os dados atuais
     */
    preencherFormularios() {
        // Formulário de tema
        const formTema = document.getElementById('formTema');
        const temaAtual = JSON.parse(localStorage.getItem('tema')) || {};
        
        formTema.corPrimaria.value = temaAtual.corPrimaria || '#1d4ed8';
        formTema.corSecundaria.value = temaAtual.corSecundaria || '#60a5fa';
        formTema.corFundo.value = temaAtual.corFundo || '#f9fafb';
        formTema.corTexto.value = temaAtual.corTexto || '#111827';

        // Formulário de configurações
        const formConfig = document.getElementById('formConfiguracoes');
        
        formConfig.horarioAbertura.value = this._configuracoes.horarioAbertura || '07:00';
        formConfig.horarioFechamento.value = this._configuracoes.horarioFechamento || '22:00';
        
        // Dias de funcionamento
        const diasFuncionamento = this._configuracoes.diasFuncionamento || [1,2,3,4,5];
        formConfig.querySelectorAll('input[name="diasFuncionamento"]').forEach(checkbox => {
            checkbox.checked = diasFuncionamento.includes(parseInt(checkbox.value));
        });

        // Intervalos
        formConfig.duracaoMinima.value = this._configuracoes.duracaoMinima || 15;
        formConfig.intervaloReservas.value = this._configuracoes.intervaloReservas || 0;

        // Notificações
        formConfig.notificarReservas.checked = this._configuracoes.notificarReservas || false;
        formConfig.notificarCancelamentos.checked = this._configuracoes.notificarCancelamentos || false;
        formConfig.notificarConflitos.checked = this._configuracoes.notificarConflitos || false;

        // Backup
        formConfig.backupAutomatico.checked = this._configuracoes.backupAutomatico || false;
        document.getElementById('ultimoBackup').textContent = 
            this._configuracoes.ultimoBackup ? 
            this.formatarDataHora(this._configuracoes.ultimoBackup) : 'Nunca';
    }

    /**
     * Atualiza a lista de últimos logs
     */
    atualizarUltimosLogs() {
        const container = document.getElementById('ultimosLogs');
        const ultimosLogs = this._logs.slice(0, 5); // Mostra apenas os 5 últimos

        container.innerHTML = ultimosLogs.map(log => `
            <div class="flex justify-between items-start p-2 hover:bg-gray-50 rounded">
                <div>
                    <p class="font-medium">${log.usuario?.nome || 'Sistema'}</p>
                    <p class="text-sm text-gray-600">${log.detalhes}</p>
                </div>
                <span class="text-sm text-gray-500">
                    ${this.formatarDataHora(log.data)}
                </span>
            </div>
        `).join('') || '<p class="text-gray-500 italic">Nenhum log encontrado</p>';
    }

    /**
     * Aplica um tema predefinido
     */
    aplicarTema(tema) {
        localStorage.setItem('tema', JSON.stringify(tema));
        window.location.reload();
    }

    /**
     * Salva o tema personalizado
     */
    async salvarTema(evento) {
        evento.preventDefault();
        const form = evento.target;
        
        const tema = {
            corPrimaria: form.corPrimaria.value,
            corSecundaria: form.corSecundaria.value,
            corFundo: form.corFundo.value,
            corTexto: form.corTexto.value
        };

        localStorage.setItem('tema', JSON.stringify(tema));
        window.location.reload();
    }

    /**
     * Salva as configurações do sistema
     */
    async salvarConfiguracoes(evento) {
        evento.preventDefault();
        const form = evento.target;

        try {
            const diasFuncionamento = Array.from(
                form.querySelectorAll('input[name="diasFuncionamento"]:checked')
            ).map(cb => parseInt(cb.value));

            const dados = {
                horarioAbertura: form.horarioAbertura.value,
                horarioFechamento: form.horarioFechamento.value,
                diasFuncionamento: diasFuncionamento,
                duracaoMinima: parseInt(form.duracaoMinima.value),
                intervaloReservas: parseInt(form.intervaloReservas.value),
                notificarReservas: form.notificarReservas.checked,
                notificarCancelamentos: form.notificarCancelamentos.checked,
                notificarConflitos: form.notificarConflitos.checked,
                backupAutomatico: form.backupAutomatico.checked
            };

            const resposta = await fetch('../api/configuracoes.php', {
                method: 'POST',
                headers: window.auth.getHeaders(),
                body: JSON.stringify(dados)
            });

            if (!resposta.ok) {
                const erro = await resposta.json();
                throw new Error(erro.erro || 'Erro ao salvar configurações');
            }

            this._configuracoes = await resposta.json();
            this.mostrarSucesso('Configurações salvas com sucesso');
        } catch (erro) {
            console.error('Erro ao salvar configurações:', erro);
            this.mostrarErro(erro.message);
        }
    }

    /**
     * Realiza o backup do sistema
     */
    async fazerBackup() {
        try {
            const resposta = await fetch('../api/backup.php', {
                method: 'POST'
            });

            if (!resposta.ok) {
                const erro = await resposta.json();
                throw new Error(erro.erro || 'Erro ao realizar backup');
            }

            const resultado = await resposta.json();
            document.getElementById('ultimoBackup').textContent = 
                this.formatarDataHora(resultado.data);
            
            this.mostrarSucesso('Backup realizado com sucesso');
        } catch (erro) {
            console.error('Erro ao fazer backup:', erro);
            this.mostrarErro(erro.message);
        }
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
            minute: '2-digit'
        });
    }

    /**
     * Mostra uma mensagem de erro
     */
    mostrarErro(mensagem) {
        alert(mensagem); // Podemos melhorar isso com um componente de toast
    }

    /**
     * Mostra uma mensagem de sucesso
     */
    mostrarSucesso(mensagem) {
        alert(mensagem); // Podemos melhorar isso com um componente de toast
    }
}

// Inicializa o gerenciador quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    window.gerenciadorConfiguracoes = new GerenciadorConfiguracoes();
}); 