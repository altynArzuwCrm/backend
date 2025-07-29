<template>
  <Modal @close="$emit('close')">
    <template #header>
      <h2 class="text-xl font-semibold text-gray-900">
        {{ client ? 'Редактировать клиента' : 'Добавить клиента' }}
      </h2>
    </template>
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Имя *</label>
        <UIInput v-model="form.name" placeholder="Введите имя" :error="errors.name" required />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Компания</label>
        <UIInput
          v-model="form.company_name"
          placeholder="Введите название компании"
          :error="errors.company_name"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Контакты</label>
        <div class="flex flex-col gap-2">
          <div
            v-for="(contact, idx) in form.contacts"
            :key="contact.localId || contact.id"
            class="flex gap-2 items-center text-gray-700"
          >
            <select
              v-model="contact.type"
              class="px-2 py-1 border rounded"
              required
              @change="handleContactTypeChange($event, idx)"
            >
              <option value="phone">Телефон</option>
              <option value="email">Email</option>
              <option value="telegram">Telegram</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="instagram">Instagram</option>
              <option value="other">Другое</option>
            </select>
            <ContactTypeIcon :type="contact.type || 'phone'" class="mr-1" />
            <UIInput
              :model-value="contact.value ?? ''"
              @update:model-value="(value) => handleContactValueChange(value, idx)"
              placeholder="Значение"
              required
              class="flex-1"
            />
            <UIButton type="button" variant="danger" @click="removeContactHandler(idx)">✕</UIButton>
          </div>
          <UIButton type="button" variant="secondary" @click="addContact"
            >+ Добавить контакт</UIButton
          >
        </div>
        <div v-if="errors.contacts" class="text-red-600 text-sm mt-1">{{ errors.contacts }}</div>
      </div>
      <div class="flex gap-3 pt-4">
        <UIButton type="submit" :loading="loading" class="flex-1">
          {{ client ? 'Сохранить' : 'Создать' }}
        </UIButton>
        <UIButton v-if="client" type="button" variant="danger" @click="handleDelete" class="flex-1">
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
import { ref, reactive, onMounted } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import UIInput from '@/components/ui/UIInput.vue'
import UIButton from '@/components/ui/UIButton.vue'
import type { Client, ClientContact } from '@/types/client'
import clientController from '@/controllers/clientControllerInstance'
import ContactTypeIcon from '@/components/clients/ClientList/ContactTypeIcon.vue'
import { toast } from '@/stores/toast'

const { createContact, updateContact, removeContact, create, update, remove } = clientController

const props = defineProps<{ client?: Client | null }>()
const emit = defineEmits(['close', 'submit', 'delete'])

const loading = ref(false)
const errors = reactive({
  name: '',
  company_name: '',
  contacts: '',
})

const form = reactive({
  name: '',
  company_name: '',
  contacts: [] as (Partial<ClientContact> & { localId?: number; id?: number })[],
})

onMounted(() => {
  if (props.client) {
    form.name = props.client.name || ''
    form.company_name = props.client.company_name || ''
    form.contacts = props.client.contacts.map((c) => ({ ...c }))
  } else {
    form.name = ''
    form.company_name = ''
    form.contacts = []
  }
})

async function addContact() {
  const newContact = {
    type: 'phone' as const,
    value: '',
    localId: Date.now() + Math.random(),
  }
  form.contacts.push(newContact)
  // Не отправляем createContact здесь!
}

async function updateContactField(idx: number, field: 'type' | 'value', value: string) {
  const contact = form.contacts[idx]
  if (field === 'type') {
    contact.type = value as 'phone' | 'email' | 'telegram' | 'whatsapp' | 'instagram' | 'other'
  } else {
    contact.value = value
  }
  // Не отправляем запросы здесь! Всё сохраняется при handleSubmit
}

async function removeContactHandler(idx: number) {
  const contact = form.contacts[idx]
  if (props.client?.id && contact.id) {
    try {
      await removeContact(props.client.id, contact.id)
    } catch (e) {}
  }
  form.contacts.splice(idx, 1)
}

function validateForm() {
  errors.name = ''
  errors.company_name = ''
  errors.contacts = ''
  let valid = true
  if (!form.name.trim()) {
    errors.name = 'Имя обязательно'
    valid = false
  }

  // Новая проверка: хотя бы один телефон
  const phoneContacts = form.contacts.filter((c) => c.type === 'phone')
  if (
    phoneContacts.length === 0 ||
    !phoneContacts.some((c) => c.value && /^\+993[-\s]?\d{2}[-\s]?\d{6}$/.test(c.value))
  ) {
    errors.contacts = 'Нужно указать хотя бы один телефон в формате +993 XX YYYYYY'
    valid = false
  }

  for (const c of form.contacts) {
    if (c.type === 'phone' && c.value) {
      const phoneRegex = /^\+993[-\s]?\d{2}[-\s]?\d{6}$/
      if (!phoneRegex.test(c.value)) {
        errors.contacts = 'Телефон должен быть в формате +993 XX YYYYYY'
        valid = false
        break
      }
    }
  }

  if (form.contacts.some((c) => !c.value || !c.type)) {
    errors.contacts = 'Все контакты должны быть заполнены'
    valid = false
  }
  return valid
}

async function handleSubmit() {
  if (!validateForm()) return
  loading.value = true
  try {
    let clientId = props.client?.id
    if (clientId) {
      const clientData: Partial<Client> = {
        name: form.name,
        company_name: form.company_name || null,
        contacts: form.contacts.map((contact) => ({
          type: contact.type || 'phone',
          value: contact.value || '',
          client_id: clientId || 0,
        })) as any,
      }
      await update(clientId, clientData as any)
      toast.show('Клиент успешно обновлён!')
    } else {
      const clientData: Partial<Client> = {
        name: form.name,
        company_name: form.company_name || null,
        contacts: form.contacts.map((contact) => ({
          type: contact.type || 'phone',
          value: contact.value || '',
          client_id: 0, // будет установлено сервером
        })) as any,
      }
      const created = await create(clientData as any)
      clientId = created.id
      toast.show('Клиент успешно добавлен!')
    }
    // Сохраняем новые и изменённые контакты
    for (const contact of form.contacts) {
      if (!contact.id && contact.type && contact.value) {
        try {
          const createdContact = await createContact(clientId, {
            type: contact.type,
            value: contact.value,
          })
          contact.id = createdContact.id
        } catch (e) {
          // Можно добавить обработку ошибок
        }
      } else if (contact.id) {
        try {
          await updateContact(clientId, contact.id, {
            type: contact.type || 'phone',
            value: contact.value || '',
          })
        } catch (e) {
          // Можно добавить обработку ошибок
        }
      }
    }
    emit('submit', { id: clientId, ...form })
    emit('close')
  } finally {
    loading.value = false
  }
}

function handleContactTypeChange(event: Event, idx: number) {
  const target = event.target as HTMLSelectElement
  updateContactField(idx, 'type', target?.value || '')
}

function handleContactValueChange(value: any, idx: number) {
  updateContactField(idx, 'value', String(value || ''))
}

function handleDelete() {
  if (props.client && props.client.id) {
    const clientId = props.client.id
    remove(clientId).then(() => {
      toast.show('Клиент удалён!')
      emit('delete', clientId)
      emit('close')
    })
  }
}
</script>
