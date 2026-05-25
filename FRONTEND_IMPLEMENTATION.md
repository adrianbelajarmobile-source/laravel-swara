# Community Chat - Frontend Implementation Guide

Panduan lengkap untuk implementasi chat di frontend menggunakan Laravel Echo dan Pusher JS.

---

## Prerequisites

- Bootstrap 5 atau Tailwind CSS (untuk styling)
- Vue.js, React, atau vanilla JavaScript
- Node.js & npm (untuk package management)

---

## Installation

### 1. Install Required Packages

```bash
npm install laravel-echo pusher-js
```

### 2. Configure Laravel Echo

Buat file `resources/js/echo.js` atau update `resources/js/bootstrap.js`:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_CLUSTER}.pusher.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    encrypted: true,
    disableStats: true,
    auth: {
        headers: {
            Authorization: `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        },
    },
});
```

### 3. Update `.env.example`

```env
VITE_PUSHER_APP_KEY=laravel-websockets-key
VITE_PUSHER_HOST=localhost
VITE_PUSHER_PORT=6001
VITE_PUSHER_SCHEME=http
VITE_PUSHER_CLUSTER=mt1
```

---

## Component Example

### Vue 3 Component

```vue
<template>
  <div class="chat-container">
    <div class="chat-header">
      <h2>{{ community.name }}</h2>
      <span class="member-count">{{ members.length }} members</span>
    </div>

    <div class="messages-list" ref="messagesList">
      <div
        v-for="message in messages"
        :key="message.id"
        :class="['message-item', { 'own-message': message.user.id === currentUser.id }]"
      >
        <div class="message-avatar">
          <img
            :src="message.user.profile?.photo_profile || '/default-avatar.jpg'"
            :alt="message.user.email"
          />
        </div>
        <div class="message-content">
          <div class="message-header">
            <span class="message-author">{{ message.user.email }}</span>
            <span class="message-time">{{ formatTime(message.created_at) }}</span>
          </div>
          <p class="message-text">{{ message.message }}</p>
        </div>
      </div>
    </div>

    <div class="chat-input-area">
      <form @submit.prevent="sendMessage">
        <div class="input-group">
          <input
            v-model="messageText"
            type="text"
            class="form-control"
            placeholder="Tulis pesan..."
            @keydown.enter="sendMessage"
            :disabled="sending"
          />
          <button
            type="submit"
            class="btn btn-primary"
            :disabled="!messageText.trim() || sending"
          >
            {{ sending ? 'Mengirim...' : 'Kirim' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import axios from 'axios';

// Props
const props = defineProps({
  communityId: {
    type: Number,
    required: true,
  },
  currentUser: {
    type: Object,
    required: true,
  },
});

// State
const community = ref({});
const messages = ref([]);
const messageText = ref('');
const sending = ref(false);
const messagesList = ref(null);
const currentPage = ref(1);
const isLoadingMore = ref(false);
const channel = ref(null);

// Computed
const members = computed(() => community.value.members || []);

// Methods
const loadMessages = async (page = 1) => {
  try {
    isLoadingMore.value = page > 1;
    const response = await axios.get(
      `/api/communities/${props.communityId}/messages`,
      {
        params: { page },
        headers: {
          Authorization: `Bearer ${localStorage.getItem('api_token')}`,
        },
      }
    );

    if (response.data.success) {
      if (page === 1) {
        messages.value = response.data.data;
      } else {
        messages.value.unshift(...response.data.data);
      }
      currentPage.value = page;
    }
  } catch (error) {
    console.error('Error loading messages:', error);
  } finally {
    isLoadingMore.value = false;
  }
};

const sendMessage = async () => {
  if (!messageText.value.trim()) return;

  try {
    sending.value = true;
    const response = await axios.post(
      `/api/communities/${props.communityId}/messages`,
      {
        message: messageText.value,
      },
      {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('api_token')}`,
        },
      }
    );

    if (response.data.success) {
      // Message akan diterima via broadcasting
      messageText.value = '';
      // Scroll ke bawah
      await nextTick();
      scrollToBottom();
    }
  } catch (error) {
    console.error('Error sending message:', error);
    if (error.response?.status === 422) {
      alert('Pesan terlalu panjang atau format tidak valid');
    }
  } finally {
    sending.value = false;
  }
};

const subscribeToChannel = () => {
  // Subscribe ke private channel
  channel.value = window.Echo.private(`community.${props.communityId}`)
    .listen('CommunityMessageSent', (event) => {
      // Tambahkan pesan baru
      messages.value.push(event);
      nextTick(() => scrollToBottom());
    })
    .error((error) => {
      console.error('Broadcasting error:', error);
    });
};

const unsubscribeFromChannel = () => {
  if (channel.value) {
    window.Echo.leave(`community.${props.communityId}`);
    channel.value = null;
  }
};

const scrollToBottom = () => {
  if (messagesList.value) {
    messagesList.value.scrollTop = messagesList.value.scrollHeight;
  }
};

const formatTime = (timestamp) => {
  const date = new Date(timestamp);
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  return `${hours}:${minutes}`;
};

// Load more messages (infinite scroll)
const handleScroll = () => {
  if (!messagesList.value) return;

  if (messagesList.value.scrollTop === 0 && !isLoadingMore.value) {
    const nextPage = currentPage.value + 1;
    loadMessages(nextPage);
  }
};

// Lifecycle
onMounted(async () => {
  await loadMessages();
  subscribeToChannel();
  if (messagesList.value) {
    messagesList.value.addEventListener('scroll', handleScroll);
    scrollToBottom();
  }
});

onBeforeUnmount(() => {
  unsubscribeFromChannel();
  if (messagesList.value) {
    messagesList.value.removeEventListener('scroll', handleScroll);
  }
});
</script>

<style scoped>
.chat-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background-color: #f5f5f5;
}

.chat-header {
  padding: 1rem;
  background-color: white;
  border-bottom: 1px solid #ddd;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.chat-header h2 {
  margin: 0;
  font-size: 1.5rem;
}

.member-count {
  font-size: 0.875rem;
  color: #666;
}

.messages-list {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.message-item {
  display: flex;
  gap: 0.75rem;
  animation: slideIn 0.3s ease-in;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.message-item.own-message {
  flex-direction: row-reverse;
}

.message-avatar img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.message-content {
  flex: 1;
  max-width: 70%;
}

.message-item.own-message .message-content {
  align-self: flex-end;
}

.message-header {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.message-author {
  font-weight: 600;
  color: #333;
}

.message-time {
  color: #999;
}

.message-text {
  margin: 0;
  padding: 0.75rem;
  background-color: white;
  border-radius: 0.5rem;
  word-wrap: break-word;
}

.message-item.own-message .message-text {
  background-color: #007bff;
  color: white;
}

.chat-input-area {
  padding: 1rem;
  background-color: white;
  border-top: 1px solid #ddd;
}

.input-group {
  display: flex;
  gap: 0.5rem;
}

.form-control {
  flex: 1;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 0.5rem;
  font-size: 1rem;
}

.form-control:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-primary {
  background-color: #007bff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #0056b3;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
```

---

## React Component Example

```jsx
import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';

const ChatComponent = ({ communityId, currentUser }) => {
  const [messages, setMessages] = useState([]);
  const [messageText, setMessageText] = useState('');
  const [sending, setSending] = useState(false);
  const [loading, setLoading] = useState(true);
  const messagesEndRef = useRef(null);
  const messagesListRef = useRef(null);

  // Load messages
  useEffect(() => {
    loadMessages();
  }, [communityId]);

  // Subscribe to channel
  useEffect(() => {
    const channel = window.Echo.private(`community.${communityId}`)
      .listen('CommunityMessageSent', (event) => {
        setMessages((prev) => [...prev, event]);
        scrollToBottom();
      });

    return () => {
      window.Echo.leave(`community.${communityId}`);
    };
  }, [communityId]);

  const loadMessages = async () => {
    try {
      setLoading(true);
      const response = await axios.get(
        `/api/communities/${communityId}/messages`,
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('api_token')}`,
          },
        }
      );

      if (response.data.success) {
        setMessages(response.data.data);
      }
    } catch (error) {
      console.error('Error loading messages:', error);
    } finally {
      setLoading(false);
    }
  };

  const sendMessage = async (e) => {
    e.preventDefault();

    if (!messageText.trim()) return;

    try {
      setSending(true);
      const response = await axios.post(
        `/api/communities/${communityId}/messages`,
        { message: messageText },
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('api_token')}`,
          },
        }
      );

      if (response.data.success) {
        setMessageText('');
      }
    } catch (error) {
      console.error('Error sending message:', error);
    } finally {
      setSending(false);
    }
  };

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  if (loading) {
    return <div className="chat-loading">Loading messages...</div>;
  }

  return (
    <div className="chat-container">
      <div className="messages-list" ref={messagesListRef}>
        {messages.map((message) => (
          <div
            key={message.id}
            className={`message-item ${
              message.user.id === currentUser.id ? 'own-message' : ''
            }`}
          >
            <img
              src={message.user.profile?.photo_profile || '/default-avatar.jpg'}
              alt={message.user.email}
              className="message-avatar"
            />
            <div className="message-content">
              <div className="message-header">
                <span className="message-author">{message.user.email}</span>
                <span className="message-time">
                  {new Date(message.created_at).toLocaleTimeString()}
                </span>
              </div>
              <p className="message-text">{message.message}</p>
            </div>
          </div>
        ))}
        <div ref={messagesEndRef} />
      </div>

      <form className="message-form" onSubmit={sendMessage}>
        <input
          type="text"
          value={messageText}
          onChange={(e) => setMessageText(e.target.value)}
          placeholder="Type your message..."
          disabled={sending}
        />
        <button type="submit" disabled={!messageText.trim() || sending}>
          {sending ? 'Sending...' : 'Send'}
        </button>
      </form>
    </div>
  );
};

export default ChatComponent;
```

---

## Vanilla JavaScript Example

```javascript
class ChatComponent {
  constructor(communityId, currentUser) {
    this.communityId = communityId;
    this.currentUser = currentUser;
    this.messages = [];
    this.currentPage = 1;
    this.channel = null;

    this.init();
  }

  async init() {
    this.setupDOM();
    await this.loadMessages();
    this.subscribeToChannel();
    this.attachEventListeners();
  }

  setupDOM() {
    const html = `
      <div class="chat-container">
        <div class="chat-header">
          <h2>Community Chat</h2>
        </div>
        <div class="messages-list" id="messagesList"></div>
        <form class="message-form" id="messageForm">
          <input
            type="text"
            id="messageInput"
            placeholder="Type your message..."
            required
          />
          <button type="submit">Send</button>
        </form>
      </div>
    `;

    document.body.innerHTML = html;
  }

  async loadMessages(page = 1) {
    try {
      const response = await fetch(
        `/api/communities/${this.communityId}/messages?page=${page}`,
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('api_token')}`,
          },
        }
      );

      const data = await response.json();

      if (data.success) {
        this.messages = data.data;
        this.renderMessages();
      }
    } catch (error) {
      console.error('Error loading messages:', error);
    }
  }

  async sendMessage(event) {
    event.preventDefault();

    const input = document.getElementById('messageInput');
    const message = input.value.trim();

    if (!message) return;

    try {
      const response = await fetch(
        `/api/communities/${this.communityId}/messages`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ message }),
        }
      );

      const data = await response.json();

      if (data.success) {
        input.value = '';
      }
    } catch (error) {
      console.error('Error sending message:', error);
    }
  }

  subscribeToChannel() {
    this.channel = window.Echo.private(`community.${this.communityId}`)
      .listen('CommunityMessageSent', (event) => {
        this.messages.push(event);
        this.renderMessages();
        this.scrollToBottom();
      });
  }

  renderMessages() {
    const list = document.getElementById('messagesList');
    list.innerHTML = '';

    this.messages.forEach((msg) => {
      const isOwnMessage = msg.user.id === this.currentUser.id;
      const messageEl = document.createElement('div');
      messageEl.className = `message-item ${isOwnMessage ? 'own-message' : ''}`;
      messageEl.innerHTML = `
        <img
          src="${msg.user.profile?.photo_profile || '/default-avatar.jpg'}"
          alt="${msg.user.email}"
          class="message-avatar"
        />
        <div class="message-content">
          <div class="message-header">
            <span class="message-author">${msg.user.email}</span>
            <span class="message-time">${new Date(msg.created_at).toLocaleTimeString()}</span>
          </div>
          <p class="message-text">${this.escapeHtml(msg.message)}</p>
        </div>
      `;
      list.appendChild(messageEl);
    });

    this.scrollToBottom();
  }

  scrollToBottom() {
    const list = document.getElementById('messagesList');
    list.scrollTop = list.scrollHeight;
  }

  attachEventListeners() {
    const form = document.getElementById('messageForm');
    form.addEventListener('submit', (e) => this.sendMessage(e));
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
}

// Usage
document.addEventListener('DOMContentLoaded', () => {
  const currentUser = {
    id: 1,
    email: 'user@example.com',
  };

  new ChatComponent(5, currentUser);
});
```

---

## HTML Template (Blade)

```html
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            {{-- Chat Component --}}
            <div id="chat-app" data-community-id="{{ $community->id }}"></div>
        </div>
    </div>
</div>

{{-- Include Laravel Echo & Pusher --}}
<script src="{{ asset('js/bootstrap.js') }}"></script>

{{-- Import Vue Component --}}
@vite(['resources/js/app.js'])

<script>
    const currentUser = @json(auth()->user());
    const communityId = document.querySelector('#chat-app').getAttribute('data-community-id');

    // Initialize Vue app or React component
    const app = createApp(ChatComponent, {
        communityId: parseInt(communityId),
        currentUser: currentUser
    });

    app.mount('#chat-app');
</script>
@endsection
```

---

## CSS Styling (Optional)

```css
/* Chat Container */
.chat-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: linear-gradient(to bottom, #f5f5f5, #e9ecef);
}

/* Messages List */
.messages-list {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Message Item */
.message-item {
  display: flex;
  gap: 0.75rem;
  align-items: flex-end;
  animation: slideIn 0.3s ease-in;
}

.message-item.own-message {
  flex-direction: row-reverse;
}

.message-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #fff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Message Content */
.message-content {
  max-width: 70%;
}

.message-header {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.message-author {
  font-weight: 600;
  color: #333;
}

.message-time {
  color: #999;
  font-size: 0.75rem;
}

.message-text {
  margin: 0;
  padding: 0.75rem 1rem;
  background-color: white;
  border-radius: 0.5rem;
  word-wrap: break-word;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.message-item.own-message .message-text {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

/* Message Form */
.message-form {
  display: flex;
  gap: 0.5rem;
  padding: 1rem;
  background-color: white;
  border-top: 1px solid #dee2e6;
  box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.05);
}

.message-form input {
  flex: 1;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 0.5rem;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.message-form input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.message-form button {
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.message-form button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.message-form button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Animations */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .message-content {
    max-width: 85%;
  }

  .chat-container {
    height: auto;
  }
}
```

---

## Debugging Tips

### Check Connection
```javascript
window.Echo.connector.socket.on('connecting', () => {
    console.log('Connecting to WebSocket...');
});

window.Echo.connector.socket.on('connected', () => {
    console.log('Connected!');
});

window.Echo.connector.socket.on('error', (error) => {
    console.error('WebSocket error:', error);
});
```

### Monitor Events
```javascript
const channel = window.Echo.private(`community.1`);

channel.listen('CommunityMessageSent', (event) => {
    console.log('Message received:', event);
});

channel.error((error) => {
    console.error('Channel error:', error);
});
```

### API Testing
```javascript
// Test send message
fetch('/api/communities/1/messages', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer TOKEN',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ message: 'Test' })
})
.then(r => r.json())
.then(d => console.log(d));

// Test get messages
fetch('/api/communities/1/messages', {
    headers: {
        'Authorization': 'Bearer TOKEN'
    }
})
.then(r => r.json())
.then(d => console.log(d));
```

---

## Next Steps

1. Choose your frontend framework (Vue 3, React, or Vanilla JS)
2. Copy the appropriate component example
3. Install dependencies: `npm install laravel-echo pusher-js`
4. Configure Laravel Echo
5. Test the real-time chat
6. Customize styles to match your design

Happy coding! 🎉
