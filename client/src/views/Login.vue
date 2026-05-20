<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { API_ENDPOINTS } from '@/config/api'

const router = useRouter()
const auth = useAuthStore()

const username = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const showForgotPassword = ref(false)
const forgotUsername = ref('')
const forgotLoading = ref(false)
const forgotSuccess = ref(false)
const forgotError = ref('')

async function handleLogin() {
  if (!username.value || !password.value) {
    auth.loginError = 'Please enter username and password.'
    return
  }
  loading.value = true
  const ok = await auth.login(username.value, password.value)
  loading.value = false
  if (ok) router.push('/')
}

function openForgotPassword() {
  showForgotPassword.value = true
  forgotUsername.value = ''
  forgotSuccess.value = false
  forgotError.value = ''
}

async function handleForgotPassword() {
  forgotError.value = ''
  forgotSuccess.value = false
  
  if (!forgotUsername.value.trim()) {
    forgotError.value = 'Please enter your username.'
    return
  }
  
  forgotLoading.value = true
  try {
    const res = await fetch(`${API_ENDPOINTS.AUTH}?action=request_password_reset`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username: forgotUsername.value.trim() })
    })
    
    const data = await res.json()
    
    if (res.ok) {
      forgotSuccess.value = true
      forgotUsername.value = ''
    } else {
      forgotError.value = data.error || 'Failed to submit password reset request.'
    }
  } catch (e) {
    forgotError.value = 'Connection error. Please try again.'
  } finally {
    forgotLoading.value = false
  }
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

        <button type="button" class="forgot-link" @click="openForgotPassword">
          Forgot your password?
        </button>
      </form>

      <div class="login-footer">
        <span>© 2026 GEAMH — IT / HR Division</span>
      </div>
    </div>

    <!-- Forgot Password Modal -->
    <Transition name="modal">
      <div v-if="showForgotPassword" class="modal-overlay" @click.self="showForgotPassword = false">
        <div class="forgot-modal">
          <div class="forgot-header">
            <h3>Reset Password</h3>
            <button class="close-btn" @click="showForgotPassword = false">✕</button>
          </div>

          <div v-if="!forgotSuccess" class="forgot-body">
            <p class="forgot-desc">Enter your username to request a password reset. An administrator will review your request and reset your password.</p>
            
            <div class="form-group">
              <label>Username</label>
              <input
                v-model="forgotUsername"
                type="text"
                placeholder="Enter your username"
                @keyup.enter="handleForgotPassword"
                :disabled="forgotLoading"
              />
            </div>

            <div v-if="forgotError" class="error-msg">
              ⚠️ {{ forgotError }}
            </div>

            <div class="forgot-actions">
              <button class="btn-cancel" @click="showForgotPassword = false" :disabled="forgotLoading">Cancel</button>
              <button class="btn-submit" @click="handleForgotPassword" :disabled="forgotLoading">
                <span v-if="forgotLoading" class="spinner">⚙️</span>
                <span v-else>Submit Request</span>
              </button>
            </div>
          </div>

          <div v-else class="forgot-success">
            <div class="success-icon">✓</div>
            <h4>Request Submitted</h4>
            <p>Your password reset request has been submitted. Please contact your administrator (HR or IT) to complete the password reset process.</p>
            <button class="btn-ok" @click="showForgotPassword = false">OK</button>
          </div>
        </div>
      </div>
    </Transition>
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

/* Forgot Password Link */
.forgot-link {
  background: none;
  border: none;
  color: #1a6b3c;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  padding: 8px;
  margin-top: 4px;
  transition: opacity 0.2s;
}
.forgot-link:hover { opacity: 0.7; text-decoration: underline; }

/* Forgot Password Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.forgot-modal {
  background: #fff;
  border-radius: 16px;
  width: 420px;
  max-width: 95vw;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  overflow: hidden;
}
.forgot-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #f0f0f0;
}
.forgot-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #1a6b3c;
}
.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  color: #888;
  cursor: pointer;
  padding: 0;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: background 0.2s;
}
.close-btn:hover { background: #f0f0f0; }
.forgot-body {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.forgot-desc {
  margin: 0;
  font-size: 13px;
  color: #555;
  line-height: 1.6;
}
.forgot-body .form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.forgot-body .form-group label {
  font-size: 12px;
  font-weight: 700;
  color: #444;
}
.forgot-body .form-group input {
  padding: 10px 12px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
}
.forgot-body .form-group input:focus { border-color: #1a6b3c; }
.forgot-body .form-group input:disabled { opacity: 0.6; background: #fafafa; }
.forgot-actions {
  display: flex;
  gap: 10px;
  margin-top: 8px;
}
.btn-cancel, .btn-submit {
  flex: 1;
  padding: 11px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
  border: none;
}
.btn-cancel {
  background: #f0f0f0;
  color: #555;
}
.btn-cancel:hover:not(:disabled) { background: #e0e0e0; }
.btn-submit {
  background: linear-gradient(135deg, #1a6b3c, #27ae60);
  color: #fff;
}
.btn-submit:hover:not(:disabled) { opacity: 0.9; }
.btn-cancel:disabled, .btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.forgot-success {
  padding: 32px 24px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.success-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #e8f5ee;
  color: #1a6b3c;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  font-weight: 700;
}
.forgot-success h4 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #1a6b3c;
}
.forgot-success p {
  margin: 0;
  font-size: 13px;
  color: #555;
  line-height: 1.6;
}
.btn-ok {
  background: linear-gradient(135deg, #1a6b3c, #27ae60);
  color: #fff;
  border: none;
  padding: 11px 32px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 8px;
  transition: opacity 0.2s;
}
.btn-ok:hover { opacity: 0.9; }

/* Modal Transition */
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-active .forgot-modal, .modal-leave-active .forgot-modal { transition: transform 0.2s ease, opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .forgot-modal, .modal-leave-to .forgot-modal { transform: scale(0.95); opacity: 0; }
</style>
