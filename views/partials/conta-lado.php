<?php
/**
 * The panel beside the account forms: the photograph and what an account is for.
 *
 * Only on a wide screen. On a phone it is the first thing that would scroll past
 * before the e-mail field, and nobody who came here to sign in wants to read an
 * argument for signing in first.
 *
 * The three lines are what this site actually does. The mock-up had a fourth
 * about gift vouchers, and there are none — a promise on a sign-in screen is a
 * promise somebody holds us to.
 */
?>
<aside class="porta__lado" aria-hidden="true">
  <div class="porta__foto" style="background-image: <?= \App\Services\Imagens::fundo('/assets/img/abertura') ?>"></div>
  <div class="porta__véu"></div>
  <div class="porta__dizer">
    <p class="porta__chamada">O teu lugar à mesa da Casa</p>
    <ul class="porta__lista">
      <li><span aria-hidden="true">&#10022;</span> Acompanhas encomendas e envios</li>
      <li><span aria-hidden="true">&#10022;</span> Remarcas consultas sem escrever a ninguém</li>
      <li><span aria-hidden="true">&#10022;</span> As tuas moradas ficam guardadas</li>
    </ul>
  </div>
</aside>
