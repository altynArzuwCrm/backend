import { OrderController } from '@/controllers/OrderController'

export function useOrderListLoadOrders() {
  const { fetchOrders } = OrderController()
  return {
    loadOrders: (
      page = 1,
      sortBy = 'id',
      sortOrder = 'asc',
      selectedStage = '',
      selectedArchive = '',
    ) => {
      const isArchived =
        selectedArchive === 'archived' ? true : selectedArchive === 'active' ? false : undefined

      fetchOrders(page, sortBy, sortOrder, selectedStage || undefined, isArchived)
    },
  }
}
