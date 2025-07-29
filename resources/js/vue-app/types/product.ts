export interface ProductAssignment {
  id: number
  user_id: number | null
  user?: { id: number; name: string }
}

export interface Product {
  id: number
  name: string
  designer_id: number | null // Оставляем для обратной совместимости
  print_operator_id: number | null // Оставляем для обратной совместимости
  workshop_worker_id: number | null // Оставляем для обратной совместимости
  has_design_stage: boolean
  has_print_stage: boolean
  has_engraving_stage: boolean
  has_workshop_stage: boolean
  // Новые поля для множественных назначений
  designers?: ProductAssignment[]
  print_operators?: ProductAssignment[]
  engraving_operators?: ProductAssignment[] // Отдельные назначения для гравировки
  workshop_workers?: ProductAssignment[]
  designer?: { id: number; name: string }
  print_operator?: { id: number; name: string }
  workshop_worker?: { id: number; name: string }
  created_at?: string
  updated_at?: string
}

export interface ProductForm {
  name: string
  designer_id: number | null // Оставляем для обратной совместимости
  print_operator_id: number | null // Оставляем для обратной совместимости
  workshop_worker_id: number | null // Оставляем для обратной совместимости
  has_design_stage: boolean
  has_print_stage: boolean
  has_engraving_stage: boolean
  has_workshop_stage: boolean
  // Новые поля для множественных назначений
  designers: ProductAssignment[]
  print_operators: ProductAssignment[]
  engraving_operators: ProductAssignment[] // Отдельные назначения для гравировки
  workshop_workers: ProductAssignment[]
}
