<template>
  <div class="min-h-screen bg-gray-50" dir="rtl">
    <!-- الشريط العلوي -->
    <header class="bg-admin-700 text-white shadow-lg">
      <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <span class="text-2xl">✂️</span>
          <div>
            <h1 class="font-bold text-xl">دورك - لوحة الإدارة</h1>
            <p class="text-admin-200 text-xs">مرحباً، {{ adminStore.admin?.name }}</p>
          </div>
        </div>
        <button @click="handleLogout" class="bg-admin-800 hover:bg-admin-900 px-4 py-2 rounded-xl text-sm font-medium transition-colors">
          خروج
        </button>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6 space-y-6">
      <!-- الإحصائيات -->
      <div v-if="stats" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="card text-center">
          <div class="text-3xl font-bold text-admin-700">{{ stats.total_barbers }}</div>
          <div class="text-xs text-gray-500 mt-1">إجمالي الحلاقين</div>
        </div>
        <div class="card text-center">
          <div class="text-3xl font-bold text-barber-600">{{ stats.active_barbers }}</div>
          <div class="text-xs text-gray-500 mt-1">اشتراك نشط</div>
        </div>
        <div class="card text-center">
          <div class="text-3xl font-bold text-yellow-600">{{ stats.trial_barbers }}</div>
          <div class="text-xs text-gray-500 mt-1">تجريبي</div>
        </div>
        <div class="card text-center">
          <div class="text-3xl font-bold text-blue-600">{{ stats.open_barbers }}</div>
          <div class="text-xs text-gray-500 mt-1">مفتوح الآن</div>
        </div>
        <div class="card text-center">
          <div class="text-3xl font-bold text-purple-600">{{ stats.today_customers }}</div>
          <div class="text-xs text-gray-500 mt-1">زبائن اليوم</div>
        </div>
        <div class="card text-center">
          <div class="text-3xl font-bold text-barber-600">{{ stats.today_served }}</div>
          <div class="text-xs text-gray-500 mt-1">تم خدمتهم</div>
        </div>
      </div>

      <!-- قائمة الحلاقين -->
      <div class="card">
        <div class="flex items-center justify-between mb-5">
          <h2 class="text-lg font-bold text-gray-900">قائمة الحلاقين</h2>
          <button @click="showAddModal = true" class="btn-primary py-2 px-4 text-sm bg-admin-700 hover:bg-admin-800">
            + إضافة حلاق
          </button>
        </div>

        <div v-if="loading" class="text-center py-8 text-gray-400">جاري التحميل...</div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th class="text-right py-3 px-4 rounded-r-lg">الحلاق</th>
                <th class="text-right py-3 px-4">المحل</th>
                <th class="text-right py-3 px-4">الاشتراك</th>
                <th class="text-right py-3 px-4">انتهاء الاشتراك</th>
                <th class="text-right py-3 px-4">الحالة</th>
                <th class="text-right py-3 px-4 rounded-l-lg">إجراءات</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="barber in barbers" :key="barber.id" class="hover:bg-gray-50 transition-colors">
                <td class="py-3 px-4">
                  <div class="font-semibold text-gray-900">{{ barber.name }}</div>
                  <div class="text-gray-500 text-xs">{{ barber.email }}</div>
                </td>
                <td class="py-3 px-4 text-gray-700">{{ barber.shop_name }}</td>
                <td class="py-3 px-4">
                  <span :class="subscriptionClass(barber.subscription_status)" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ subscriptionLabel(barber.subscription_status) }}
                  </span>
                </td>
                <td class="py-3 px-4 text-gray-600">
                  {{ barber.subscription_expires_at || '—' }}
                </td>
                <td class="py-3 px-4">
                  <span :class="barber.is_open ? 'text-barber-600' : 'text-gray-400'" class="font-medium text-xs">
                    {{ barber.is_open ? '🟢 مفتوح' : '🔴 مغلق' }}
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="flex gap-2">
                    <button @click="openSubscriptionModal(barber)" class="text-admin-600 hover:text-admin-800 text-xs font-medium">اشتراك</button>
                    <button @click="openEditModal(barber)" class="text-blue-600 hover:text-blue-800 text-xs font-medium">تعديل</button>
                    <button @click="confirmDelete(barber)" class="text-red-600 hover:text-red-800 text-xs font-medium">حذف</button>
                  </div>
                </td>
              </tr>
              <tr v-if="barbers.length === 0">
                <td colspan="6" class="text-center py-8 text-gray-400">لا يوجد حلاقين بعد</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- مودال إضافة/تعديل حلاق -->
    <div v-if="showAddModal || showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <h3 class="text-lg font-bold mb-5">{{ showEditModal ? 'تعديل بيانات الحلاق' : 'إضافة حلاق جديد' }}</h3>

          <form @submit.prevent="showEditModal ? handleUpdate() : handleAdd()" class="space-y-4">
            <input v-model="barberForm.name" type="text" placeholder="الاسم الكامل" class="input-field" required />
            <input v-model="barberForm.shop_name" type="text" placeholder="اسم الصالون" class="input-field" required />
            <input v-model="barberForm.email" type="email" placeholder="البريد الإلكتروني" class="input-field" required />
            <input v-model="barberForm.phone" type="tel" placeholder="رقم الهاتف" class="input-field" required />
            <input v-model="barberForm.whatsapp" type="tel" placeholder="رقم الواتساب" class="input-field" />
            <input v-model="barberForm.password" type="password" :placeholder="showEditModal ? 'كلمة المرور الجديدة (اتركها فارغة للإبقاء)' : 'كلمة المرور'" class="input-field" :required="!showEditModal" />
            <input v-model="barberForm.address" type="text" placeholder="العنوان" class="input-field" />
            <div>
              <label class="text-sm font-medium text-gray-700">متوسط وقت الخدمة (دقائق)</label>
              <input v-model.number="barberForm.avg_service_minutes" type="number" min="5" max="120" class="input-field mt-1" />
            </div>

            <div v-if="formError" class="bg-red-50 text-red-700 rounded-xl p-3 text-sm">{{ formError }}</div>

            <div class="flex gap-3 pt-2">
              <button type="submit" :disabled="formLoading" class="flex-1 btn-primary bg-admin-700 hover:bg-admin-800">
                {{ formLoading ? 'جاري الحفظ...' : 'حفظ' }}
              </button>
              <button type="button" @click="closeModals" class="flex-1 btn-secondary">إلغاء</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- مودال الاشتراك -->
    <div v-if="showSubModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="p-6">
          <h3 class="text-lg font-bold mb-5">تحديث اشتراك {{ selectedBarber?.name }}</h3>

          <form @submit.prevent="handleSubscriptionUpdate" class="space-y-4">
            <div>
              <label class="text-sm font-medium text-gray-700">حالة الاشتراك</label>
              <select v-model="subForm.subscription_status" class="input-field mt-1">
                <option value="trial">تجريبي</option>
                <option value="active">نشط</option>
                <option value="inactive">غير نشط</option>
              </select>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-700">تاريخ انتهاء الاشتراك</label>
              <input v-model="subForm.subscription_expires_at" type="date" class="input-field mt-1" />
            </div>

            <div class="flex gap-3">
              <button type="submit" :disabled="formLoading" class="flex-1 btn-primary bg-admin-700 hover:bg-admin-800">
                {{ formLoading ? 'جاري...' : 'تحديث' }}
              </button>
              <button type="button" @click="showSubModal = false" class="flex-1 btn-secondary">إلغاء</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminStore } from '@/stores/admin'
import api from '@/api/axios'

const router = useRouter()
const adminStore = useAdminStore()

const barbers = ref([])
const stats = ref(null)
const loading = ref(true)
const showAddModal = ref(false)
const showEditModal = ref(false)
const showSubModal = ref(false)
const selectedBarber = ref(null)
const formLoading = ref(false)
const formError = ref('')

const barberForm = ref({
  name: '', shop_name: '', email: '', phone: '',
  whatsapp: '', password: '', address: '', avg_service_minutes: 20,
})

const subForm = ref({ subscription_status: 'trial', subscription_expires_at: '' })

onMounted(async () => {
  await Promise.all([loadBarbers(), loadStats()])
})

async function loadBarbers() {
  loading.value = true
  try {
    const res = await api.get('/admin/barbers')
    barbers.value = res.data.data
  } finally {
    loading.value = false
  }
}

async function loadStats() {
  const res = await api.get('/admin/stats')
  stats.value = res.data.data
}

async function handleAdd() {
  formLoading.value = true
  formError.value = ''
  try {
    await api.post('/admin/barbers', barberForm.value)
    await loadBarbers()
    closeModals()
  } catch (e) {
    formError.value = e.response?.data?.message || 'حدث خطأ'
  } finally {
    formLoading.value = false
  }
}

async function handleUpdate() {
  formLoading.value = true
  formError.value = ''
  try {
    await api.put(`/admin/barbers/${selectedBarber.value.id}`, barberForm.value)
    await loadBarbers()
    closeModals()
  } catch (e) {
    formError.value = e.response?.data?.message || 'حدث خطأ'
  } finally {
    formLoading.value = false
  }
}

async function handleSubscriptionUpdate() {
  formLoading.value = true
  try {
    await api.put(`/admin/barbers/${selectedBarber.value.id}/subscription`, subForm.value)
    await loadBarbers()
    showSubModal.value = false
  } finally {
    formLoading.value = false
  }
}

async function confirmDelete(barber) {
  if (!confirm(`هل تريد حذف ${barber.name}؟`)) return
  await api.delete(`/admin/barbers/${barber.id}`)
  await loadBarbers()
}

function openEditModal(barber) {
  selectedBarber.value = barber
  barberForm.value = {
    name: barber.name, shop_name: barber.shop_name, email: barber.email,
    phone: barber.phone, whatsapp: barber.whatsapp || '', password: '',
    address: barber.address || '', avg_service_minutes: barber.avg_service_minutes,
  }
  showEditModal.value = true
}

function openSubscriptionModal(barber) {
  selectedBarber.value = barber
  subForm.value = {
    subscription_status: barber.subscription_status,
    subscription_expires_at: barber.subscription_expires_at || '',
  }
  showSubModal.value = true
}

function closeModals() {
  showAddModal.value = false
  showEditModal.value = false
  selectedBarber.value = null
  formError.value = ''
  barberForm.value = { name: '', shop_name: '', email: '', phone: '', whatsapp: '', password: '', address: '', avg_service_minutes: 20 }
}

async function handleLogout() {
  await adminStore.logout()
  router.push('/admin/login')
}

function subscriptionClass(status) {
  return {
    active: 'bg-green-100 text-green-800',
    trial: 'bg-yellow-100 text-yellow-800',
    inactive: 'bg-red-100 text-red-800',
  }[status] || 'bg-gray-100 text-gray-800'
}

function subscriptionLabel(status) {
  return { active: 'نشط', trial: 'تجريبي', inactive: 'غير نشط' }[status] || status
}
</script>
