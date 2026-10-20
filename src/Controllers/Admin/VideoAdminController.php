<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use Admedia\Core\Controller;
use Admedia\Core\Request;
use Admedia\Core\Response;
use Admedia\Shop\Models\Product;
use App\Models\Video;

/**
 * O Grimório, por dentro: as sessões do canal.
 *
 * Mesma forma que a Sala: uma lista, um campo para criar, e um ecrã de edição
 * com tudo. Um formulário de criação com metade dos campos e outro de edição
 * com todos são dois formulários a divergir.
 *
 * **O endereço do vídeo é o que o editor copia da barra do browser.** Quem o lê
 * é `Admedia\Cms\Video::youtube`, que aceita os parâmetros de seguimento, a
 * lista de onde veio e o «começar aos 1:00» — e devolve as duas coisas que a
 * página precisa: qual é o vídeo e a partir de que segundo.
 */
final class VideoAdminController extends Controller
{
    public function index(Request $req): Response
    {
        $sessões = Video::all();

        $peças = [];
        foreach ($sessões as $v) {
            $peças[(int)$v['id']] = count(Video::produtos((int)$v['id']));
        }

        return $this->render('admin/grimorio/index', [
            'sessões' => $sessões,
            'peças'   => $peças,
        ], 'layouts/admin');
    }

    public function store(Request $req): Response
    {
        $título = trim($req->string('title'));

        if ($título === '') {
            $this->flash('error', 'Escreva um título para começar.');
            return $this->redirect('/admin/grimorio');
        }

        $id = Video::create([
            'title'        => $título,
            'slug'         => $req->string('slug'),
            'is_published' => 0,
            'sort_order'   => (int)$this->db->fetchColumn('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM videos'),
        ]);

        $this->flash('success', 'Criada. Agora cole o endereço do vídeo e diga que peças foram usadas.');
        return $this->redirect('/admin/grimorio/' . $id . '/edit');
    }

    public function edit(Request $req, array $params): Response
    {
        $sessão = Video::find((int)$params['id']);
        if (!$sessão) {
            $this->notFound('Essa sessão não existe.');
        }

        return $this->render('admin/grimorio/edit', [
            'sessão'     => $sessão,
            'produtos'   => Product::published(),
            'escolhidos' => array_map(
                static fn(array $p): int => (int)$p['id'],
                Video::produtos((int)$sessão['id'])
            ),
        ], 'layouts/admin');
    }

    public function update(Request $req, array $params): Response
    {
        $sessão = Video::find((int)$params['id']);
        if (!$sessão) {
            $this->notFound('Essa sessão não existe.');
        }

        $id  = (int)$sessão['id'];
        $url = trim($req->string('url'));

        Video::update($id, [
            'title'        => $req->string('title'),
            'slug'         => $req->string('slug') ?: $req->string('title'),
            'theme'        => $req->string('theme'),
            'url'          => $url,
            'duration'     => $req->string('duration'),
            'summary'      => $req->string('summary'),
            'body'         => $req->string('body'),
            'chapters'     => $req->string('chapters'),
            'drawing'      => $req->string('drawing'),
            'published_at' => $req->string('published_at'),
            'is_published' => $req->string('is_published') !== '' ? 1 : 0,
            'sort_order'   => $req->int('sort_order'),
        ]);

        $escolhidos = $req->input('products', []);
        Video::setProdutos($id, is_array($escolhidos) ? $escolhidos : []);

        /* Um endereço que o leitor não consegue ler é um endereço que desenha
           uma moldura vazia sem dizer porquê. Dito aqui, enquanto quem o colou
           ainda está a olhar para ele. */
        if ($url !== '' && \Admedia\Cms\Video::youtube($url) === null) {
            $this->flash('error', 'Guardado — mas esse endereço não parece ser de um vídeo do '
                . 'YouTube, e a página vai mostrar a moldura vazia. Cole o que está na barra '
                . 'do browser enquanto vê o vídeo.');
        } else {
            $this->flash('success', 'Guardado.');
        }

        return $this->redirect('/admin/grimorio/' . $id . '/edit');
    }

    public function togglePublish(Request $req, array $params): Response
    {
        $sessão = Video::find((int)$params['id']);
        if (!$sessão) {
            $this->notFound('Essa sessão não existe.');
        }

        Video::togglePublished((int)$sessão['id']);

        $this->flash('success', empty($sessão['is_published'])
            ? 'Está no Grimório, à vista de quem visita o site.'
            : 'Saiu do Grimório. A página dela deixa de responder.');

        return $this->redirect('/admin/grimorio');
    }

    public function destroy(Request $req, array $params): Response
    {
        $sessão = Video::find((int)$params['id']);
        if (!$sessão) {
            $this->notFound('Essa sessão não existe.');
        }

        Video::delete((int)$sessão['id']);

        $this->flash('success', 'Apagada.');
        return $this->redirect('/admin/grimorio');
    }
}
