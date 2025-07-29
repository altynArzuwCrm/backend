<template>
  <Layout>
    <template #content>
      <div class="p-6">
        <RoleManager />
      </div>
    </template>
  </Layout>
</template>

<script setup lang="ts">
import Layout from '@/components/layout/Layout.vue'
import RoleManager from '@/components/admin/RoleManager.vue'
</script>
