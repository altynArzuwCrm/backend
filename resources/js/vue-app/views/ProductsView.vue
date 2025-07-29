<template>
  <Layout v-slot="{ search }">
    <div class="flex-1 flex flex-col h-full min-h-0 overflow-hidden">
      <ReadOnlyMessage
        v-if="!canCreateEdit()"
        message="Вы можете только просматривать товары. Создание и редактирование доступно только администраторам и менеджерам."
      />
      <ProductList
        :search="search"
        :show-create-modal="showCreateModal"
        @close-create-modal="closeCreateModal"
      />
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import ProductList from '../components/products/ProductList/ProductList.vue'
import Layout from '../components/layout/Layout.vue'
import ReadOnlyMessage from '../components/ui/ReadOnlyMessage.vue'
import { canCreateEdit } from '../utils/permissions'

const route = useRoute()
const search = ref(route.query.search || '')

watch(
  () => route.query.search,
  (val) => {
    search.value = val || ''
  },
)

const showCreateModal = ref(false)

function closeCreateModal() {
  showCreateModal.value = false
}
</script>
