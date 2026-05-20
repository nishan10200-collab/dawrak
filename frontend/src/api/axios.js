import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// إضافة التوكن تلقائياً لكل طلب
api.interceptors.request.use((config) => {
  const adminToken = localStorage.getItem('admin_token')
  const barberToken = localStorage.getItem('barber_token')

  if (config.url?.startsWith('/admin') && adminToken) {
    config.headers.Authorization = `Bearer ${adminToken}`
  } else if (barberToken) {
    config.headers.Authorization = `Bearer ${barberToken}`
  }

  return config
})

// معالجة الأخطاء العامة
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // انتهت الجلسة
      const path = window.location.pathname
      if (path.startsWith('/admin')) {
        localStorage.removeItem('admin_token')
        window.location.href = '/admin/login'
      } else if (path.startsWith('/barber')) {
        localStorage.removeItem('barber_token')
        window.location.href = '/barber/login'
      }
    }
    return Promise.reject(error)
  }
)

export default api
