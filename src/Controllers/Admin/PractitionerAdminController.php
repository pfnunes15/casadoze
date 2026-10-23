<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use Admedia\Core\Controller;
use Admedia\Core\Request;
use Admedia\Core\Response;
use App\Models\Consultation;
use App\Models\Practitioner;

/**
 * A Sala, por dentro: quem lê as cartas na Casa.
 *
 * Um ecrã de lista e um de edição. Não há ecrã de criação à parte — criar é
 * guardar uma linha vazia e cair logo na edição, que é onde está tudo o que há
 * para escrever. Um formulário de criação com metade dos campos e outro de
 * edição com todos são dois formulários a divergir.
 *
 * **Apagar pergunta primeiro.** Uma pessoa apagada leva consigo a ligação às
 * consultas que dá; isso não desmarca ninguém — as marcações são da consulta e
 * não da pessoa —, mas a página dela deixa de existir e um endereço que alguém
 * guardou passa a dar 404. Por isso o botão confirma.
 */
final class PractitionerAdminController extends Controller
{
    public function index(Request $req): Response
    {
        $pessoas = Practitioner::all();

        /* Quantas leituras dá cada uma, para a lista o poder dizer sem se abrir
           cada linha. Uma pergunta por pessoa; são quatro. */
        $quantas = [];
        foreach ($pessoas as $p) {
            $quantas[(int)$p['id']] = count(Practitioner::consultas((int)$p['id']));
        }

        return $this->render('admin/sala/index', [
            'pessoas' => $pessoas,
            'quantas' => $quantas,
        ], 'layouts/admin');
    }

    public function store(Request $req): Response
    {
        $nome = trim($req->string('name'));

        if ($nome === '') {
            $this->flash('error', 'Escreva um nome para começar.');
            return $this->redirect('/admin/sala');
        }

        $id = Practitioner::create([
            'name'         => $nome,
            'slug'         => $req->string('slug'),
            'is_published' => 0,
            'sort_order'   => (int)$this->db->fetchColumn('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM practitioners'),
        ]);

        $this->flash('success', 'Criada. Agora escreva quem é e que leituras dá.');
        return $this->redirect('/admin/sala/' . $id . '/edit');
    }

    public function edit(Request $req, array $params): Response
    {
        $pessoa = Practitioner::find((int)$params['id']);
        if (!$pessoa) {
            $this->notFound('Essa pessoa não existe.');
        }

        return $this->render('admin/sala/edit', [
            'pessoa'    => $pessoa,
            'consultas' => Consultation::all(),
            'escolhidas' => array_map(
                static fn(array $c): int => (int)$c['id'],
                Practitioner::consultas((int)$pessoa['id'])
            ),
        ], 'layouts/admin');
    }

    public function update(Request $req, array $params): Response
    {
        $pessoa = Practitioner::find((int)$params['id']);
        if (!$pessoa) {
            $this->notFound('Essa pessoa não existe.');
        }

        $id = (int)$pessoa['id'];

        Practitioner::update($id, [
            'name'         => $req->string('name'),
            'slug'         => $req->string('slug') ?: $req->string('name'),
            'role'         => $req->string('role'),
            'quote'        => $req->string('quote'),
            'bio'          => $req->string('bio'),
            'deck'         => $req->string('deck'),
            'sign'         => $req->string('sign'),
            'years'        => $req->int('years'),
            'image'        => $req->string('image'),
            'image_alt'    => $req->string('image_alt'),
            'image_focus'  => $req->string('image_focus'),
            'specialties'  => $req->string('specialties'),
            'formats'      => $req->string('formats'),
            'languages'    => $req->string('languages'),
            'is_published' => $req->string('is_published') !== '' ? 1 : 0,
            'sort_order'   => $req->int('sort_order'),
        ]);

        $escolhidas = $req->input('consultations', []);
        Practitioner::setConsultas($id, is_array($escolhidas) ? $escolhidas : []);

        $this->flash('success', 'Guardado.');
        return $this->redirect('/admin/sala/' . $id . '/edit');
    }

    public function togglePublish(Request $req, array $params): Response
    {
        $pessoa = Practitioner::find((int)$params['id']);
        if (!$pessoa) {
            $this->notFound('Essa pessoa não existe.');
        }

        Practitioner::togglePublished((int)$pessoa['id']);

        $this->flash('success', empty($pessoa['is_published'])
            ? 'Está na Sala, à vista de quem visita o site.'
            : 'Saiu da Sala. A página dela deixa de responder.');

        return $this->redirect('/admin/sala');
    }

    public function destroy(Request $req, array $params): Response
    {
        $pessoa = Practitioner::find((int)$params['id']);
        if (!$pessoa) {
            $this->notFound('Essa pessoa não existe.');
        }

        Practitioner::delete((int)$pessoa['id']);

        $this->flash('success', 'Apagada.');
        return $this->redirect('/admin/sala');
    }
}
