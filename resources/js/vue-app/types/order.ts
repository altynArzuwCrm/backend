export interface Order {
  id: number
  title: string
  project_id?: number | null
  client_id?: number | null
  client?: {
    id: number
    name: string
    company_name?: string | null
  } | null
  product?: {
    id: number
    name: string
  } | null
  status: string
  stage?: string
  reason?: string
  reason_status?: string
  is_archived?: boolean
  archived_at?: string
  created_at: string
  updated_at: string
  has_design_stage?: boolean
  has_print_stage?: boolean
  has_workshop_stage?: boolean
  has_engraving_stage?: boolean
  designer_id?: number | null
  print_operator_id?: number | null
  workshop_worker_id?: number | null
  // Добавьте другие поля по необходимости
  [key: string]: any
}

export interface OrderForm {
  client_id: number
  project_id?: number
  product_id?: number
  quantity?: number
  deadline?: string | null
  price?: number | null
  stage?: string
  has_design_stage?: boolean
  has_print_stage?: boolean
  has_workshop_stage?: boolean
  has_engraving_stage?: boolean
  designer_id?: number | null
  print_operator_id?: number | null
  workshop_worker_id?: number | null
}

export interface OrderUpdateForm {
  client_id?: number
  project_id?: number
  product_id?: number
  quantity?: number
  deadline?: string
  price?: number
  stage?: string
}

export interface StageUpdateForm {
  stage: string
  reason?: string
  reason_status?: string
  work_type?: string
  designer_id?: number
  print_operator_id?: number
  workshop_worker_id?: number
}
