import { ref } from 'vue'

const message = ref('')
const visible = ref(false)
const type = ref<'success' | 'error'>('success')
let timeout: ReturnType<typeof setTimeout>

function show(msg: string, toastType: 'success' | 'error' = 'success', duration = 3000) {
  // Форсируем обновление даже если сообщение то же самое
  message.value = ''
  visible.value = false
  setTimeout(() => {
    message.value = msg
    type.value = toastType
    visible.value = true
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      visible.value = false
    }, duration)
  }, 10)
}

export const toast = { message, visible, type, show }

// Add useToast composable for compatibility
export function useToast() {
  return toast
}
