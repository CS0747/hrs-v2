import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  { path: '/login', name: 'Login', component: () => import('@/views/Login.vue'), meta: { public: true } },
  { path: '/signup', name: 'Signup', component: () => import('@/views/Signup.vue'), meta: { public: true, disabled: true } },
  { path: '/', name: 'Dashboard', component: () => import('@/views/Dashboard.vue') },
  { path: '/employees', name: 'EmployeeMasterlist', component: () => import('@/views/employees/EmployeeMasterlist.vue') },
  { path: '/employees/new', name: 'EmployeeNew', component: () => import('@/views/employees/EmployeeForm.vue') },
  { path: '/employees/:id/edit', name: 'EmployeeEdit', component: () => import('@/views/employees/EmployeeForm.vue') },
  { path: '/employees/birthdays', name: 'BirthdayCelebrants', component: () => import('@/views/employees/BirthdayCelebrants.vue') },
  { path: '/dtr', name: 'DTRTransmittal', component: () => import('@/views/dtr/DTRTransmittal.vue') },
  { path: '/leave', name: 'LeaveManagement', component: () => import('@/views/leave/LeaveManagement.vue') },
  { path: '/to', name: 'TOManagement', component: () => import('@/views/to/TOManagement.vue') },
  { path: '/verification', name: 'Verification', component: () => import('@/views/verification/Verification.vue') },
  { path: '/tracking', name: 'TrackingReceiving', component: () => import('@/views/tracking/TrackingReceiving.vue') },
  { path: '/signatories', name: 'Signatories', component: () => import('@/views/signatories/Signatories.vue') },
  { path: '/audit', name: 'AuditTransmittal', component: () => import('@/views/audit/AuditTransmittal.vue') },
  { path: '/schedule', name: 'ScheduleDatabase', component: () => import('@/views/schedule/ScheduleDatabase.vue') },
  { path: '/ai-scanning', name: 'AIScanningTools', component: () => import('@/views/ai/AIScanningTools.vue') },
  { path: '/accounts', name: 'AccountManagement', component: () => import('@/views/accounts/AccountManagement.vue'), meta: { adminOrDios: true } },
  { path: '/trainings', name: 'TrainingsManagement', component: () => import('@/views/trainings/TrainingsManagement.vue') },
  { path: '/departments', name: 'DepartmentManagement', component: () => import('@/views/departments/DepartmentManagement.vue') },
  { path: '/audit-trail', name: 'AuditHistory', component: () => import('@/views/admin/AuditHistory.vue'), meta: { adminOrDios: true } },
  { path: '/version-history', name: 'VersionHistory', component: () => import('@/views/admin/VersionHistory.vue'), meta: { adminOrDios: true } },
  { path: '/user-manual', name: 'UserManual', component: () => import('@/views/admin/UserManual.vue') },
  { path: '/dios-account', name: 'DiosAccount', component: () => import('@/views/admin/DiosAccount.vue'), meta: { adminOrDios: true } },
  { path: '/dios-control', name: 'DiosSystemControl', component: () => import('@/views/admin/DiosSystemControl.vue'), meta: { diosOnly: true } },
  { path: '/password-resets', name: 'PasswordResetRequests', component: () => import('@/views/admin/PasswordResetRequests.vue'), meta: { diosOnly: true } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, _from, next) => {
  const auth = useAuthStore()

  if (!to.meta.public && !auth.isLoggedIn) {
    return next('/login')
  }
  if (to.path === '/login' && auth.isLoggedIn) {
    return next('/')
  }
  // Signup is disabled — only DIOS can create accounts via Account Management
  if (to.path === '/signup') {
    return next(auth.isLoggedIn ? '/' : '/login')
  }

  // Section Admin: only allowed routes
  if (auth.isLoggedIn && auth.isSectionAdmin) {
    const allowed = ['/', '/schedule', '/user-manual']
    const isAllowed = allowed.includes(to.path)
    if (!isAllowed) return next('/')
  }

  // Admin or DIOS only routes — allow Super Admin, Admin, and DIOS roles
  if (to.meta.adminOrDios && auth.isLoggedIn) {
    const allowed = ['Super Admin', 'Admin', 'DIOS']
    if (!allowed.includes(auth.userRole)) {
      return next('/')
    }
  }

  // DIOS only routes
  if (to.meta.diosOnly && auth.isLoggedIn) {
    if (auth.userRole !== 'DIOS') return next('/')
  }

  next()
})

export default router
