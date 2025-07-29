import { OrderController } from './OrderController'
const instance = OrderController()
export default instance
export type OrderControllerType = typeof instance
