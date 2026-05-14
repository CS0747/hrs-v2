<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const username = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)

async function handleLogin() {
  if (!username.value || !password.value) {
    auth.loginError.value = 'Please enter username and password.'
    return
  }
  loading.value = true
  const ok = await auth.login(username.value, password.value)
  loading.value = false
  if (ok) router.push('/')
}
</script>

<template>
  <div class="login-page">
    <div class="login-bg"></div>
    <div class="login-card">
      <!-- Hospital Branding -->
      <div class="login-header">
        <img src="/GEAMH LOGO.png" alt="GEAMH Logo" class="login-logo" />
        <h1>GEAMH HRIS</h1>
        <p>General Emilio Aguinaldo Memorial Hospital</p>
        <span class="system-label">Human Resource Information System</span>
      </div>

      <!-- Form -->
      <form class="login-form" @submit.prevent="handleLogin">
        <div class="form-group">
          <label>Username</label>
          <div class="input-wrapper">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="#1a6b3c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
              </svg>
            </span>
            <input
              v-model="username"
              type="text"
              placeholder="Enter username"
              autocomplete="off"
              :disabled="loading"
            />
          </div>
        </div>

        <div class="form-group">
          <label>Biometrics Number</label>
          <div class="input-wrapper">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="#1a6b3c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <rect x="5" y="11" width="14" height="10" rx="2"/>
                <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
              </svg>
            </span>
            <input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Enter biometrics number (min. 6 digits)"
              autocomplete="new-password"
              :disabled="loading"
            />
            <button type="button" class="show-pwd" @click="showPassword = !showPassword" :title="showPassword ? 'Hide password' : 'Show password'">
              <!-- Eye open -->
              <svg v-if="!showPassword" viewBox="0 0 24 24" fill="none" stroke="#1a6b3c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <path d="M1 12S5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <!-- Eye off -->
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="#1a6b3c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19C5 19 1 12 1 12a18.9 18.9 0 0 1 5.06-5.94"/>
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
        </div>

        <div v-if="auth.loginError" class="error-msg">
          ⚠️ {{ auth.loginError }}
        </div>

        <button type="submit" class="login-btn" :disabled="loading">
          <span v-if="loading" class="spinner">⚙️</span>
          <span v-else> Log in </span>
        </button>
      </form>

      <div class="login-footer">
        <span>© 2026 GEAMH — IT / HR Division</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  background: linear-gradient(135deg, #0d3d20 0%, #1a6b3c 50%, #27ae60 100%);
  overflow: hidden;
}
.login-bg {
  position: absolute;
  inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.login-card {
  background: #fff;
  border-radius: 16px;
  padding: 40px 36px;
  width: 400px;
  max-width: 95vw;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  position: relative;
  z-index: 1;
  animation: fadeDown 0.5s ease both;
}

@keyframes fadeDown {
  from {
    opacity: 0;
    transform: translateY(-32px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.login-header {
  text-align: center;
  margin-bottom: 32px;
}
.hospital-icon {
  font-size: 52px;
  margin-bottom: 10px;
}
.login-logo {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  object-fit: cover;
  margin-bottom: 10px;
  display: block;
  margin-left: auto;
  margin-right: auto;
}
.login-header h1 {
  margin: 0 0 4px;
  font-size: 24px;
  font-weight: 800;
  color: #1a6b3c;
}
.login-header p {
  margin: 0 0 6px;
  font-size: 13px;
  color: #555;
}
.system-label {
  display: inline-block;
  background: #e8f5ee;
  color: #1a6b3c;
  padding: 3px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
}
.login-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.form-group label {
  font-size: 12px;
  font-weight: 700;
  color: #444;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.input-wrapper {
  display: flex;
  align-items: center;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  overflow: hidden;
  transition: border-color 0.2s;
  background: #fafafa;
}
.input-wrapper:focus-within {
  border-color: #1a6b3c;
  background: #fff;
}
.input-icon {
  padding: 0 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.input-wrapper input {
  flex: 1;
  padding: 11px 8px;
  border: none;
  outline: none;
  font-size: 14px;
  background: transparent;
}
.input-wrapper input:disabled {
  opacity: 0.6;
}
.show-pwd {
  background: none;
  border: none;
  padding: 0 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  opacity: 0.8;
  transition: opacity 0.2s;
}
.show-pwd:hover { opacity: 1; }
.error-msg {
  background: #fdecea;
  color: #c0392b;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 13px;
  border: 1px solid #f5b7b1;
}
.login-btn {
  background: linear-gradient(135deg, #1a6b3c, #27ae60);
  color: #fff;
  border: none;
  padding: 13px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: opacity 0.2s, transform 0.1s;
  margin-top: 4px;
}
.login-btn:hover:not(:disabled) {
  opacity: 0.9;
  transform: translateY(-1px);
}
.login-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.spinner {
  display: inline-block;
  animation: spin 1s linear infinite;
}
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.login-footer {
  text-align: center;
  margin-top: 24px;
  font-size: 11px;
  color: #aaa;
}
</style>
