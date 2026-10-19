# Casa de Zé — Design do site

Abrir os ficheiros `.dc.html` diretamente no browser (Chrome, Safari, Firefox). Manter todos os ficheiros e a pasta `assets/` juntos na mesma pasta — as páginas ligam-se entre si.

Para melhor resultado, servir a pasta localmente (ex.: `npx serve .` ou `python3 -m http.server`) e abrir `http://localhost:…/Landing Page.dc.html`.

## Páginas públicas
- `Landing Page.dc.html` — página inicial (começar aqui)
- `Loja.dc.html` — catálogo, produto, carrinho, checkout, confirmação
- `Consultas.dc.html` — serviços, marcação, checkout, confirmação
- `Sala.dc.html` — equipa de tarólogas e perfis
- `Grimorio.dc.html` — sessões do YouTube e página de vídeo
- `Casa.dc.html` — sobre o Zé
- `Contactos.dc.html`, `Legal.dc.html`, `Pesquisa.dc.html`, `404.dc.html`

## Áreas privadas
- `Conta.dc.html` — área de cliente (ambiente claro)
- `Painel.dc.html` — painel de gestão do Zé e da equipa

## Componentes partilhados
- `Rodape.dc.html` — rodapé comum
- `Cookies.dc.html` — aviso de cookies
- `covil-art.js` — ilustrações dos produtos (provisórias)
- `support.js` — runtime das páginas (não editar)

## Notas
- Textos entre [ ] são para o cliente preencher.
- Pagamentos, agenda e envios são simulados; a integração fica para os programadores.
- O estado (carrinho, conta) é guardado no browser (localStorage).
