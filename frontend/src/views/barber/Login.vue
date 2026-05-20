<template>
  <div class="min-h-screen bg-gradient-to-br from-barber-700 to-barber-900 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8">
      <!-- الشعار -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 bg-barber-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
          <span class="text-4xl">💈</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">دورك</h1>
        <p class="text-gray-500 mt-1">نظام إدارة الطابور للحلاقين</p>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-5">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="barber@example.com"
            class="input-field"
            required
            autocomplete="email"
          />
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">كلمة المرور</label>
          <input
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            class="input-field"
            required
            autocomplete="current-password"
          />
        </div>

        <div v-if="error" class="bg-red-50 border border-red-100 text-red-700 rounded-xl p-3 text-sm text-center">
          {{ error }}
        </div>

        <button type="submit" :disabled="loading" class="w-full btn-primary flex items-center justify-center gap-2">
          <span v-if="loading" class="animate-spin text-lg">⏳</span>
          <span>{{ loading ? 'جاري الدخول...' : 'دخول' }}</span>
        </button>
      </form>

      <p class="text-center text-xs text-gray-400 mt-6">
        للاشتراك في النظام تواصل مع الإدارة
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useBarberStore } from '@/stores/barber'

const router = useRouter()
const barberStore = useBarberStore()

const form = ref({ email: '', password: '' })
const loading = ref(false)
const error = ref('')

async function handleLogin() {
  loading.value = true
  error.value = ''
  try {
    await barberStore.login(form.value.email, form.value.password)
    router.push('/barber/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ في تسجيل الدخول'
  } finally {
    loading.value = false
  }
}
</script>
