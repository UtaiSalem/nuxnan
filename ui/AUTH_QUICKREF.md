# 🔐 JWT Authentication - Quick Reference

## API Endpoints Created

### Frontend → Backend Proxy

All in `server/api/`:

| Endpoint        | Method | Purpose                  |
| --------------- | ------ | ------------------------ |
| `/api/login`    | POST   | Login user               |
| `/api/register` | POST   | Register new user        |
| `/api/logout`   | POST   | Logout user              |
| `/api/refresh`  | POST   | Refresh JWT token        |
| `/api/me`       | GET    | Get current user         |
| `/api/newsfeed` | GET    | Get newsfeed (protected) |

## Usage Patterns

### 1. Login

```javascript
const authStore = useAuthStore()
await authStore.login({
  login: 'user@example.com',
  password: 'password',
})
```

### 2. Register

```javascript
await authStore.register({
  name: 'John Doe',
  email: 'john@example.com',
  password: 'password',
  password_confirmation: 'password',
})
```

### 3. Logout

```javascript
await authStore.logout()
```

### 4. Refresh Token

```javascript
await authStore.refreshToken()
```

### 5. Protected API Call (Option 1 - Recommended)

```javascript
const api = useApi()
const data = await api.get('/api/newsfeed')
```

### 6. Protected API Call (Option 2)

```javascript
const authStore = useAuthStore()
const data = await $fetch('/api/newsfeed', {
  headers: {
    Authorization: `Bearer ${authStore.token}`,
  },
})
```

## Middleware

### Protect Routes

```javascript
definePageMeta({
  middleware: 'auth',
})
```

### Guest Only Routes

```javascript
definePageMeta({
  middleware: 'guest',
})
```

## Auth State

```javascript
const authStore = useAuthStore()

authStore.isAuthenticated // boolean
authStore.user // user object or null
authStore.token // JWT token or null
authStore.isRefreshing // boolean (token refresh in progress)
```

## Pages with Middleware

### Protected (auth middleware)

- ✅ `/newsfeed`
- ✅ `/dashboard`
- ✅ `/settings`

### Guest Only (guest middleware)

- ✅ `/auth` (login/register)

## Auto Features

- ✅ Token automatically included in all `/api/*` requests
- ✅ Token refreshes every 55 minutes automatically
- ✅ On 401 error, attempts token refresh and retries request
- ✅ User fetched on app initialization if token exists
- ✅ Redirects to login if token invalid/expired

## Laravel Backend Expected Format

```php
// Login Response
[
  'access_token' => 'jwt_token_here',
  'user' => [
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    // ... other user fields
  ]
]

// Refresh Response
[
  'access_token' => 'new_jwt_token_here'
]
```

## Environment Variables

`.env` file:

```
NUXT_PUBLIC_API_BASE=http://localhost:8000
NUXT_PUBLIC_SITE_URL=http://localhost:3000
```

## Troubleshooting

### Token not persisting

- Check browser cookies
- Ensure secure: true only on HTTPS

### 401 errors

- Verify Laravel backend is running (port 8000)
- Check CORS configuration in Laravel
- Verify JWT secret in Laravel `.env`

### Redirect loops

- Clear cookies
- Check middleware configuration
- Verify token is valid
