-- ---------------------------------------------------------------------------
-- Where a portrait is cropped from.
--
-- The cards are 4:5 and the photographs are not. `object-fit: cover` crops from
-- the middle, which is right for a face in the middle and wrong for Rogério,
-- whose photograph puts him on the right of a seascape — the card showed the
-- sea and cut him off.
--
-- A column and not a crop at generation time: cropping throws away the part of
-- the picture the client chose, and the same photograph is framed differently
-- in the card (4:5) and on the profile. The browser does the cropping; this
-- says where from.
--
-- The values are CSS `object-position` keywords, so the column goes straight
-- into the stylesheet. Free text and not an enum because `object-position`
-- takes percentages too, and a face two thirds up is `center 30%`.
-- ---------------------------------------------------------------------------
ALTER TABLE practitioners
    ADD COLUMN image_focus VARCHAR(40) NOT NULL DEFAULT 'center' AFTER image_alt;

UPDATE practitioners SET image_focus = 'right center' WHERE slug = 'rogerio-de-exu';
