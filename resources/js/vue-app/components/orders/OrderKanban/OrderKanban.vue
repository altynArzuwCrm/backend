<template>
  <div class="kanban-board bitrix-bg flex w-full p-0 min-h-[90vh]">
    <div
      v-for="(stageObj, idx) in statuses"
      :key="stageObj.key"
      class="kanban-column bitrix-column flex flex-col max-h-[88vh] p-0 relative flex-1 min-w-0"
      :class="{
        'with-divider': idx !== statuses.length - 1,
        'kanban-column--dragover': dragOverStage === stageObj.key,
        'first-col': idx === 0,
        'last-col': idx === statuses.length - 1,
      }"
      :style="{ borderLeft: idx !== 0 ? '1px solid #ece6f6' : 'none' }"
    >
      <!-- Цветная линия сверху -->
      <div class="bitrix-top-bar" :style="{ background: getStatusColor(stageObj.key) }"></div>
      <div
        class="kanban-column-header bitrix-header flex items-center justify-between px-3 py-2 font-bold relative"
        :class="{ 'first-col-header': idx === 0, 'last-col-header': idx === statuses.length - 1 }"
        :style="{ background: getStatusColor(stageObj.key) }"
      >
        <span class="bitrix-header-title">{{ stageObj.label }}</span>
        <!-- Цветная плашка-счетчик -->
        <span
          class="bitrix-counter-badge"
          :style="{
            borderColor: getStatusColor(stageObj.key),
            color: getStatusColor(stageObj.key),
          }"
        >
          {{ ordersByStage(stageObj.key).length }}
        </span>
      </div>
      <!-- Кнопка '+ Добавить заказ' только в Черновик и только для администраторов/менеджеров -->
      <button
        v-if="stageObj.key === 'draft' && canCreateEdit()"
        class="bitrix-add-btn"
        @click="$emit('add-order', stageObj.key)"
      >
        <span class="plus-icon">+</span> Добавить заказ
      </button>
      <div
        class="kanban-cards flex flex-col gap-0 p-1 overflow-y-auto min-h-[40px] transition-all"
        :id="'col-' + stageObj.key"
        @dragover.prevent
        @drop="onDrop($event, stageObj.key)"
        @dragenter="onDragEnter(stageObj.key)"
        @dragleave="onDragLeave(stageObj.key)"
        :class="{ 'kanban-cards--dragover': dragOverStage === stageObj.key }"
      >
        <slot name="add-card" :stage="stageObj.key"></slot>
        <div
          v-for="order in ordersByStage(stageObj.key)"
          :key="order.id"
          class="kanban-order-card-wrapper bitrix-card-wrapper cursor-move transition"
          :class="{ dragging: draggingOrder && draggingOrder.id === order.id }"
          draggable="true"
          @dragstart="onDragStart(order)"
          @dragend="onDragEnd"
        >
          <slot name="card" :order="order">
            <OrderCard
              :order="order"
              :dragging="draggingOrder && draggingOrder.id === order.id"
              @click="handleOrderCardClick"
            />
          </slot>
        </div>
      </div>
    </div>
    <div v-if="toastMsg" class="fixed top-4 right-4 z-[9999]">
      <div
        :class="[
          'mb-2 px-4 py-2 rounded shadow text-white',
          toastType === 'error' ? 'bg-red-500' : 'bg-green-500',
        ]"
      >
        {{ toastMsg }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import OrderCard from './OrderCard.vue'
import { OrderController } from '../../../controllers/OrderController'
import { canCreateEdit } from '@/utils/permissions'
import { API_CONFIG } from '../../../config/api'

const { orders } = OrderController()
const props = defineProps<{
  statuses: { key: string; label: string }[]
  orders: any[]
}>()
const emit = defineEmits<{
  (e: 'update:orders'): void
  (e: 'open-order', payload: any): void
  (e: 'add-order', stage: string): void
  (e: 'updated'): void
  (e: 'order-updated', orderId: string): void
}>()

function handleOrderCardClick(order: any) {
  emit('open-order', { order })
}

const draggingOrder = ref<any>(null)
const dragOverStage = ref<string | null>(null)
const toastMsg = ref('')
const toastType = ref<'success' | 'error'>('success')
let toastTimeout: any = null
let pollingInterval: any = null

onMounted(() => {
  OrderController().fetchAllOrdersForKanban()
  pollingInterval = setInterval(() => {
    OrderController().fetchAllOrdersForKanban()
  }, 7000)
})

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval)
})

function showToast(msg: string, type: 'success' | 'error' = 'success') {
  toastMsg.value = msg
  toastType.value = type
  clearTimeout(toastTimeout)
  toastTimeout = setTimeout(() => {
    toastMsg.value = ''
  }, 2200)
}

function ordersByStage(stage: string) {
  return Array.isArray(props.orders) ? props.orders.filter((order) => order.stage === stage) : []
}
function getStatusColor(key: string) {
  switch (key) {
    case 'draft':
      return '#d1d5db'
    case 'design':
      return '#3b82f6'
    case 'print':
      return '#fbbf24'
    case 'engraving':
      return '#f97316'
    case 'workshop':
      return '#8b5cf6'
    case 'final':
      return '#22c55e'
    case 'completed':
      return '#059669'
    case 'cancelled':
      return '#ef4444'
    default:
      return '#6366f1'
  }
}
function onDragStart(order: any) {
  console.log('dragStart', order)
  draggingOrder.value = order
}
function onDragEnd() {
  draggingOrder.value = null
}
function onDragEnter(stage: string) {
  dragOverStage.value = stage
}
function onDragLeave(stage: string) {
  if (dragOverStage.value === stage) dragOverStage.value = null
}
async function onDrop(event: DragEvent, newStage: string) {
  event.preventDefault()
  dragOverStage.value = null
  console.log('DROP', draggingOrder.value, newStage)
  const order = draggingOrder.value
  draggingOrder.value = null
  if (!order) {
    console.log('NO ORDER')
    return
  }
  if (order.stage === newStage) {
    console.log('SAME STAGE', order.stage, newStage)
    return
  }
  console.log('GO TO REQUEST', order.id, '->', newStage)
  const payload: any = { stage: newStage }
  if (newStage === 'cancelled') {
    payload.reason = 'Отменено через kanban'
    payload.reason_status = 'refused'
  }
  try {
    await OrderController().updateStage(order.id, payload)
    // Загружаем все заказы для канбан доски
    await OrderController().fetchAllOrdersForKanban()
    showToast('Статус обновлён: ' + newStage, 'success')
    emit('update:orders')
    emit('updated') // Эмитим событие обновления
    emit('order-updated', order.id) // Эмитим id заказа для обновления деталей, если открыт OrderDetailsModal
  } catch (err: any) {
    const msg = err?.message || 'Ошибка смены стадии'

    // Проверяем, связана ли ошибка с отсутствием назначений
    if (
      msg.includes('дизайнер') ||
      msg.includes('печатник') ||
      msg.includes('цех') ||
      msg.includes('назначен')
    ) {
      showToast('Назначьте сотрудника для перехода на этот этап', 'error')
      emit('open-order', {
        order,
        highlightAssignments: true,
        message: `Необходимо назначить сотрудника на этап "${getStatusLabel(newStage)}"`,
      })
    } else {
      showToast(msg, 'error')
    }
  }
}

function getStatusLabel(stage: string): string {
  switch (stage) {
    case 'design':
      return 'Дизайн'
    case 'print':
      return 'Печать'
    case 'engraving':
      return 'Гравировка'
    case 'workshop':
      return 'Цех'
    default:
      return stage
  }
}
</script>

<style scoped>
.kanban-board.bitrix-bg {
  background: transparent;
  min-height: 90vh;
  border-radius: 0;
  box-shadow: none;
  gap: 0;
  width: 100%;
  overflow-x: auto;
  overflow-y: hidden;
  display: flex;
  flex-wrap: nowrap;
  min-width: 100vw;
}
.bitrix-column {
  background: linear-gradient(135deg, #f7eafd 0%, #f5f3ff 100%);
  border-radius: 0;
  box-shadow: none;
  display: flex;
  flex-direction: column;
  margin-bottom: 0;
  transition:
    box-shadow 0.12s,
    transform 0.12s;
  position: relative;
  min-width: 140px;
  max-width: 280px;
}
.bitrix-column.first-col {
  border-radius: 10px 0 0 0;
}
.bitrix-column.last-col {
  border-radius: 0 10px 0 0;
}
.kanban-order-card-wrapper,
.bitrix-card-wrapper {
  display: block;
  width: 100%;
  min-width: 0;
  max-width: 100%;
}
.bitrix-top-bar {
  height: 5px;
  border-radius: 10px 10px 0 0;
  width: 100%;
  margin-bottom: -5px;
}
.kanban-column--dragover {
  box-shadow:
    0 0 0 2px #6366f1aa,
    0 2px 8px #0002;
  transform: scale(1.01);
  z-index: 2;
}
.bitrix-header {
  border-radius: 0;
  color: #fff;
  font-size: 1em;
  font-weight: 600;
  letter-spacing: 0.01em;
  background: #6366f1;
  min-height: 38px;
  box-shadow: none;
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.first-col-header {
  border-radius: 10px 0 0 0;
}
.last-col-header {
  border-radius: 0 10px 0 0;
}
.bitrix-header-title {
  font-weight: 600;
  font-size: 1em;
  text-shadow: none;
}
.bitrix-counter-badge {
  background: #fff;
  border: 1.5px solid;
  border-radius: 8px;
  font-size: 0.92em;
  font-weight: 700;
  min-width: 22px;
  text-align: center;
  padding: 0 6px;
  margin-left: 8px;
  box-shadow: 0 1px 3px #0001;
  z-index: 2;
  display: flex;
  align-items: center;
  height: 22px;
  position: static;
}
.bitrix-add-btn {
  margin: 8px 8px 4px 8px;
  padding: 4px 12px;
  border: 1.5px dashed #bdbdbd;
  border-radius: 8px;
  background: #fafdff;
  color: #6366f1;
  font-weight: 600;
  font-size: 0.97em;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition:
    background 0.13s,
    border 0.13s;
}
.bitrix-add-btn:hover {
  background: #f0f4ff;
  border-color: #6366f1;
}
.plus-icon {
  font-size: 1.2em;
  font-weight: 700;
  margin-right: 2px;
}
.kanban-cards {
  display: block;
  max-height: calc(90vh - 60px);
  min-height: 40px;
  overflow-y: auto;
  transition: background 0.12s;
  background: transparent;
}
.kanban-order-card-wrapper {
  transition:
    box-shadow 0.18s,
    transform 0.18s,
    background 0.18s;
}
.kanban-order-card-wrapper.dragging {
  opacity: 0.7;
  transform: scale(1.04) rotate(-1deg);
  box-shadow:
    0 8px 32px #6366f155,
    0 2px 8px #0002;
  z-index: 10;
}
.kanban-cards--dragover {
  background: #e0e7ff44 !important;
  border-radius: 8px;
  box-shadow: 0 0 0 2px #a5b4fc55;
  transition:
    background 0.18s,
    box-shadow 0.18s;
}
.bitrix-card-wrapper {
  background: #fff;
  border-radius: 6px;
  box-shadow: 0 1px 3px #0001;
  margin-bottom: 3px;
  transition:
    box-shadow 0.1s,
    transform 0.1s;
  will-change: box-shadow, transform;
  padding: 0;
  min-height: 38px;
  display: flex;
  align-items: stretch;
}
.bitrix-card-wrapper:hover {
  box-shadow: 0 2px 8px #0002;
  transform: translateY(-1px) scale(1.01);
}
.bitrix-card-inner {
  padding: 6px 8px 5px 8px;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1px;
  font-size: 0.97em;
}
.kanban-column-header.bitrix-header {
  position: sticky;
  top: 0;
  z-index: 2;
}
.bitrix-add-btn {
  position: sticky;
  top: 38px; /* высота заголовка */
  z-index: 2;
}
@media (max-width: 900px) {
  .kanban-board.bitrix-bg {
    overflow-x: auto;
    flex-wrap: nowrap !important;
    min-width: 0;
  }
  .bitrix-column {
    min-width: 260px;
    max-width: 340px;
    flex: 0 0 80vw;
  }
  .kanban-order-card-wrapper,
  .bitrix-card-wrapper {
    min-width: 0;
    max-width: 100%;
    width: 100%;
  }
}
</style>
