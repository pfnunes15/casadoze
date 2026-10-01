<?php
declare(strict_types=1);

namespace App\Services;

use Admedia\Core\App;
use App\Models\ConsultationClosure;
use App\Models\ConsultationSchedule;

/**
 * O horário, transformado em vagas.
 *
 * A Casa escreve «terças e quintas às 21h e às 21h45» e isto faz disso linhas
 * concretas em `consultation_slots`, dia a dia, para os próximos meses.
 *
 * **Porque é que as vagas são escritas e não calculadas.** Um horário calculado
 * na hora responde a «que dias há consulta»; não responde a «aquela hora ainda
 * está livre», porque não há linha nenhuma onde descontar. Escrevê-las dá uma
 * linha por hora — que se tranca ao marcar, que se fecha por si num dia de
 * férias, e a que se muda a lotação sem mexer no horário nem perder quem já lá
 * está.
 *
 * **Correr isto as vezes que forem precisas não faz mal.** A chave `uk_slot`
 * (consulta + hora) impede a segunda cópia da mesma vaga, e a inserção é feita
 * com `INSERT IGNORE`: o que já existe fica como está, com as marcações que
 * tiver. É por isso que se pode chamar sem medo — e é o que o calendário das
 * Marcações faz ao abrir, quando o horizonte lhe parece curto.
 *
 * O que isto **não** faz: apagar. Uma vaga gerada por um horário que entretanto
 * mudou continua lá, porque pode ter gente marcada. Quem a quiser fora fecha-a
 * ou apaga-a no backoffice, a ver quem lá está.
 */
final class GeradorDeVagas
{
    /** Até onde se geram vagas. Três meses é o que se planeia uma consulta. */
    public const HORIZONTE_DIAS = 90;

    /**
     * Encher o calendário até ao horizonte.
     *
     * @param int|null $consultationId Só uma consulta, ou todas quando é nulo.
     * @return int Quantas vagas foram criadas — zero quando já estava tudo lá.
     */
    public static function correr(?int $consultationId = null, int $dias = self::HORIZONTE_DIAS): int
    {
        $dias   = max(1, min(365, $dias));
        $hoje   = new \DateTimeImmutable('today');
        $fim    = $hoje->modify('+' . $dias . ' days');
        $fechos = ConsultationClosure::betweenAsSet($hoje, $fim);

        $db      = App::instance()->db();
        $criadas = 0;

        for ($dia = $hoje; $dia < $fim; $dia = $dia->modify('+1 day')) {
            $ymd = $dia->format('Y-m-d');

            foreach (ConsultationSchedule::activeOn($dia) as $linha) {
                $cid = (int)$linha['consultation_id'];

                if ($consultationId !== null && $cid !== $consultationId) {
                    continue;
                }
                if (ConsultationClosure::isClosed($fechos, $ymd, $cid)) {
                    continue;
                }

                $capacidade = $linha['capacity'] !== null
                    ? (int)$linha['capacity']
                    : (int)$linha['consultation_capacity'];

                /* INSERT IGNORE e não «ver se existe, depois inserir»: entre a
                   pergunta e a resposta cabe outro pedido a fazer o mesmo, e o
                   que se apanhava era um erro de chave duplicada em vez de uma
                   vaga. Aqui o índice decide, e quem perde não faz nada. */
                $criadas += $db->query(
                    "INSERT IGNORE INTO consultation_slots
                        (consultation_id, schedule_id, starts_at, capacity, source)
                     VALUES (:cid, :sid, :quando, :cap, 'horario')",
                    [
                        'cid'    => $cid,
                        'sid'    => (int)$linha['id'],
                        'quando' => $ymd . ' ' . substr((string)$linha['start_time'], 0, 8),
                        'cap'    => max(1, $capacidade),
                    ]
                )->rowCount();
            }
        }

        return $criadas;
    }

    /**
     * Correr só se fizer falta.
     *
     * O calendário das Marcações chama isto ao abrir, e não se quer uma varredura
     * de noventa dias em cada carregamento. A pergunta é barata — a vaga mais
     * distante que existe — e a resposta é quase sempre «não»: só quando o
     * calendário está a menos de duas semanas do fim é que se volta a encher.
     *
     * É isto em vez de um botão «Encher o calendário» no backoffice. O botão
     * funcionava, mas era a pergunta errada a fazer a quem gere: ninguém que
     * atende o telefone tem de saber que existe um gerador, nem de se lembrar de o
     * correr antes de o horizonte acabar.
     *
     * Continua a valer a pena ter isto num `bin/` para correr de madrugada: quem
     * abre o backoffice de um site parado há três meses não deve ser quem paga a
     * geração. É o que faz o bin/gerar-vagas.php.
     */
    public static function correrSePreciso(?int $consultationId = null): int
    {
        $where  = 'starts_at >= NOW()';
        $params = [];

        if ($consultationId !== null) {
            $where .= ' AND consultation_id = :cid';
            $params['cid'] = $consultationId;
        }

        $ultima = App::instance()->db()->fetchColumn(
            "SELECT MAX(starts_at) FROM consultation_slots WHERE {$where}",
            $params
        );

        $limite = (new \DateTimeImmutable('today'))->modify('+14 days');
        if ($ultima !== null && $ultima !== false && new \DateTimeImmutable((string)$ultima) > $limite) {
            return 0;
        }

        return self::correr($consultationId);
    }
}
