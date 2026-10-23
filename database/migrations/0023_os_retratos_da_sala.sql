-- ---------------------------------------------------------------------------
-- The portraits of Débora and Rogério.
--
-- Both profiles were drawing initials over the house background, which is what
-- the Sala does when there is no photograph. The client has given the two.
--
-- The path carries the .jpg that no longer exists on disk, and that is on
-- purpose: App\Services\Imagens drops the extension and looks for the WebP and
-- AVIF variants beside it. Written this way, a photograph uploaded through the
-- backoffice — which has no variants — still resolves to itself.
-- ---------------------------------------------------------------------------
UPDATE practitioners
   SET image = '/assets/img/debora.jpg',
       image_alt = 'Débora D''Exu'
 WHERE slug = 'debora-dexu';

UPDATE practitioners
   SET image = '/assets/img/rogerio.jpg',
       image_alt = 'Rogério de Exu'
 WHERE slug = 'rogerio-de-exu';
