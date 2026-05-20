<template>
  <div class="min-h-screen bg-gray-50" dir="rtl">
    <header class="bg-barber-700 text-white shadow-lg sticky top-0 z-40">
      <div class="max-w-2xl mx-auto px-4 py-3 flex items-center gap-3">
        <router-link to="/barber/dashboard" class="text-barber-200 hover:text-white">← رجوع</router-link>
        <h1 class="font-bold text-lg">الخدمات والأسعار</h1>
      </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-5">
      <!-- تبويبات الفئات -->
      <div class="flex gap-2 mb-5 overflow-x-auto pb-2">
        <button
          v-for="(label, key) in categories"
          :key="key"
          @click="activeCategory = key"
          :class="[
            'px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-all',
            activeCategory === key
              ? 'bg-barber-600 text-white shadow-sm'
              : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50',
          ]"
        >
          {{ label }}
        </button>
      </div>

      <div class="card mb-4">
        <button @click="openAddModal" class="w-full btn-primary flex items-center justify-center gap-2">
          <span>+</span>
          <span>إضافة خدمة في "{{ categories[activeCategory] }}"</span>
        </button>
      </div>

      <!-- قائمة الخدمات -->
      <div v-if="loading" class="text-center py-8 text-gray-400">جاري التحميل...</div>

      <div v-else class="space-y-3">
        <div
          v-for="service in currentServices"
          :key="service.id"
          class="card flex items-center justify-between gap-3"
        >
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-barber-50 flex items-center justify-center overflow-hidden flex-shrink-0">
              <img v-if="service.image" :src="service.image" class="w-full h-full object-cover" />
              <span v-else class="text-2xl">{{ categoryIcon(activeCategory) }}</span>
            </div>
            <div>
              <div class="font-semibold text-gray-900">{{ service.name }}</div>
              <div class="text-sm text-barber-600 font-bold">{{ service.price }} ريال</div>
              <div class="text-xs text-gray-400">{{ service.duration_minutes }} دقيقة</div>
            </div>
          </div>

          <div class="flex flex-col gap-1.5">
            <span :class="service.is_available ? 'text-barber-600' : 'text-gray-400'" class="text-xs font-medium">
              {{ service.is_available ? 'متاح' : 'غير متاح' }}
            </span>
            <div class="flex gap-1">
              <button @click="openEditModal(service)" class="text-blue-600 text-xs font-medium hover:text-blue-800">تعديل</button>
              <span class="text-gray-300">|</span>
              <button @click="deleteService(service)" class="text-red-500 text-xs font-medium hover:text-red-700">حذف</button>
            </div>
          </div>
        </div>

        <div v-if="currentServices.length === 0" class="card text-center py-8 text-gray-400">
          لا توجد خدمات في هذه الفئة
        </div>
      </div>
    </main>

    <!-- مودال إضافة/تعديل خدمة -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <h3 class="text-lg font-bold mb-5">{{ editingService ? 'تعديل الخدمة' : 'إضافة خدمة جديدة' }}</h3>

          <form @submit.prevent="handleSave" class="space-y-4">
            <div>
              <label class="text-sm font-medium text-gray-700">الفئة</label>
              <select v-model="form.category" class="input-field mt-1">
                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>
            <input v-model="form.name" type="text" placeholder="اسم الخدمة" class="input-field" required />
            <textarea v-model="form.description" placeholder="وصف الخدمة (اختياري)" class="input-field h-20 resize-none"></textarea>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-xs text-gray-600">السعر (ريال)</label>
                <input v-model.number="form.price" type="number" min="0" step="0.5" class="input-field mt-1" required />
              </div>
              <div>
                <label class="text-xs text-gray-600">المدة (دقيقة)</label>
                <input v-model.number="form.duration_minutes" type="number" min="5" max="180" class="input-field mt-1" required />
              </div>
            </div>
            <div class="flex items-center gap-3">
              <input v-model="form.is_available" type="checkbox" id="available" class="w-4 h-4 text-barber-600" />
              <label for="available" class="text-sm text-gray-700">الخدمة متاحة للحجز</label>
            </div>

            <div v-if="formError" class="bg-red-50 text-red-700 rounded-xl p-3 text-sm">{{ formError }}</div>

            <div class="flex gap-3">
              <button type="submit" :disabled="formLoading" class="flex-1 btn-primary">
                {{ formLoading ? 'جاري الحفظ...' : 'حفظ' }}
              </button>
              <button type="button" @click="showModal = false" class="flex-1 btn-secondary">إلغاء</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api/axios'

const categories = { hair: 'شعر', beard: 'ذقن', special: 'خاص', offer: 'عروض' }
const activeCategory = ref('hair')
const services = ref({})
const loading = ref(true)
const showModal = ref(false)
const editingService = ref(null)
const formLoading = ref(false)
const formError = ref('')

const form = ref({
  category: 'hair', name: '', description: '',
  price: 0, duration_minutes: 20, is_available: true,
})

const currentServices = computed(() => services.value[activeCategory.value] || [])

onMounted(loadServices)

async function loadServices() {
  loading.value = true
  try {
    const res = await api.get('/barber/services')
    services.value = res.data.data
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  editingService.value = null
  form.value = { category: activeCategory.value, name: '', description: '', price: 0, duration_minutes: 20, is_available: true }
  showModal.value = true
}

function openEditModal(service) {
  editingService.value = service
  form.value = { ...service }
  showModal.value = true
}

async function handleSave() {
  formLoading.value = true
  formError.value = ''
  try {
    if (editingService.value) {
      await api.put(`/barber/services/${editingService.value.id}`, form.value)
    } else {
      await api.post('/barber/services', form.value)
    }
    await loadServices()
    showModal.value = false
  } catch (e) {
    formError.value = e.response?.data?.message || 'حدث خطأ'
  } finally {
    formLoading.value = false
  }
}

async function deleteService(service) {
  if (!confirm(`هل تريد حذف خدمة "${service.name}"؟`)) return
  await api.delete(`/barber/services/${service.id}`)
  await loadServices()
}

function categoryIcon(cat) {
  return { hair: '✂️', beard: '🪒', special: '⭐', offer: '🎁' }[cat] || '✂️'
}
</script>
