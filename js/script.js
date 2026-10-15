const API = 'ajax.php';

function toast(msg, type = 'info') {
    const el = document.createElement('div');
    el.className = `alert alert-${type}`;
    el.textContent = msg;
    document.body.insertBefore(el, document.body.firstChild);
    setTimeout(() => el.remove(), 3000);
}

async function ajax(action, data = {}) {
    const fd = new FormData();
    fd.append('action', action);
    for (const [k, v] of Object.entries(data)) fd.append(k, v);
    const res = await fetch(API, { method: 'POST', body: fd });
    return res.json();
}

async function toggleLike(postId, btn) {
    const r = await ajax('like_post', { post_id: postId });
    if (r.ok) {
        const countEl = btn.querySelector('.like-count');
        const isLiked = r.liked;
        btn.classList.toggle('active', isLiked);
        if (countEl) {
            countEl.textContent = r.like_count;
        }
        toast(isLiked ? 'Liked' : 'Unliked');
    } else {
        toast(r.error || 'Failed', 'error');
    }
}

async function addComment(postId, form) {
    const input = form.querySelector('textarea');
    if (!input.value.trim()) return;
    const r = await ajax('add_comment', { post_id: postId, content: input.value });
    if (r.ok) {
        input.value = '';
        input.style.height = 'auto';
        loadComments(postId);
    } else {
        toast(r.error || 'Failed', 'error');
    }
}

async function loadComments(postId) {
    const r = await ajax('get_comments', { post_id: postId });
    const container = document.getElementById('comments-' + postId);
    if (!container || !r.ok) return;
    container.innerHTML = '';
    for (const c of r.comments) {
        const el = document.createElement('div');
        el.className = 'comment';
        el.innerHTML = `
            <img src="${h(c.avatar)}" class="comment-avatar" alt="">
            <div class="comment-body">
                <div class="comment-header"><strong>${h(c.first_name)} ${h(c.last_name)}</strong><span class="time">${timeAgo(c.created_at)}</span></div>
                <div class="comment-content">${h(c.content)}</div>
            </div>
        `;
        container.appendChild(el);
    }
}

function h(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function timeAgo(datetime) {
    const time = new Date(datetime).getTime();
    const diff = (Date.now() - time) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
    return new Date(datetime).toLocaleDateString();
}

async function sendFriendRequest(userId, btn) {
    const r = await ajax('friend_request', { user_id: userId });
    if (r.ok) {
        toast(r.message);
        btn.textContent = 'Requested';
        btn.disabled = true;
    } else {
        toast(r.error || 'Failed', 'error');
    }
}

async function handleRequest(requestId, accept) {
    const r = await ajax('respond_request', { request_id: requestId, accept: accept ? 1 : 0 });
    if (r.ok) {
        toast(accept ? 'Accepted' : 'Rejected');
        location.reload();
    } else {
        toast(r.error || 'Failed', 'error');
    }
}

async function markNotificationsRead() {
    await ajax('notifications_read');
    const badge = document.getElementById('notif-badge');
    if (badge) badge.style.display = 'none';
}

async function loadMessages(conversationId) {
    const r = await ajax('get_messages', { conversation_id: conversationId });
    const container = document.getElementById('chat-messages');
    if (!container || !r.ok) return;
    container.innerHTML = '';
    for (const m of r.messages) {
        const me = m.sender_id == document.getElementById('me-id').value;
        const el = document.createElement('div');
        el.className = 'message ' + (me ? 'me' : 'them');
        let content = h(m.content);
        if (m.image) content += '<br><img src="' + h(m.image) + '" alt="">';
        el.innerHTML = content + '<span class="message-time">' + timeAgo(m.created_at) + '</span>';
        container.appendChild(el);
    }
    container.scrollTop = container.scrollHeight;
}

async function sendMessage(conversationId) {
    const input = document.getElementById('msg-input');
    if (!input.value.trim()) return;
    const r = await ajax('send_message', { conversation_id: conversationId, content: input.value });
    if (r.ok) {
        input.value = '';
        loadMessages(conversationId);
    } else {
        toast(r.error || 'Failed', 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const badge = document.getElementById('notif-badge');
    if (badge) setInterval(markNotificationsRead, 30000);

    const msgForm = document.getElementById('msg-form');
    if (msgForm) {
        msgForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const conv = document.getElementById('conv-id').value;
            sendMessage(conv);
        });
        const input = document.getElementById('msg-input');
        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = input.scrollHeight + 'px';
        });
    }

    document.querySelectorAll('.auto-grow').forEach(el => {
        el.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });

    const notifLink = document.querySelector('a[href="notifications.php"]');
    if (notifLink) notifLink.addEventListener('click', markNotificationsRead);
});
