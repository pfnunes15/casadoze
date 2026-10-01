// Rich-text editor for backoffice content fields.
//
// TinyMCE is served from public/assets/vendor/tinymce, not from a CDN. A CDN
// script tag with no integrity attribute is arbitrary third-party code running
// in an authenticated administrator's session, and the Content-Security-Policy
// forbids it anyway (script-src 'self').
//
// Attaches to any <textarea class="rich-editor">, and exposes
// window.cmsEditor.attach(selector) so a field added to the page after load —
// a new item in a block — can become an editor too.
(function () {
  'use strict';

  if (typeof tinymce === 'undefined') return;

  var meta = document.querySelector('meta[name="csrf-token"]');
  var csrf = meta ? meta.getAttribute('content') : '';

  function uploadImage(blobInfo) {
    return new Promise(function (resolve, reject) {
      var body = new FormData();
      body.append('file', blobInfo.blob(), blobInfo.filename());
      body.append('_csrf', csrf);

      fetch('/admin/editor/upload', {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrf },
        body: body,
        credentials: 'same-origin',
      })
        .then(function (response) {
          return response.json().then(function (json) {
            return { ok: response.ok, json: json };
          });
        })
        .then(function (result) {
          if (!result.ok || !result.json.location) {
            reject({ message: result.json.error || 'Falha no carregamento.', remove: true });
            return;
          }
          resolve(result.json.location);
        })
        .catch(function () {
          reject({ message: 'Erro de rede ao carregar a imagem.', remove: true });
        });
    });
  }

  function pickFile(callback) {
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function () {
      var file = this.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function () {
        var cache = tinymce.activeEditor.editorUpload.blobCache;
        var info = cache.create('blob-' + Date.now(), file, String(reader.result).split(',')[1]);
        cache.add(info);
        callback(info.blobUri(), { title: file.name });
      };
      reader.readAsDataURL(file);
    };
    input.click();
  }

  // Each heading carries its level in the margin. Someone who has never written
  // HTML still has to end up with a sane document outline, and the only way to
  // choose sensibly is to see what you already made — the block menu alone tells
  // you what you are about to type, never what is above it.
  // Selectors carry the .mce-content-body class because TinyMCE's own content
  // stylesheet targets it, and a bare `body` rule loses to it on specificity —
  // which is how the left padding these badges hang in went missing.
  var CONTENT_STYLE = [
    'body.mce-content-body{padding:1.25rem 1.5rem 1.25rem 3.6rem;',
    '  font-family:system-ui,-apple-system,"Segoe UI",sans-serif;',
    '  color:#1f2937;line-height:1.65;}',
    'img{max-width:100%;height:auto;}',
    '.mce-content-body :is(h2,h3,h4,h5,h6){position:relative;line-height:1.25;}',
    '.mce-content-body :is(h2,h3,h4,h5,h6)::before{',
    '  position:absolute;left:-2.6rem;top:.35em;',
    '  font:600 11px/1.6 system-ui,sans-serif;letter-spacing:.06em;',
    '  color:#94a3b8;background:#f1f5f9;border-radius:4px;padding:0 .4em;}',
    'h2::before{content:"H2";}',
    'h3::before{content:"H3";}',
    'h4::before{content:"H4";}',
    'h5::before{content:"H5";}',
    'h6::before{content:"H6";}',
    'blockquote{border-left:3px solid #cbd5e1;margin-left:0;padding-left:1rem;color:#475569;}',
  ].join('');

  var SETTINGS = {
    license_key: 'gpl',
    base_url: '/assets/vendor/tinymce',
    suffix: '.min',

    language: 'pt_PT',
    language_url: '/assets/vendor/tinymce/langs/pt_PT.js',

    height: 420,
    menubar: false,
    branding: false,
    promotion: false,
    convert_urls: false,

    // No autoresize: it writes the body's horizontal padding as an inline
    // style, which no stylesheet can beat, and that padding is the gutter the
    // level badges hang in. It also fights the fixed height set above — an
    // editor that grows without bound pushes the save bar off a long form.
    plugins: 'lists link image table autolink quickbars wordcount',
    toolbar:
      'undo redo | blocks | bold italic | bullist numlist | ' +
      'alignleft aligncenter alignright | link image table | removeformat',

    // Plain names rather than tag jargon, with the level kept alongside so the
    // words and the badges in the margin refer to the same thing. h1 is
    // deliberately absent: a page has one main title and it belongs to the
    // block, not to a paragraph inside one.
    block_formats:
      'Texto normal=p; Título de secção (H2)=h2; Subtítulo (H3)=h3; ' +
      'Título pequeno (H4)=h4; Citação=blockquote',

    quickbars_insert_toolbar: false,
    quickbars_selection_toolbar: 'bold italic | blocks | bullist numlist | link',
    contextmenu: 'link image table',

    // Only the elements the output filter keeps. Anything else the editor
    // produced would be stripped on the way out, which looks like data loss.
    valid_elements:
      'p,br,hr,strong/b,em/i,u,s,sub,sup,small,mark,' +
      'h2,h3,h4,h5,h6,ul,ol[start|type],li,dl,dt,dd,' +
      'blockquote[cite],pre,code,span[class],div[class],' +
      'figure[class],figcaption,section,article,' +
      'a[href|target|rel|title],img[src|alt|width|height|loading],' +
      'table,thead,tbody,tfoot,tr,th[colspan|rowspan|scope],td[colspan|rowspan],caption',

    automatic_uploads: true,
    paste_data_images: true,
    image_caption: true,
    image_dimensions: false,
    image_title: false,
    file_picker_types: 'image',
    file_picker_callback: pickFile,
    content_style: CONTENT_STYLE,
    images_upload_handler: uploadImage,
  };

  function attach(selector) {
    var settings = {};
    for (var key in SETTINGS) {
      if (Object.prototype.hasOwnProperty.call(SETTINGS, key)) settings[key] = SETTINGS[key];
    }
    settings.selector = selector;
    return tinymce.init(settings);
  }

  window.cmsEditor = { attach: attach };

  if (document.querySelector('textarea.rich-editor')) {
    attach('textarea.rich-editor');
  }
})();
