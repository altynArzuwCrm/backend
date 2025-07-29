import { ProductController } from './ProductController'
const instance = ProductController()
export default instance
export type ProductControllerType = typeof instance
