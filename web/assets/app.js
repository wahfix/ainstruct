'use strict';

/* WebUI Template ainstruct.
 * Vanilla JS tanpa build step. Semua nilai dari server dirender lewat
 * textContent, bukan innerHTML, agar konten file/teks aman dari injeksi.
 * Gerakan hanya pada hover / state (dial MOTION 1). */

const $ = (id) => document.getElementById(id);
const encode = encodeURIComponent;

const state = {
  templates: null,
  active: null,
  treePath: '',
  openFile: null,
  editorDirty: false,
};

const oc = {
  status: null,
  sessions: [],
  listKey: '',
  activeId: null,
  pollTimer: null,
  outTimer: null,
  directorySet: false,
};

const els = {
  listLoading: $('list-loading'),
  listError: $('list-error'),
  customGroup: $('custom-group'),
  customList: $('custom-list'),
  builtinGroup: $('builtin-group'),
  builtinList: $('builtin-list'),
  listEmpty: $('list-empty'),
  welcome: $('welcome'),
  detail: $('detail'),
  detailName: $('detail-name'),
  detailOrigin: $('detail-origin'),
  detailDirectory: $('detail-directory'),
  detailSource: $('detail-source'),
  cloneBtn: $('clone-btn'),
  updateBtn: $('update-btn'),
  deleteBtn: $('delete-btn'),
  breadcrumb: $('breadcrumb'),
  treeLoading: $('tree-loading'),
  treeError: $('tree-error'),
  entries: $('entries'),
  editor: $('editor'),
  editorPath: $('editor-path'),
  editorMeta: $('editor-meta'),
  editorReadonly: $('editor-readonly'),
  editorArea: $('editor-area'),
  editorError: $('editor-error'),
  editorCancel: $('editor-cancel'),
  editorSave: $('editor-save'),
  toast: $('toast'),
  createDialog: $('create-dialog'),
  createForm: $('create-form'),
  createName: $('create-name'),
  createSource: $('create-source'),
  createRef: $('create-ref'),
  createForce: $('create-force'),
  createError: $('create-error'),
  cloneDialog: $('clone-dialog'),
  cloneForm: $('clone-form'),
  cloneName: $('clone-name'),
  cloneSource: $('clone-source'),
  cloneForce: $('clone-force'),
  cloneError: $('clone-error'),
  updateDialog: $('update-dialog'),
  updateForm: $('update-form'),
  updateSource: $('update-source'),
  updateRef: $('update-ref'),
  updateError: $('update-error'),
  deleteDialog: $('delete-dialog'),
  deleteForm: $('delete-form'),
  deleteName: $('delete-name'),
  deleteError: $('delete-error'),
  tplView: $('template-view'),
  ocView: $('opencode-view'),
  tabsTpl: $('templates-tab'),
  tabsOc: $('opencode-tab'),
  ocStatus: $('oc-status'),
  ocForm: $('oc-form'),
  ocDirectory: $('oc-directory'),
  ocPrompt: $('oc-prompt'),
  ocModel: $('oc-model'),
  ocAgent: $('oc-agent'),
  ocSession: $('oc-session'),
  ocAuto: $('oc-auto'),
  ocRunError: $('oc-run-error'),
  ocList: $('oc-list'),
  ocListLoading: $('oc-list-loading'),
  ocListError: $('oc-list-error'),
  ocEmpty: $('oc-empty'),
  ocOutput: $('oc-output'),
  ocOutputTitle: $('oc-output-title'),
  ocOutputMeta: $('oc-output-meta'),
  ocOutputPre: $('oc-output-pre'),
  ocStopBtn: $('oc-stop-btn'),
  ocRefreshBtn: $('oc-refresh-btn'),
  ocCloseBtn: $('oc-close-btn'),
};

function show(el) {
  el.hidden = false;
}

function hide(el) {
  el.hidden = true;
}

function clearList(el) {
  el.textContent = '';
}

function setError(el, message) {
  if (message === '') {
    hide(el);
    el.textContent = '';
    return;
  }
  el.textContent = message;
  show(el);
}

/* ---------- API ---------- */

async function api(method, path, body) {
  const opts = { method, headers: {} };

  if (body !== undefined) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  }

  let res;

  try {
    res = await fetch(path, opts);
  } catch (err) {
    throw new Error('Server tidak merespons. Pastikan `ainstruct webui` masih berjalan.');
  }

  let data = null;

  try {
    data = await res.json();
  } catch (err) {
    data = null;
  }

  if (!res.ok || !data || data.ok !== true) {
    throw new Error(data && data.error ? data.error : 'Permintaan gagal (HTTP ' + res.status + ').');
  }

  return data.data;
}

/* ---------- Toast ---------- */

let toastTimer = null;

function toast(message, isError = false) {
  clearTimeout(toastTimer);
  els.toast.textContent = message;
  els.toast.classList.toggle('is-error', isError);
  els.toast.setAttribute('role', isError ? 'alert' : 'status');
  show(els.toast);
  toastTimer = setTimeout(() => hide(els.toast), 4200);
}

function formatBytes(n) {
  if (!Number.isFinite(n) || n < 0) {
    return '';
  }

  if (n < 1024) {
    return n + ' B';
  }

  let value = n / 1024;
  let unit = 'KB';

  if (value >= 1024) {
    value /= 1024;
    unit = 'MB';
  }

  return value.toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' ' + unit;
}

/* ---------- Daftar template ---------- */

async function refreshTemplates(selectName) {
  show(els.listLoading);
  hide(els.listError);

  try {
    const data = await api('GET', '/api/templates');
    state.templates = data;
    renderList(data);

    if (selectName) {
      await loadDetail(selectName);

      return;
    }

    if (!state.active) {
      return;
    }

    if (findTemplate(data, state.active.name)) {
      if (!state.editorDirty) {
        await loadDetail(state.active.name);
      }
    } else {
      state.active = null;
      state.openFile = null;
      state.editorDirty = false;
      hide(els.editor);
      hide(els.detail);
      show(els.welcome);
      setActiveItem(null);
    }
  } catch (err) {
    show(els.listError);
    els.listError.textContent = err.message;
  } finally {
    hide(els.listLoading);
  }
}

function findTemplate(data, name) {
  return data.custom.find((t) => t.name === name)
    || data.builtin.find((t) => t.name === name)
    || null;
}

function renderList(data) {
  clearList(els.customList);
  clearList(els.builtinList);

  for (const tpl of data.custom) {
    els.customList.appendChild(templateButton(tpl));
  }

  for (const tpl of data.builtin) {
    els.builtinList.appendChild(templateButton(tpl));
  }

  els.customGroup.hidden = data.custom.length === 0;
  els.builtinGroup.hidden = data.builtin.length === 0;
  els.listEmpty.hidden = data.custom.length + data.builtin.length > 0;
}

function templateButton(tpl) {
  const li = document.createElement('li');
  const btn = document.createElement('button');

  btn.type = 'button';
  btn.className = 'template-item';
  btn.dataset.name = tpl.name;

  const name = document.createElement('span');
  name.className = 'tmpl-name';
  name.textContent = tpl.name;

  const dir = document.createElement('span');
  dir.className = 'tmpl-dir';
  dir.textContent = tpl.directory;

  btn.append(name, dir);
  li.appendChild(btn);

  return li;
}

function setActiveItem(name) {
  for (const list of [els.customList, els.builtinList]) {
    for (const btn of list.querySelectorAll('.template-item')) {
      const active = btn.dataset.name === name;
      btn.classList.toggle('active', active);

      if (active) {
        btn.setAttribute('aria-current', 'true');
      } else {
        btn.removeAttribute('aria-current');
      }
    }
  }
}

/* ---------- Detail ---------- */

async function loadDetail(name) {
  hide(els.welcome);
  show(els.detail);
  hide(els.editor);
  state.openFile = null;
  state.editorDirty = false;
  setActiveItem(name);

  try {
    const tpl = await api('GET', '/api/templates/' + encode(name));
    state.active = tpl;
    renderDetailHeader(tpl);
    state.treePath = '';
    await loadTree();
  } catch (err) {
    toast(err.message, true);
  }
}

function renderDetailHeader(tpl) {
  els.detailName.textContent = tpl.name;

  els.detailOrigin.textContent = tpl.origin === 'custom' ? 'Custom' : 'Built-in (terproteksi)';
  els.detailOrigin.className = tpl.origin === 'custom' ? 'origin-custom' : 'origin-builtin';

  els.detailDirectory.textContent = tpl.directory;

  if (tpl.source) {
    let label = 'Sumber tersimpan: ' + tpl.source.source;

    if (tpl.source.ref) {
      label += ' (ref ' + tpl.source.ref + ')';
    }

    els.detailSource.textContent = label;
    show(els.detailSource);
  } else {
    hide(els.detailSource);
  }

  els.updateBtn.hidden = !tpl.editable;
  els.deleteBtn.hidden = !tpl.editable;
  els.cloneBtn.hidden = false;
}

/* ---------- Pohon file ---------- */

async function loadTree() {
  show(els.treeLoading);
  hide(els.treeError);
  clearList(els.entries);

  try {
    const query = state.treePath === '' ? '' : '?path=' + encode(state.treePath);
    const data = await api('GET', '/api/templates/' + encode(state.active.name) + '/tree' + query);
    renderBreadcrumb(data.path);
    renderEntries(data.entries);
  } catch (err) {
    show(els.treeError);
    els.treeError.textContent = err.message;
  } finally {
    hide(els.treeLoading);
  }
}

function renderBreadcrumb(path) {
  els.breadcrumb.textContent = '';

  const root = crumbButton('root');
  const atRoot = path === '';

  if (atRoot) {
    root.classList.add('current');
    root.disabled = true;
    root.setAttribute('aria-current', 'true');
  } else {
    root.addEventListener('click', () => {
      state.treePath = '';
      loadTree();
    });
  }

  els.breadcrumb.appendChild(root);

  if (atRoot) {
    return;
  }

  let acc = '';

  for (const part of path.split('/')) {
    acc = acc === '' ? part : acc + '/' + part;

    const btn = crumbButton(part);
    const last = acc === path;

    if (last) {
      btn.classList.add('current');
      btn.disabled = true;
      btn.setAttribute('aria-current', 'true');
    } else {
      btn.addEventListener('click', () => {
        state.treePath = acc;
        loadTree();
      });
    }

    els.breadcrumb.appendChild(btn);
  }
}

function crumbButton(label) {
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'crumb';
  btn.textContent = label;

  return btn;
}

function renderEntries(entries) {
  for (const entry of entries) {
    const li = document.createElement('li');
    li.className = 'entry';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'entry-btn';

    const kind = document.createElement('span');
    kind.className = 'entry-kind';
    kind.textContent = entry.type === 'dir' ? 'direktori' : 'file';

    const name = document.createElement('span');
    name.textContent = entry.type === 'dir' ? entry.name + '/' : entry.name;

    btn.append(kind, name);

    if (entry.type === 'file') {
      const size = document.createElement('span');
      size.className = 'entry-size';
      size.textContent = formatBytes(entry.size);
      btn.appendChild(size);
    }

    if (entry.type === 'dir') {
      btn.addEventListener('click', () => {
        state.treePath = entry.path;
        loadTree();
      });
    } else {
      btn.addEventListener('click', () => openEditor(entry.path));
    }

    li.appendChild(btn);
    els.entries.appendChild(li);
  }
}

/* ---------- Editor ---------- */

async function openEditor(path) {
  if (state.editorDirty && !window.confirm('Ada perubahan yang belum disimpan. Batalkan perubahan dan buka file lain?')) {
    return;
  }

  try {
    const data = await api('GET', '/api/templates/' + encode(state.active.name) + '/file?path=' + encode(path));

    state.openFile = {
      path: data.path,
      content: data.content,
      size: data.size,
      editable: data.editable,
    };
    state.editorDirty = false;
    renderEditor();
  } catch (err) {
    toast(err.message, true);
  }
}

function renderEditor() {
  show(els.editor);

  els.editorPath.textContent = state.openFile.path;
  els.editorMeta.textContent = 'Ukuran: ' + formatBytes(state.openFile.size);

  const readOnly = !state.active.editable || !state.openFile.editable;

  els.editorArea.disabled = readOnly;
  els.editorArea.value = state.openFile.content;
  els.editorSave.disabled = readOnly;
  els.editorReadonly.hidden = !readOnly;
  setError(els.editorError, '');
}

function closeEditor() {
  hide(els.editor);
  state.openFile = null;
  state.editorDirty = false;
}

async function saveEditor() {
  const content = els.editorArea.value;

  try {
    const data = await api('PUT', '/api/templates/' + encode(state.active.name) + '/file', {
      path: state.openFile.path,
      content,
    });

    state.openFile = { ...state.openFile, content, size: data.size };
    state.editorDirty = false;
    toast('Tersimpan: ' + state.openFile.path);
    await loadTree();
  } catch (err) {
    show(els.editorError);
    els.editorError.textContent = err.message;
  }
}

/* ---------- Dialog: buat ---------- */

function openCreateDialog() {
  setError(els.createError, '');
  els.createForm.reset();
  els.createDialog.showModal();
  els.createName.focus();
}

async function onCreateSubmit(e) {
  e.preventDefault();

  const name = els.createName.value.trim();
  const source = els.createSource.value.trim();
  const force = els.createForce.checked;

  setError(els.createError, '');

  if (name === '') {
    setError(els.createError, 'Nama wajib diisi.');
    return;
  }

  try {
    const tpl = await api('POST', '/api/templates', {
      name,
      source: source === '' ? undefined : source,
      ref: els.createRef.value.trim() === '' ? undefined : els.createRef.value.trim(),
      force,
    });

    els.createDialog.close();
    await refreshTemplates(tpl.name);
    toast('Template dibuat: ' + tpl.name);
  } catch (err) {
    setError(els.createError, err.message);
  }
}

/* ---------- Dialog: clone ---------- */

function openCloneDialog() {
  setError(els.cloneError, '');
  els.cloneForm.reset();

  if (state.active) {
    els.cloneSource.value = state.active.name;
  }

  els.cloneDialog.showModal();
  els.cloneName.focus();
}

async function onCloneSubmit(e) {
  e.preventDefault();

  const name = els.cloneName.value.trim();
  const source = els.cloneSource.value.trim();
  const force = els.cloneForce.checked;

  setError(els.cloneError, '');

  if (name === '' || source === '') {
    setError(els.cloneError, 'Nama baru dan sumber wajib diisi.');
    return;
  }

  try {
    const tpl = await api('POST', '/api/templates/' + encode(name) + '/clone', {
      source,
      force,
    });

    els.cloneDialog.close();
    await refreshTemplates(tpl.name);
    toast('Template di-clone: ' + tpl.name);
  } catch (err) {
    setError(els.cloneError, err.message);
  }
}

/* ---------- Dialog: update ---------- */

function openUpdateDialog() {
  setError(els.updateError, '');
  els.updateForm.reset();
  els.updateDialog.showModal();
  els.updateSource.focus();
}

async function onUpdateSubmit(e) {
  e.preventDefault();

  if (!state.active) {
    return;
  }

  const source = els.updateSource.value.trim();

  setError(els.updateError, '');

  try {
    const tpl = await api('PUT', '/api/templates/' + encode(state.active.name), {
      from: source === '' ? undefined : source,
      ref: els.updateRef.value.trim() === '' ? undefined : els.updateRef.value.trim(),
      force: true,
    });

    els.updateDialog.close();
    await refreshTemplates(tpl.name);
    toast('Template diperbarui: ' + tpl.name);
  } catch (err) {
    setError(els.updateError, err.message);
  }
}

/* ---------- Dialog: hapus ---------- */

function openDeleteDialog() {
  setError(els.deleteError, '');
  els.deleteName.textContent = state.active ? state.active.name : '';
  els.deleteDialog.showModal();
  document.getElementById('delete-confirm').focus();
}

async function onDeleteSubmit(e) {
  e.preventDefault();

  if (!state.active) {
    return;
  }

  const name = state.active.name;

  setError(els.deleteError, '');

  try {
    await api('DELETE', '/api/templates/' + encode(name), { force: true });

    els.deleteDialog.close();
    await refreshTemplates(null);
    toast('Template dihapus: ' + name);
  } catch (err) {
    setError(els.deleteError, err.message);
  }
}

/* ---------- Tampilan: Template vs Sesi OpenCode ---------- */

function showView(name) {
  const isTemplates = name === 'templates';

  els.tplView.hidden = !isTemplates;
  els.ocView.hidden = isTemplates;
  els.tabsTpl.classList.toggle('active', isTemplates);
  els.tabsOc.classList.toggle('active', !isTemplates);

  if (isTemplates) {
    stopOcPolling();
    refreshTemplates(null);
  } else {
    startOcPolling();
    loadOcStatus();
    refreshSessions();
  }
}

/* ---------- Sesi OpenCode: status ---------- */

async function loadOcStatus() {
  try {
    const data = await api('GET', '/api/opencode/status');
    oc.status = data;
    renderOcStatus(data);

    if (!oc.directorySet) {
      els.ocDirectory.value = data.defaultDirectory;
      oc.directorySet = true;
    }

    if (!data.available) {
      setError(els.ocRunError, 'opencode tidak ditemukan. Pasang opencode (https://opencode.ai) atau atur AINSTRUCT_OPENCODE_BIN, lalu muat ulang halaman.');
    } else if (!data.windowsSupported) {
      setError(els.ocRunError, 'Menjalankan sesi opencode dari WebUI belum didukung di Windows. Gunakan terminal di direktori proyek.');
    } else {
      setError(els.ocRunError, '');
    }
  } catch (err) {
    els.ocStatus.textContent = 'Status opencode tidak dapat dibaca: ' + err.message;
  }
}

function renderOcStatus(data) {
  if (!data.available) {
    els.ocStatus.textContent = 'opencode tidak tersedia (bin: ' + data.bin + ').';
    els.ocStatus.className = 'oc-status is-unavailable';
    return;
  }

  els.ocStatus.textContent = 'opencode tersedia' + (data.version ? ' (' + data.version + ')' : '') + ' · bin: ' + data.bin;
  els.ocStatus.className = 'oc-status is-ok';
}

/* ---------- Sesi OpenCode: daftar ---------- */

async function refreshSessions() {
  show(els.ocListLoading);
  hide(els.ocListError);

  try {
    const data = await api('GET', '/api/opencode/sessions');
    oc.sessions = data.sessions;

    const key = oc.sessions.map((s) => s.id + ':' + s.status).join('|');

    if (key !== oc.listKey) {
      oc.listKey = key;
      renderSessions(oc.sessions);
    }

    if (oc.activeId && !oc.sessions.some((s) => s.id === oc.activeId)) {
      oc.activeId = null;
      oc.listKey = '';
      hide(els.ocOutput);
    }
  } catch (err) {
    show(els.ocListError);
    els.ocListError.textContent = err.message;
  } finally {
    hide(els.ocListLoading);
  }
}

function renderSessions(sessions) {
  clearList(els.ocList);
  els.ocEmpty.hidden = sessions.length > 0;

  for (const session of sessions) {
    els.ocList.appendChild(sessionItem(session));
  }
}

function sessionItem(session) {
  const li = document.createElement('li');
  li.className = 'oc-item';

  const head = document.createElement('div');
  head.className = 'oc-item-head';

  const badge = document.createElement('span');
  badge.className = 'oc-badge ' + badgeClass(session.status);
  badge.textContent = session.status;

  const id = document.createElement('span');
  id.className = 'oc-item-id';
  id.textContent = session.id;

  head.append(badge, id);

  const dir = document.createElement('p');
  dir.className = 'oc-item-meta muted';
  dir.textContent = session.directory;

  const prompt = document.createElement('p');
  prompt.className = 'oc-item-prompt';
  prompt.textContent = session.prompt;

  const actions = document.createElement('div');
  actions.className = 'oc-item-actions';

  const openBtn = makeButton('Buka', 'btn');
  openBtn.addEventListener('click', () => openOutput(session.id));
  actions.appendChild(openBtn);

  if (session.status === 'running') {
    const stopBtn = makeButton('Hentikan', 'btn danger');
    stopBtn.addEventListener('click', () => stopSession(session.id));
    actions.appendChild(stopBtn);
  }

  const deleteBtn = makeButton('Hapus', 'btn danger');
  deleteBtn.addEventListener('click', () => deleteSession(session.id));
  actions.appendChild(deleteBtn);

  li.append(head, dir, prompt, actions);

  return li;
}

function badgeClass(status) {
  if (status === 'running') return 'is-running';
  if (status === 'stopped') return 'is-stopped';
  return 'is-finished';
}

function makeButton(label, className) {
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = className;
  btn.textContent = label;

  return btn;
}

/* ---------- Sesi OpenCode: jalankan / output / stop / hapus ---------- */

async function onOcSubmit(e) {
  e.preventDefault();
  setError(els.ocRunError, '');

  const directory = els.ocDirectory.value.trim();
  const prompt = els.ocPrompt.value.trim();

  if (directory === '') {
    setError(els.ocRunError, 'Direktori proyek wajib diisi.');
    return;
  }

  if (prompt === '') {
    setError(els.ocRunError, 'Instruksi (prompt) wajib diisi.');
    return;
  }

  try {
    const session = await api('POST', '/api/opencode/sessions', {
      directory,
      prompt,
      model: els.ocModel.value.trim() === '' ? undefined : els.ocModel.value.trim(),
      agent: els.ocAgent.value.trim() === '' ? undefined : els.ocAgent.value.trim(),
      session: els.ocSession.value.trim() === '' ? undefined : els.ocSession.value.trim(),
      auto: els.ocAuto.checked,
    });

    els.ocPrompt.value = '';
    els.ocListError.hidden = true;
    toast('Sesi dimulai: ' + session.id);
    oc.listKey = '';
    await refreshSessions();
    await openOutput(session.id);
  } catch (err) {
    setError(els.ocRunError, err.message);
  }
}

async function openOutput(id) {
  oc.activeId = id;
  show(els.ocOutput);
  await refreshOutput();
}

async function refreshOutput() {
  if (!oc.activeId) {
    return;
  }

  try {
    const data = await api('GET', '/api/opencode/sessions/' + encode(oc.activeId));
    const meta = data.meta;

    els.ocOutputTitle.textContent = meta.id;
    els.ocOutputMeta.textContent = meta.directory
      + ' · mulai ' + meta.startedAt
      + ' · status ' + meta.status
      + (meta.finishedAt ? ' · selesai ' + meta.finishedAt : '');
    els.ocOutputPre.textContent = data.output.content;
    els.ocStopBtn.disabled = meta.status !== 'running';
  } catch (err) {
    els.ocOutputPre.textContent = 'Gagal memuat output: ' + err.message;
  }
}

async function stopSession(id) {
  try {
    await api('POST', '/api/opencode/sessions/' + encode(id) + '/stop');
    toast('Sesi dihentikan: ' + id);
    oc.listKey = '';
    await refreshSessions();

    if (oc.activeId === id) {
      await refreshOutput();
    }
  } catch (err) {
    toast(err.message, true);
  }
}

async function deleteSession(id) {
  if (!window.confirm('Hapus catatan sesi ' + id + ' beserta lognya? Tindakan ini permanen.')) {
    return;
  }

  try {
    await api('DELETE', '/api/opencode/sessions/' + encode(id), { force: true });
    toast('Sesi dihapus: ' + id);

    if (oc.activeId === id) {
      oc.activeId = null;
      hide(els.ocOutput);
    }

    oc.listKey = '';
    await refreshSessions();
  } catch (err) {
    toast(err.message, true);
  }
}

/* ---------- Sesi OpenCode: polling ---------- */

function startOcPolling() {
  stopOcPolling();

  oc.pollTimer = setInterval(refreshSessions, 3000);
  oc.outTimer = setInterval(() => {
    if (!oc.activeId) {
      return;
    }

    const active = oc.sessions.find((s) => s.id === oc.activeId);

    if (active && active.status === 'running') {
      refreshOutput();
    }
  }, 2000);
}

function stopOcPolling() {
  clearInterval(oc.pollTimer);
  clearInterval(oc.outTimer);
  oc.pollTimer = null;
  oc.outTimer = null;
}

/* ---------- Init ---------- */

function init() {
  $('reload-btn').addEventListener('click', () => {
    if (!els.tplView.hidden) {
      refreshTemplates(null);
    } else {
      loadOcStatus();
      refreshSessions();
    }
  });
  $('create-btn').addEventListener('click', openCreateDialog);

  els.tabsTpl.addEventListener('click', () => showView('templates'));
  els.tabsOc.addEventListener('click', () => showView('opencode'));

  els.cloneBtn.addEventListener('click', openCloneDialog);
  els.updateBtn.addEventListener('click', openUpdateDialog);
  els.deleteBtn.addEventListener('click', openDeleteDialog);

  els.customList.addEventListener('click', (e) => {
    const btn = e.target.closest('.template-item');
    if (btn) loadDetail(btn.dataset.name);
  });

  els.builtinList.addEventListener('click', (e) => {
    const btn = e.target.closest('.template-item');
    if (btn) loadDetail(btn.dataset.name);
  });

  els.editorArea.addEventListener('input', () => {
    state.editorDirty = true;
  });

  els.editorSave.addEventListener('click', saveEditor);

  els.editorCancel.addEventListener('click', () => {
    if (state.editorDirty && !window.confirm('Ada perubahan yang belum disimpan. Tutup editor?')) {
      return;
    }
    closeEditor();
  });

  els.createForm.addEventListener('submit', onCreateSubmit);
  els.cloneForm.addEventListener('submit', onCloneSubmit);
  els.updateForm.addEventListener('submit', onUpdateSubmit);
  els.deleteForm.addEventListener('submit', onDeleteSubmit);

  els.ocForm.addEventListener('submit', onOcSubmit);
  els.ocRefreshBtn.addEventListener('click', refreshOutput);
  els.ocStopBtn.addEventListener('click', () => {
    if (oc.activeId) {
      stopSession(oc.activeId);
    }
  });
  els.ocCloseBtn.addEventListener('click', () => {
    oc.activeId = null;
    hide(els.ocOutput);
  });

  for (const btn of document.querySelectorAll('[data-close]')) {
    btn.addEventListener('click', () => {
      document.getElementById(btn.dataset.close).close();
    });
  }

  refreshTemplates(null);
}

document.addEventListener('DOMContentLoaded', init);