# 🧪 Testing Your JWT Authentication

## Quick Test Checklist

### 1. ✅ Backend Running

```bash
cd your-laravel-project
php artisan serve
```

Expected: Server running on `http://localhost:8000`

### 2. ✅ Frontend Running

```bash
cd ui
npm run dev
```

Expected: Server running on `http://localhost:3000`

### 3. ✅ Test Login Flow

**Visit:** `http://localhost:3000/auth/login`

**Test Credentials:**

- Email: Your test user email
- Password: Your test user password

**Expected Behavior:**

1. Enter credentials
2. Click "Login"
3. See "Logging in..." button state
4. Redirected to `/newsfeed` on success
5. Error message on failure

### 4. ✅ Check Token Storage

Open Browser DevTools:

1. Go to **Application** tab
2. Check **Cookies** → `http://localhost:3000`
3. Should see `token` cookie with JWT value

### 5. ✅ Test Protected Routes

**Try accessing:**

- ✅ `/newsfeed` - Should work (redirects to login if not authenticated)
- ✅ `/dashboard` - Should work (redirects to login if not authenticated)
- ✅ `/settings` - Should work (redirects to login if not authenticated)

### 6. ✅ Test Guest Routes

When logged in, try accessing:

- `/auth/login` - Should redirect to `/newsfeed`
- `/auth?tab=register` - Should redirect to `/newsfeed`

### 7. ✅ Test API Calls

Open Browser Console and run:

```javascript
// Get auth store
const authStore = useAuthStore()

// Check auth status
console.log('Authenticated:', authStore.isAuthenticated)
console.log('User:', authStore.user)
console.log('Token:', authStore.token)

// Test API call
const api = useApi()
const data = await api.get('/api/me')
console.log('User data:', data)
```

### 8. ✅ Test Token Refresh

**Manual Test:**

```javascript
const authStore = useAuthStore()
const success = await authStore.refreshToken()
console.log('Token refreshed:', success)
```

**Automatic Test:**

- Wait 55 minutes (or modify plugin to refresh sooner)
- Token should refresh automatically
- Check console for "Token refreshed" logs

### 9. ✅ Test Logout

Click logout button or run:

```javascript
const authStore = useAuthStore()
await authStore.logout()
```

**Expected:**

1. Token removed from cookies
2. User set to null
3. Redirected to `/auth/login`

### 10. ✅ Test 401 Handling

**Simulate expired token:**

1. Login successfully
2. In browser console, corrupt the token:

```javascript
document.cookie = 'token=invalid_token; path=/'
```

3. Try to access `/api/me`
4. Should attempt token refresh
5. If refresh fails, should logout and redirect to login

---

## 🐛 Debugging

### Check Logs

**Browser Console:**

- Look for authentication errors
- Check network tab for API calls
- Verify token in request headers

**Nuxt Dev Console:**

- Check for server-side errors
- Verify API endpoint calls

### Common Test Issues

#### Login Returns 401

- ✅ Verify Laravel backend is running
- ✅ Check credentials are correct
- ✅ Verify `/api/login` endpoint exists in Laravel
- ✅ Check Laravel logs for errors

#### Token Not Persisting

- ✅ Check cookie settings in browser
- ✅ Verify cookie domain matches
- ✅ Try clearing all cookies

#### Protected Routes Not Working

- ✅ Verify middleware is applied
- ✅ Check auth store has token
- ✅ Verify routes are configured correctly

#### CORS Errors

- ✅ Configure Laravel CORS properly
- ✅ Allow credentials in CORS
- ✅ Add `http://localhost:3000` to allowed origins

---

## 📊 Expected Network Calls

### On Login:

```
POST /api/login
→ Response: { access_token: "...", user: {...} }
```

### On Protected Page Load:

```
GET /api/newsfeed
Headers: Authorization: Bearer {token}
→ Response: { activities: [...], ... }
```

### On Token Refresh:

```
POST /api/refresh
Headers: Authorization: Bearer {old_token}
→ Response: { access_token: "new_token" }
```

### On Logout:

```
POST /api/logout
Headers: Authorization: Bearer {token}
→ Response: { message: "Logged out" }
```

---

## ✅ Success Criteria

Your authentication is working correctly if:

- [x] Can login with valid credentials
- [x] Token stored in cookies
- [x] Protected routes redirect to login when not authenticated
- [x] Can access protected routes when authenticated
- [x] Guest routes redirect to newsfeed when authenticated
- [x] Token refresh works (manual and automatic)
- [x] Logout clears token and redirects
- [x] 401 errors trigger token refresh and retry
- [x] API calls include Authorization header

---

## 🎉 All Tests Passing?

**Congratulations!** Your JWT authentication is fully operational.

Now you can:

- Start building protected features
- Add more protected pages
- Implement user profile pages
- Add password reset functionality
- Build your application features

**Happy coding! 🚀**
