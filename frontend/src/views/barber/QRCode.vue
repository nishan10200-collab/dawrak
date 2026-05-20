<template>
  <div class="min-h-screen bg-gray-50" dir="rtl">
    <header class="bg-barber-700 text-white shadow-lg">
      <div class="max-w-2xl mx-auto px-4 py-3 flex items-center gap-3">
        <router-link to="/barber/dashboard" class="text-barber-200 hover:text-white">← رجوع</router-link>
        <h1 class="font-bold text-lg">رمز QR الخاص بمحلك</h1>
      </div>
    </header>

    <main class="max-w-md mx-auto px-4 py-8">
      <div v-if="loading" class="text-center py-10 text-gray-400">جاري التحميل...</div>

      <div v-else-if="qrData" class="card text-center space-y-5">
        <div>
          <h2 class="font-bold text-xl text-gray-900">{{ barberStore.barber?.shop_name }}</h2>
          <p class="text-gray-500 text-sm mt-1">ضع هذا الكود في مدخل صالونك</p>
        </div>

        <!-- عرض QR Code -->
        <div class="flex justify-center">
          <div class="bg-white border-4 border-barber-600 rounded-2xl p-4 shadow-lg">
            <img
              :src="'data:image/svg+xml;base64,' + qrData.qr_svg"
              alt="QR Code"
              class="w-64 h-64"
            />
          </div>
        </div>

        <!-- الرابط -->
        <div class="bg-gray-50 rounded-xl p-4">
          <p class="text-xs text-gray-500 mb-2">رابط صفحة المحل:</p>
          <a :href="qrData.shop_url" target="_blank" class="text-barber-600 text-sm font-medium break-all hover:underline">
            {{ qrData.shop_url }}
          </a>
        </div>

        <!-- زر نسخ الرابط -->
        <button @click="copyLink" class="w-full btn-secondary">
          {{ copied ? '✅ تم النسخ!' : '📋 نسخ الرابط' }}
        </button>

        <!-- تعليمات الطباعة -->
        <div class="bg-barber-50 rounded-xl p-4 text-right">
          <h3 class="font-semibold text-barber-800 text-sm mb-2">💡 تعليمات:</h3>
          <ul class="text-xs text-barber-700 space-y-1 list-disc list-inside">
            <li>اطبع هذه الصفحة وضعها في مدخل صالونك</li>
            <li>يمكن للزبائن مسح الكود للانضمام للطابور</li>
            <li>أو شارك الرابط مباشرة على الواتساب</li>
          </ul>
        </div>
      </div>

      <div v-else class="card text-center py-8 text-red-500">
        حدث خطأ في تحميل رمز QR
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useBarberStore } from '@/stores/barber'
import api from '@/api/axios'

const barberStore = useBarberStore()
const qrData = ref(null)
const loading = ref(true)
const copied = ref(false)

onMounted(async () => {
  try {
    const res = await api.get('/barber/qr')
    qrData.value = res.data.data
  } finally {
    loading.value = false
  }
})

async function copyLink() {
  if (qrData.value?.shop_url) {
    await navigator.clipboard.writeText(qrData.value.shop_url)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  }
}
</script>
