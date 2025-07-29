<template>
  <input
    :value="modelValue"
    @input="handleInput"
    :placeholder="placeholder"
    type="text"
    class="px-3 py-1.5 border border-gray-300 rounded bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
    style="min-width: 260px"
  />
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue: string
  placeholder?: string
}>()
const emit = defineEmits(['update:modelValue', 'input'])

function handleInput(e: Event) {
  const value = (e.target as HTMLInputElement).value
  emit('update:modelValue', value)
  emit('input', value)
}
</script>
