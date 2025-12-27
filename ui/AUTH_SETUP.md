# JWT Authentication Setup - Complete Guide

## 🚀 Overview

This Nuxt 3 application now has complete JWT authentication integrated with your Laravel backend.

## 📋 What's Been Implemented

### 1. **Auth Store** (`stores/auth.ts`)

- ✅ Uses `$fetch` instead of `useFetch` for proper client-side mutations
- ✅ Stores JWT token in secure HTTP-only cookie
- ✅ Token refresh functionality
- ✅ Login, register, logout, and fetchUser methods
- ✅ Proper error handling

### 2. **Server API Endpoints** (`server/api/`)

- ✅ `/api/login` - Forwards to Laravel backend
- ✅ `/api/register` - User registration
- ✅ `/api/logout` - User logout
- ✅ `/api/refresh` - Token refresh
- ✅ `/api/me` - Get current user
- ✅ `/api/newsfeed` - Protected newsfeed endpoint

### 3. **Middleware** (`middleware/`)

- ✅ `auth.ts` - Protects routes requiring authentication
- ✅ `guest.ts` - Redirects authenticated users from login/register pages

### 4. **Plugins**

- ✅ `plugins/auth.ts` - Auto-fetches user on app load & sets up token refresh
- ✅ `plugins/api.ts` - Global API interceptor with auto token refresh

### 5. **Composables**

- ✅ `composables/useApi.ts` - Helper for authenticated API calls with auto-retry

### 6. **Environment Variables**

- ✅ `.env` and `.env.example` with API configuration
- ✅ `nuxt.config.ts` updated with runtime config

## 🔧 Configuration

### Environment Variables

Create a `.env` file in the root directory:

```env
NUXT_PUBLIC_API_BASE=http://localhost:8000
NUXT_PUBLIC_SITE_URL=http://localhost:3000
```

## 📖 Usage Examples

### 1. Login

```vue
<script setup>
const authStore = useAuthStore()
const router = useRouter()

const credentials = ref({
  email: '',
  password: '',
})

const handleLogin = async () => {
  try {
    await authStore.login({
      login: credentials.value.email,
      password: credentials.value.password,
    })

    router.push('/newsfeed')
  } catch (error) {
    console.error('Login failed:', error.message)
  }
}
</script>
```

### 2. Protected Route

Add middleware to any page that requires authentication:

```vue
<script setup>
definePageMeta({
  middleware: 'auth',
})
</script>
```

### 3. Make Authenticated API Calls

**Option A: Using useApi composable (Recommended)**

```vue
<script setup>
const api = useApi()
const data = ref(null)

const fetchData = async () => {
  try {
    data.value = await api.get('/api/newsfeed')
  } catch (error) {
    console.error('Failed to fetch:', error)
  }
}
</script>
```

**Option B: Direct $fetch with manual token**

```vue
<script setup>
const authStore = useAuthStore()

const fetchData = async () => {
  const data = await $fetch('/api/newsfeed', {
    headers: {
      Authorization: `Bearer ${authStore.token}`,
    },
  })
}
</script>
```

### 4. Logout

```vue
<script setup>
const authStore = useAuthStore()

const handleLogout = async () => {
  await authStore.logout()
  // User will be redirected to /auth/login automatically
}
</script>
```

### 5. Check Authentication Status

```vue
<script setup>
const authStore = useAuthStore()

// In template
// <div v-if="authStore.isAuthenticated">Welcome, {{ authStore.user?.name }}</div>
</script>
```

## 🔐 How Token Refresh Works

1. **Automatic Refresh**: Plugin checks token every 55 minutes and refreshes automatically
2. **On 401 Error**: API interceptor catches 401 errors and attempts token refresh
3. **Manual Refresh**: Call `authStore.refreshToken()` anytime

## 🛡️ Security Features

- ✅ JWT tokens stored in secure HTTP-only cookies
- ✅ Automatic token refresh before expiration
- ✅ 401 error handling with auto-retry after refresh
- ✅ Protected routes via middleware
- ✅ Environment-based API configuration
- ✅ Proper error handling and user feedback

## 🚦 Laravel Backend Requirements

Your Laravel backend should have these endpoints:

```
POST /api/login
Body: { "login": "email", "password": "password" }
Response: { "access_token": "...", "user": {...} }

POST /api/register
Body: { "name": "...", "email": "...", "password": "..." }
Response: { "access_token": "...", "user": {...} }

POST /api/logout
Headers: Authorization: Bearer {token}
Response: { "message": "Logged out" }

POST /api/refresh
Headers: Authorization: Bearer {token}
Response: { "access_token": "..." }

GET /api/auth/me
Headers: Authorization: Bearer {token}
Response: { "user": {...} }

GET /api/newsfeed
Headers: Authorization: Bearer {token}
Response: { "activities": {...}, ... }
```

## 📝 Example: Update Existing Pages

To protect a page, simply add the middleware:

```vue
// pages/dashboard.vue
<script setup>
definePageMeta({
  middleware: 'auth',
})
</script>
```

To prevent authenticated users from accessing auth pages:

```vue
// pages/auth/login.vue
<script setup>
definePageMeta({
  middleware: 'guest',
})
</script>
```

## 🧪 Testing

1. Start your Laravel backend: `php artisan serve` (port 8000)
2. Start Nuxt dev server: `npm run dev` (port 3000)
3. Try logging in at `/auth/login`
4. Check that protected pages redirect to login when not authenticated
5. Verify token is stored in browser cookies
6. Test token refresh by waiting ~55 minutes or manually calling `authStore.refreshToken()`

## 🐛 Troubleshooting

### CORS Issues

Make sure your Laravel backend has CORS configured properly in `config/cors.php`:

```php
'allowed_origins' => ['http://localhost:3000'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

### Token Not Persisting

Check that cookies are being set properly in browser DevTools → Application → Cookies

### 401 Errors

- Verify Laravel backend is running
- Check that token is being sent in Authorization header
- Verify JWT secret is configured in Laravel `.env`

## 📚 Next Steps

- [ ] Add password reset functionality
- [ ] Add email verification
- [ ] Add remember me functionality
- [ ] Add 2FA support
- [ ] Add role-based access control (RBAC)
- [ ] Add social authentication (Google, Facebook, etc.)

## 🎉 You're All Set!

Your Nuxt 3 app now has complete JWT authentication with automatic token refresh and proper security measures.
