<?php
declare(strict_types=1);

namespace App\Controllers;

use Admedia\Core\Accounts\CustomerAuth;
use Admedia\Core\App;
use Admedia\Core\Controller;
use Admedia\Core\Request;
use Admedia\Core\Response;

/**
 * Os dados de quem compra, mudados por quem compra.
 *
 * A conta mostrava a morada e mandava mudá-la no checkout da compra seguinte —
 * o que é verdade e é pouco: uma pessoa que muda de casa não quer esperar pela
 * próxima encomenda para o dizer, e quem comprou como convidado não tem morada
 * nenhuma guardada para corrigir.
 *
 * **Isto devia viver no pacote**, ao lado do resto do `AccountsController` —
 * seis sites partilham-no e todos ganhariam o mesmo ecrã. Está aqui porque é
 * onde se podia escrever hoje; o dia em que subir, este ficheiro desaparece e
 * a rota passa a apontar para lá. Ver config/routes.php.
 *
 * Só escreve as colunas da própria pessoa. O email não está cá de propósito:
 * é com ele que se entra, e mudá-lo é mudar a chave da porta — pede confirmação
 * no endereço novo, e isso é outro ecrã e outro fluxo.
 */
final class ContaController extends Controller
{
    /** O que se deixa guardar, e o tamanho que a coluna aguenta. */
    private const CAMPOS = [
        'name'          => 190,
        'phone'         => 60,
        'ship_line1'    => 190,
        'ship_line2'    => 190,
        'ship_postcode' => 30,
        'ship_city'     => 120,
    ];

    /**
     * Para onde a casa envia, em ISO-3166 alfa-2.
     *
     * **Aqui e não na vista**, que a lê daqui: duas listas destas são duas
     * listas que têm de concordar, e a que ficasse desactualizada era a que
     * valida. Um país fora dela entra como vazio — truncar «Portugal» aos dois
     * primeiros caracteres guardava «PO», que não é país nenhum e só se
     * descobre quando a encomenda vai para o estrangeiro.
     *
     * @var array<string,string>
     */
    public const PAISES = [
        'PT' => 'Portugal',
        'ES' => 'Espanha',
        'FR' => 'França',
        'DE' => 'Alemanha',
        'GB' => 'Reino Unido',
        'BR' => 'Brasil',
    ];

    public function guardar(Request $req): Response
    {
        $customer = CustomerAuth::instance()->customer();

        if ($customer === null) {
            return $this->redirect(CustomerAuth::loginPath());
        }

        $dados = [];
        foreach (self::CAMPOS as $campo => $limite) {
            $dados[$campo] = mb_substr(trim($req->string($campo)), 0, $limite);
        }

        /* O país é escolhido de uma lista, e o que não estiver nela não é país.
           Validado e não cortado: ver PAISES. */
        $pais = strtoupper(trim($req->string('ship_country')));
        $dados['ship_country'] = isset(self::PAISES[$pais]) ? $pais : '';

        App::instance()->db()->update(
            'customers',
            $dados,
            'id = :id',
            ['id' => (int)$customer['id']]
        );

        /* Não é preciso limpar nada: o CustomerAuth guarda a linha em memória
           dentro do pedido, e quem desenha a página a seguir é outro pedido,
           que a vai buscar outra vez. */
        $this->flash('account.ok', 'Os teus dados ficaram guardados.');

        return $this->redirect(CustomerAuth::homePath());
    }
}
