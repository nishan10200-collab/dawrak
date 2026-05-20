<template>
  <div class="min-h-screen bg-gradient-to-br from-admin-700 to-admin-900 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8">
      <!-- الشعار -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 bg-admin-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
          <span class="text-4xl">✂️</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">لوحة تحكم المدير</h1>
        <p class="text-gray-500 mt-1">تطبيق دورك - إدارة الحلاقين</p>
      </div>

      <!-- نموذج الدخول -->
      <form @submit.prevent="handleLogin" class="space-y-5">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">البريد الإلكتروني</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="admin@dawrak.com"
            class="input-field"
            required
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
          />
        </div>

        <div v-if="error" class="bg-red-50 text-red-700 rounded-xl p-3 text-sm text-center">
          {{ error }}
        </div>

        <button type="submit" :disabled="loading" class="w-full btn-primary bg-admin-700 hover:bg-admin-800 flex items-center justify-center gap-2">
          <span v-if="loading" class="animate-spin">⏳</span>
          <span>{{ loading ? 'جاري الدخول...' : 'دخول' }}</span>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminStore } from '@/stores/admin'

const router = useRouter()
const adminStore = useAdminStore()

const form = ref({ email: '', password: '' })
const loading = ref(false)
const error = ref('')

async function handleLogin() {
  loading.value = true
  error.value = ''
  try {
    await adminStore.login(form.value.email, form.value.password)
    router.push('/admin/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ، يرجى المحاولة مجدداً'
  } finally {
    loading.value = false
  }
}
</script>
