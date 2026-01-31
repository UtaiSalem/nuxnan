# Authentication System Checklist

## ✅ Frontend Implementation (Nuxt 3)

### Login System

- ✅ LoginForm component with validation
  - Username/Email field (required)
  - Password field (min 6 characters)
  - Error handling and display
  - Loading states
  - Google OAuth integration

### Registration System

- ✅ RegisterForm component with validation
  - Email validation (valid format)
  - Username validation (min 3 characters)
  - Password validation (min 8 characters)
  - Password confirmation match
  - Newsletter opt-in
  - Show/hide password toggle
  - Error and success messages
  - Google OAuth integration

### Auth Store (Pinia)

- ✅ Login function with error handling
- ✅ Register function with error handling
- ✅ Token management (7-day cookie)
- ✅ User state management
- ✅ Auto token refresh
- ✅ Logout functionality
- ✅ Fetch user data

### Middleware & Guards

- ✅ `auth` middleware - Protects authenticated routes
- ✅ `guest` middleware - Redirects authenticated users
- ✅ Auto-redirect on 401 errors

### Pages

- ✅ `/auth` - Main auth page with login/register tabs
- ✅ `/auth/callback` - OAuth callback handler
- ❌ `/auth/login` - Removed (use /auth?tab=login)

### Server API Routes

- ✅ `/api/login` - Proxy to Laravel backend
- ✅ `/api/register` - Proxy to Laravel backend
- ✅ `/api/me` - Get authenticated user
- ✅ `/api/refresh` - Token refresh
- ✅ `/api/logout` - Logout user

## ⚠️ Backend Requirements (Laravel)

### Required Routes

```php
POST /api/login
POST /api/register
GET  /api/auth/me
POST /api/refresh
POST /api/logout
GET  /auth/google/redirect
GET  /auth/google/callback
```

### Google OAuth Setup

1. Install Laravel Socialite:

   ```bash
   composer require laravel/socialite
   ```

2. Add to `.env`:

   ```env
   GOOGLE_CLIENT_ID=your_client_id
   GOOGLE_CLIENT_SECRET=your_client_secret
   GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
   FRONTEND_URL=http://localhost:3000
   ```

3. Configure `config/services.php`:

   ```php
   'google' => [
       'client_id' => env('GOOGLE_CLIENT_ID'),
       'client_secret' => env('GOOGLE_CLIENT_SECRET'),
       'redirect' => env('GOOGLE_REDIRECT_URI'),
   ],
   ```

4. Create Controller:

   ```php
   php artisan make:controller Auth/SocialAuthController
   ```

5. Add routes in `routes/web.php` or `routes/api.php`

### Google Cloud Console

- ✅ Create OAuth 2.0 Client ID
- ✅ Add authorized redirect URI:
  ```
  http://localhost:8000/auth/google/callback
  ```

## 🔐 Security Features

- ✅ JWT token authentication
- ✅ Secure HTTP-only cookies
- ✅ Token auto-refresh (55 minutes)
- ✅ CSRF protection (SameSite cookie)
- ✅ Password length requirements
- ✅ Email format validation
- ✅ Error sanitization

## 🎯 User Flow

### Login Flow

1. User enters credentials
2. Frontend validates input
3. Request sent to `/api/login`
4. Server proxy to Laravel backend
5. JWT token returned
6. Token stored in cookie
7. Redirect to `/newsfeed`

### Registration Flow

1. User fills registration form
2. Frontend validates all fields
3. Request sent to `/api/register`
4. Server proxy to Laravel backend
5. User created, JWT token returned
6. Token stored in cookie
7. Success message displayed
8. Auto-redirect to `/newsfeed`

### Google OAuth Flow

1. User clicks "Login with Google"
2. Redirect to: `http://localhost:8000/auth/google/redirect`
3. Google authentication
4. Google redirects to: `http://localhost:8000/auth/google/callback`
5. Laravel generates JWT
6. Redirect to: `http://localhost:3000/auth/callback?token={jwt}`
7. Frontend stores token
8. Redirect to `/newsfeed`

## 📝 Validation Rules

### Login

- Username/Email: Required
- Password: Required, min 6 characters

### Registration

- Email: Required, valid email format
- Username: Required, min 3 characters
- Password: Required, min 8 characters
- Password Confirmation: Must match password

## 🔄 Token Management

- Token stored in HTTP-only cookie
- Max age: 7 days
- Auto-refresh: Every 55 minutes
- Refresh fails → Auto logout → Redirect to `/auth`

## 🚀 Testing Checklist

### Login

- [ ] Valid credentials → Success
- [ ] Invalid credentials → Error message
- [ ] Empty fields → Validation error
- [ ] Short password → Validation error
- [ ] Token stored correctly
- [ ] Redirect to newsfeed works

### Registration

- [ ] Valid data → Success
- [ ] Invalid email → Error
- [ ] Short username → Error
- [ ] Short password → Error
- [ ] Password mismatch → Error
- [ ] Empty fields → Error
- [ ] Account created in database
- [ ] Auto-login after registration

### Google OAuth

- [ ] Redirect to Google works
- [ ] Callback receives token
- [ ] Token stored correctly
- [ ] User created/updated in database
- [ ] Auto-redirect to newsfeed

### Protected Routes

- [ ] Unauthenticated access → Redirect to `/auth`
- [ ] Authenticated access → Allow
- [ ] Token expired → Auto-refresh or logout

### Logout

- [ ] Token cleared
- [ ] Cookie removed
- [ ] Redirect to `/auth`

## 🐛 Common Issues & Solutions

### Issue: 404 on Google OAuth

**Solution:** Ensure Laravel routes are defined and backend is running on port 8000

### Issue: Token not persisting

**Solution:** Check cookie settings, ensure `secure: true` only in production

### Issue: CORS errors

**Solution:** Configure Laravel CORS middleware to allow frontend origin

### Issue: 401 on protected routes

**Solution:** Verify token is being sent in Authorization header

## 📚 Related Files

### Frontend

- `components/molecules/LoginForm.vue`
- `components/molecules/RegisterForm.vue`
- `stores/auth.ts`
- `middleware/auth.ts`
- `middleware/guest.ts`
- `plugins/auth.ts`
- `plugins/api.ts`
- `pages/auth/index.vue`
- `pages/auth/callback.vue`
- `server/api/login.ts`
- `server/api/register.ts`
- `server/api/me.ts`
- `server/api/refresh.ts`
- `server/api/logout.ts`

### Backend (Laravel)

- `routes/api.php` or `routes/web.php`
- `app/Http/Controllers/Auth/SocialAuthController.php`
- `app/Models/User.php`
- `config/services.php`
- `.env`
