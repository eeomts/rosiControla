const Moeda = {
    valor(texto) {
        const limpo = String(texto)
            .replace(/[^\d,.-]/g, '')
            .replace(/\.(?=.*[.,])/g, '')
            .replace(',', '.')

        return parseFloat(limpo) || 0
    },

    texto(numero) {
        return numero.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    },

    mascarar(texto) {
        const digitos = String(texto).replace(/\D/g, '').replace(/^0+/, '')

        return digitos === '' ? '' : this.texto(parseInt(digitos, 10) / 100)
    },
}

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''

const semAcento = (texto) => String(texto ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()

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

    Alpine.directive('moeda', (el) => {
        const aplicar = (formatado) => {
            if (el.value === formatado) {
                return
            }

            el.value = formatado
            el.dispatchEvent(new Event('input', { bubbles: true }))
        }

        el.addEventListener('input', () => aplicar(Moeda.mascarar(el.value)))

        queueMicrotask(() => {
            if (el.value !== '') {
                aplicar(Moeda.texto(Moeda.valor(el.value)))
            }
        })
    })

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

    Alpine.data('modal', (aberto = false) => ({
        aberto: aberto,

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

    Alpine.data('cadastroRapido', (url, alvo) => ({
        aberto: false,
        nome: '',
        erro: '',
        salvando: false,

        abrir() {
            this.nome = ''
            this.erro = ''
            this.aberto = true

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

            select.dispatchEvent(new Event('change'))
        },
    }))

    Alpine.data('cadastroEmpilhado', (url, evento, vazio) => ({
        aberto: false,
        dados: { ...vazio },
        erros: {},
        erro: '',
        salvando: false,

        abrir() {
            this.dados = { ...vazio }
            this.erros = {}
            this.erro = ''
            this.aberto = true

            this.$dispatch('modal-travar', true)
            this.$nextTick(() => this.$refs.primeiro?.focus())
        },

        fechar() {
            this.aberto = false
            this.$dispatch('modal-travar', false)
        },

        prender(evento) {
            prenderFoco(this.$refs.caixa, evento)
        },

        async salvar() {
            if (this.salvando || String(this.dados.nome ?? '').trim() === '') {
                return
            }

            this.salvando = true
            this.erros = {}
            this.erro = ''

            try {
                const resposta = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'fetch',
                        'X-CSRF-Token': csrf(),
                    },
                    body: new URLSearchParams(this.dados),
                })

                const dados = await resposta.json()

                if (!resposta.ok || !dados.ok) {
                    this.erros = dados.erros || {}
                    
                    this.erro = dados.erro || (Object.keys(this.erros).length ? '' : 'Nao deu para salvar.')
                    return
                }

                window.dispatchEvent(new CustomEvent(evento, { detail: dados.item }))

                this.fechar()
            } catch (falha) {
                this.erro = 'Sem resposta do servidor. Tente de novo.'
            } finally {
                this.salvando = false
            }
        },
    }))

    Alpine.data('escolha', (config) => ({
        config,
        itens: config.itens,
        aberto: false,
        busca: '',
        marcado: null,
        escolhido: config.valor ?? null,

        init() {
            if (config.evento) {
                window.addEventListener(config.evento, (e) => this.acrescentar(e.detail))
            }

            this.$el.closest('form')?.addEventListener('reset', () => {
                this.escolhido = null
            })
        },

        get visiveis() {
            const termo = semAcento(this.busca)

            if (termo === '') {
                return this.itens
            }

            return this.itens.filter((item) =>
                config.colunas.some((coluna) => semAcento(item[coluna.chave]).includes(termo))
            )
        },

        get legenda() {
            const item = this.item(this.escolhido)

            return item ? config.legenda.map((coluna) => item[coluna]).filter(Boolean).join(' - ') : ''
        },

        item(id) {
            return id === null ? null : this.itens.find((item) => String(item.id) === String(id)) ?? null
        },

        ehMarcado(item) {
            return this.marcado !== null && String(this.marcado) === String(item.id)
        },

        classe(item) {
            if (this.marcado === null) {
                return ''
            }

            return this.ehMarcado(item) ? 'escolha-marcada' : 'escolha-ofuscada'
        },

        abrir() {
            this.marcado = this.escolhido
            this.busca = ''
            this.aberto = true

this.$dispatch('modal-travar', true)
            this.$nextTick(() => this.$refs.busca?.focus())
        },

        fechar() {
            this.aberto = false
            this.$dispatch('modal-travar', false)
        },

        marcar(id) {
            this.marcado = id
        },

        confirmar() {
            if (this.marcado === null) {
                return
            }

            this.escolhido = this.marcado
            this.fechar()
        },

        escolherJa(id) {
            this.marcado = id
            this.confirmar()
        },

        acrescentar(item) {
            if (!item || item.id === undefined) {
                return
            }

            if (this.item(item.id) === null) {
                const ordem = config.legenda[0]

                this.itens = [...this.itens, item].sort((a, b) =>
                    String(a[ordem] ?? '').localeCompare(String(b[ordem] ?? ''), 'pt-BR')
                )
            }

            this.escolhido = item.id
        },

        prender(evento) {
            prenderFoco(this.$refs.caixa, evento)
        },
    }))

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

                if (resposta.ok && form.hasAttribute('data-limpar')) {
                    form.reset()
                    this.recado = 'Produto adicionado.'
                    form.querySelector('[data-primeiro], select, input:not([type=hidden])')?.focus()
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
