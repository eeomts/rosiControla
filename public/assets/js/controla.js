/**
 * Componentes Alpine reaproveitados pelas telas do Controla.
 * @author Mateus - github.com/eeomts
 */

/** O texto que ela digita ("39,90") de um lado, numero do outro. */
const Moeda = {
    valor(texto) {
        const limpo = String(texto)
            .replace(/[^\d,.-]/g, '')
            .replace(/\.(?=.*[.,])/g, '')
            .replace(',', '.')

        return parseFloat(limpo) || 0
    },

    texto(numero) {
        return numero.toFixed(2).replace('.', ',')
    },
}

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
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
})
