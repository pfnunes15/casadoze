/* ---------------------------------------------------------------------------
   Typing on the page.

   This runs inside the frame that draws one block of a page in the editor. The
   markup is the site's own — its partial, its stylesheet, its content — and
   the spans marked `data-live` are the pieces of it that came out of a column
   of the database. Admedia\Cms\LiveEdit put them there and knows how to put
   what is typed back where it came from.

   What this file is responsible for:

     keeping the typing from going anywhere. A block is a piece of a live
     website: it has links in it, and buttons, and a click on one of those in
     an editor means "I want to change this text", never "take me there".

     saving without being asked. There is no save button on the sheet — the
     thing being edited is a word in a heading, and a word in a heading is not
     worth a trip to a button. It saves when the cursor leaves, and again a
     couple of seconds after the typing stops, so a long paragraph is not one
     unsaved lump.

     telling the page editor what happened, so the bar on the block can say
     "a guardar" and then "guardado", and so the canvas can re-measure: a
     heading that grew to two lines made the block taller.

   Deliberately not a rich text editor. The fields that hold plain text are
   `contenteditable="plaintext-only"`, so a paste out of Word arrives as the
   words and not as Word's markup.
   --------------------------------------------------------------------------- */
(function () {
  'use strict';

  var fields = Array.prototype.slice.call(document.querySelectorAll('[data-live]'));
  if (!fields.length) return;

  var token   = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var section = sectionId();
  var parentApi = api();

  /** This block's id, off the address this frame was loaded from. */
  function sectionId() {
    var m = window.location.pathname.match(/\/sections\/(\d+)\/preview/);
    return m ? m[1] : null;
  }

  /** The page editor around this frame, when there is one. Same origin, so it
      is simply there — no handshake, no messages that may not arrive. */
  function api() {
    try {
      return window.parent !== window ? (window.parent.canvasLive || null) : null;
    } catch (e) {
      return null;   // cross-origin: the frame is being looked at on its own
    }
  }

  function tell(what, detail) {
    if (parentApi && typeof parentApi[what] === 'function') {
      try { parentApi[what](section, detail); } catch (e) { /* never the editor's problem */ }
    }
  }

  /* ------------------------------------------------------------------ */
  /* Nothing in here navigates.                                          */

  document.addEventListener('click', function (event) {
    var link = event.target.closest('a[href], button, [type="submit"]');
    if (!link) return;
    event.preventDefault();

    // A click on a link that *is* an editable field should still put the
    // cursor in it — the text of a button is text like any other.
    var field = event.target.closest('[data-live]');
    if (field) field.focus();
  }, true);

  document.addEventListener('submit', function (event) { event.preventDefault(); }, true);

  /* ------------------------------------------------------------------ */
  /* Saving.                                                             */

  var timers  = new WeakMap();
  var saved   = new WeakMap();   // what the server last agreed to
  var pending = 0;

  fields.forEach(function (field) {
    saved.set(field, read(field));

    field.addEventListener('focus', function () {
      field.classList.add('is-editing');
      tell('focused');
    });

    field.addEventListener('blur', function () {
      field.classList.remove('is-editing');
      clearTimeout(timers.get(field));
      var changed = read(field) !== saved.get(field);
      save(field);
      // Leaving a field without having changed it saves nothing, so nothing
      // would ever say the writing had stopped and the block would stay
      // unfolded for the rest of the session.
      if (!changed) tell('left');
    });

    field.addEventListener('input', function () {
      clearTimeout(timers.get(field));
      timers.set(field, setTimeout(function () { save(field); }, 1600));
      // The block may have changed height — a heading that wrapped, a
      // paragraph that grew a line. The canvas scales each frame to a measured
      // height and has no other way to know.
      tell('resized');
    });

    field.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        event.preventDefault();
        write(field, saved.get(field));
        field.blur();
        return;
      }
      // One line means one line. Enter would put a break into a column that
      // nothing downstream expects to contain one.
      if (event.key === 'Enter' && field.dataset.liveMode === 'line') {
        event.preventDefault();
        field.blur();
      }
    });
  });

  function read(field) {
    return field.dataset.liveMode === 'html' ? field.innerHTML : field.innerText;
  }

  function write(field, value) {
    if (field.dataset.liveMode === 'html') field.innerHTML = value;
    else field.innerText = value;
  }

  function save(field) {
    var value = read(field);
    if (value === saved.get(field)) return;
    if (!section) return;

    var body = new URLSearchParams();
    body.set('field', field.dataset.live);
    body.set('value', value);

    pending++;
    tell('saving');
    field.classList.add('is-saving');

    fetch('/admin/sections/' + section + '/field', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-CSRF-Token': token,
        'Accept': 'application/json'
      },
      body: body.toString(),
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
      .then(function (res) {
        if (!res.ok) throw new Error((res.body && res.body.error) || 'Não foi possível guardar.');

        // The server may have shortened it to what the column holds. Put back
        // what was actually stored, so the sheet is not showing something the
        // site will never print.
        if (typeof res.body.value === 'string' && res.body.value !== value && document.activeElement !== field) {
          write(field, res.body.value);
        }
        saved.set(field, typeof res.body.value === 'string' ? res.body.value : value);
        field.classList.remove('is-saving');
        field.classList.add('is-saved');
        setTimeout(function () { field.classList.remove('is-saved'); }, 1200);
        done(null);
      })
      .catch(function (err) {
        field.classList.remove('is-saving');
        field.classList.add('is-unsaved');
        done(err.message || 'Não foi possível guardar.');
      });
  }

  function done(error) {
    pending = Math.max(0, pending - 1);
    if (error) { tell('failed', error); return; }
    if (pending === 0) tell('saved');
  }

  /* A page closed on an unsaved edit is an edit lost. The timer may still be
     counting, and a blur does not happen when a tab goes away. */
  window.addEventListener('pagehide', flush);
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') flush();
  });

  function flush() {
    fields.forEach(function (field) {
      clearTimeout(timers.get(field));
      var value = read(field);
      if (value === saved.get(field) || !section) return;

      var body = new URLSearchParams();
      body.set('field', field.dataset.live);
      body.set('value', value);
      body.set('_csrf', token);
      // Survives the page going away, which fetch() does not.
      if (navigator.sendBeacon) {
        navigator.sendBeacon('/admin/sections/' + section + '/field', body);
        saved.set(field, value);
      }
    });
  }

  tell('ready', fields.length);
})();
