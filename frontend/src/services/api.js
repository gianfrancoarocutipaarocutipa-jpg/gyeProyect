import { ref } from 'vue'

const apiBase = import.meta.env.VITE_API_BASE || 'http://localhost:8000/api'
const cloudinaryUploadPreset = import.meta.env.VITE_CLOUDINARY_UPLOAD_PRESET || null
const cloudinaryCloudName = import.meta.env.VITE_CLOUDINARY_CLOUD_NAME || null

class ApiService {
  constructor() {
    this.token = localStorage.getItem('gem_motors_token') || null
  }

  setToken(token) {
    this.token = token
    if (token) {
      localStorage.setItem('gem_motors_token', token)
    } else {
      localStorage.removeItem('gem_motors_token')
    }
  }

  getToken() {
    return this.token
  }

  async request(endpoint, options = {}) {
    const url = `${apiBase}${endpoint}`
    
    const headers = {
      'Content-Type': 'application/json',
      ...options.headers
    }

    const currentLocalToken = localStorage.getItem('gem_motors_token') || null;
    if (this.token !== currentLocalToken) {
      this.token = currentLocalToken;
    }

    if (this.token) {
      headers.Authorization = `Bearer ${this.token}`
    }

    try {
      const response = await fetch(url, {
        ...options,
        headers
      })

      // Handle non-JSON responses (like 500 errors with HTML)
      let data
      try {
        data = await response.json()
      } catch (e) {
        data = { success: false, mensaje: 'Error interno del servidor' }
      }

      // Handle HTTP error status codes
      if (!response.ok) {
        const error = new Error(data.mensaje || 'Error en la solicitud')
        error.status = response.status
        error.data = data
        throw error
      }

      // Handle backend success flag
      if (!data.success) {
        const error = new Error(data.mensaje || 'Error en la operación')
        error.status = 400 // Default to bad request for business logic errors
        error.data = data
        throw error
      }

      return data
    } catch (error) {
      // Re-throw network errors or our custom errors
      throw error
    }
  }

  // Helper methods for common HTTP verbs
  get(endpoint, params = {}) {
    const queryString = new URLSearchParams(params).toString()
    const url = queryString ? `${endpoint}?${queryString}` : endpoint
    return this.request(url, { method: 'GET' })
  }

  post(endpoint, data = {}) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data)
    })
  }

  put(endpoint, data = {}) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data)
    })
  }

  delete(endpoint) {
    return this.request(endpoint, { method: 'DELETE' })
  }

  async uploadToCloudinary(file) {
    if (!cloudinaryUploadPreset || !cloudinaryCloudName) {
      throw new Error('Cloudinary no está configurado. Verifique VITE_CLOUDINARY_UPLOAD_PRESET y VITE_CLOUDINARY_CLOUD_NAME')
    }

    const url = `https://api.cloudinary.com/v1_1/${cloudinaryCloudName}/auto/upload`
    const formData = new FormData()
    formData.append('file', file)
    formData.append('upload_preset', cloudinaryUploadPreset)

    try {
      const response = await fetch(url, {
        method: 'POST',
        body: formData
      })

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.error?.message || 'Error al subir archivo a Cloudinary')
      }

      const data = await response.json()
      return { success: true, url: data.secure_url || data.url, data }
    } catch (error) {
      throw error
    }
  }
}

export const apiService = new ApiService()