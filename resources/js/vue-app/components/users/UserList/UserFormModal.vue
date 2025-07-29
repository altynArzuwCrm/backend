<template>
  <Modal @close="$emit('close')">
    <template #header>
      <h2 class="text-xl font-semibold text-gray-900">
        {{ user ? 'Редактировать пользователя' : 'Добавить пользователя' }}
      </h2>
    </template>

    <form @submit.prevent="handleSubmit" class="space-y-4" enctype="multipart/form-data">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Имя *</label>
        <UIInput v-model="form.name" placeholder="Введите имя" :error="errors.name" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Логин *</label>
        <UIInput
          v-model="form.username"
          placeholder="Введите логин"
          :error="errors.username"
          required
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
        <UIInput v-model="form.phone" placeholder="+7 (999) 123-45-67" :error="errors.phone" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ user ? 'Новый пароль' : 'Пароль *' }}
        </label>
        <UIInput
          v-model="form.password"
          type="password"
          placeholder="Минимум 6 символов"
          :error="errors.password"
          :required="!user"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Роли *</label>
        <Vue3Select
          v-model="form.roles"
          :options="roleOptions"
          label="label"
          :reduce="(option) => option.value"
          placeholder="Выберите роли"
          :clearable="true"
          :searchable="true"
          :multiple="true"
          required
          :z-index="99999"
        />
        <p v-if="errors.roles" class="text-xs text-red-500 mt-1">{{ errors.roles }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Фото</label>
        <input
          type="file"
          @change="handleImageChange"
          accept="image/*"
          class="w-full px-3 py-2 border border-gray-300 text-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          name="image"
          id="image"
        />
        <p class="text-xs text-gray-500 mt-1">Максимум 2MB, JPG, PNG</p>
      </div>

      <div class="flex gap-3 pt-4">
        <UIButton type="submit" :loading="loading" class="flex-1">
          {{ user ? 'Сохранить' : 'Создать' }}
        </UIButton>
        <UIButton v-if="user" type="button" variant="danger" @click="handleDelete" class="flex-1">
          Удалить
        </UIButton>
        <UIButton v-else type="button" variant="secondary" @click="$emit('close')" class="flex-1">
          Отмена
        </UIButton>
      </div>
    </form>
  </Modal>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import UIInput from '@/components/ui/UIInput.vue'
import UIButton from '@/components/ui/UIButton.vue'
import Vue3Select from 'vue3-select'
import 'vue3-select/dist/vue3-select.css'
import { toast } from '@/stores/toast'
import { getRoles } from '@/services/api'

const props = defineProps<{
  user?: any
}>()

const emit = defineEmits(['close', 'submit', 'delete'])

const loading = ref(false)
const errors = reactive({
  name: '',
  username: '',
  phone: '',
  password: '',
  roles: '',
  image: '',
})

const form = reactive({
  name: '',
  username: '',
  phone: '',
  password: '',
  roles: [] as number[],
  image: null as File | null,
})

const allRoles = ref<Array<{ id: number; name: string; display_name?: string }>>([])

onMounted(async () => {
  allRoles.value = await getRoles()
  // Не заполняем форму здесь, чтобы не было проблем при повторном открытии
})

watch(
  () => props.user,
  (newUser) => {
    if (newUser) {
      form.name = newUser.name || ''
      form.username = newUser.username || ''
      form.phone = newUser.phone || ''
      form.roles = newUser.roles ? newUser.roles.map((r: any) => Number(r.id)) : []
    } else {
      form.name = ''
      form.username = ''
      form.phone = ''
      form.roles = []
      form.password = ''
      form.image = null
    }
  },
  { immediate: true },
)

const roleOptions = computed(() =>
  allRoles.value.map((r) => ({ value: r.id, label: r.display_name || r.name })),
)

const handleImageChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  console.log('handleImageChange called')
  console.log('target.files:', target.files)
  if (target.files && target.files[0]) {
    console.log('Setting form.image to:', target.files[0])
    form.image = target.files[0]
    console.log('form.image after setting:', form.image)
    console.log('form.image instanceof File:', form.image instanceof File)
  } else {
    console.log('No file selected')
    form.image = null
  }
}

const validateForm = () => {
  Object.keys(errors).forEach((key) => {
    errors[key as keyof typeof errors] = ''
  })
  let isValid = true
  if (!form.name.trim()) {
    errors.name = 'Имя обязательно'
    isValid = false
  }
  if (!form.username.trim()) {
    errors.username = 'Логин обязателен'
    isValid = false
  }
  if (!props.user && !form.password) {
    errors.password = 'Пароль обязателен'
    isValid = false
  }
  if (form.password && form.password.length < 6) {
    errors.password = 'Пароль должен быть минимум 6 символов'
    isValid = false
  }
  if (!form.roles || form.roles.length === 0) {
    errors.roles = 'Нужно выбрать хотя бы одну роль'
    isValid = false
  }
  return isValid
}

const convertHeicToJpg = async (file: File): Promise<File> => {
  if (!file.name.toLowerCase().endsWith('.heic')) {
    return file
  }

  console.log('Converting HEIC to JPG...')

  return new Promise((resolve) => {
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    const img = new Image()

    img.onload = () => {
      canvas.width = img.width
      canvas.height = img.height
      ctx?.drawImage(img, 0, 0)

      canvas.toBlob(
        (blob) => {
          if (blob) {
            const convertedFile = new File([blob], file.name.replace('.heic', '.jpg'), {
              type: 'image/jpeg',
              lastModified: Date.now(),
            })
            console.log('HEIC converted to JPG:', convertedFile)
            resolve(convertedFile)
          } else {
            console.log('Failed to convert HEIC, using original file')
            resolve(file)
          }
        },
        'image/jpeg',
        0.8,
      )
    }

    img.onerror = () => {
      console.log('Failed to load HEIC image, using original file')
      resolve(file)
    }

    img.src = URL.createObjectURL(file)
  })
}

const handleSubmit = async () => {
  console.log('HANDLE SUBMIT CALLED', form)
  console.log('form.image type:', typeof form.image)
  console.log('form.image instanceof File:', form.image instanceof File)
  console.log('form.image:', form.image)

  if (!validateForm()) return
  loading.value = true
  try {
    const dataToSend: any = {
      name: form.name,
      username: form.username,
      phone: form.phone,
      roles: Array.isArray(form.roles)
        ? form.roles
            .map((r) => Number(typeof r === 'object' ? r.id : r))
            .filter((id) => Number.isInteger(id) && id > 0)
        : [],
    }
    if (form.password) dataToSend.password = form.password

    if (form.image instanceof File) {
      console.log('Adding image to dataToSend:', form.image)
      const convertedImage = await convertHeicToJpg(form.image)
      dataToSend.image = convertedImage
    }

    console.log('Final dataToSend:', dataToSend)
    console.log('dataToSend.image type:', typeof dataToSend.image)
    console.log('dataToSend.image instanceof File:', dataToSend.image instanceof File)

    // Проверяем, что данные не теряются при передаче
    const eventData = { ...dataToSend }
    console.log('Event data before emit:', eventData)
    console.log('Event data.image type:', typeof eventData.image)
    console.log('Event data.image instanceof File:', eventData.image instanceof File)

    // Тест сериализации
    try {
      const serialized = JSON.stringify(eventData)
      console.log('Serialization test - can serialize:', serialized.length > 0)
    } catch (e) {
      console.log('Serialization test - cannot serialize File object:', e)
    }

    emit('submit', eventData)
    toast.show(props.user ? 'Пользователь обновлён!' : 'Пользователь создан!')
  } catch (error) {
    console.error('Ошибка отправки формы:', error)
  } finally {
    loading.value = false
  }
}

const handleDelete = () => {
  if (confirm('Вы уверены, что хотите удалить этого пользователя?')) {
    emit('delete', props.user.id)
    toast.show('Пользователь удалён!')
  }
}
</script>

<style scoped>
.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #374151;
  font-size: 0.875rem;
}

.form-input {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  background-color: white;
  transition: all 0.15s ease-in-out;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input:disabled {
  background-color: #f9fafb;
  cursor: not-allowed;
}

.form-input::placeholder {
  color: #9ca3af;
}

:deep(.vue3-select__dropdown) {
  z-index: 99999 !important;
  min-width: 320px !important;
  width: 100% !important;
  max-width: 420px !important;
  max-height: 340px !important;
  overflow-y: auto !important;
  font-size: 1.08rem !important;
  line-height: 1.7 !important;
  border-radius: 12px !important;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
  padding: 8px 0 !important;
}

:deep(.vue3-select__option) {
  padding: 10px 18px !important;
  font-size: 1.08rem !important;
  border-radius: 8px !important;
  margin: 2px 6px !important;
}

:deep(.vue3-select__option--selected) {
  background: #2563eb !important;
  color: #fff !important;
  font-weight: 600;
}

:deep(.vue3-select__option--highlight) {
  background: #e0e7ff !important;
  color: #1e293b !important;
}

:deep(.vue3-select__selected-option) {
  background: #f1f5f9 !important;
  color: #1e293b !important;
  border-radius: 8px !important;
  padding: 4px 10px !important;
  margin: 2px 4px !important;
  font-weight: 500;
  font-size: 1.02rem;
}

.modal {
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
  padding: 32px 24px;
  background: #fff;
}
</style>
