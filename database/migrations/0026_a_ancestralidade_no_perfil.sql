-- ---------------------------------------------------------------------------
-- The ancestrality text, whole, on Débora's profile.
--
-- The homepage carries three paragraphs of it — see 0025, which explains why.
-- The profile is where there is room for all seven, and where someone who read
-- the short version goes to read the rest.
-- ---------------------------------------------------------------------------
UPDATE practitioners
   SET bio = CONCAT(bio,
     '<h3>A ancestralidade que me acompanha</h3>',
     '<p>Falar do meu percurso é também honrar quem me ajudou a encontrar sentido quando, numa fase da minha vida, já nada parecia fazer sentido.</p>',
     '<p>José Pelintra do Cruzeiro das Almas é a minha principal entidade e o ancestral que me acompanha. Foi através da minha ligação a Seu Zé Pelintra das Almas que se abriu um novo caminho na minha vida. Na sua presença espiritual encontrei amparo, orientação e força para continuar. Essa ligação está na origem do trabalho que hoje desenvolvo e do compromisso com que acolho quem me procura.</p>',
     '<p>O respeito e a gratidão que lhe tenho fazem parte da minha vida e da forma como exerço o meu cuidado espiritual. Reconheço nos seus ensinamentos uma presença que me ajuda a aprender, a amadurecer e a assumir a responsabilidade do meu caminho.</p>',
     '<p>As minhas forças de cabeça são Exu Marabô das Sete Encruzilhadas e Dona Tata Mulambo. Honro a ligação a cada um deles, com respeito pelos seus fundamentos e pelo lugar que ocupam na minha caminhada.</p>',
     '<p>A todos agradeço o conhecimento que me trouxeram, o trabalho que me confiam e a oportunidade de colocar os ensinamentos recebidos ao serviço de quem me procura. A minha gratidão expressa-se na dedicação, no sigilo e na seriedade com que procuro trabalhar todos os dias.</p>',
     '<p>Seu Zé Pelintra das Almas foi a abertura para tudo na minha vida. Honrar essa história é manter vivas as minhas raízes e nunca esquecer como este caminho começou.</p>'
   )
 WHERE slug = 'debora-dexu'
   AND bio NOT LIKE '%ancestralidade que me acompanha%';
