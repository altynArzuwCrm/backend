<template>
  <div class="input-wrapper">
    <label v-if="label" :for="id" class="block text-sm font-medium text-gray-900 mb-1">
      {{ label }}
    </label>
    <input
      :id="id"
      :type="type"
      :placeholder="placeholder"
      :value="displayValue"
      :disabled="disabled"
      :class="[
        'w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200',
        'text-black placeholder:text-gray-900',
        {
          'opacity-50 cursor-not-allowed': disabled,
          'border-red-500 focus:ring-red-500': error,
        },
      ]"
      @input="handleInput"
      @blur="$emit('blur')"
      @focus="$emit('focus')"
    />
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
defineOptions({
  name: 'UIInput',
})

interface Props {
  modelValue: string | number | null
  label?: string
  placeholder?: string
  type?: 'text' | 'username' | 'password' | 'number'
  disabled?: boolean
  error?: string
  id?: string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  disabled: false,
  id: () => `input-${Math.random().toString(36).substr(2, 9)}`,
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number | null]
  blur: []
  focus: []
}>()

const displayValue = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined) {
    return ''
  }
  return String(props.modelValue)
})

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  const value = target.value

  if (props.type === 'number') {
    if (value === '') {
      emit('update:modelValue', null)
    } else {
      const numValue = parseFloat(value)
      emit('update:modelValue', isNaN(numValue) ? 0 : numValue)
    }
  } else {
    emit('update:modelValue', value)
  }
}
</script>
