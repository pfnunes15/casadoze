// Admin — minimal JS
(function () {
  'use strict';

  // Cascading category selection: Category -> Subcategory -> Tab
  const cascade = document.getElementById('cat-cascade');
  if (cascade) {
    const cats = JSON.parse(cascade.dataset.cats || '[]');
    const byParent = {};
    cats.forEach((c) => { (byParent[c.parent_id || 0] = byParent[c.parent_id || 0] || []).push(c); });
    const l1 = document.getElementById('cat_l1');
    const l2 = document.getElementById('cat_l2');
    const l3 = document.getElementById('cat_l3');
    const w2 = document.getElementById('cat_l2_wrap');
    const w3 = document.getElementById('cat_l3_wrap');

    const fill = (sel, items, placeholder) => {
      sel.innerHTML = '';
      const o = document.createElement('option');
      o.value = ''; o.textContent = placeholder;
      sel.appendChild(o);
      items.forEach((c) => {
        const op = document.createElement('option');
        op.value = c.id; op.textContent = c.name;
        sel.appendChild(op);
      });
    };
    const refreshL2 = () => {
      const kids = byParent[l1.value] || [];
      if (l1.value && kids.length) { fill(l2, kids, '— escolher subcategoria —'); w2.style.display = ''; }
      else { l2.value = ''; w2.style.display = 'none'; }
    };
    const refreshL3 = () => {
      const kids = byParent[l2.value] || [];
      if (l2.value && kids.length) { fill(l3, kids, '— escolher separador —'); w3.style.display = ''; }
      else { l3.value = ''; w3.style.display = 'none'; }
    };
    l1.addEventListener('change', () => { refreshL2(); refreshL3(); });
    l2.addEventListener('change', () => { refreshL3(); });

    // Initialisation (editing): restore the stored path
    refreshL2();
    if (cascade.dataset.l2) { l2.value = cascade.dataset.l2; }
    refreshL3();
    if (cascade.dataset.l3) { l3.value = cascade.dataset.l3; }
  }

  // Auto-slug from the title
  document.querySelectorAll('[data-slug-from]').forEach((slugInput) => {
    const sourceId = slugInput.getAttribute('data-slug-from');
    const source = document.getElementById(sourceId);
    if (!source) return;
    const slugify = (s) => s
      .toString().toLowerCase()
      .normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
    let dirty = slugInput.value !== '';
    slugInput.addEventListener('input', () => { dirty = slugInput.value !== ''; });
    source.addEventListener('input', () => {
      if (!dirty) slugInput.value = slugify(source.value);
    });
  });

  // Confirmation through a MODAL (replaces the native confirm()). Deletions happen
  // without leaving the page (AJAX) — the row disappears from the table and the
  // search and filter stay as they were.
  const modal = document.getElementById('admin-modal');
  if (modal) {
    const msgEl = modal.querySelector('.admin-modal__msg');
    const okBtn = modal.querySelector('[data-modal-ok]');
    const cancelBtn = modal.querySelector('[data-modal-cancel]');
    let pendingForm = null;

    const close = () => { modal.hidden = true; pendingForm = null; document.body.classList.remove('modal-open'); };
    const open = (form) => {
      pendingForm = form;
      msgEl.textContent = form.getAttribute('data-confirm') || 'Confirma esta operação?';
      const isDelete = !!form.querySelector('input[name="_method"][value="DELETE"]');
      okBtn.textContent = isDelete ? 'Apagar' : 'Confirmar';
      okBtn.classList.toggle('btn-admin--danger', isDelete);
      okBtn.classList.toggle('btn-admin--primary', !isDelete);
      modal.hidden = false;
      document.body.classList.add('modal-open');
      okBtn.focus();
    };

    const toast = (text) => {
      const t = document.createElement('div');
      t.className = 'admin-toast';
      t.textContent = text;
      document.body.appendChild(t);
      requestAnimationFrame(() => t.classList.add('show'));
      setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 2600);
    };

    document.querySelectorAll('form[data-confirm]').forEach((f) => {
      f.addEventListener('submit', (e) => { e.preventDefault(); open(f); });
    });

    cancelBtn.addEventListener('click', close);
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.hidden) close(); });

    okBtn.addEventListener('click', () => {
      const form = pendingForm;
      if (!form) return;
      const isDelete = !!form.querySelector('input[name="_method"][value="DELETE"]');
      const row = form.closest('tr');
      if (isDelete && row) {
        // AJAX: delete and remove the row without reloading the page
        okBtn.disabled = true;
        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
          .then((r) => {
            okBtn.disabled = false;
            if (!r.ok && r.status >= 400) throw new Error('erro');
            row.remove();
            const count = document.getElementById('doc-count');
            if (count) count.textContent = document.querySelectorAll('#docs-table tbody tr').length;
            close();
            toast('Apagado.');
          })
          .catch(() => { okBtn.disabled = false; close(); toast('Não foi possível apagar.'); });
      } else {
        close();
        form.submit();
      }
    });
  }
})();

// Documents — search + filter by category (instant, in the browser)
(function () {
  'use strict';
  var search = document.getElementById('doc-search');
  var catSel = document.getElementById('doc-cat');
  var table  = document.getElementById('docs-table');
  if ((!search && !catSel) || !table) return;
  var rows  = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
  var count = document.getElementById('doc-count');
  var empty = document.getElementById('doc-empty');

  function apply() {
    var q = (search && search.value || '').toLowerCase().trim();
    var cat = (catSel && catSel.value || '').trim();
    var shown = 0;
    rows.forEach(function (r) {
      var okText = !q || (r.getAttribute('data-text') || '').indexOf(q) !== -1;
      var okCat  = !cat || (' ' + (r.getAttribute('data-cats') || '') + ' ').indexOf(' ' + cat + ' ') !== -1;
      var vis = okText && okCat;
      r.style.display = vis ? '' : 'none';
      if (vis) shown++;
    });
    if (count) count.textContent = shown;
    if (empty) empty.hidden = shown !== 0;
  }
  if (search) search.addEventListener('input', apply);
  if (catSel) catSel.addEventListener('change', apply);
})();

// Tables sortable in the browser — clicking a header sorts the table itself.
// Switch it on with <table class="admin-table" data-sortable> and
// <th data-sort="text|num">. For non-textual values (dates), put a
// data-sort-value on the <td>.
(function () {
  'use strict';
  document.querySelectorAll('table.admin-table[data-sortable]').forEach(function (table) {
    var tbody = table.querySelector('tbody');
    if (!tbody) return;
    var headers = Array.prototype.slice.call(table.querySelectorAll('thead th'));
    var current = { idx: -1, dir: 1 };

    function value(row, idx, type) {
      var cell = row.children[idx];
      if (!cell) return type === 'num' ? 0 : '';
      var raw = (cell.dataset && cell.dataset.sortValue != null && cell.dataset.sortValue !== '')
        ? cell.dataset.sortValue
        : (cell.textContent || '').trim();
      if (type === 'num') return parseFloat(String(raw).replace(/[^0-9.\-]/g, '')) || 0;
      return raw.toLowerCase();
    }

    function sortBy(idx, type, dir) {
      var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
      rows.sort(function (a, b) {
        var va = value(a, idx, type), vb = value(b, idx, type);
        if (va < vb) return -dir;
        if (va > vb) return dir;
        return 0;
      });
      rows.forEach(function (r) { tbody.appendChild(r); });
    }

    headers.forEach(function (th, idx) {
      var type = th.getAttribute('data-sort');
      if (!type) return; // a column that cannot be sorted (e.g. actions)
      th.classList.add('th-sortable');
      th.setAttribute('role', 'button');
      th.tabIndex = 0;
      function activate() {
        var dir = (current.idx === idx) ? -current.dir : 1;
        current = { idx: idx, dir: dir };
        headers.forEach(function (h) { h.classList.remove('sorted-asc', 'sorted-desc'); h.removeAttribute('aria-sort'); });
        th.classList.add(dir === 1 ? 'sorted-asc' : 'sorted-desc');
        th.setAttribute('aria-sort', dir === 1 ? 'ascending' : 'descending');
        sortBy(idx, type, dir);
      }
      th.addEventListener('click', activate);
      th.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activate(); }
      });
    });
  });
})();

// Users — search + filters (role / status), instant in the browser
(function () {
  'use strict';
  var search = document.getElementById('user-search');
  var roleSel = document.getElementById('user-role');
  var statSel = document.getElementById('user-status');
  var table  = document.getElementById('users-table');
  if (!table || (!search && !roleSel && !statSel)) return;
  var rows  = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
  var count = document.getElementById('user-count');
  var empty = document.getElementById('user-empty');

  function apply() {
    var q    = (search && search.value || '').toLowerCase().trim();
    var role = (roleSel && roleSel.value || '');
    var stat = (statSel && statSel.value || '');
    var shown = 0;
    rows.forEach(function (r) {
      var okText = !q   || (r.getAttribute('data-text') || '').indexOf(q) !== -1;
      var okRole = !role || r.getAttribute('data-role') === role;
      var okStat = stat === '' || r.getAttribute('data-active') === stat;
      var vis = okText && okRole && okStat;
      r.style.display = vis ? '' : 'none';
      if (vis) shown++;
    });
    if (count) count.textContent = shown;
    if (empty) empty.hidden = shown !== 0;
  }
  if (search) search.addEventListener('input', apply);
  if (roleSel) roleSel.addEventListener('change', apply);
  if (statSel) statSel.addEventListener('change', apply);
})();

// Generic search + filters (Pages, News, Announcements, Events…)
// Markup: <div class="admin-toolbar" data-filter-table="ID" data-filter-empty="ID_EMPTY">
//   <input class="admin-search" data-filter-search>
//   <select class="admin-filter" data-filter-key="status">…</select>  (the row needs a data-status)
//   <strong data-filter-count>N</strong>
// Each <tr> carries data-text (searchable) and a data-<key> for every filter.
(function () {
  'use strict';
  document.querySelectorAll('[data-filter-table]').forEach(function (toolbar) {
    var table = document.getElementById(toolbar.getAttribute('data-filter-table'));
    if (!table) return;
    var rows    = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
    var search  = toolbar.querySelector('[data-filter-search]');
    var selects = Array.prototype.slice.call(toolbar.querySelectorAll('[data-filter-key]'));
    var count   = toolbar.querySelector('[data-filter-count]');
    var empty   = document.getElementById(toolbar.getAttribute('data-filter-empty') || '');

    function apply() {
      var q = (search && search.value || '').toLowerCase().trim();
      var shown = 0;
      rows.forEach(function (r) {
        var ok = !q || (r.getAttribute('data-text') || '').indexOf(q) !== -1;
        if (ok) {
          for (var i = 0; i < selects.length; i++) {
            var val = selects[i].value;
            if (val !== '' && r.getAttribute('data-' + selects[i].getAttribute('data-filter-key')) !== val) { ok = false; break; }
          }
        }
        r.style.display = ok ? '' : 'none';
        if (ok) shown++;
      });
      if (count) count.textContent = shown;
      if (empty) empty.hidden = shown !== 0;
    }
    if (search) search.addEventListener('input', apply);
    selects.forEach(function (s) { s.addEventListener('change', apply); });
  });
})();

// Repeatable items inside a block editor.
//
// Adding, removing and reordering happen here, in the page, and are written to
// the database by the block's single save. Before this, each of those actions
// was its own POST and its own page load: adding a card meant a round trip that
// created an empty row, scrolled you back to the top and left you to find it.
//
// The order is kept in one hidden field rather than a number per row, so what
// the editor sees after a drag is exactly what saves — there is no second copy
// of the order to disagree with the first.
(function () {
  'use strict';

  var form = document.querySelector('[data-item-form]');
  if (!form) return;

  var list     = form.querySelector('[data-item-list]');
  var template = form.querySelector('[data-item-template]');
  var orderIn  = form.querySelector('[data-item-order]');
  var addBtn   = form.querySelector('[data-item-add]');
  var emptyMsg = form.querySelector('[data-item-empty]');
  if (!list || !template || !orderIn) return;

  var created = 0;

  // Kept in step with views/admin/sections/edit.php.
  var NEW_KEY = 'NEWITEMKEY';

  function cards() {
    return Array.prototype.slice.call(list.querySelectorAll('[data-item]'));
  }

  function live() {
    return cards().filter(function (card) { return !card.hasAttribute('data-removed'); });
  }

  // Renumber the visible cards and rewrite the order field. Removed cards keep
  // their place in the list so the server still sees their delete flag.
  function sync() {
    var position = 0;
    cards().forEach(function (card) {
      var n = card.querySelector('[data-item-number]');
      if (!n) return;
      // A removed card keeps its place in the list but not its number: two
      // rows both showing "4" is worse than one showing none.
      n.textContent = card.hasAttribute('data-removed') ? '—' : String(++position);
    });
    orderIn.value = cards().map(function (card) { return card.dataset.key; }).join(',');
    if (emptyMsg) emptyMsg.hidden = live().length > 0;
  }

  function add() {
    created += 1;
    var key  = 'new-' + created;
    // Replaces the placeholder in every attribute it appears in — name, id,
    // and the label's `for` — so each card's fields are addressable on their
    // own. See the note beside NEW_KEY in views/admin/sections/edit.php.
    var html = template.innerHTML.split(NEW_KEY).join(key);

    var holder = document.createElement('div');
    holder.innerHTML = html;
    var card = holder.querySelector('[data-item]');
    if (!card) return;
    card.dataset.key = key;

    list.appendChild(card);
    sync();

    // A cloned textarea is not an editor: TinyMCE only sees what was in the
    // document when it started, so a rich-text field in a new item has to be
    // attached by hand. See public/assets/js/editor.js.
    if (window.cmsEditor) {
      card.querySelectorAll('textarea.rich-editor').forEach(function (area) {
        window.cmsEditor.attach('#' + area.id);
      });
    }

    var first = card.querySelector('input[type="text"], textarea, select');
    if (first) first.focus();
  }

  // Marked, not deleted: an item removed by mistake comes back until the save.
  function remove(card) {
    var flag = card.querySelector('[data-item-delete]');
    if (flag) flag.value = '1';
    card.setAttribute('data-removed', '');
    card.querySelectorAll('input, textarea, select').forEach(function (el) {
      if (el !== flag) el.disabled = true;
    });
    sync();
  }

  function restore(card) {
    var flag = card.querySelector('[data-item-delete]');
    if (flag) flag.value = '';
    card.removeAttribute('data-removed');
    card.querySelectorAll('input, textarea, select').forEach(function (el) {
      el.disabled = false;
    });
    sync();
  }

  function move(card, direction) {
    var siblings = live();
    var at = siblings.indexOf(card);
    var to = direction === 'up' ? at - 1 : at + 1;
    if (to < 0 || to >= siblings.length) return;

    if (direction === 'up') list.insertBefore(card, siblings[to]);
    else list.insertBefore(siblings[to], card);
    sync();
  }

  list.addEventListener('click', function (event) {
    var card = event.target.closest('[data-item]');
    if (!card) return;

    if (event.target.closest('[data-item-remove]')) {
      event.preventDefault();
      card.hasAttribute('data-removed') ? restore(card) : remove(card);
      var button = card.querySelector('[data-item-remove]');
      if (button) button.textContent = card.hasAttribute('data-removed') ? 'Repor' : 'Remover';
      return;
    }

    var moveBtn = event.target.closest('[data-item-move]');
    if (moveBtn) {
      event.preventDefault();
      move(card, moveBtn.getAttribute('data-item-move'));
    }
  });

  if (addBtn) addBtn.addEventListener('click', function (e) { e.preventDefault(); add(); });

  // ---- Dragging -----------------------------------------------------------
  // The arrows above do the same job for anyone not using a mouse, so this is
  // an addition rather than the only way through.
  var dragging = null;

  list.addEventListener('pointerdown', function (event) {
    var handle = event.target.closest('[data-item-handle]');
    if (!handle) return;
    var card = handle.closest('[data-item]');
    if (!card || card.hasAttribute('data-removed')) return;
    card.setAttribute('draggable', 'true');
  });

  // A press that never became a drag has to give the attribute back, or the
  // row stays draggable for the rest of the session and text inside it can no
  // longer be selected.
  ['pointerup', 'pointercancel'].forEach(function (type) {
    list.addEventListener(type, function () {
      if (dragging) return;
      list.querySelectorAll('[data-item][draggable]').forEach(function (card) {
        card.removeAttribute('draggable');
      });
    });
  });

  list.addEventListener('dragstart', function (event) {
    var card = event.target.closest('[data-item]');
    if (!card) return;
    dragging = card;
    card.classList.add('is-dragging');
    event.dataTransfer.effectAllowed = 'move';
    // Firefox ignores a drag with no payload.
    event.dataTransfer.setData('text/plain', card.dataset.key);
  });

  list.addEventListener('dragover', function (event) {
    if (!dragging) return;
    event.preventDefault();
    var over = event.target.closest('[data-item]');
    if (!over || over === dragging || over.hasAttribute('data-removed')) return;

    var box = over.getBoundingClientRect();
    var after = event.clientY > box.top + box.height / 2;
    list.insertBefore(dragging, after ? over.nextSibling : over);
  });

  list.addEventListener('drop', function (event) { event.preventDefault(); });

  list.addEventListener('dragend', function () {
    if (!dragging) return;
    dragging.classList.remove('is-dragging');
    dragging.removeAttribute('draggable');
    dragging = null;
    sync();
  });

  sync();
})();

// Reordering the blocks of a page.
//
// The list is not a form — each block carries its own show/hide and delete —
// so the new order is written into one hidden field and submitted separately.
// The save strip stays hidden until something actually moves: a permanent
// "save the order" button next to a list nobody touched is noise, and noise is
// what people learn to click past.
(function () {
  'use strict';

  var list = document.querySelector('[data-block-list]');
  var save = document.querySelector('[data-block-save]');
  if (!list || !save) return;

  var field = save.querySelector('[data-block-order]');
  var original = ids().join(',');

  function blocks() {
    return Array.prototype.slice.call(list.querySelectorAll('[data-block]'));
  }

  function ids() {
    return blocks().map(function (block) { return block.dataset.id; });
  }

  function sync() {
    var now = ids();
    field.value = now.join(',');
    save.hidden = now.join(',') === original;

    // The arrows disable at the ends, and the ends have just moved.
    blocks().forEach(function (block, i, all) {
      var up = block.querySelector('[data-block-move="up"]');
      var down = block.querySelector('[data-block-move="down"]');
      if (up) up.disabled = i === 0;
      if (down) down.disabled = i === all.length - 1;
    });
  }

  list.addEventListener('click', function (event) {
    var button = event.target.closest('[data-block-move]');
    if (!button) return;
    event.preventDefault();

    var block = button.closest('[data-block]');
    var all = blocks();
    var at = all.indexOf(block);
    var to = button.getAttribute('data-block-move') === 'up' ? at - 1 : at + 1;
    if (to < 0 || to >= all.length) return;

    if (to < at) list.insertBefore(block, all[to]);
    else list.insertBefore(all[to], block);
    sync();
  });

  var dragging = null;

  list.addEventListener('pointerdown', function (event) {
    var handle = event.target.closest('[data-block-handle]');
    if (handle) handle.closest('[data-block]').setAttribute('draggable', 'true');
  });

  // See the note on the item list: a press that never became a drag has to
  // give the attribute back.
  ['pointerup', 'pointercancel'].forEach(function (type) {
    list.addEventListener(type, function () {
      if (dragging) return;
      list.querySelectorAll('[data-block][draggable]').forEach(function (block) {
        block.removeAttribute('draggable');
      });
    });
  });

  list.addEventListener('dragstart', function (event) {
    var block = event.target.closest('[data-block]');
    if (!block) return;
    dragging = block;
    block.classList.add('is-dragging');
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', block.dataset.id);
  });

  list.addEventListener('dragover', function (event) {
    if (!dragging) return;
    event.preventDefault();
    var over = event.target.closest('[data-block]');
    if (!over || over === dragging) return;
    var box = over.getBoundingClientRect();
    list.insertBefore(dragging, event.clientY > box.top + box.height / 2 ? over.nextSibling : over);
  });

  list.addEventListener('drop', function (event) { event.preventDefault(); });

  list.addEventListener('dragend', function () {
    if (!dragging) return;
    dragging.classList.remove('is-dragging');
    dragging.removeAttribute('draggable');
    dragging = null;
    sync();
  });

  sync();
})();

/* ---------------------------------------------------------------------------
   The "Definições avançadas" drawers open and close with motion.

   A <details> opens all at once: the content appears in one go and the page
   jumps below it. Here the box grows from the closed height to the open one,
   and shrinks back the other way — the same thing the browser does, but with
   the path in sight, which is what tells whoever clicked that what appeared
   came from there.

   It animates the height of the <details> itself and not of a wrapper around
   the content: the CSS counts on the box's direct children — `> *:not(summary)`,
   `> *:last-child` — and wrapping them broke those rules. The `overflow` is
   hidden only while the motion lasts, so it does not clip whatever is focused
   once the drawer has settled.
   --------------------------------------------------------------------------- */
(function () {
  'use strict';

  const drawers = document.querySelectorAll('details.admin-card--advanced');
  if (!drawers.length) return;

  // Whoever asked the system for no motion keeps the behaviour they always had:
  // the browser opens and closes in one go, and nothing here interferes.
  const stillness = window.matchMedia('(prefers-reduced-motion: reduce)');

  const DURATION = 260;
  const EASING   = 'cubic-bezier(.22, .61, .36, 1)';

  drawers.forEach((drawer) => {
    const summary = drawer.querySelector('summary');
    if (!summary) return;

    let animation = null;

    /* Open or closed is what was asked for, and not what the attribute says:
       during a close the drawer stays `open` — that is what lets the content be
       seen shrinking — and a second click halfway would be read as "close
       again" instead of "open after all". */
    let isOpen = drawer.open;

    /* The height of the closed drawer: the summary, which is the only thing
       visible, plus the box's frame. Measured on the spot and not stored up
       front, because the summary can break onto two lines when the window
       narrows. */
    const closedHeight = () => {
      const box = getComputedStyle(drawer);
      return summary.getBoundingClientRect().height
        + parseFloat(box.borderTopWidth) + parseFloat(box.borderBottomWidth)
        + parseFloat(box.paddingTop) + parseFloat(box.paddingBottom);
    };

    summary.addEventListener('click', (event) => {
      if (stillness.matches) return;

      // The browser was about to open or close; from here on this is in charge.
      event.preventDefault();

      /* The height the drawer is at right now — read before cancelling whatever
         was running, because cancelling returns it to where it started from and
         the drawer jumped. */
      const from = drawer.getBoundingClientRect().height;
      if (animation) { animation.cancel(); animation = null; }

      const isClosing = isOpen;
      isOpen = !isOpen;
      let to;

      if (isClosing) {
        // It stays open the whole way; it only closes at the end.
        to = closedHeight();
      } else {
        drawer.open = true;
        to = drawer.getBoundingClientRect().height;
      }

      drawer.style.overflow = 'hidden';

      const thisRun = drawer.animate(
        { height: [from + 'px', to + 'px'] },
        { duration: DURATION, easing: EASING }
      );
      animation = thisRun;

      /* The cancel notice does not arrive the instant you cancel: it arrives
         later, when another animation is already in charge of the drawer.
         Without this guard, the dead one would tidy away what the new one had
         just set — and the drawer was left opening with its content
         overflowing. */
      const tidyUp = () => {
        if (animation !== thisRun) return;
        drawer.style.overflow = '';
        animation = null;
      };

      thisRun.onfinish = () => {
        if (animation !== thisRun) return;
        if (isClosing) drawer.open = false;
        tidyUp();
      };

      thisRun.oncancel = tidyUp;
    });
  });

  /* ------------------------------------------------------------------ */
  /* Settings: one group at a time.                                      */
  /*                                                                     */
  /* Twenty-three fields in a single column, and changing the phone       */
  /* number meant scrolling past the logo and the languages. It is still  */
  /* one single form — what gets saved is the whole set — and what        */
  /* changes is what is in sight.                                         */
  /*                                                                     */
  /* Without this every panel stays open and the left-hand column is a    */
  /* list of jump links: the page works just the same, only longer.       */
  (function () {
    const formEl = document.querySelector('[data-settings]');
    if (!formEl) return;

    const tabs    = Array.from(formEl.querySelectorAll('[data-settings-tab]'));
    const panels = Array.from(formEl.querySelectorAll('.settings__panel'));
    if (tabs.length < 2) return;

    const show = (id, remember) => {
      const target = panels.some((p) => p.id === id) ? id : panels[0].id;
      panels.forEach((p) => { p.hidden = p.id !== target; });
      tabs.forEach((a) => a.classList.toggle('is-on', a.dataset.settingsTab === target));
      /* Remembered between saves: saving reloads the page, and always coming
         back to the first group meant finding again the one you were in. */
      if (remember) { try { sessionStorage.setItem('admin.settings.group', target); } catch (e) {} }
    };

    formEl.classList.add('is-live');

    tabs.forEach((a) => {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        show(a.dataset.settingsTab, true);
      });
    });

    /* The address's fragment wins, so a link from outside can open a group.
       After that, the last group seen. */
    let initial = (location.hash || '').replace('#', '');
    if (!initial) {
      try { initial = sessionStorage.getItem('admin.settings.group') || ''; } catch (e) {}
    }
    show(initial, false);

    /* An invalid field inside a hidden panel cannot be seen, and the browser
       refuses to save without saying why. Its panel is opened. */
    formEl.addEventListener('invalid', (e) => {
      const panel = e.target.closest('.settings__panel');
      if (panel && panel.hidden) show(panel.id, true);
    }, true);
  })();

})();
