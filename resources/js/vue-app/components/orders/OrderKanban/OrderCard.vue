<template>
  <div
    class="order-card bitrix-order-card"
    :class="[deadlineClass, stageClass, cardBgClass, { compact }]"
    @click="handleCardClick"
  >
    <div class="order-card-content">
      <div class="order-id-badge">#{{ order.id }}</div>
      <div class="order-card-header">
        <span
          class="order-title"
          :title="order.title || order.product?.name || `Заказ #${order.id}`"
        >
          {{ order.title || order.product?.name || `Заказ #${order.id}` }}
        </span>
      </div>
      <div v-if="canViewPrices()" class="order-amount">
        {{ order.price ? order.price + ' TMT' : '-' }}
      </div>
      <div v-if="order.deadline" class="order-date-row" :class="deadlineClass">
        <svg class="calendar-icon" viewBox="0 0 16 16" width="14" height="14">
          <path
            fill="currentColor"
            d="M4 2a1 1 0 1 1 2 0h4a1 1 0 1 1 2 0h1a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h1zm0 2H3v8h10V4h-1v1a1 1 0 1 1-2 0V4H6v1a1 1 0 1 1-2 0V4zm2 2v2h4V6H6zm0 3v2h4V9H6z"
          />
        </svg>
        {{ formatDeadline(order.deadline) }}
      </div>
      <div class="order-info">
        <div class="order-row">
          <span
            class="order-value truncate max-w-[180px]"
            :title="order.client?.company_name || '-'"
          >
            {{ order.client?.company_name || '-' }}
          </span>
        </div>
        <div class="order-row">
          <span class="order-value order-status">{{ getStageLabel(order.stage) }}</span>
        </div>
      </div>
      <div v-if="order.description" class="order-description">
        {{ order.description }}
      </div>
      <span class="order-card-menu"><span class="menu-dots">⋮</span></span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { canViewPrices } from '@/utils/permissions'

const props = defineProps<{ order: any; compact?: boolean; dragging?: boolean }>()
const emit = defineEmits<{
  (e: 'click', order: any): void
}>()

function handleCardClick() {
  if (!props.dragging) {
    emit('click', props.order)
  }
}

function formatDeadline(deadline: string) {
  if (!deadline) return '—'
  const date = new Date(deadline)
  return date.toLocaleDateString('ru-RU', { day: '2-digit', month: 'short', year: 'numeric' })
}

function getStageLabel(stage: string) {
  switch (stage) {
    case 'draft':
      return 'Черновик'
    case 'design':
      return 'Дизайн'
    case 'print':
      return 'Печать'
    case 'engraving':
      return 'Гравировка'
    case 'workshop':
      return 'Цех'
    case 'final':
      return 'Финальный'
    case 'completed':
      return 'Завершён'
    case 'cancelled':
      return 'Отменён'
    default:
      return stage
  }
}

const deadlineClass = computed(() => {
  if (!props.order.deadline) return ''
  const now = new Date()
  const deadline = new Date(props.order.deadline)
  if (deadline < now) return 'overdue'
  const diff = (deadline.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)
  if (diff < 3) return 'soon'
  return ''
})

const cardBgClass = computed(() => {
  if (!props.order.deadline) return ''
  const now = new Date()
  const deadline = new Date(props.order.deadline)
  if (deadline < now) return 'kanban-deadline-overdue'
  const diff = (deadline.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)
  if (diff < 3) return 'kanban-deadline-soon'
  return ''
})

const stageClass = computed(() => {
  switch (props.order.stage) {
    case 'draft':
      return 'stage-draft'
    case 'design':
      return 'stage-design'
    case 'print':
      return 'stage-print'
    case 'engraving':
      return 'stage-engraving'
    case 'workshop':
      return 'stage-workshop'
    case 'final':
      return 'stage-final'
    case 'completed':
      return 'stage-completed'
    case 'cancelled':
      return 'stage-cancelled'
    default:
      return 'stage-default'
  }
})
</script>

export default { name: 'OrderCard' }

<style scoped>
.order-card.bitrix-order-card {
  background: #fff;
  border-radius: 4px;
  box-shadow: 0 1px 2px #0001;
  border: 1px solid #ececec;
  display: flex;
  flex-direction: column;
  min-height: 40px;
  margin-bottom: 4px;
  transition:
    box-shadow 0.18s,
    transform 0.18s,
    background 0.18s;
  will-change: box-shadow, transform, background;
  font-size: 0.93em;
  position: relative;
  padding: 0;
  cursor: pointer;
  align-items: stretch;
  width: 100%;
  max-width: 100%;
  overflow: hidden;
}
.order-card.bitrix-order-card:hover {
  box-shadow:
    0 6px 24px #6366f122,
    0 2px 8px #0002;
  transform: scale(1.03) translateY(-2px);
  background: #f8fafc;
}
.kanban-order-card-wrapper.dragging .order-card.bitrix-order-card {
  opacity: 0.7;
  transform: scale(1.04) rotate(-1deg);
  box-shadow:
    0 8px 32px #6366f155,
    0 2px 8px #0002;
  background: #f3f4f6;
  pointer-events: none;
}
.order-card.bitrix-order-card.dragging {
  opacity: 0.7;
  transform: scale(1.04) rotate(-1deg);
  box-shadow:
    0 8px 32px #6366f155,
    0 2px 8px #0002;
  background: #f3f4f6;
  pointer-events: none;
}
.order-card.bitrix-order-card::before {
  content: '';
  display: block;
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 5px;
  border-radius: 6px 0 0 6px;
  background: #ece6f6;
}
.stage-draft.order-card.bitrix-order-card::before {
  background: #d1d5db;
}
.stage-design.order-card.bitrix-order-card::before {
  background: #3b82f6;
}
.stage-print.order-card.bitrix-order-card::before {
  background: #fbbf24;
}
.stage-workshop.order-card.bitrix-order-card::before {
  background: #8b5cf6;
}
.stage-final.order-card.bitrix-order-card::before {
  background: #22c55e;
}
.stage-completed.order-card.bitrix-order-card::before {
  background: #059669;
}
.stage-cancelled.order-card.bitrix-order-card::before {
  background: #ef4444;
}
.stage-engraving.order-card.bitrix-order-card::before {
  background: #f97316;
}
.stage-default.order-card.bitrix-order-card::before {
  background: #6366f1;
}
.order-card-content {
  padding: 6px 8px 6px 10px;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}
.order-id-badge {
  position: absolute;
  top: 6px;
  right: 8px;
  z-index: 2;
  background-color: #f3f4f6;
  border-radius: 3px;
  padding: 1px 5px;
  font-size: 0.75em;
  font-weight: 600;
  color: #4b5563;
  border: 1px solid #e5e7eb;
}
.order-card-header {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  margin-bottom: 2px;
  gap: 0;
  width: 100%;
}
.order-title {
  font-weight: 600;
  font-size: 0.97em;
  color: #22223b;
  flex: 1;
  margin-right: 0;
  line-height: 1.13;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  white-space: normal;
}
.order-amount {
  color: #059669;
  font-weight: 700;
  font-size: 1em;
  margin: 2px 0 0 0;
  white-space: nowrap;
}
.order-date-row {
  display: flex;
  align-items: center;
  gap: 4px;
  font-weight: 600;
  font-size: 0.97em;
  margin: 2px 0 0 0;
}
.order-date-row.overdue {
  color: #b91c1c;
}
.order-date-row.soon {
  color: #b45309;
}
.order-date-row:not(.overdue):not(.soon) {
  color: #6366f1;
}
.calendar-icon {
  margin-right: 2px;
  color: inherit;
}
.order-info {
  display: flex;
  flex-direction: column;
  gap: 0;
  align-items: flex-start;
  margin-top: 1px;
}
.order-row {
  display: flex;
  font-size: 0.91em;
  gap: 3px;
}
.order-value {
  color: #22223b;
  font-weight: 500;
}
.order-status {
  color: #6366f1;
  font-weight: 600;
}
.order-description {
  color: #bdbdbd;
  font-size: 0.89em;
  margin-top: 1px;
  line-height: 1.1;
  max-height: 2em;
  overflow: hidden;
  text-overflow: ellipsis;
}
.order-card-menu {
  position: absolute;
  top: 6px;
  right: 8px;
  z-index: 2;
}
.menu-dots {
  font-size: 1em;
  color: #bdbdbd;
  cursor: pointer;
  user-select: none;
  transition: color 0.15s;
}
.menu-dots:hover {
  color: #6366f1;
}
/* Цвет фона карточки в зависимости от дедлайна */
.kanban-deadline-overdue.order-card.bitrix-order-card {
  background: #fee2e2 !important; /* красный (light) */
}
.kanban-deadline-soon.order-card.bitrix-order-card {
  background: #fef9c3 !important; /* жёлтый (light) */
}
</style>
