<script setup>
import { ref, nextTick } from 'vue'

const isOpen = ref(false)
const isMinimized = ref(false)
const input = ref('')
const isLoading = ref(false)
const messagesEl = ref(null)
const inputEl = ref(null)

const GROQ_API_KEY = import.meta.env.VITE_GROQ_API_KEY

const SYSTEM_PROMPT = `You are a helpful HR assistant for General Emilio Aguinaldo Memorial Hospital (GEAMH) in Trece Martires City, Cavite, Philippines. 
You assist HR staff with:
- Employee masterlist management (KP and GEAMH employee groups)
- DTR (Daily Time Record) transmittals — Main and Thea types
- Leave management (Vacation Leave, Sick Leave, Maternity, Paternity, etc.)
- Travel Orders (T.O.)
- Schedule database (Morning, Afternoon, Night shifts)
- Verification and tracking of documents
- Signatories and audit transmittals
- Birthday celebrants and 65th birthday (retirement age) filtering
- AI document scanning
Be concise, professional, and helpful. Use Philippine government HR terminology where appropriate.`

function now() {
  return new Date().toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true })
}

const messages = ref([
  {
    role: 'assistant',
    content: "Hello! I'm the GEAMH HRIS Assistant. I can help you with employee records, DTR transmittals, leave management, schedules, and more. How can I assist you today?",
    time: now(),
  }
])

function toggle() {
  if (!isOpen.value) {
    isOpen.value = true
    isMinimized.value = false
    nextTick(() => inputEl.value?.focus())
  } else {
    isMinimized.value = !isMinimized.value
  }
}

function close() {
  isOpen.value = false
  isMinimized.value = false
}

async function send() {
  const text = input.value.trim()
  if (!text || isLoading.value) return

  messages.value.push({ role: 'user', content: text, time: now() })
  input.value = ''
  isLoading.value = true
  scrollToBottom()

  try {
    const history = messages.value
      .slice(-10)
      .map(m => ({ role: m.role, content: m.content }))

    const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${GROQ_API_KEY}`,
      },
      body: JSON.stringify({
        model: 'llama-3.3-70b-versatile',
        messages: [
          { role: 'system', content: SYSTEM_PROMPT },
          ...history,
        ],
        max_tokens: 512,
        temperature: 0.7,
      }),
    })

    if (!response.ok) {
      const err = await response.json().catch(() => ({}))
      throw new Error(err?.error?.message || `HTTP ${response.status}`)
    }

    const data = await response.json()
    const reply = data.choices?.[0]?.message?.content || 'Sorry, I could not generate a response.'
    messages.value.push({ role: 'assistant', content: reply, time: now() })
  } catch (err) {
    messages.value.push({
      role: 'assistant',
      content: `⚠️ Error: ${err.message || 'Could not connect to AI. Please try again.'}`,
      time: now(),
      error: true,
    })
  } finally {
    isLoading.value = false
    scrollToBottom()
    nextTick(() => inputEl.value?.focus())
  }
}

function scrollToBottom() {
  nextTick(() => {
    if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight
  })
}

function onKeydown(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    send()
  }
}

function clearChat() {
  messages.value = [{ role: 'assistant', content: 'Chat cleared. How can I help you?', time: now() }]
}

const quickPrompts = [
  'How do I add a new employee?',
  'What is the DTR transmittal process?',
  'How to file a leave request?',
  'What are the shift schedules?',
]
</script>

<template>
  <div class="chatbot-widget">
    <!-- Floating Button -->
    <button
      v-if="!isOpen"
      class="chat-fab"
      @click="toggle"
      title="GEAMH HR Assistant"
    >
      <svg viewBox="0 0 24 24" fill="currentColor" width="26" height="26">
        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
      </svg>
      <span class="fab-label">HR Assistant</span>
    </button>

    <!-- Chat Window -->
    <transition name="chat-slide">
      <div v-if="isOpen" class="chat-window" :class="{ minimized: isMinimized }">
        <!-- Header -->
        <div class="chat-header" @click="isMinimized && (isMinimized = false)">
          <div class="chat-header-left">
            <div class="bot-avatar">
              <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                <path d="M20 9V7c0-1.1-.9-2-2-2h-3c0-1.66-1.34-3-3-3S9 3.34 9 5H6c-1.1 0-2 .9-2 2v2c-1.66 0-3 1.34-3 3s1.34 3 3 3v4c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-4c1.66 0 3-1.34 3-3s-1.34-3-3-3zm-9 7H9v-2h2v2zm4 0h-2v-2h2v2zm1-5H8V7h8v4z"/>
              </svg>
            </div>
            <div class="chat-header-info">
              <span class="chat-title">GEAMH HR Assistant</span>
              <span class="chat-status">
                <span class="status-dot" :class="{ loading: isLoading }"></span>
                {{ isLoading ? 'Thinking...' : 'Online' }}
              </span>
            </div>
          </div>
          <div class="chat-header-actions">
            <button class="hdr-btn" @click.stop="clearChat" title="Clear chat">
              <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
            </button>
            <button class="hdr-btn" @click.stop="toggle" :title="isMinimized ? 'Expand' : 'Minimize'">
              <svg v-if="!isMinimized" viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M19 13H5v-2h14v2z"/></svg>
              <svg v-else viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            </button>
            <button class="hdr-btn close-btn" @click.stop="close" title="Close">
              <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
          </div>
        </div>

        <!-- Body (hidden when minimized) -->
        <template v-if="!isMinimized">
          <!-- Messages -->
          <div class="chat-messages" ref="messagesEl">
            <div
              v-for="(msg, i) in messages"
              :key="i"
              class="message-row"
              :class="msg.role"
            >
              <div v-if="msg.role === 'assistant'" class="msg-avatar">
                <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M20 9V7c0-1.1-.9-2-2-2h-3c0-1.66-1.34-3-3-3S9 3.34 9 5H6c-1.1 0-2 .9-2 2v2c-1.66 0-3 1.34-3 3s1.34 3 3 3v4c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-4c1.66 0 3-1.34 3-3s-1.34-3-3-3zm-9 7H9v-2h2v2zm4 0h-2v-2h2v2zm1-5H8V7h8v4z"/></svg>
              </div>
              <div class="message-bubble" :class="{ error: msg.error }">
                <div class="msg-content">{{ msg.content }}</div>
                <div class="msg-time">{{ msg.time }}</div>
              </div>
            </div>

            <!-- Typing indicator -->
            <div v-if="isLoading" class="message-row assistant">
              <div class="msg-avatar">
                <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M20 9V7c0-1.1-.9-2-2-2h-3c0-1.66-1.34-3-3-3S9 3.34 9 5H6c-1.1 0-2 .9-2 2v2c-1.66 0-3 1.34-3 3s1.34 3 3 3v4c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-4c1.66 0 3-1.34 3-3s-1.34-3-3-3zm-9 7H9v-2h2v2zm4 0h-2v-2h2v2zm1-5H8V7h8v4z"/></svg>
              </div>
              <div class="message-bubble typing">
                <span></span><span></span><span></span>
              </div>
            </div>
          </div>

          <!-- Quick prompts (only show when 1 message) -->
          <div v-if="messages.length === 1" class="quick-prompts">
            <button
              v-for="p in quickPrompts"
              :key="p"
              class="quick-btn"
              @click="input = p; send()"
            >{{ p }}</button>
          </div>

          <!-- Input -->
          <div class="chat-input-area">
            <textarea
              ref="inputEl"
              v-model="input"
              class="chat-input"
              placeholder="Ask about HR, DTR, Leave, Schedule..."
              rows="1"
              @keydown="onKeydown"
              :disabled="isLoading"
            ></textarea>
            <button
              class="send-btn"
              @click="send"
              :disabled="!input.trim() || isLoading"
              title="Send"
            >
              <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
              </svg>
            </button>
          </div>
          <div class="chat-footer">Powered by Groq · llama-3.3-70b</div>
        </template>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.chatbot-widget {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9000;
  font-family: 'Segoe UI', sans-serif;
}

/* FAB */
.chat-fab {
  display: flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(135deg, #1a6b3c, #27ae60);
  color: #fff;
  border: none;
  padding: 12px 20px 12px 14px;
  border-radius: 50px;
  cursor: pointer;
  box-shadow: 0 4px 20px rgba(26,107,60,0.4);
  font-size: 13px;
  font-weight: 700;
  transition: transform 0.2s, box-shadow 0.2s;
}
.chat-fab:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(26,107,60,0.5);
}
.fab-label { white-space: nowrap; }

/* Chat window */
.chat-window {
  width: 360px;
  max-height: 560px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 12px 48px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid #e0e0e0;
}
.chat-window.minimized {
  max-height: 56px;
}

/* Header */
.chat-header {
  background: linear-gradient(135deg, #1a6b3c, #27ae60);
  padding: 12px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  flex-shrink: 0;
}
.chat-header-left { display: flex; align-items: center; gap: 10px; }
.bot-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  background: rgba(255,255,255,0.2);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.bot-avatar svg { fill: #fff; }
.chat-header-info { display: flex; flex-direction: column; }
.chat-title { font-size: 13px; font-weight: 700; color: #fff; }
.chat-status { display: flex; align-items: center; gap: 5px; font-size: 10px; color: rgba(255,255,255,0.8); }
.status-dot {
  width: 7px; height: 7px; border-radius: 50%; background: #7dff9e;
  animation: pulse 2s infinite;
}
.status-dot.loading { background: #ffd700; animation: blink 0.8s infinite; }
@keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.5; } }
@keyframes blink { 0%,100% { opacity:1; } 50% { opacity:0.2; } }
.chat-header-actions { display: flex; align-items: center; gap: 4px; }
.hdr-btn {
  background: rgba(255,255,255,0.15); border: none; color: #fff;
  width: 24px; height: 24px; border-radius: 6px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.2s;
}
.hdr-btn:hover { background: rgba(255,255,255,0.3); }
.hdr-btn.close-btn:hover { background: rgba(192,57,43,0.7); }
.hdr-btn svg { fill: #fff; }

/* Messages */
.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: #f8fafb;
  min-height: 0;
  max-height: 360px;
}
.chat-messages::-webkit-scrollbar { width: 4px; }
.chat-messages::-webkit-scrollbar-thumb { background: #ccc; border-radius: 2px; }

.message-row {
  display: flex;
  align-items: flex-end;
  gap: 8px;
}
.message-row.user { flex-direction: row-reverse; }

.msg-avatar {
  width: 26px; height: 26px; border-radius: 50%;
  background: linear-gradient(135deg, #1a6b3c, #27ae60);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.msg-avatar svg { fill: #fff; }

.message-bubble {
  max-width: 78%;
  padding: 9px 12px;
  border-radius: 14px;
  font-size: 13px;
  line-height: 1.5;
  word-break: break-word;
}
.message-row.assistant .message-bubble {
  background: #fff;
  border: 1px solid #e8f5ee;
  border-bottom-left-radius: 4px;
  color: #333;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.message-row.user .message-bubble {
  background: linear-gradient(135deg, #1a6b3c, #27ae60);
  color: #fff;
  border-bottom-right-radius: 4px;
}
.message-bubble.error { background: #fdecea; border-color: #f5b7b1; color: #c0392b; }
.msg-time { font-size: 10px; opacity: 0.55; margin-top: 4px; text-align: right; }
.message-row.assistant .msg-time { text-align: left; }

/* Typing indicator */
.message-bubble.typing {
  display: flex; align-items: center; gap: 4px; padding: 12px 16px;
}
.message-bubble.typing span {
  width: 7px; height: 7px; border-radius: 50%; background: #1a6b3c;
  animation: bounce 1.2s infinite;
}
.message-bubble.typing span:nth-child(2) { animation-delay: 0.2s; }
.message-bubble.typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes bounce { 0%,60%,100% { transform:translateY(0); } 30% { transform:translateY(-6px); } }

/* Quick prompts */
.quick-prompts {
  padding: 8px 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  background: #f8fafb;
  border-top: 1px solid #f0f0f0;
}
.quick-btn {
  background: #e8f5ee; border: 1px solid #b7dfc8; color: #1a6b3c;
  padding: 5px 10px; border-radius: 14px; font-size: 11px; font-weight: 600;
  cursor: pointer; transition: all 0.2s; white-space: nowrap;
}
.quick-btn:hover { background: #1a6b3c; color: #fff; }

/* Input */
.chat-input-area {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  padding: 10px 12px;
  border-top: 1px solid #f0f0f0;
  background: #fff;
  flex-shrink: 0;
}
.chat-input {
  flex: 1;
  border: 1.5px solid #ddd;
  border-radius: 10px;
  padding: 8px 12px;
  font-size: 13px;
  resize: none;
  outline: none;
  font-family: inherit;
  line-height: 1.4;
  max-height: 80px;
  overflow-y: auto;
  transition: border-color 0.2s;
}
.chat-input:focus { border-color: #1a6b3c; }
.chat-input:disabled { opacity: 0.6; }
.send-btn {
  width: 36px; height: 36px; border-radius: 10px;
  background: linear-gradient(135deg, #1a6b3c, #27ae60);
  border: none; cursor: pointer; display: flex;
  align-items: center; justify-content: center;
  flex-shrink: 0; transition: opacity 0.2s, transform 0.1s;
}
.send-btn:hover:not(:disabled) { opacity: 0.9; transform: scale(1.05); }
.send-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.send-btn svg { fill: #fff; }

.chat-footer {
  text-align: center;
  font-size: 10px;
  color: #bbb;
  padding: 4px 0 6px;
  background: #fff;
  flex-shrink: 0;
}

/* Slide animation */
.chat-slide-enter-active { animation: slideUp 0.25s ease; }
.chat-slide-leave-active { animation: slideUp 0.2s ease reverse; }
@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px) scale(0.95); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
