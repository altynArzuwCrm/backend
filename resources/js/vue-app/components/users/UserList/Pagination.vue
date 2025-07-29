<template>
  <div class="flex justify-end mt-4 pb-7 pr-2 shrink-0">
    <nav class="flex gap-2">
      <button
        @click="$emit('go-to-page', currentPage - 1)"
        :disabled="currentPage === 1"
        class="px-3 py-1 rounded-full border border-gray-300 bg-white text-gray-900 hover:bg-gray-100 flex items-center"
      >
        ←
      </button>
      <template v-for="page in pagesToShow" :key="page + '-' + currentPage">
        <button
          v-if="typeof page === 'number'"
          @click="$emit('go-to-page', page)"
          :class="[
            'px-3 py-1 rounded-full border',
            page === currentPage
              ? 'border-blue-600 bg-blue-600 text-white font-bold shadow'
              : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-100',
          ]"
        >
          {{ page }}
        </button>
        <span
          v-else
          class="px-3 py-1 text-gray-400 select-none"
          @click="
            $emit(
              'go-to-page',
              page === '...prev'
                ? Math.max(1, currentPage - windowSize)
                : Math.min(lastPage, currentPage + windowSize),
            )
          "
          style="cursor: pointer"
        >
          ...
        </span>
      </template>
      <button
        @click="$emit('go-to-page', currentPage + 1)"
        :disabled="currentPage === lastPage"
        class="px-3 py-1 rounded-full border border-gray-300 bg-white text-gray-900 hover:bg-gray-100 flex items-center"
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
  lastPage: number
}>()
defineEmits<{
  'go-to-page': [page: number]
}>()

const windowSize = 10
const pagesToShow = computed(() => {
  const pages = []
  const windowStart = Math.floor((props.currentPage - 1) / windowSize) * windowSize + 1
  const windowEnd = Math.min(windowStart + windowSize - 1, props.lastPage)
  if (windowStart > 1) pages.push(1)
  if (windowStart > 2) pages.push('...prev')
  for (let i = windowStart; i <= windowEnd; i++) {
    pages.push(i)
  }
  if (windowEnd < props.lastPage - 1) pages.push('...next')
  if (windowEnd < props.lastPage) pages.push(props.lastPage)
  return pages
})
</script>
