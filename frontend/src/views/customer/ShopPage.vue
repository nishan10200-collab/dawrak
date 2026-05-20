<template>
  <div class="min-h-screen bg-gray-50" dir="rtl">
    <!-- حالة التحميل -->
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <div class="text-5xl mb-4 animate-bounce">💈</div>
        <p class="text-gray-500">جاري التحميل...</p>
      </div>
    </div>

    <!-- المحتوى الرئيسي -->
    <div v-else-if="shop">
      <!-- رأس الصفحة -->
      <div class="bg-gradient-to-b from-customer-700 to-customer-600 text-white pt-8 pb-16 px-4">
        <div class="max-w-md mx-auto text-center">
          <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <img v-if="shop.logo" :src="shop.logo" class="w-full h-full object-cover rounded-2xl" />
            <span v-else class="text-4xl">💈</span>
          </div>
          <h1 class="text-2xl font-bold">{{ shop.shop_name }}</h1>
          <p class="text-customer-100 mt-1">{{ shop.address }}</p>

          <!-- حالة المحل -->
          <div class="mt-3 inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold"
               :class="shop.is_open ? 'bg-green-400/30 text-green-100' : 'bg-red-400/30 text-red-100'">
            <span :class="shop.is_open ? 'animate-pulse' : ''">{{ shop.is_open ? '🟢' : '🔴' }}</span>
            {{ shop.is_open ? 'مفتوح الآن' : 'مغلق حالياً' }}
          </div>
        </div>
      </div>

      <!-- بطاقة الطابور -->
      <div class="max-w-md mx-auto px-4 -mt-8">
        <div class="card shadow-lg mb-4">
          <div class="grid grid-cols-2 gap-4 text-center">
            <div>
              <div class="text-3xl font-bold text-customer-600">{{ shop.queue_count }}</div>
              <div class="text-xs text-gray-500 mt-1">في الانتظار حالياً</div>
            </div>
            <div>
              <div class="text-3xl font-bold text-customer-600">{{ shop.estimated_wait_minutes }}</div>
              <div class="text-xs text-gray-500 mt-1">دقيقة وقت انتظار تقريبي</div>
            </div>
          </div>
        </div>

        <!-- نموذج الانضمام -->
        <div v-if="shop.is_open && !joinedEntry" class="card mb-4">
          <h2 class="font-bold text-lg text-gray-900 mb-4">انضم للطابور</h2>

          <form @submit.prevent="handleJoin" class="space-y-4">
            <div>
              <label class="text-sm font-semibold text-gray-700 mb-2 block">اسمك</label>
              <input v-model="joinForm.customer_name" type="text" placeholder="اسمك الكامل" class="input-field" required />
            </div>

            <div>
              <label class="text-sm font-semibold text-gray-700 mb-2 block">رقم الواتساب</label>
              <input
                v-model="joinForm.customer_whatsapp"
                type="tel"
                placeholder="+966501234567"
                class="input-field"
                required
                dir="ltr"
              />
              <p class="text-xs text-gray-400 mt-1">سنرسل إشعارك عبر الواتساب عندما يقترب دورك</p>
            </div>

            <!-- اختيار الخدمة -->
            <div>
              <label class="text-sm font-semibold text-gray-700 mb-2 block">الخدمة المطلوبة (اختياري)</label>
              <select v-model="joinForm.service_id" class="input-field">
                <option :value="null">اختر خدمة...</option>
                <optgroup v-for="(services, cat) in shop.services" :key="cat" :label="categoryLabel(cat)">
                  <option v-for="s in services" :key="s.id" :value="s.id">
                    {{ s.name }} - {{ s.price }} ريال
                  </option>
                </optgroup>
              </select>
            </div>

            <div v-if="joinError" class="bg-red-50 border border-red-100 text-red-700 rounded-xl p-3 text-sm">
              {{ joinError }}
            </div>

            <button type="submit" :disabled="joining" class="w-full py-4 bg-customer-600 hover:bg-customer-700 text-white font-bold rounded-xl transition-all text-lg shadow-lg active:scale-95">
              {{ joining ? 'جاري الانضمام...' : '🚀 انضم للطابور' }}
            </button>
          </form>
        </div>

        <!-- المحل مغلق -->
        <div v-else-if="!shop.is_open" class="card text-center py-8">
          <div class="text-5xl mb-3">🔒</div>
          <h3 class="font-bold text-gray-700">المحل مغلق حالياً</h3>
          <p class="text-gray-400 text-sm mt-2">يرجى العودة لاحقاً</p>
        </div>

        <!-- بعد الانضمام -->
        <div v-if="joinedEntry" class="card mb-4 border-2 border-customer-300 bg-customer-50">
          <div class="text-center">
            <div class="text-5xl mb-3">🎉</div>
            <h3 class="font-bold text-xl text-customer-800">أنت في الطابور!</h3>
            <div class="mt-4 bg-white rounded-xl p-4 shadow-sm">
              <div class="text-5xl font-bold text-customer-600">{{ joinedEntry.position }}</div>
              <div class="text-gray-500 text-sm mt-1">رقمك في الطابور</div>
              <div class="mt-3 text-barber-600 font-medium">
                ⏱️ وقت الانتظار التقريبي: {{ joinedEntry.estimated_wait_minutes }} دقيقة
              </div>
            </div>
            <p class="text-customer-700 text-sm mt-4">
              📱 ستصلك رسالة واتساب عندما يقترب دورك
            </p>
            <div class="flex gap-3 mt-4">
              <router-link :to="`/queue/${joinedEntry.entry_id}`" class="flex-1 bg-customer-600 text-white py-3 rounded-xl font-semibold text-sm text-center">
                متابعة الحالة
              </router-link>
              <button @click="cancelEntry" class="flex-1 btn-secondary text-sm">
                إلغاء المكان
              </button>
            </div>
          </div>
        </div>

        <!-- قائمة الخدمات -->
        <div class="card mb-6">
          <h2 class="font-bold text-lg text-gray-900 mb-4">الخدمات والأسعار</h2>

          <!-- تبويبات الفئات -->
          <div class="flex gap-2 mb-4 overflow-x-auto pb-1">
            <button
              v-for="cat in availableCategories"
              :key="cat"
              @click="activeCategory = cat"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-all',
                activeCategory === cat
                  ? 'bg-customer-600 text-white'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
              ]"
            >
              {{ categoryLabel(cat) }}
            </button>
          </div>

          <div class="space-y-2">
            <div
              v-for="service in currentCategoryServices"
              :key="service.id"
              class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0"
            >
              <div>
                <div class="font-semibold text-gray-900 text-sm">{{ service.name }}</div>
                <div v-if="service.description" class="text-xs text-gray-400 mt-0.5">{{ service.description }}</div>
                <div class="text-xs text-gray-400 mt-0.5">{{ service.duration_minutes }} دقيقة</div>
              </div>
              <div class="text-customer-700 font-bold">{{ service.price }} ريال</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- محل غير موجود -->
    <div v-else class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <div class="text-5xl mb-4">🔍</div>
        <p class="text-gray-500">المحل غير موجود</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/axios'

const route = useRoute()
const router = useRouter()

const shop = ref(null)
const loading = ref(true)
const joining = ref(false)
const joinError = ref('')
const joinedEntry = ref(null)
const activeCategory = ref('hair')

const joinForm = ref({
  customer_name: '',
  customer_whatsapp: '',
  service_id: null,
})

const availableCategories = computed(() => Object.keys(shop.value?.services || {}))

const currentCategoryServices = computed(() =>
  shop.value?.services?.[activeCategory.value] || []
)

onMounted(async () => {
  try {
    const res = await api.get(`/shop/${route.params.barberId}`)
    shop.value = res.data.data
    if (availableCategories.value.length > 0) {
      activeCategory.value = availableCategories.value[0]
    }
  } finally {
    loading.value = false
  }
})

async function handleJoin() {
  joining.value = true
  joinError.value = ''
  try {
    const res = await api.post(`/shop/${route.params.barberId}/join`, joinForm.value)
    joinedEntry.value = res.data.data
  } catch (e) {
    joinError.value = e.response?.data?.message || 'حدث خطأ في الانضمام'
    if (e.response?.data?.data?.entry_id) {
      joinedEntry.value = { entry_id: e.response.data.data.entry_id, position: '?', estimated_wait_minutes: 0 }
    }
  } finally {
    joining.value = false
  }
}

async function cancelEntry() {
  if (!confirm('هل تريد إلغاء مكانك في الطابور؟')) return
  try {
    await api.delete(`/queue/${joinedEntry.value.entry_id}`)
    joinedEntry.value = null
  } catch (e) {
    alert('حدث خطأ في الإلغاء')
  }
}

function categoryLabel(cat) {
  return { hair: '✂️ شعر', beard: '🪒 ذقن', special: '⭐ خاص', offer: '🎁 عروض' }[cat] || cat
}
</script>
