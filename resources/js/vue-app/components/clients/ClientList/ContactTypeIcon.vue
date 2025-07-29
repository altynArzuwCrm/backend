<template>
  <IconPhone stroke="{2}" v-if="type === 'phone'" class="w-5 h-5 text-blue-500" />
  <IconMail stroke="{2}" v-else-if="type === 'email'" class="w-5 h-5 text-red-500" />
  <IconBrandTelegram stroke="{2}" v-else-if="type === 'telegram'" class="w-5 h-5 text-blue-400" />
  <IconBrandWhatsapp stroke="{2}" v-else-if="type === 'whatsapp'" class="w-5 h-5 text-green-500" />
  <IconBrandInstagram
    stroke="{1.25}"
    v-else-if="type === 'instagram'"
    class="w-5 h-5 text-pink-500"
  />
  <IconLink stroke="{2}" v-else class="w-5 h-5 text-gray-400" />
</template>

<script setup lang="ts">
import { defineProps } from 'vue'
import { IconPhone } from '@tabler/icons-vue'
import { IconMail } from '@tabler/icons-vue'
import { IconBrandTelegram } from '@tabler/icons-vue'
import { IconBrandWhatsapp } from '@tabler/icons-vue'
import { IconBrandInstagram } from '@tabler/icons-vue'
import { IconLink } from '@tabler/icons-vue'

defineProps<{ type: string }>()
</script>
