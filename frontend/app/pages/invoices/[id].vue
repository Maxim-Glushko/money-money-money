<script setup lang="ts">
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import type { Invoice } from '~/types/invoice'

const route = useRoute()
const { getInvoice, updateInvoice } = useInvoiceApi()

const invoice = ref<Invoice | null>(null)
const loading = ref(false)
const loadError = ref<string | null>(null)
const saveSuccess = ref(false)
const saveError = ref<string | null>(null)

async function load() {
  loading.value = true
  loadError.value = null
  try {
    invoice.value = await getInvoice(route.params.id as string)
  } catch {
    loadError.value = 'Інвойс не знайдено або сталася помилка сервера.'
  } finally {
    loading.value = false
  }
}

onMounted(load)

// --- Form ---
const schema = toTypedSchema(
  z.object({
    net_amount: z.coerce.number({ invalid_type_error: 'Введіть число' }).positive('Має бути > 0'),
    vat_amount: z.coerce.number({ invalid_type_error: 'Введіть число' }).min(0, 'Має бути ≥ 0'),
    due_date: z.string().min(1, 'Обов\'язкове поле'),
  })
)

const { handleSubmit, defineField, errors, isSubmitting, resetForm } = useForm({
  validationSchema: schema,
})

// Populate form when invoice loads
watch(invoice, (inv) => {
  if (!inv) return
  resetForm({
    values: {
      net_amount: Number(inv.net_amount),
      vat_amount: Number(inv.vat_amount),
      due_date: inv.due_date.substring(0, 10),
    },
  })
})

const [netAmount, netAmountAttrs] = defineField('net_amount')
const [vatAmount, vatAmountAttrs] = defineField('vat_amount')
const [dueDate, dueDateAttrs] = defineField('due_date')

const grossAmount = computed(() => {
  const net = Number(netAmount.value) || 0
  const vat = Number(vatAmount.value) || 0
  return (net + vat).toFixed(2)
})

const isPending = computed(() => invoice.value?.status === 'pending')

const onSubmit = handleSubmit(async (values) => {
  saveSuccess.value = false
  saveError.value = null
  try {
    invoice.value = await updateInvoice(route.params.id as string, {
      net_amount: values.net_amount,
      vat_amount: values.vat_amount,
      gross_amount: Number(grossAmount.value),
      due_date: values.due_date,
    })
    saveSuccess.value = true
  } catch (err: unknown) {
    const e = err as { data?: { errors?: Record<string, string[]>; message?: string } }
    if (e?.data?.errors) {
      const first = Object.values(e.data.errors)[0]?.[0]
      saveError.value = first ?? 'Помилка збереження'
    } else {
      saveError.value = e?.data?.message ?? 'Помилка збереження'
    }
  }
})

// --- Helpers ---
function formatDate(date: string) {
  return new Date(date).toLocaleDateString('uk-UA', {
    day: '2-digit', month: '2-digit', year: 'numeric',
  })
}

function formatDatetime(date: string) {
  return new Date(date).toLocaleString('uk-UA', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function formatAmount(amount: string, currency: string) {
  return new Intl.NumberFormat('uk-UA', { style: 'currency', currency }).format(Number(amount))
}
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-8">
    <!-- Back -->
    <NuxtLink to="/invoices" class="mb-6 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800">
      ← Назад до списку
    </NuxtLink>

    <!-- Loading -->
    <div v-if="loading" class="mt-6 space-y-4">
      <div class="h-8 w-48 animate-pulse rounded bg-gray-200" />
      <div class="h-64 animate-pulse rounded-lg bg-gray-200" />
    </div>

    <!-- Load error -->
    <div v-else-if="loadError" class="mt-6 rounded-md bg-red-50 p-4 text-sm text-red-700">
      {{ loadError }}
      <button class="ml-2 underline" @click="load">Спробувати знову</button>
    </div>

    <template v-else-if="invoice">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ invoice.number }}</h1>
          <p class="mt-1 text-sm text-gray-500">Оновлено {{ formatDatetime(invoice.updated_at) }}</p>
        </div>
        <InvoiceStatusBadge :status="invoice.status" />
      </div>

      <!-- Info card -->
      <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <dl class="divide-y divide-gray-100">
          <div class="grid grid-cols-3 gap-4 px-6 py-4">
            <dt class="text-sm font-medium text-gray-500">Постачальник</dt>
            <dd class="col-span-2 text-sm text-gray-900">{{ invoice.supplier_name }}</dd>
          </div>
          <div class="grid grid-cols-3 gap-4 px-6 py-4">
            <dt class="text-sm font-medium text-gray-500">ЄДРПОУ / ІПН</dt>
            <dd class="col-span-2 text-sm text-gray-900">{{ invoice.supplier_tax_id }}</dd>
          </div>
          <div class="grid grid-cols-3 gap-4 px-6 py-4">
            <dt class="text-sm font-medium text-gray-500">Сума без ПДВ</dt>
            <dd class="col-span-2 text-sm text-gray-900">{{ formatAmount(invoice.net_amount, invoice.currency) }}</dd>
          </div>
          <div class="grid grid-cols-3 gap-4 px-6 py-4">
            <dt class="text-sm font-medium text-gray-500">ПДВ</dt>
            <dd class="col-span-2 text-sm text-gray-900">{{ formatAmount(invoice.vat_amount, invoice.currency) }}</dd>
          </div>
          <div class="grid grid-cols-3 gap-4 px-6 py-4">
            <dt class="text-sm font-medium text-gray-500">Сума до сплати</dt>
            <dd class="col-span-2 text-sm font-semibold text-gray-900">{{ formatAmount(invoice.gross_amount, invoice.currency) }}</dd>
          </div>
          <div class="grid grid-cols-3 gap-4 px-6 py-4">
            <dt class="text-sm font-medium text-gray-500">Дата виставлення</dt>
            <dd class="col-span-2 text-sm text-gray-900">{{ formatDate(invoice.issue_date) }}</dd>
          </div>
          <div class="grid grid-cols-3 gap-4 px-6 py-4">
            <dt class="text-sm font-medium text-gray-500">Термін оплати</dt>
            <dd class="col-span-2 text-sm text-gray-900">{{ formatDate(invoice.due_date) }}</dd>
          </div>
          <div class="grid grid-cols-3 gap-4 px-6 py-4">
            <dt class="text-sm font-medium text-gray-500">Валюта</dt>
            <dd class="col-span-2 text-sm text-gray-900">{{ invoice.currency }}</dd>
          </div>
        </dl>
      </div>

      <!-- Edit form -->
      <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4">
          <h2 class="text-base font-semibold text-gray-900">Редагування</h2>
          <p v-if="!isPending" class="mt-1 text-sm text-gray-500">
            Редагування доступне лише для інвойсів зі статусом «Очікує».
          </p>
        </div>

        <form class="px-6 py-4" @submit.prevent="onSubmit">
          <fieldset :disabled="!isPending" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <!-- net_amount -->
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                  Сума без ПДВ
                </label>
                <input
                  v-model="netAmount"
                  v-bind="netAmountAttrs"
                  type="number"
                  step="0.01"
                  min="0.01"
                  class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:bg-gray-50 disabled:text-gray-400"
                />
                <p v-if="errors.net_amount" class="mt-1 text-xs text-red-600">{{ errors.net_amount }}</p>
              </div>

              <!-- vat_amount -->
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                  ПДВ
                </label>
                <input
                  v-model="vatAmount"
                  v-bind="vatAmountAttrs"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:bg-gray-50 disabled:text-gray-400"
                />
                <p v-if="errors.vat_amount" class="mt-1 text-xs text-red-600">{{ errors.vat_amount }}</p>
              </div>
            </div>

            <!-- gross_amount (readonly, computed) -->
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">
                Сума до сплати (розраховується автоматично)
              </label>
              <input
                :value="grossAmount"
                type="number"
                readonly
                class="w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500"
              />
            </div>

            <!-- due_date -->
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">
                Термін оплати
              </label>
              <input
                v-model="dueDate"
                v-bind="dueDateAttrs"
                type="date"
                :min="invoice.issue_date.substring(0, 10)"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:bg-gray-50 disabled:text-gray-400"
              />
              <p v-if="errors.due_date" class="mt-1 text-xs text-red-600">{{ errors.due_date }}</p>
            </div>
          </fieldset>

          <!-- Feedback -->
          <div v-if="saveSuccess" class="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-700">
            Інвойс успішно збережено.
          </div>
          <div v-if="saveError" class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
            {{ saveError }}
          </div>

          <div class="mt-4">
            <button
              v-if="isPending"
              type="submit"
              :disabled="isSubmitting"
              class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
            >
              {{ isSubmitting ? 'Збереження...' : 'Зберегти' }}
            </button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>
