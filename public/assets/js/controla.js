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
})
