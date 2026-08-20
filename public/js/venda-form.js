/**

 * @author Mateus - github.com/eeomts
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('vendaForm', (estoque = [], itens = [], desconto = '0,00') => ({
        estoque: estoque,
        itens: itens,
        desconto: desconto,
        busca: '',

        // ------------------------------------------------------- o estoque

        get alvo() {
            return this.busca.toLowerCase().trim()
        },

        get grupos() {
            return this.estoque.filter(
                (g) => this.alvo === '' || g.produto.toLowerCase().includes(this.alvo)
            )
        },

        temItem(id) {
            return this.itens.some((i) => String(i.fk_variacao_produto) === String(id))
        },

        usados(grupo) {
            return grupo.ids.filter((id) => this.temItem(id)).length
        },

        sobram(grupo) {
            return grupo.ids.length - this.usados(grupo)
        },

        // --------------------------------------------------- itens da venda

        adicionar(grupo) {
            const livre = grupo.ids.find((id) => !this.temItem(id))

            if (livre === undefined) {
                return
            }

            this.itens.push({
                fk_variacao_produto: String(livre),
                mon_venda: grupo.preco.replace('.', ','),
                mon_desconto: '0,00',
            })
        },

        remover(indice) {
            this.itens.splice(indice, 1)
        },

        grupoDe(id) {
            return this.estoque.find((g) => g.ids.some((x) => String(x) === String(id))) || {}
        },

        rotulo(id) {
            const grupo = this.grupoDe(id)

            return grupo.produto ? grupo.produto + ' - ' + grupo.ciclo : 'unidade ' + id
        },

        // ------------------------------------------------------- os totais

        moeda(numero) {
            return Moeda.texto(numero)
        },

        liquido(item) {
            return Math.max(0, Moeda.valor(item.mon_venda) - Moeda.valor(item.mon_desconto))
        },

        get bruto() {
            return this.itens.reduce((soma, item) => soma + this.liquido(item), 0)
        },

        get descontoDaVenda() {
            return Math.min(Moeda.valor(this.desconto), this.bruto)
        },

        get total() {
            return this.bruto - this.descontoDaVenda
        },

        /** Espelho do VendaService::validarItens(). */
        get descontoPassaDoTotal() {
            return Moeda.valor(this.desconto) > this.bruto
        },
    }))
})
