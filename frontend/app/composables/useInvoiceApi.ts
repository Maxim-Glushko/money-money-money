import type { Invoice, PaginatedInvoices, UpdateInvoicePayload } from '~/types/invoice'

export function useInvoiceApi() {
  const config = useRuntimeConfig()
  const base = config.public.apiBase as string

  async function getInvoices(page = 1): Promise<PaginatedInvoices> {
    return $fetch<PaginatedInvoices>(`${base}/invoices`, { params: { page } })
  }

  async function getInvoice(id: string): Promise<Invoice> {
    return $fetch<Invoice>(`${base}/invoices/${id}`)
  }

  async function updateInvoice(id: string, payload: UpdateInvoicePayload): Promise<Invoice> {
    return $fetch<Invoice>(`${base}/invoices/${id}`, {
      method: 'PUT',
      body: payload,
    })
  }

  return { getInvoices, getInvoice, updateInvoice }
}
