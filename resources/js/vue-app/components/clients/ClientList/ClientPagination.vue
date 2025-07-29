<template>
  <div class="flex justify-between items-center mt-4 px-4 py-2 bg-gray-50 rounded-lg">
    <div class="text-sm text-gray-600">
      Показано {{ startIndex + 1 }}-{{ endIndex }} из {{ totalItems }} клиентов
    </div>
    <nav class="flex gap-1">
      <button
        @click="$emit('go-to-page', currentPage - 1)"
        :disabled="currentPage === 1"
        class="px-3 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed flex items-center text-sm"
      >
        ←
      </button>

      <template v-if="totalPages <= 7">
        <button
          v-for="page in totalPages"
          :key="page"
          @click="$emit('go-to-page', page)"
          :class="[
            'px-3 py-2 rounded-md border text-sm',
            page === currentPage
              ? 'border-blue-600 bg-blue-600 text-white font-medium'
              : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
          ]"
        >
          {{ page }}
        </button>
      </template>

      <template v-else>
        <!-- Первая страница -->
        <button
          @click="$emit('go-to-page', 1)"
          :class="[
            'px-3 py-2 rounded-md border text-sm',
            1 === currentPage
              ? 'border-blue-600 bg-blue-600 text-white font-medium'
              : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
          ]"
        >
          1
        </button>

        <!-- Многоточие в начале -->
        <span v-if="startPage > 2" class="px-2 py-2 text-gray-500">...</span>

        <!-- Страницы вокруг текущей -->
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="$emit('go-to-page', page)"
          :class="[
            'px-3 py-2 rounded-md border text-sm',
            page === currentPage
              ? 'border-blue-600 bg-blue-600 text-white font-medium'
              : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
          ]"
        >
          {{ page }}
        </button>

        <span v-if="endPage < totalPages - 1" class="px-2 py-2 text-gray-500">...</span>

        <button
          v-if="totalPages > 1"
          @click="$emit('go-to-page', totalPages)"
          :class="[
            'px-3 py-2 rounded-md border text-sm',
            totalPages === currentPage
              ? 'border-blue-600 bg-blue-600 text-white font-medium'
              : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
          ]"
        >
          {{ totalPages }}
        </button>
      </template>

      <button
        @click="$emit('go-to-page', currentPage + 1)"
        :disabled="currentPage === totalPages"
        class="px-3 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed flex items-center text-sm"
      >
        →
      </button>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  currentPage: number
  totalPages: number
  totalItems: number
  itemsPerPage: number
}>()

defineEmits<{
  'go-to-page': [page: number]
}>()

const startIndex = computed(() => (props.currentPage - 1) * props.itemsPerPage)
const endIndex = computed(() => Math.min(props.currentPage * props.itemsPerPage, props.totalItems))

const startPage = computed(() => Math.max(1, props.currentPage - 2))
const endPage = computed(() => Math.min(props.totalPages, props.currentPage + 2))

const visiblePages = computed(() => {
  const pages = []
  for (let i = startPage.value; i <= endPage.value; i++) {
    if (i > 1 && i < props.totalPages) {
      pages.push(i)
    }
  }
  return pages
})
</script>
