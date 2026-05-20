import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/axios'

export const useBarberStore = defineStore('barber', () => {
  const token = ref(localStorage.getItem('barber_token') || null)
  const barber = ref(JSON.parse(localStorage.getItem('barber_data') || 'null'))

  const isLoggedIn = computed(() => !!token.value)
  const isOpen = computed(() => barber.value?.is_open ?? false)

  async function login(email, password) {
    const res = await api.post('/barber/login', { email, password })
    token.value = res.data.data.token
    barber.value = res.data.data.barber
    localStorage.setItem('barber_token', token.value)
    localStorage.setItem('barber_data', JSON.stringify(barber.value))
    return res.data
  }

  async function logout() {
    try {
      await api.post('/barber/logout')
    } finally {
      token.value = null
      barber.value = null
      localStorage.removeItem('barber_token')
      localStorage.removeItem('barber_data')
    }
  }

  async function fetchMe() {
    const res = await api.get('/barber/me')
    barber.value = res.data.data
    localStorage.setItem('barber_data', JSON.stringify(barber.value))
    return barber.value
  }

  async function toggleShopStatus(isOpen) {
    const res = await api.put('/barber/status', { is_open: isOpen })
    barber.value = { ...barber.value, is_open: res.data.data.is_open }
    localStorage.setItem('barber_data', JSON.stringify(barber.value))
    return res.data
  }

  return { token, barber, isLoggedIn, isOpen, login, logout, fetchMe, toggleShopStatus }
})
