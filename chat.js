(() => {
  'use strict';
  const app = document.getElementById('chat-app');
  if (!app) return;
  const conversationId = Number(app.dataset.conversationId);
  const userId = Number(app.dataset.userId);
  const csrf = app.dataset.csrf;
  const inbox = document.getElementById('chat-inbox');
  const list = document.getElementById('chat-messages');
  const history = document.getElementById('chat-history');
  const empty = document.getElementById('chat-empty');
  const older = document.getElementById('chat-older');
  const composer = document.getElementById('chat-composer');
  const status = document.getElementById('chat-status');
  const body = document.getElementById('chat-body');
  const sendButton = composer?.querySelector('button');
  const token = composer?.elements.namedItem('message_token');
  const existing = [...(list?.children || [])].map(node => Number(node.dataset.messageId));
  const rendered = new Set(existing);
  // Only fetched messages advance the cursor: a send response can arrive ahead
  // of unseen incoming messages, which must still be fetched on the next poll.
  let cursor = Math.max(0, ...existing);
  let marked = 0;
  let polling = false;
  let sending = false;
  let reading = false;
  let stopped = false;
  let sendError = '';
  let peerActive = body ? !body.disabled : true;
  let timer;

  function showStatus(message = '') { if (status) status.textContent = message; }
  function atBottom() { return history && history.scrollHeight - history.scrollTop - history.clientHeight < 80; }
  function historyVisible() {
    if (!history) return false;
    const rect = history.getBoundingClientRect();
    return rect.bottom > 0 && rect.bottom <= window.innerHeight + 1;
  }
  function toBottom() { if (history) history.scrollTop = history.scrollHeight; }
  function newToken() {
    return [...crypto.getRandomValues(new Uint8Array(16))].map(byte => byte.toString(16).padStart(2, '0')).join('');
  }
  async function request(url, fields) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 12000);
    try {
      const response = await fetch(url, {
        method: fields ? 'POST' : 'GET',
        body: fields ? new URLSearchParams({...fields, csrf}) : undefined,
        credentials: 'same-origin', cache: 'no-store', signal: controller.signal
      });
      let data;
      try { data = await response.json(); } catch { throw new Error('Unable to connect. Please refresh the page or try again.'); }
      if (!response.ok) {
        if (response.status === 401) { stopped = true; clearTimeout(timer); }
        throw new Error(data.error || 'Unable to complete this request.');
      }
      return data;
    } finally { clearTimeout(timeout); }
  }
  function renderInbox(threads) {
    const fragment = document.createDocumentFragment();
    for (const thread of threads) {
      const link = document.createElement('a');
      link.className = 'chat-inbox-item';
      link.href = `messages.php?conversation_id=${Number(thread.id)}`;
      if (Number(thread.id) === conversationId) { link.classList.add('selected'); link.setAttribute('aria-current', 'page'); }
      const name = document.createElement('span');
      name.className = 'chat-contact-name'; name.textContent = thread.peer_name;
      const preview = document.createElement('span');
      preview.className = 'chat-preview'; preview.textContent = thread.last_body ?? 'Start your conversation';
      link.append(name, preview);
      if (Number(thread.unread_count) > 0) {
        const badge = document.createElement('span');
        badge.className = 'chat-unread'; badge.textContent = thread.unread_count;
        badge.setAttribute('aria-label', `${thread.unread_count} unread messages`);
        link.append(badge);
      }
      fragment.append(link);
    }
    if (!threads.length) {
      const note = document.createElement('p');
      note.className = 'muted'; note.textContent = 'No conversations yet. Choose someone below to get started.';
      fragment.append(note);
    }
    inbox.replaceChildren(fragment);
  }
  function addMessages(messages) {
    for (const message of messages) {
      const id = Number(message.id);
      if (rendered.has(id)) continue;
      const mine = Number(message.sender_id) === userId;
      const item = document.createElement('li');
      item.className = `chat-message${mine ? ' mine' : ''}`; item.dataset.messageId = id;
      const bubble = document.createElement('div'); bubble.className = 'chat-bubble';
      const text = document.createElement('p'); text.textContent = message.body;
      const meta = document.createElement('div'); meta.className = 'chat-message-meta';
      const time = document.createElement('time'); time.textContent = message.created_at;
      meta.append(time);
      if (mine) {
        const receipt = document.createElement('span'); receipt.className = 'chat-receipt';
        receipt.textContent = message.read_at ? 'Seen' : 'Sent'; meta.append(receipt);
      }
      bubble.append(text, meta); item.append(bubble);
      const next = [...list.children].find(node => Number(node.dataset.messageId) > id);
      list.insertBefore(item, next || null); rendered.add(id);
    }
    if (messages.length && empty) empty.hidden = true;
  }
  function updateReceipts(upTo) {
    for (const item of list?.querySelectorAll('.mine') || []) {
      if (Number(item.dataset.messageId) <= Number(upTo)) item.querySelector('.chat-receipt').textContent = 'Seen';
    }
  }
  async function markRead() {
    if (!conversationId || reading || stopped || document.hidden || !atBottom() || !historyVisible() || cursor <= marked) return;
    reading = true;
    const upTo = cursor;
    try {
      await request('chat_api.php', {action: 'read', conversation_id: conversationId, up_to: upTo});
      marked = upTo;
    } catch (error) { showStatus(error.message); }
    finally { reading = false; }
  }
  async function poll() {
    clearTimeout(timer);
    if (stopped) return;
    if (document.hidden || polling || sending) { timer = setTimeout(poll, 3000); return; }
    polling = true;
    let more = false;
    try {
      const url = conversationId
        ? `chat_api.php?action=messages&conversation_id=${conversationId}&after_id=${cursor}&inbox=1`
        : 'chat_api.php?action=inbox';
      const data = await request(url);
      renderInbox(data.conversations);
      if (conversationId) {
        const pin = atBottom();
        addMessages(data.messages);
        for (const message of data.messages) cursor = Math.max(cursor, Number(message.id));
        updateReceipts(data.peer_read_up_to);
        peerActive = data.peer_active;
        body.disabled = !peerActive || sending;
        sendButton.disabled = !peerActive || sending;
        if (pin) toBottom();
        more = data.has_more;
        showStatus(sending ? 'Sending…' : sendError || (peerActive ? '' : 'This account is disabled. You can still read your messages.'));
        await markRead();
      }
    } catch (error) { showStatus(error.name === 'AbortError' ? 'Connection timed out. Reconnecting…' : error.message); }
    finally {
      polling = false;
      if (!stopped) timer = setTimeout(poll, more ? 0 : 3000);
    }
  }
  composer?.addEventListener('submit', async event => {
    event.preventDefault();
    if (sending || !peerActive || stopped) return;
    const message = body.value.trim();
    if (!message) { body.focus(); return; }
    sending = true; body.disabled = true; sendButton.disabled = true;
    sendError = ''; showStatus('Sending…');
    try {
      const data = await request('chat_api.php', {action: 'send', conversation_id: conversationId, body: message, message_token: token.value});
      addMessages([data.message]); toBottom();
      body.value = ''; token.value = newToken();
      showStatus();
    } catch (error) {
      sendError = error.name === 'AbortError' ? 'Send timed out. Try again; your message will not be duplicated.' : error.message;
      showStatus(sendError);
    }
    finally {
      sending = false; body.disabled = !peerActive; sendButton.disabled = !peerActive;
      if (peerActive) body.focus();
    }
  });
  // Editing a failed draft starts a new message; retrying the same draft keeps
  // its token so a lost acknowledgement cannot create a duplicate.
  body?.addEventListener('input', () => {
    if (!sending) { token.value = newToken(); sendError = ''; showStatus(); }
  });
  body?.addEventListener('keydown', event => {
    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
      event.preventDefault(); composer.requestSubmit();
    }
  });
  older?.addEventListener('click', async () => {
    const first = list.firstElementChild;
    if (!first) return;
    older.disabled = true;
    try {
      const data = await request(`chat_api.php?action=messages&conversation_id=${conversationId}&before_id=${first.dataset.messageId}`);
      const previousHeight = history.scrollHeight;
      const previousTop = history.scrollTop;
      addMessages(data.messages); updateReceipts(data.peer_read_up_to);
      older.hidden = !data.has_more;
      history.scrollTop = previousTop + history.scrollHeight - previousHeight;
    } catch (error) { showStatus(error.message); }
    finally { older.disabled = false; }
  });
  history?.addEventListener('scroll', markRead, {passive: true});
  window.addEventListener('scroll', markRead, {passive: true});
  document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
  toBottom();
  poll();
})();
