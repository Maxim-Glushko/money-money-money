<script setup lang="ts">
import type { PaginatedInvoices } from '~/types/invoice'

const { getInvoices } = useInvoiceApi()

const page = ref(1)
const result = ref<PaginatedInvoices | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    result.value = await getInvoices(page.value)
  } catch {
    error.value = 'Не вдалося завантажити інвойси. Перевірте з\'єднання з сервером.'
  } finally {
    loading.value = false
  }
}

async function goToPage(p: number) {
  page.value = p
  await load()
}

// Показуємо max 7 кнопок: завжди першу, останню і 2 навколо поточної
const pageNumbers = computed(() => {
  if (!result.value) return []
  const last = result.value.last_page
  const cur  = result.value.current_page
  const pages = new Set<number | '...'>()

  pages.add(1)
  pages.add(last)
  for (let i = Math.max(2, cur - 2); i <= Math.min(last - 1, cur + 2); i++) pages.add(i)

  const sorted = [...pages].sort((a, b) => (a as number) - (b as number))

  const result2: (number | '...')[] = []
  for (let i = 0; i < sorted.length; i++) {
    result2.push(sorted[i])
    if (i + 1 < sorted.length && (sorted[i + 1] as number) - (sorted[i] as number) > 1) {
      result2.push('...')
    }
  }
  return result2
})

onMounted(load)

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('uk-UA', {
    day: '2-digit', month: '2-digit', year: 'numeric',
  })
}

function formatAmount(amount: string, currency: string) {
  return new Intl.NumberFormat('uk-UA', { style: 'currency', currency }).format(Number(amount))
}
</script>

<template>
  <div class="mx-auto max-w-6xl px-4 py-8">
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Інвойси</h1>

    <!-- Error -->
    <div v-if="error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">
      {{ error }}
      <button class="ml-2 underline" @click="load">Спробувати знову</button>
    </div>

    <!-- Loading skeleton -->
    <div v-else-if="loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="h-12 animate-pulse rounded-md bg-gray-200" />
    </div>

    <!-- Table -->
    <template v-else-if="result">
      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Номер</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Постачальник</th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Сума</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Статус</th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Термін оплати</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr
              v-for="invoice in result.data"
              :key="invoice.id"
              class="cursor-pointer transition-colors hover:bg-gray-50"
              @click="navigateTo(`/invoices/${invoice.id}`)"
            >
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                {{ invoice.number }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-700">{{ invoice.supplier_name }}</td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-900">
                {{ formatAmount(invoice.gross_amount, invoice.currency) }}
              </td>
              <td class="whitespace-nowrap px-6 py-4">
                <InvoiceStatusBadge :status="invoice.status" />
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                {{ formatDate(invoice.due_date) }}
              </td>
            </tr>
            <tr v-if="result.data.length === 0">
              <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">Інвойси відсутні</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="result.last_page > 1" class="mt-4 flex items-center justify-between text-sm text-gray-600">
        <span>Всього {{ result.total }} інвойсів</span>
        <div class="flex items-center gap-1">
          <button
            :disabled="result.current_page <= 1"
            class="rounded border px-3 py-1.5 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed"
            @click="goToPage(result!.current_page - 1)"
          >
            ←
          </button>

          <template v-for="p in pageNumbers" :key="p">
            <span v-if="p === '...'" class="px-2 py-1.5 text-gray-400">…</span>
            <button
              v-else
              :class="[
                'min-w-[2rem] rounded border px-3 py-1.5',
                p === result.current_page
                  ? 'bg-blue-600 border-blue-600 text-white font-medium'
                  : 'hover:bg-gray-100'
              ]"
              @click="goToPage(p as number)"
            >
              {{ p }}
            </button>
          </template>

          <button
            :disabled="result.current_page >= result.last_page"
            class="rounded border px-3 py-1.5 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed"
            @click="goToPage(result!.current_page + 1)"
          >
            →
          </button>
        </div>
      </div>
    </template>
  </div>
</template>
