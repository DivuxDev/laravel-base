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

Las respuestas de validación (422) incluyen un campo adicional `errors`.

---

## 1. Registro

**`POST /api/auth/register`**

> Rate limit: **5 intentos por minuto** por IP.

### Request

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "Password1!",
  "password_confirmation": "Password1!"
}
```

> **Política de contraseña:** mínimo 8 caracteres, al menos una mayúscula, una minúscula, un número y un carácter especial.

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
  "success": false,
  "data": null,
  "message": "Validation failed.",
  "errors": {
    "email": ["This email is already registered."],
    "password": [
      "The password must contain at least one uppercase and one lowercase letter.",
      "The password must contain at least one number.",
      "The password must contain at least one special character."
    ]
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
  "password": "Password1!"
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

### Response `423 Locked` (cuenta bloqueada por brute force)

Tras 5 intentos fallidos, la cuenta se bloquea por 15 minutos.

```json
{
  "success": false,
  "data": {
    "locked_for_seconds": 842
  },
  "message": "Account locked due to too many failed attempts. Try again in 15 minute(s)."
}
```

### Response `429 Too Many Requests`

```json
{
  "success": false,
  "data": null,
  "message": "Too many requests. Please slow down."
}
```

---

## 3. Logout

**`POST /api/auth/logout`**

> Requiere cabecera `Authorization: Bearer {token}`

### Response `200 OK`

```json
{
  "success": true,
  "data": null,
  "message": "Logged out successfully."
}
```

---

## 4. Perfil del usuario autenticado

**`GET /api/user`**

> Requiere cabecera `Authorization: Bearer {token}`. Rate limit: 60/min.

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

---

## 5. Actualizar perfil

**`PUT /api/user/profile`**

> Requiere cabecera `Authorization: Bearer {token}`. Content-Type: `multipart/form-data`.

### Request (FormData)

| Campo | Tipo | Requerido | Descripción |
|---|---|---|---|
| `name` | string | No | Nuevo nombre (max 255) |
| `avatar` | file (image) | No | Nueva foto de perfil (max 2MB, formatos imagen) |

### Response `200 OK`

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Updated",
      "email": "john@example.com",
      "avatar": "http://localhost:8000/storage/avatars/abc123.jpg",
      "created_at": "2026-03-11T10:00:00.000000Z",
      "updated_at": "2026-03-19T15:30:00.000000Z"
    }
  },
  "message": "Profile updated successfully."
}
```

---

## 6. OAuth Google — Redirect

**`GET /api/auth/google/redirect`**

El frontend NO llama a este endpoint con Axios. Debe **redirigir el navegador** directamente:

```ts
window.location.href = 'http://localhost:8000/api/auth/google/redirect'
```

---

## 7. OAuth Google — Callback

**`GET /api/auth/google/callback`**

Éxito:
```
http://localhost:5173/auth/callback?token=3|ghi789rst...
```

Error:
```
http://localhost:5173/auth/callback?error=google_auth_failed
```

---

## 8. Listar usuarios (Admin)

**`GET /api/admin/users`**

> Requiere Bearer token + `role = admin`. Rate limit: 30/min.

### Query params

| Param | Tipo | Default | Descripción |
|---|---|---|---|
| `page` | int | 1 | Número de página |
| `per_page` | int | 15 | Elementos por página (1–100) |
| `search` | string | — | Busca en `name` y `email` |
| `sort` | string | `created_at` | Columna: `name`, `email`, `role`, `created_at` |
| `sort_dir` | string | `desc` | `asc` o `desc` |

### Response `200 OK`

```json
{
  "success": true,
  "data": {
    "users": [
      {
        "id": 1,
        "name": "Admin",
        "email": "admin@example.com",
        "role": "admin",
        "avatar": null,
        "created_at": "2026-03-11T10:00:00.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 15,
      "total": 42
    }
  },
  "message": "Users retrieved successfully."
}
```

---

## 9. Cambiar rol (Admin)

**`PATCH /api/admin/users/{id}/role`**

### Request

```json
{
  "role": "admin"
}
```

> No puedes cambiar tu propio rol (devuelve 422).

### Response `200 OK`

```json
{
  "success": true,
  "data": {
    "user": { "id": 2, "name": "User", "email": "user@example.com", "role": "admin", "avatar": null, "created_at": "..." }
  },
  "message": "Role updated successfully."
}
```

---

## 10. Resetear contraseña (Admin)

**`POST /api/admin/users/{id}/reset-password`**

### Response `200 OK`

```json
{
  "success": true,
  "data": {
    "new_password": "aBcDeFgHiJkL"
  },
  "message": "Password reset successfully."
}
```

---

## 11. Eliminar usuario (Admin)

**`DELETE /api/admin/users/{id}`**

> No puedes eliminarte a ti mismo (devuelve 422).

### Response `200 OK`

```json
{
  "success": true,
  "data": null,
  "message": "User deleted successfully."
}
```

---

## 12. Registros de auditoría (Admin)

**`GET /api/admin/audit-logs`**

> Requiere Bearer token + `role = admin`. Rate limit: 30/min.

### Query params

| Param | Tipo | Default | Descripción |
|---|---|---|---|
| `page` | int | 1 | Número de página |
| `per_page` | int | 15 | Elementos por página (1–100) |
| `search` | string | — | Busca en `action`, nombre y email del usuario |
| `from` | date (Y-m-d) | — | Fecha inicio (inclusive) |
| `to` | date (Y-m-d) | — | Fecha fin (inclusive) |
| `sort` | string | `created_at` | Columna: `action`, `created_at` |
| `sort_dir` | string | `desc` | `asc` o `desc` |

### Response `200 OK`

```json
{
  "success": true,
  "data": {
    "audit_logs": [
      {
        "id": 1,
        "action": "admin.user.role_changed",
        "auditable_type": "App\\Models\\User",
        "auditable_id": 2,
        "old_values": { "role": "user" },
        "new_values": { "role": "admin" },
        "ip_address": "127.0.0.1",
        "user_agent": "Mozilla/5.0 ...",
        "created_at": "2026-03-19T12:30:00.000000Z",
        "user": {
          "name": "Admin",
          "email": "admin@example.com"
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 5
    }
  },
  "message": "Audit logs retrieved successfully."
}
```

### Acciones registradas

| Acción | Descripción |
|---|---|
| `user.login` | Login exitoso |
| `user.registered` | Nuevo registro de usuario |
| `admin.user.role_changed` | Admin cambió el rol de un usuario |
| `admin.user.password_reset` | Admin reseteó la contraseña de un usuario |
| `admin.user.deleted` | Admin eliminó un usuario |

---

## Cabeceras de seguridad

Todas las respuestas incluyen:

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
```

En producción (HTTPS) se añade: `Strict-Transport-Security: max-age=31536000; includeSubDomains`

---

## Rate limiting

| Limiter | Límite | Endpoints |
|---|---|---|
| Login | 10/min por IP | `POST /api/auth/login` |
| Register | 5/min por IP | `POST /api/auth/register` |
| Admin | 30/min por usuario | Todos los `/api/admin/*` |
| User API | 60/min por usuario | `GET /api/user` |

---

## Configuración Axios recomendada

```ts
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.DEV ? '' : import.meta.env.VITE_API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: true,
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      window.dispatchEvent(new CustomEvent('auth:unauthenticated'))
    }
    return Promise.reject(error)
  }
)

export default api
```

---

## Resumen de endpoints

| Método | Endpoint | Auth | Rate limit | Descripción |
|---|---|---|---|---|
| `POST` | `/api/auth/register` | No | 5/min | Registro de usuario |
| `POST` | `/api/auth/login` | No | 10/min | Login (devuelve token) |
| `POST` | `/api/auth/logout` | Bearer | — | Revoca el token |
| `GET` | `/api/user` | Bearer | 60/min | Perfil del usuario |
| `PUT` | `/api/user/profile` | Bearer | — | Actualizar nombre/avatar |
| `GET` | `/api/auth/google/redirect` | No | — | Inicia OAuth Google |
| `GET` | `/api/auth/google/callback` | No | — | Callback OAuth Google |
| `GET` | `/api/admin/users` | Admin | 30/min | Listar usuarios (paginado) |
| `PATCH` | `/api/admin/users/{id}/role` | Admin | 30/min | Cambiar rol |
| `POST` | `/api/admin/users/{id}/reset-password` | Admin | 30/min | Resetear contraseña |
| `DELETE` | `/api/admin/users/{id}` | Admin | 30/min | Eliminar usuario |
| `GET` | `/api/admin/audit-logs` | Admin | 30/min | Registros de auditoría (paginado) |

---

## Usuarios de prueba (seeder)

| Email | Password | Role |
|---|---|---|
| `admin@example.com` | `password` | admin |
| `user@example.com` | `password` | user |
| `test@example.com` | `password` | user |
