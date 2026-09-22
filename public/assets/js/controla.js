const Moeda = {
    valor(texto) {
        const limpo = String(texto)
            .replace(/[^\d,.-]/g, '')
            .replace(/\.(?=.*[.,])/g, '')
            .replace(',', '.')

        return parseFloat(limpo) || 0
    },

    // texto(numero) {
    //     return numero.toFixed(2).replace('.', ',')
    // },

    /** 1234.5 -> "1.234,50", igual ao Moeda::brl() do PHP. */
    texto(numero) {
        return numero.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    },

    /** Caixa registradora: so os digitos contam e os dois ultimos sao centavos. */
    mascarar(texto) {
        const digitos = String(texto).replace(/\D/g, '').replace(/^0+/, '')

        return digitos === '' ? '' : this.texto(parseInt(digitos, 10) / 100)
    },
}

/** O token que o CsrfMiddleware exige; o layout deixa numa <meta>. */
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''

/** Tab nao sai da caixa: sem isto o foco cai no que esta atras do scrim. */
const prenderFoco = (caixa, evento) => {
    const focaveis = caixa?.querySelectorAll(
        'a[href], button:not([disabled]), input:not([type=hidden]), select, textarea'
    )

    if (!focaveis || focaveis.length === 0) {
        return
    }

    const primeiro = focaveis[0]
    const ultimo = focaveis[focaveis.length - 1]

    if (evento.shiftKey && document.activeElement === primeiro) {
        evento.preventDefault()
        ultimo.focus()
    } else if (!evento.shiftKey && document.activeElement === ultimo) {
        evento.preventDefault()
        primeiro.focus()
    }
}

document.addEventListener('alpine:init', () => {
    /**
     * Mascara de dinheiro: <input x-moeda>.
     *
     * Se o input tiver x-model, o listener dele pode rodar antes deste e guardar
     * o valor cru; por isso, quando a mascara muda o texto, ela redispara o
     * 'input'. Na segunda volta o texto ja esta formatado e nada muda: sem loop.
     */
    Alpine.directive('moeda', (el) => {
        const aplicar = (formatado) => {
            if (el.value === formatado) {
                return
            }

            el.value = formatado
            el.dispatchEvent(new Event('input', { bubbles: true }))
        }

        el.addEventListener('input', () => aplicar(Moeda.mascarar(el.value)))

        // o que veio do servidor ("123.00") ou do x-model: depois que o x-model preencheu
        queueMicrotask(() => {
            if (el.value !== '') {
                aplicar(Moeda.texto(Moeda.valor(el.value)))
            }
        })
    })

    /**
     * Filtro das listas (ciclo, cliente, venda).
     *
     * Cada <tr> carrega o proprio termo em data-busca; o array `termos` existe
     * so para saber se ALGUMA linha casou, e assim decidir o aviso de vazio.
     */
    Alpine.data('listaFiltravel', (termos = []) => ({
        busca: '',
        termos: termos,

        get alvo() {
            return this.busca.toLowerCase().trim()
        },

        casa(linha) {
            return this.alvo === '' || linha.dataset.busca.includes(this.alvo)
        },

        get achou() {
            return this.alvo === '' || this.termos.some((t) => t.includes(this.alvo))
        },
    }))

    /**
     * Exclusao em dois cliques.
     *
     * Sem JS o form posta de primeira, que e o comportamento certo para quem
     * nao tem script; com Alpine o primeiro clique so arma a confirmacao.
     */
    Alpine.data('confirmacao', () => ({
        confirmando: false,

        armar(evento) {
            if (!this.confirmando) {
                evento.preventDefault()
                this.confirmando = true
            }
        },

        cancelar() {
            this.confirmando = false
        },
    }))

    /**
     * Telefone com mascara na tela e so digito no banco.
     */
    Alpine.data('telefone', (inicial = '') => ({
        telefone: inicial,

        get digitos() {
            return this.telefone.replace(/\D/g, '')
        },

        get curto() {
            const total = this.digitos.length

            return total > 0 && total !== 10 && total !== 11
        },

        mascarar() {
            const d = this.digitos.slice(0, 11)
            const ddd = d.slice(0, 2)
            const resto = d.slice(2)
            const corte = d.length > 10 ? 5 : 4

            if (d.length === 0) {
                this.telefone = ''
                return
            }

            if (resto.length === 0) {
                this.telefone = '(' + ddd
                return
            }

            this.telefone = resto.length > corte
                ? '(' + ddd + ') ' + resto.slice(0, corte) + '-' + resto.slice(corte)
                : '(' + ddd + ') ' + resto
        },
    }))

    Alpine.data('cicloForm', (numero = '', inicio = '', termino = '') => ({
        numero: numero,
        inicio: inicio,
        termino: termino,

        get sugestao() {
            return this.numero ? 'Ciclo ' + this.numero : 'Ciclo'
        },

        get terminoAntes() {
            return this.inicio !== '' && this.termino !== '' && this.termino < this.inicio
        },
    }))
    /**
     * Modal.
     *
     * O servidor decide se nasce aberto (erro de validacao e /x/form precisam
     * disso); daqui pra frente quem manda e o clique.
     */
    /**
     * Modal.
     *
     * O servidor decide se nasce aberto (erro de validacao e /x/form precisam
     * disso); daqui pra frente quem manda e o clique.
     */
    Alpine.data('modal', (aberto = false) => ({
        aberto: aberto,

        /** Ligada por um modal empilhado: enquanto ele estiver aberto, este nao fecha. */
        travado: false,

        init() {
            if (this.aberto) {
                this.$nextTick(() => this.focarPrimeiro())
            }
        },

        abrir() {
            this.aberto = true
            this.$nextTick(() => this.focarPrimeiro())
        },

        fechar() {
            if (this.travado) {
                return
            }

            this.aberto = false
        },

        focarPrimeiro() {
            const alvo = this.$refs.caixa?.querySelector(
                'input:not([type=hidden]), select, textarea, button'
            )
            alvo?.focus()
        },

        prender(evento) {
            if (this.travado) {
                return
            }

            prenderFoco(this.$refs.caixa, evento)
        },
    }))

    /**
     * Cadastro de apoio, num modal por cima de outro: cria so com o nome e
     * devolve a opcao para o select da tela de tras, sem recarregar nada.
     *
     * @param url    endpoint que responde JSON
     * @param alvo   id do <select> que recebe a opcao nova
     */
    Alpine.data('cadastroRapido', (url, alvo) => ({
        aberto: false,
        nome: '',
        erro: '',
        salvando: false,

        abrir() {
            this.nome = ''
            this.erro = ''
            this.aberto = true

            // trava o modal de tras: senao um Esc fecharia os dois de uma vez
            this.$dispatch('modal-travar', true)
            this.$nextTick(() => this.$refs.campo?.focus())
        },

        fechar() {
            this.aberto = false
            this.$dispatch('modal-travar', false)
        },

        prender(evento) {
            prenderFoco(this.$refs.caixa, evento)
        },

        async salvar() {
            if (this.salvando) {
                return
            }

            this.salvando = true
            this.erro = ''

            try {
                const resposta = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        // sem ele, um 419 viria como pagina HTML e o json() abaixo quebraria
                        'X-Requested-With': 'fetch',
                        'X-CSRF-Token': csrf(),
                    },
                    body: new URLSearchParams({ nome: this.nome }),
                })

                const dados = await resposta.json()

                if (!resposta.ok || !dados.ok) {
                    this.erro = dados.erro || 'Nao deu para salvar.'
                    return
                }

                this.inserir(dados.id, dados.nome)
                this.fechar()
            } catch (falha) {
                this.erro = 'Sem resposta do servidor. Tente de novo.'
            } finally {
                this.salvando = false
            }
        },

        inserir(id, nome) {
            const select = document.getElementById(alvo)

            if (!select) {
                return
            }

            select.add(new Option(nome, id, true, true))

            // o x-model de quem escuta o select so percebe pelo evento
            select.dispatchEvent(new Event('change'))
        },
    }))

    /**
     * O lancar e o +/- do pedido sem recarregar: o servidor devolve o "No pedido" pronto
     * (pedido/unidades.php) e ele so troca de lugar. Nada de moeda em JS.
     */
    Alpine.data('unidadesPedido', () => ({
        ocupado: false,
        falha: '',
        recado: '',

        async enviar(evento) {
            const form = evento.target

            if (!form.matches('[data-fragmento]')) {
                return
            }

            evento.preventDefault()

            // clique repetido enquanto o anterior nao voltou: ignora, senao duplica unidade
            if (this.ocupado) {
                return
            }

            const foco = evento.submitter?.dataset.foco
            this.ocupado = true
            this.recado = ''
            this.$refs.alvo.setAttribute('aria-busy', 'true')

            try {
                const resposta = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'fetch', 'X-CSRF-Token': csrf() },
                    body: new URLSearchParams(new FormData(form)),
                })

                const html = await resposta.text()

                // o middleware do Csrf responde JSON, nao o pedaco de tela
                if (resposta.headers.get('content-type')?.includes('json')) {
                    this.falha = JSON.parse(html).erro || 'Nao deu para atualizar.'
                    return
                }

                if (html.trim() === '') {
                    this.falha = 'Nao deu para atualizar. Tente de novo.'
                    return
                }

                this.falha = ''
                this.$refs.alvo.innerHTML = html
                this.atualizarLista()

                // o form de lancar zera so se deu certo: no 422 ela corrige o que digitou
                if (resposta.ok && form.hasAttribute('data-limpar')) {
                    form.reset()
                    this.recado = 'Produto adicionado.'
                    form.querySelector('select, input:not([type=hidden])')?.focus()
                } else {
                    this.devolverFoco(foco)
                }
            } catch (erro) {
                this.falha = 'Sem resposta do servidor. Tente de novo.'
            } finally {
                this.ocupado = false
                this.$refs.alvo.removeAttribute('aria-busy')
            }
        },

        /** A linha deste pedido na lista de tras, senao ela fica com o numero velho. */
        atualizarLista() {
            const dados = this.$refs.alvo.querySelector('.unidades')?.dataset
            const linha = dados && document.querySelector(`tr[data-pedido="${dados.pedido}"]`)

            if (!linha) {
                return
            }

            const colunas = {
                unidades: dados.unidades,
                total: dados.total,
                'lucro-estimado': dados.lucroEstimado,
                'lucro-real': dados.lucroReal,
            }

            for (const [coluna, valor] of Object.entries(colunas)) {
                const celula = linha.querySelector(`[data-coluna="${coluna}"]`)

                if (celula) {
                    celula.textContent = valor
                }
            }
        },

        /** O botao clicado foi trocado por um novo; o foco volta pro equivalente. */
        devolverFoco(foco) {
            if (!foco) {
                return
            }

            const botao = this.$refs.alvo.querySelector(`[data-foco="${CSS.escape(foco)}"]`)

            if (botao && !botao.disabled) {
                botao.focus()
            }
        },
    }))
})
