import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/axios'

export const useAdminStore = defineStore('admin', () => {
  const token = ref(localStorage.getItem('admin_token') || null)
  const admin = ref(JSON.parse(localStorage.getItem('admin_data') || 'null'))

  const isLoggedIn = computed(() => !!token.value)

  async function login(email, password) {
    const res = await api.post('/admin/login', { email, password })
    token.value = res.data.data.token
    admin.value = res.data.data.admin
    localStorage.setItem('admin_token', token.value)
    localStorage.setItem('admin_data', JSON.stringify(admin.value))
    return res.data
  }

  async function logout() {
    try {
      await api.post('/admin/logout')
    } finally {
      token.value = null
      admin.value = null
      localStorage.removeItem('admin_token')
      localStorage.removeItem('admin_data')
    }
  }

  return { token, admin, isLoggedIn, login, logout }
})
