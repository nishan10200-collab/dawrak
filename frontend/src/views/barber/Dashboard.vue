<template>
  <div class="min-h-screen bg-gray-50" dir="rtl">
    <!-- الشريط العلوي -->
    <header class="bg-barber-700 text-white shadow-lg sticky top-0 z-40">
      <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <span class="text-2xl">💈</span>
          <div>
            <h1 class="font-bold">{{ barberStore.barber?.shop_name }}</h1>
            <p class="text-barber-200 text-xs">{{ barberStore.barber?.name }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <router-link to="/barber/services" class="bg-barber-600 hover:bg-barber-500 px-3 py-1.5 rounded-lg text-xs font-medium">الخدمات</router-link>
          <router-link to="/barber/qr" class="bg-barber-600 hover:bg-barber-500 px-3 py-1.5 rounded-lg text-xs font-medium">QR</router-link>
          <button @click="handleLogout" class="bg-barber-800 hover:bg-barber-900 px-3 py-1.5 rounded-lg text-xs font-medium">خروج</button>
        </div>
      </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-5 space-y-4">
      <!-- مفتاح فتح/إغلاق المحل -->
      <div class="card flex items-center justify-between">
        <div>
          <h2 class="font-bold text-lg text-gray-900">حالة المحل</h2>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ barberStore.isOpen ? '🟢 المحل مفتوح ويستقبل الزبائن' : '🔴 المحل مغلق حالياً' }}
          </p>
        </div>
        <!-- مفتاح toggle كبير وواضح -->
        <button
          @click="toggleShop"
          :disabled="toggling"
          :class="[
            'relative inline-flex h-10 w-20 items-center rounded-full transition-colors duration-300 focus:outline-none shadow-inner',
            barberStore.isOpen ? 'bg-barber-500' : 'bg-gray-300',
            toggling ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer',
          ]"
        >
          <span
            :class="[
              'inline-block h-8 w-8 transform rounded-full bg-white shadow-md transition-transform duration-300',
              barberStore.isOpen ? '-translate-x-1' : 'translate-x-11',
            ]"
          />
        </button>
      </div>

      <!-- إحصائيات اليوم -->
      <div class="grid grid-cols-3 gap-3">
        <div class="card text-center py-4">
          <div class="text-2xl font-bold text-barber-600">{{ queueData.today_served }}</div>
          <div class="text-xs text-gray-500 mt-1">خُدمو اليوم</div>
        </div>
        <div class="card text-center py-4">
          <div class="text-2xl font-bold text-blue-600">{{ queue.length }}</div>
          <div class="text-xs text-gray-500 mt-1">في الانتظار</div>
        </div>
        <div class="card text-center py-4">
          <div class="text-2xl font-bold text-yellow-600">
            {{ queue.length * (barberStore.barber?.avg_service_minutes || 20) }}
          </div>
          <div class="text-xs text-gray-500 mt-1">دقيقة انتظار</div>
        </div>
      </div>

      <!-- قائمة الانتظار -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold text-lg text-gray-900">قائمة الانتظار</h2>
          <button @click="loadQueue" class="text-barber-600 text-sm font-medium hover:text-barber-800">
            🔄 تحديث
          </button>
        </div>

        <div v-if="loadingQueue" class="text-center py-6 text-gray-400">جاري التحميل...</div>

        <div v-else-if="queue.length === 0" class="text-center py-10">
          <div class="text-5xl mb-3">😴</div>
          <p class="text-gray-400 font-medium">الطابور فارغ حالياً</p>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="entry in queue"
            :key="entry.id"
            :class="[
              'rounded-xl border-2 p-4 transition-all',
              entry.status === 'notified' ? 'border-blue-300 bg-blue-50 pulse-notification' : 'border-gray-100 bg-white',
              entry.status === 'confirmed' ? 'border-green-300 bg-green-50' : '',
            ]"
          >
            <div class="flex items-start justify-between gap-3">
              <!-- رقم الترتيب + المعلومات -->
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-barber-100 text-barber-700 font-bold text-lg flex items-center justify-center flex-shrink-0">
                  {{ entry.position }}
                </div>
                <div>
                  <div class="font-semibold text-gray-900">{{ entry.customer_name }}</div>
                  <div v-if="entry.service" class="text-xs text-gray-500 mt-0.5">
                    {{ entry.service.name }} • {{ entry.service.price }} ريال
                  </div>
                  <div class="text-xs text-gray-400 mt-0.5">انضم: {{ entry.joined_at }}</div>
                </div>
              </div>

              <!-- الحالة -->
              <div class="flex flex-col items-end gap-2">
                <span :class="statusClass(entry.status)" class="text-xs">
                  {{ statusLabel(entry.status) }}
                </span>

                <!-- أزرار الإجراءات -->
                <div class="flex gap-1.5">
                  <button
                    @click="markDone(entry)"
                    :disabled="actionLoading === entry.id"
                    class="btn-success text-xs py-1 px-2.5"
                  >
                    ✅ انتهيت
                  </button>
                  <button
                    @click="markNoShow(entry)"
                    :disabled="actionLoading === entry.id"
                    class="btn-danger text-xs py-1 px-2.5"
                  >
                    ❌ غائب
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useBarberStore } from '@/stores/barber'
import api from '@/api/axios'

const router = useRouter()
const barberStore = useBarberStore()

const queue = ref([])
const queueData = ref({ today_served: 0 })
const loadingQueue = ref(true)
const toggling = ref(false)
const actionLoading = ref(null)

// تحديث تلقائي كل 30 ثانية
let refreshInterval = null

onMounted(async () => {
  await loadQueue()
  refreshInterval = setInterval(loadQueue, 30000)
})

onUnmounted(() => {
  clearInterval(refreshInterval)
})

async function loadQueue() {
  loadingQueue.value = true
  try {
    const res = await api.get('/barber/queue')
    queue.value = res.data.data.queue
    queueData.value = res.data.data
  } catch (e) {
    // تجاهل أخطاء التحديث التلقائي
  } finally {
    loadingQueue.value = false
  }
}

async function toggleShop() {
  toggling.value = true
  try {
    await barberStore.toggleShopStatus(!barberStore.isOpen)
  } finally {
    toggling.value = false
  }
}

async function markDone(entry) {
  actionLoading.value = entry.id
  try {
    await api.put(`/barber/queue/${entry.id}/done`)
    await loadQueue()
  } finally {
    actionLoading.value = null
  }
}

async function markNoShow(entry) {
  actionLoading.value = entry.id
  try {
    await api.put(`/barber/queue/${entry.id}/no-show`)
    await loadQueue()
  } finally {
    actionLoading.value = null
  }
}

async function handleLogout() {
  await barberStore.logout()
  router.push('/barber/login')
}

function statusClass(status) {
  const classes = {
    waiting: 'badge-waiting',
    notified: 'badge-notified',
    confirmed: 'badge-confirmed',
    serving: 'badge-serving',
    done: 'badge-done',
  }
  return classes[status] || 'badge-waiting'
}

function statusLabel(status) {
  const labels = {
    waiting: 'ينتظر',
    notified: '🔔 تم إشعاره',
    confirmed: '✅ أكّد',
    serving: '💈 يُخدم',
    done: 'انتهى',
  }
  return labels[status] || status
}
</script>
