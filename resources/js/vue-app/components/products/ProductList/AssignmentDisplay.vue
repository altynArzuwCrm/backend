<template>
  <div class="space-y-1">
    <div v-if="assignments.length === 0" class="text-sm text-gray-500 italic">Не назначены</div>
    <div v-else class="space-y-1">
      <div
        v-for="assignment in assignments"
        :key="assignment.id"
        class="flex items-center gap-2 text-sm"
      >
        <span class="font-medium text-gray-700">
          {{ assignment.user?.name || 'Неизвестный' }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ProductAssignment } from '@/types/product'

interface Props {
  assignments: ProductAssignment[]
}

const props = defineProps<Props>()

console.log('AssignmentDisplay props:', {
  assignments: props.assignments,
  assignmentsLength: props.assignments.length,
})
</script>
