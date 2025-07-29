<template>
  <div v-if="visible" class="modal-overlay" @click="$emit('close')">
    <div class="modal-container" @click.stop>
      <header class="modal-header">
        <slot name="header">
          <h3 class="modal-title">Заголовок</h3>
        </slot>
        <button @click="$emit('close')" class="modal-close" aria-label="Закрыть">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </header>

      <section class="modal-body">
        <slot />
      </section>

      <footer v-if="$slots.footer" class="modal-footer">
        <slot name="footer" />
      </footer>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps({ visible: { type: Boolean, default: true } })
</script>

export default { name: 'Modal', }

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  animation: fade-in 0.25s ease;
  backdrop-filter: blur(3px);
}

.modal-container {
  background: white;
  color: #1e293b;
  border-radius: 1rem;
  width: 90vw;
  max-width: 800px;
  max-height: 85vh;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  animation: scale-in 0.3s ease;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  background: linear-gradient(to right, #f8fafc, #fff);
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 600;
}

.modal-close {
  background: transparent;
  border: none;
  cursor: pointer;
  color: #64748b;
  transition: color 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close:hover {
  color: #1e293b;
}

.modal-body {
  padding: 1.5rem;
  background: white;
  overflow-y: auto;
  flex: 1;
  max-height: calc(85vh - 120px);
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e2e8f0;
  background: #f9fafb;
  text-align: right;
}

@keyframes fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes scale-in {
  from {
    transform: scale(0.96);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}
</style>
