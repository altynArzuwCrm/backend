export interface ClientContact {
  id: number
  type: 'phone' | 'email' | 'telegram' | 'whatsapp' | 'instagram' | 'other'
  value: string
  client_id: number
}

export interface Client {
  id: number
  name: string
  company_name?: string | null
  contacts: ClientContact[]
  created_at?: string
  updated_at?: string
}
