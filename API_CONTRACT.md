# API Contract — Laravel Backend

Base URL: `http://localhost:8000/api`

Todas las respuestas tienen el formato:

```json
{
  "success": true | false,
  "data": { ... } | null,
  "message": "string"
}
```

---

## 1. Registro

**`POST /api/auth/register`**

### Request

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

### Response `201 Created`

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "avatar": null,
      "created_at": "2026-03-11T10:00:00.000000Z",
      "updated_at": "2026-03-11T10:00:00.000000Z"
    },
    "token": "1|abc123xyz..."
  },
  "message": "User registered successfully."
}
```

### Response `422 Unprocessable Entity` (validación)

```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["This email is already registered."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

## 2. Login

**`POST /api/auth/login`**

> Rate limit: **10 intentos por minuto** por IP. Superar el límite devuelve `429`.

### Request

```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

### Response `200 OK`

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "avatar": null,
      "created_at": "2026-03-11T10:00:00.000000Z",
      "updated_at": "2026-03-11T10:00:00.000000Z"
    },
    "token": "2|def456uvw..."
  },
  "message": "Login successful."
}
```

### Response `401 Unauthorized` (credenciales incorrectas)

```json
{
  "success": false,
  "data": null,
  "message": "Invalid credentials. Please check your email and password."
}
```

### Response `422 Unprocessable Entity` (campos vacíos)

```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

### Response `429 Too Many Requests`

```json
{
  "message": "Too Many Attempts."
}
```

---

## 3. Logout

**`POST /api/auth/logout`**

> Requiere cabecera `Authorization: Bearer {token}`

### Headers

```
Authorization: Bearer 2|def456uvw...
Accept: application/json
```

### Response `200 OK`

```json
{
  "success": true,
  "data": null,
  "message": "Logged out successfully."
}
```

### Response `401 Unauthorized` (token inválido o ausente)

```json
{
  "message": "Unauthenticated."
}
```

---

## 4. Perfil del usuario autenticado

**`GET /api/user`**

> Requiere cabecera `Authorization: Bearer {token}`

### Headers

```
Authorization: Bearer 2|def456uvw...
Accept: application/json
```

### Response `200 OK`

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "avatar": null,
      "created_at": "2026-03-11T10:00:00.000000Z",
      "updated_at": "2026-03-11T10:00:00.000000Z"
    }
  },
  "message": "User profile retrieved successfully."
}
```

### Response `401 Unauthorized`

```json
{
  "message": "Unauthenticated."
}
```

---

## 5. OAuth Google — Redirect

**`GET /api/auth/google/redirect`**

El frontend NO llama a este endpoint con Axios. Debe **redirigir el navegador** directamente:

```ts
// Vue 3
window.location.href = 'http://localhost:8000/api/auth/google/redirect'
```

Laravel redirige automáticamente al consent screen de Google.

---

## 6. OAuth Google — Callback

**`GET /api/auth/google/callback`**

Este endpoint lo gestiona el **navegador**, no el frontend directamente. Google redirige aquí tras la autenticación.

El backend procesa la respuesta de Google y redirige al frontend con el token:

```
http://localhost:5173/auth/callback?token=3|ghi789rst...
```

### En caso de error:

```
http://localhost:5173/auth/callback?error=google_auth_failed
```

El frontend debe leer los query params de la URL al montar la página `/auth/callback`:

```ts
// Vue 3 — composable en /auth/callback
import { useRoute, useRouter } from 'vue-router'
import { onMounted } from 'vue'

const route  = useRoute()
const router = useRouter()

onMounted(() => {
  const token = route.query.token as string
  const error = route.query.error as string

  if (error) {
    // manejar error
    router.push('/login?error=google_auth_failed')
    return
  }

  if (token) {
    localStorage.setItem('token', token)
    // o guardar en Pinia/Vuex
    router.push('/dashboard')
  }
})
```

---

## Configuración Axios recomendada para Vue 3

```ts
// src/lib/axios.ts
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true, // necesario para Sanctum SPA
})

// Interceptor: inyecta el token en cada request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Interceptor: maneja errores globales
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
```

---

## Resumen de endpoints

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| `POST` | `/api/auth/register` | No | Registro de usuario |
| `POST` | `/api/auth/login` | No | Login (devuelve token) |
| `POST` | `/api/auth/logout` | Bearer token | Revoca el token |
| `GET`  | `/api/user` | Bearer token | Perfil del usuario |
| `GET`  | `/api/auth/google/redirect` | No | Inicia OAuth Google |
| `GET`  | `/api/auth/google/callback` | No | Callback OAuth Google |

---

## Usuarios de prueba (seeder)

| Campo | Valor |
|-------|-------|
| Email | `test@example.com` |
| Password | `password` |
