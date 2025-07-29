<template>
  <Layout>
    <template #content>
      <div class="p-6">
        <StageManager />
      </div>
    </template>
  </Layout>
</template>

<script setup lang="ts">
import Layout from '@/components/layout/Layout.vue'
import StageManager from '@/components/admin/StageManager.vue'
</script>
