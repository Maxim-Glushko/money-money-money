<script setup lang="ts">
import type { InvoiceStatus } from '~/types/invoice'

const props = defineProps<{ status: InvoiceStatus }>()

const config: Record<InvoiceStatus, { label: string; classes: string }> = {
  pending:  { label: 'Очікує',    classes: 'bg-yellow-100 text-yellow-800' },
  approved: { label: 'Затверджено', classes: 'bg-green-100 text-green-800' },
  rejected: { label: 'Відхилено', classes: 'bg-red-100 text-red-800' },
}
</script>

<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
    :class="config[props.status].classes"
  >
    {{ config[props.status].label }}
  </span>
</template>
