import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/', redirect: '/onboarding' },
  { path: '/onboarding', name: 'onboarding-dashboard', component: () => import('../views/onboarding/OnboardingDashboard.vue') },
  { path: '/onboarding/checklist', name: 'onboarding-checklist', component: () => import('../views/onboarding/Checklist.vue') },
  { path: '/onboarding/suivi/:employeId?', name: 'onboarding-suivi', component: () => import('../views/onboarding/EmployeTracking.vue'), props: true },
  { path: '/onboarding/items/nouveau', name: 'onboarding-item-create', component: () => import('../views/onboarding/ItemCreate.vue') },
  { path: '/onboarding/validation', name: 'onboarding-validation', component: () => import('../views/onboarding/ItemValidate.vue') },
  { path: '/onboarding/historique', name: 'onboarding-historique', component: () => import('../views/onboarding/ActionHistory.vue') },
]

export default createRouter({
  history: createWebHistory(),
  routes,
})
