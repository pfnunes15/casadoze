<?php
declare(strict_types=1);

namespace App\Controllers;

use Admedia\Core\Controller;
use Admedia\Core\Request;
use Admedia\Core\Response;
use App\Models\Video;

/**
 * O Grimório: as sessões gratuitas do Zé.
 *
 * A lista e a página de cada sessão. Ver `docs/maquete/Grimorio.dc.html`.
 *
 * **É a secção que liga o que se vê de graça ao que se vende**, e por isso cada
 * sessão traz a peça da loja que foi usada nela. Sem essa ligação o Grimório é
 * uma lista de vídeos que podia estar em qualquer lado.
 */
final class GrimorioController extends Controller
{
    public function index(Request $req): Response
    {
        $sessões = Video::published();

        $produtos = [];
        foreach ($sessões as $v) {
            $produtos[(int)$v['id']] = Video::produtos((int)$v['id']);
        }

        return $this->render('grimorio/index', [
            'sessões'  => $sessões,
            'produtos' => $produtos,
            'temas'    => Video::temas(),
        ]);
    }

    public function show(Request $req, array $params): Response
    {
        $sessão = Video::findBySlug((string)($params['slug'] ?? ''));

        if ($sessão === null || empty($sessão['is_published'])) {
            $this->notFound();
        }

        $id = (int)$sessão['id'];

        /* As outras, para o fim da página. As três mais recentes chegam: uma
           lista inteira no fim de cada sessão é a lista outra vez. */
        $outras = array_slice(array_values(array_filter(
            Video::published(),
            static fn(array $v): bool => (int)$v['id'] !== $id
        )), 0, 3);

        return $this->render('grimorio/show', [
            'sessão'    => $sessão,
            'capítulos' => Video::capítulos($sessão),
            'produtos'  => Video::produtos($id),
            'outras'    => $outras,
        ]);
    }
}
