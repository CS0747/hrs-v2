<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

// Block access if not logged in as Super Admin (only Super Admin can create accounts)
onMounted(() => {
  const auth = useAuthStore()
  if (auth.isLoggedIn && !['Super Admin', 'IT'].includes(auth.userRole)) {
    router.replace('/')
  }
})

const form = ref({
  name: '',
  username: '',
  password: '',
  confirmPassword: '',
  role: 'Admin',
})

const showPassword = ref(false)
const showConfirm = ref(false)
const loading = ref(false)
const success = ref(false)
const fieldErrors = ref({})

// SVG icons — matches system design
const svgIcons = {
  user:    `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>`,
  at:      `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10h5v-2h-5c-4.34 0-8-3.66-8-8s3.66-8 8-8 8 3.66 8 8v1.43c0 .79-.71 1.57-1.5 1.57s-1.5-.78-1.5-1.57V12c0-2.76-2.24-5-5-5s-5 2.24-5 5 2.24 5 5 5c1.38 0 2.64-.56 3.54-1.47.65.89 1.77 1.47 2.96 1.47C19.05 22 21 20.1 21 17.43V12c0-5.52-4.48-10-9-10zm0 13c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3z"/></svg>`,
  role:    `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4l5 2.18V11c0 3.5-2.33 6.79-5 7.93-2.67-1.14-5-4.43-5-7.93V7.18L12 5z"/></svg>`,
  lock:    `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>`,
  eye:     `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>`,
  eyeOff:  `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>`,
  check:   `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`,
  warn:    `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>`,
  spinner: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4V2A10 10 0 0 0 2 12h2a8 8 0 0 1 8-8z"/></svg>`,
}

const roles = ['Admin', 'Super Admin', 'IT', 'Section Admin']

function onlyLetters(e) {
  if (!/^[a-zA-ZÀ-ÿ\s\-\.'ñÑ,]$/.test(e.key) && !['Backspace','Delete','ArrowLeft','ArrowRight','Tab'].includes(e.key)) {
    e.preventDefault()
  }
}
function onlyAlphanumeric(e) {
  if (!/^[a-zA-Z0-9_]$/.test(e.key) && !['Backspace','Delete','ArrowLeft','ArrowRight','Tab'].includes(e.key)) {
    e.preventDefault()
  }
}

// Password strength
const strengthScore = computed(() => {
  const p = form.value.password
  if (!p) return 0
  let score = 0
  if (p.length >= 6) score++
  if (p.length >= 10) score++
  if (/[A-Z]/.test(p)) score++
  if (/[0-9]/.test(p)) score++
  if (/[^a-zA-Z0-9]/.test(p)) score++
  return score
})
const strengthWidth = computed(() => ['0%','20%','40%','60%','80%','100%'][strengthScore.value])
const strengthColor = computed(() => ['#ccc','#c0392b','#e67e22','#f1c40f','#27ae60','#1a6b3c'][strengthScore.value])
const strengthLabel = computed(() => ['','Very Weak','Weak','Fair','Strong','Very Strong'][strengthScore.value])

function validate() {
  fieldErrors.value = {}
  if (!form.value.name.trim()) fieldErrors.value.name = 'Full name is required.'
  if (!form.value.username.trim()) fieldErrors.value.username = 'Username is required.'
  else if (form.value.username.length < 3) fieldErrors.value.username = 'At least 3 characters.'
  if (!form.value.password) fieldErrors.value.password = 'Password is required.'
  else if (form.value.password.length < 6) fieldErrors.value.password = 'At least 6 characters.'
  if (form.value.password !== form.value.confirmPassword) {
    fieldErrors.value.confirmPassword = 'Passwords do not match.'
  }
  return Object.keys(fieldErrors.value).length === 0
}

async function handleSignup() {
  auth.signupError = ''
  if (!validate()) return
  loading.value = true
  await new Promise(r => setTimeout(r, 600))
  const ok = auth.signup({
    name: form.value.name,
    username: form.value.username,
    password: form.value.password,
    confirmPassword: form.value.confirmPassword,
    role: form.value.role,
  })
  loading.value = false
  if (ok) {
    success.value = true
    setTimeout(() => router.push('/login'), 1800)
  }
}
</script>

<template>
  <div class="signup-page">
    <div class="signup-bg"></div>
    <div class="signup-card">
      <!-- Header -->
      <div class="signup-header">
        <img src="/GEAMH LOGO.png" alt="GEAMH Logo" class="signup-logo" />
        <h1>Create Account</h1>
        <p>General Emilio Aguinaldo Memorial Hospital</p>
        <span class="system-label">HRIS — New User Registration</span>
      </div>

      <!-- Success -->
      <div v-if="success" class="success-box">
        <div class="success-svg"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg></div>
        <h3>Account Created!</h3>
        <p>Redirecting to login page...</p>
      </div>

      <!-- Form -->
      <form v-else class="signup-form" @submit.prevent="handleSignup" novalidate>

        <div class="form-group">
          <label>Full Name <span class="req">*</span></label>
          <div class="input-wrapper" :class="{ 'has-error': fieldErrors.name }">
            <span class="input-icon" v-html="svgIcons.user"></span>
            <input v-model="form.name" type="text" placeholder="Last Name, First Name M."
              @keydown="onlyLetters" maxlength="80" :disabled="loading" />
          </div>
          <span v-if="fieldErrors.name" class="err-msg">{{ fieldErrors.name }}</span>
        </div>

        <div class="form-group">
          <label>Username <span class="req">*</span></label>
          <div class="input-wrapper" :class="{ 'has-error': fieldErrors.username }">
            <span class="input-icon" v-html="svgIcons.at"></span>
            <input v-model="form.username" type="text" placeholder="e.g. jdelacruz"
              @keydown="onlyAlphanumeric" maxlength="30" autocomplete="username" :disabled="loading" />
          </div>
          <span v-if="fieldErrors.username" class="err-msg">{{ fieldErrors.username }}</span>
          <span v-else class="hint">Letters, numbers, underscores only. Min 3 characters.</span>
        </div>

        <div class="form-group">
          <label>Role</label>
          <div class="input-wrapper">
            <span class="input-icon" v-html="svgIcons.role"></span>
            <select v-model="form.role" :disabled="loading">
              <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Biometrics Number <span class="req">*</span></label>
          <div class="input-wrapper" :class="{ 'has-error': fieldErrors.password }">
            <span class="input-icon" v-html="svgIcons.lock"></span>
            <input v-model="form.password" :type="showPassword ? 'text' : 'password'"
              placeholder="Minimum 6 characters" maxlength="50"
              autocomplete="new-password" :disabled="loading" />
            <button type="button" class="show-pwd" @click="showPassword = !showPassword">
              <span class="show-pwd-icon" v-html="showPassword ? svgIcons.eyeOff : svgIcons.eye"></span>
            </button>
          </div>
          <span v-if="fieldErrors.password" class="err-msg">{{ fieldErrors.password }}</span>
          <div v-if="form.password" class="strength-bar">
            <div class="strength-fill" :style="{ width: strengthWidth, background: strengthColor }"></div>
          </div>
          <span v-if="form.password" class="strength-label" :style="{ color: strengthColor }">
            {{ strengthLabel }}
          </span>
        </div>

        <div class="form-group">
          <label>Confirm Biometrics Number <span class="req">*</span></label>
          <div class="input-wrapper" :class="{ 'has-error': fieldErrors.confirmPassword }">
            <span class="input-icon" v-html="svgIcons.lock"></span>
            <input v-model="form.confirmPassword" :type="showConfirm ? 'text' : 'password'"
              placeholder="Re-enter biometrics number" maxlength="50"
              autocomplete="new-password" :disabled="loading" />
            <button type="button" class="show-pwd" @click="showConfirm = !showConfirm">
              <span class="show-pwd-icon" v-html="showConfirm ? svgIcons.eyeOff : svgIcons.eye"></span>
            </button>
          </div>
          <span v-if="fieldErrors.confirmPassword" class="err-msg">{{ fieldErrors.confirmPassword }}</span>
        </div>

        <div v-if="auth.signupError" class="error-msg">
          <span class="err-icon" v-html="svgIcons.warn"></span>
          {{ auth.signupError }}
        </div>

        <button type="submit" class="signup-btn" :disabled="loading">
          <span v-if="loading" class="spin-icon" v-html="svgIcons.spinner"></span>
          <span v-else class="btn-icon-wrap" v-html="svgIcons.check"></span>
          {{ loading ? 'Creating...' : 'Create Account' }}
        </button>

        <div class="login-link">
          Already have an account?
          <router-link to="/login">Sign In →</router-link>
        </div>
      </form>

      <div class="signup-footer">© 2026 GEAMH — IT / HR Division</div>
    </div>
  </div>
</template>

<style scoped>
.signup-page {
  min-height: 100vh; display: flex; align-items: center; justify-content: center;
  position: relative;
  background: linear-gradient(135deg, #0d3d20 0%, #1a6b3c 50%, #27ae60 100%);
  overflow: hidden; padding: 24px;
}
.signup-bg {
  position: absolute; inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.signup-card {
  background: #fff; border-radius: 16px; padding: 36px 32px;
  width: 480px; max-width: 100%;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3); position: relative; z-index: 1;
}
.signup-header { text-align: center; margin-bottom: 24px; }
.hospital-icon { font-size: 44px; margin-bottom: 8px; }
.signup-logo {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  object-fit: cover;
  display: block;
  margin: 0 auto 10px;
}
.signup-header h1 { margin: 0 0 4px; font-size: 22px; font-weight: 800; color: #1a6b3c; }
.signup-header p { margin: 0 0 6px; font-size: 12px; color: #555; }
.system-label {
  display: inline-block; background: #e8f5ee; color: #1a6b3c;
  padding: 3px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;
}
.success-box { text-align: center; padding: 40px 20px; }
.success-svg { width: 56px; height: 56px; margin: 0 auto 12px; }
.success-svg svg { width: 100%; height: 100%; fill: #1a6b3c; }
.success-box h3 { color: #1a6b3c; margin: 0 0 6px; font-size: 20px; }
.success-box p { color: #888; font-size: 13px; }
.signup-form { display: flex; flex-direction: column; gap: 14px; }
.two-col { display: flex; gap: 12px; }
.two-col .form-group { flex: 1; }
.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group label { font-size: 11px; font-weight: 700; color: #444; text-transform: uppercase; letter-spacing: 0.4px; }
.input-wrapper {
  display: flex; align-items: center;
  border: 2px solid #e0e0e0; border-radius: 10px;
  overflow: hidden; transition: border-color 0.2s; background: #fafafa;
}
.input-wrapper:focus-within { border-color: #1a6b3c; background: #fff; }
.input-wrapper.has-error { border-color: #c0392b; }
.input-icon {
  padding: 0 10px; flex-shrink: 0;
  width: 36px; display: flex; align-items: center; justify-content: center; color: #aaa;
}
.input-icon :deep(svg) { width: 16px; height: 16px; fill: #aaa; }
.input-wrapper:focus-within .input-icon :deep(svg) { fill: #1a6b3c; }
.input-wrapper.has-error .input-icon :deep(svg) { fill: #c0392b; }
.input-wrapper input, .input-wrapper select {
  flex: 1; padding: 10px 8px; border: none; outline: none; font-size: 13px; background: transparent;
}
.input-wrapper input:disabled, .input-wrapper select:disabled { opacity: 0.6; }
.show-pwd { background: none; border: none; padding: 0 10px; cursor: pointer; display: flex; align-items: center; color: #aaa; }
.show-pwd:hover { color: #1a6b3c; }
.show-pwd-icon { display: inline-flex; align-items: center; }
.show-pwd-icon :deep(svg) { width: 16px; height: 16px; fill: currentColor; }
.success-svg { width: 52px; height: 52px; margin: 0 auto 12px; color: #1a6b3c; }
.success-svg svg { width: 100%; height: 100%; fill: #1a6b3c; }
.err-icon { display: inline-flex; align-items: center; margin-right: 6px; vertical-align: middle; }
.err-icon :deep(svg) { width: 15px; height: 15px; fill: #c0392b; }
.spin-icon { display: inline-flex; align-items: center; margin-right: 6px; animation: spin 1s linear infinite; }
.spin-icon :deep(svg) { width: 16px; height: 16px; fill: #fff; }
.btn-icon-wrap { display: inline-flex; align-items: center; margin-right: 6px; }
.btn-icon-wrap :deep(svg) { width: 16px; height: 16px; fill: #fff; }
.hint { font-size: 10px; color: #aaa; }
.err-msg { font-size: 11px; color: #c0392b; font-weight: 600; }
.req { color: #c0392b; }
.strength-bar { height: 4px; background: #eee; border-radius: 2px; margin-top: 4px; overflow: hidden; }
.strength-fill { height: 100%; border-radius: 2px; transition: all 0.3s; }
.strength-label { font-size: 10px; font-weight: 600; }
.error-msg {
  background: #fdecea; color: #c0392b; padding: 10px 14px;
  border-radius: 8px; font-size: 13px; border: 1px solid #f5b7b1;
}
.signup-btn {
  background: linear-gradient(135deg, #1a6b3c, #27ae60);
  color: #fff; border: none; padding: 13px; border-radius: 10px;
  font-size: 15px; font-weight: 700; cursor: pointer;
  transition: opacity 0.2s, transform 0.1s; margin-top: 4px;
  display: flex; align-items: center; justify-content: center; gap: 6px;
}
.signup-btn:hover:not(:disabled) { opacity: 0.9; transform: translateY(-1px); }
.signup-btn:disabled { opacity: 0.6; cursor: not-allowed; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.login-link { text-align: center; font-size: 13px; color: #555; }
.login-link a { color: #1a6b3c; font-weight: 700; text-decoration: none; margin-left: 4px; }
.login-link a:hover { text-decoration: underline; }
.signup-footer { text-align: center; margin-top: 20px; font-size: 11px; color: #aaa; }
</style>
