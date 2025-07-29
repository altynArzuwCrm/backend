<template>
  <transition name="fade">
    <div
      v-if="visible"
      :class="[
        'fixed top-5 right-5 z-[9999] text-white px-4 py-2 rounded shadow transition-all duration-300',
        type === 'error' ? 'bg-red-500' : 'bg-green-500',
      ]"
    >
      {{ message }}
    </div>
  </transition>
</template>

<script setup lang="ts">
const props = defineProps({
  message: String,
  visible: Boolean,
  type: {
    type: String,
    default: 'success',
  },
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
