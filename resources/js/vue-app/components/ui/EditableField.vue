<template>
  <div class="relative group w-full">
    <div v-if="!editing" class="flex items-center min-h-[32px]">
      <span class="truncate w-full" :title="String(modelValue)">{{ modelValue }}</span>
      <button
        v-if="!editing"
        class="ml-2 opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded hover:bg-gray-100"
        @click="startEdit"
        aria-label="Редактировать"
        tabindex="-1"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-4 w-4 text-gray-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a4 4 0 01-1.414.828l-4 1a1 1 0 01-1.263-1.263l1-4a4 4 0 01.828-1.414z"
          />
        </svg>
      </button>
    </div>
    <div v-else class="flex items-center gap-2 w-full">
      <input
        v-model="editValue"
        :type="type"
        class="border rounded px-2 py-1 text-sm w-full focus:outline-none focus:ring-2 focus:ring-blue-200"
        :placeholder="placeholder"
        @keydown.enter.prevent="saveEdit"
        @keydown.esc.prevent="cancelEdit"
        ref="inputRef"
        :maxlength="maxlength"
        :minlength="minlength"
        :required="required"
        :pattern="pattern"
        :inputmode="getInputMode()"
        :autofocus="true"
      />
      <button
        @click="saveEdit"
        class="p-1 rounded hover:bg-green-100"
        :disabled="!canSave"
        aria-label="Сохранить"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-4 w-4 text-green-500"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 13l4 4L19 7"
          />
        </svg>
      </button>
      <button @click="cancelEdit" class="p-1 rounded hover:bg-red-100" aria-label="Отмена">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-4 w-4 text-red-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>
    </div>
    <div v-if="error" class="text-xs text-red-500 mt-1">{{ error }}</div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue'

function getInputMode(): any {
  return props.inputmode
}

const props = defineProps({
  modelValue: {
    type: [String, Number],
    required: true,
  },
  type: {
    type: String,
    default: 'text',
  },
  placeholder: {
    type: String,
    default: '',
  },
  maxlength: Number,
  minlength: Number,
  required: Boolean,
  pattern: String,
  inputmode: String,
  validator: Function, // (value) => string | null
})

const emit = defineEmits(['update:modelValue', 'save', 'cancel'])

const editing = ref(false)
const editValue = ref(props.modelValue)
const error = ref<string | null>(null)
const inputRef = ref<HTMLInputElement | null>(null)

watch(
  () => props.modelValue,
  (val) => {
    if (!editing.value) editValue.value = val
  },
)

function startEdit() {
  editing.value = true
  editValue.value = props.modelValue
  nextTick(() => {
    inputRef.value?.focus()
    inputRef.value?.select()
  })
}

function cancelEdit() {
  editing.value = false
  editValue.value = props.modelValue
  error.value = null
  emit('cancel')
}

const canSave = computed(() => {
  if (props.required && !editValue.value) return false
  if (props.validator) {
    return !props.validator(editValue.value)
  }
  return true
})

function saveEdit() {
  if (props.validator) {
    const validation = props.validator(editValue.value)
    if (validation) {
      error.value = validation
      return
    }
  }
  if (props.required && !editValue.value) {
    error.value = 'Поле обязательно'
    return
  }
  error.value = null
  emit('update:modelValue', editValue.value)
  emit('save', editValue.value)
  editing.value = false
}
</script>

<style scoped>
.group:hover .group-hover\:opacity-100 {
  opacity: 1;
}
</style>
