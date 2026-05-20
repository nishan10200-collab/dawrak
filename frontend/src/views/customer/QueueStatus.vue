<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4" dir="rtl">
    <div class="w-full max-w-md">
      <div v-if="loading" class="text-center py-10">
        <div class="text-5xl animate-bounce mb-4">💈</div>
        <p class="text-gray-400">جاري التحميل...</p>
      </div>

      <div v-else-if="entry" class="space-y-4">
        <!-- رأس الصفحة -->
        <div class="text-center mb-2">
          <h1 class="text-xl font-bold text-gray-900">{{ entry.shop_name }}</h1>
          <p :class="entry.is_shop_open ? 'text-barber-600' : 'text-gray-400'" class="text-sm mt-1">
            {{ entry.is_shop_open ? '🟢 المحل مفتوح' : '🔴 المحل مغلق' }}
          </p>
        </div>

        <!-- حالة الانتظار -->
        <div :class="statusCardClass" class="card text-center border-2">
          <div class="text-4xl mb-3">{{ statusEmoji }}</div>
          <h2 class="text-xl font-bold">{{ statusTitle }}</h2>
          <p class="text-sm mt-2 opacity-80">{{ statusDescription }}</p>

          <!-- رقم الدور -->
          <div v-if="entry.status === 'waiting'" class="mt-4">
            <div class="text-5xl font-bold">{{ entry.position }}</div>
            <div class="text-sm mt-1 opacity-70">رقمك في الطابور</div>
            <div class="mt-2 font-semibold">
              {{ entry.ahead_count }} شخص قبلك •
              {{ entry.estimated_wait_minutes }} دقيقة تقريباً
            </div>
          </div>

          <!-- زر التأكيد -->
          <div v-if="entry.status === 'notified' && !confirmed" class="mt-5">
            <p class="text-sm font-medium mb-3">
              ⏰ دورك قادم! أكّد حضورك قبل انتهاء الوقت
            </p>
            <button
              @click="confirmArrival"
              :disabled="confirming"
              class="bg-barber-600 hover:bg-barber-700 text-white font-bold py-4 px-8 rounded-xl text-lg shadow-lg transition-all active:scale-95 w-full"
            >
              {{ confirming ? 'جاري التأكيد...' : '✅ أنا وصلت!' }}
            </button>
          </div>

          <!-- تأكيد ناجح -->
          <div v-if="entry.status === 'confirmed' || confirmed" class="mt-4">
            <div class="bg-green-100 text-green-800 rounded-xl p-3 text-sm font-medium">
              ✅ تم التأكيد! يرجى الانتظار في صالون {{ entry.shop_name }}
            </div>
          </div>

          <!-- رابط الخدمة -->
          <div v-if="entry.service" class="mt-4 bg-white/50 rounded-xl p-3">
            <div class="text-sm font-medium">{{ entry.service.name }}</div>
            <div class="text-sm opacity-70">{{ entry.service.price }} ريال</div>
          </div>
        </div>

        <!-- معلومات إضافية -->
        <div class="card">
          <div class="flex justify-between text-sm py-2 border-b border-gray-50">
            <span class="text-gray-500">اسمك</span>
            <span class="font-medium">{{ entry.customer_name }}</span>
          </div>
          <div class="flex justify-between text-sm py-2 border-b border-gray-50">
            <span class="text-gray-500">وقت الانضمام</span>
            <span class="font-medium">{{ entry.joined_at }}</span>
          </div>
          <div v-if="entry.notified_at" class="flex justify-between text-sm py-2">
            <span class="text-gray-500">وقت الإشعار</span>
            <span class="font-medium text-blue-600">{{ entry.notified_at }}</span>
          </div>
        </div>

        <!-- إلغاء المكان -->
        <div v-if="['waiting', 'notified'].includes(entry.status) && !confirmed">
          <button @click="cancelEntry" class="w-full btn-secondary text-red-500 border-red-200 hover:bg-red-50">
            ❌ إلغاء مكاني في الطابور
          </button>
        </div>

        <!-- تحديث تلقائي -->
        <p class="text-center text-xs text-gray-400">
          يتحدث تلقائياً كل 30 ثانية
        </p>
      </div>

      <div v-else class="card text-center py-8">
        <div class="text-5xl mb-3">🔍</div>
        <p class="text-gray-500">لم يتم العثور على هذا الطلب</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/axios'

const route = useRoute()
const router = useRouter()

const entry = ref(null)
const loading = ref(true)
const confirming = ref(false)
const confirmed = ref(false)
let refreshInterval = null

onMounted(async () => {
  await loadStatus()
  // تأكيد تلقائي إذا جاء من رابط التأكيد
  if (route.name === 'QueueConfirm' && entry.value?.status === 'notified') {
    await confirmArrival()
  }
  refreshInterval = setInterval(loadStatus, 30000)
})

onUnmounted(() => clearInterval(refreshInterval))

async function loadStatus() {
  try {
    const res = await api.get(`/queue/${route.params.entryId}/status`)
    entry.value = res.data.data
  } finally {
    loading.value = false
  }
}

async function confirmArrival() {
  confirming.value = true
  try {
    await api.post(`/queue/${route.params.entryId}/confirm`)
    confirmed.value = true
    await loadStatus()
  } finally {
    confirming.value = false
  }
}

async function cancelEntry() {
  if (!confirm('هل تريد إلغاء مكانك؟')) return
  await api.delete(`/queue/${route.params.entryId}`)
  router.push('/')
}

const statusCardClass = computed(() => {
  const s = confirmed.value ? 'confirmed' : entry.value?.status
  return {
    waiting: 'border-yellow-200 bg-yellow-50',
    notified: 'border-blue-300 bg-blue-50',
    confirmed: 'border-green-300 bg-green-50',
    serving: 'border-purple-300 bg-purple-50',
    done: 'border-gray-200 bg-gray-50',
    cancelled: 'border-red-200 bg-red-50',
    no_show: 'border-red-200 bg-red-50',
  }[s] || 'border-gray-200'
})

const statusEmoji = computed(() => {
  const s = confirmed.value ? 'confirmed' : entry.value?.status
  return { waiting: '⏳', notified: '🔔', confirmed: '✅', serving: '💈', done: '🎉', cancelled: '❌', no_show: '❌' }[s] || '⏳'
})

const statusTitle = computed(() => {
  const s = confirmed.value ? 'confirmed' : entry.value?.status
  return {
    waiting: 'أنت في قائمة الانتظار',
    notified: 'دورك قادم!',
    confirmed: 'تم التأكيد',
    serving: 'يُخدَم الآن',
    done: 'انتهت الخدمة',
    cancelled: 'تم الإلغاء',
    no_show: 'تم تسجيل غيابك',
  }[s] || 'غير معروف'
})

const statusDescription = computed(() => {
  const s = confirmed.value ? 'confirmed' : entry.value?.status
  return {
    waiting: 'انتظر رسالة الواتساب عندما يقترب دورك',
    notified: 'أكّد حضورك خلال 10 دقائق وإلا سيُلغى مكانك',
    confirmed: 'انتظر في الصالون، دورك قريب',
    serving: 'يجري تقديم الخدمة لك الآن',
    done: 'شكراً لزيارتك! نراك قريباً',
    cancelled: 'تم إلغاء مكانك. يمكنك إعادة الانضمام',
    no_show: 'تم تسجيل غيابك',
  }[s] || ''
})
</script>
