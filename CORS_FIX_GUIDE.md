# CORS Error Fix - Production Deployment Guide

**Issue:** Frontend can't access backend API in production  
**Error:** "CORS policy: No 'Access-Control-Allow-Origin' header is present"  
**Date Fixed:** November 14, 2025

---

## 🔍 What is CORS Error?

**CORS (Cross-Origin Resource Sharing)** adalah security feature browser yang **block** request dari domain berbeda, kecuali server explicitly allow it.

### Example Scenario:

**Development (No Error):**
```
Frontend: http://localhost:3000  ← Same origin
Backend:  http://localhost:8000  ← Same origin
Result: ✅ Works fine
```

**Production (CORS Error):**
```
Frontend: https://kassa-one.vercel.app     ← Different origin
Backend:  https://api.kassa-one.com        ← Different origin
Result: ❌ CORS Error - Browser blocks request
```

---

## ❌ Original Problem

**File:** `config/cors.php`

**Before (Hardcoded):**
```php
'allowed_origins' => ['http://localhost:5173', 'http://127.0.0.1:5173'],
```

**Issue:**
- Hanya allow localhost
- Production domain **tidak** dalam list
- Browser block semua request dari production

---

## ✅ Solution Applied

### 1. Update `config/cors.php`

**After (Dynamic from ENV):**
```php
'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173')),
```

**Benefits:**
- ✅ Read from `.env` file
- ✅ Support multiple origins (comma-separated)
- ✅ Easy to update per environment
- ✅ Default fallback to localhost

---

### 2. Add to `.env.example`

```env
# CORS Allowed Origins (comma-separated, no spaces)
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173,http://127.0.0.1:5173
```

---

## 🚀 How to Configure for Production

### Step 1: Update `.env` on Production Server

Add/update this line with your **actual frontend URLs**:

```env
CORS_ALLOWED_ORIGINS=https://kassa-one.vercel.app,https://kassa-one.com,https://www.kassa-one.com
```

**Rules:**
- ✅ Use comma (`,`) to separate multiple origins
- ✅ **NO spaces** between URLs
- ✅ Include protocol (`https://` or `http://`)
- ✅ Include all domain variations (www and non-www)
- ❌ **Don't use** trailing slash: `https://domain.com/` ← Wrong
- ✅ **Correct:** `https://domain.com` ← Right

---

### Step 2: Clear Config Cache

After updating `.env`:

```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache  # Optional: cache for production
```

---

### Step 3: Restart Server

**If using Apache/Nginx:**
```bash
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
```

**If using PHP-FPM:**
```bash
sudo systemctl restart php8.2-fpm
```

---

## 📋 Configuration Examples

### Development (.env)
```env
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173,http://127.0.0.1:5173,http://127.0.0.1:3000
```

### Staging (.env)
```env
CORS_ALLOWED_ORIGINS=https://staging.kassa-one.com,https://staging-frontend.vercel.app
```

### Production (.env)
```env
CORS_ALLOWED_ORIGINS=https://kassa-one.com,https://www.kassa-one.com,https://kassa-one.vercel.app
```

### Multiple Environments (All in one)
```env
CORS_ALLOWED_ORIGINS=https://kassa-one.com,https://www.kassa-one.com,https://staging.kassa-one.com,http://localhost:3000
```

---

## 🧪 Testing CORS Configuration

### Method 1: Browser Console

Open browser console (F12) and check:

**Before Fix:**
```
Access to fetch at 'https://api.kassa-one.com/api/login' from origin 
'https://kassa-one.vercel.app' has been blocked by CORS policy: 
No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

**After Fix:**
```
✅ No error, request successful
```

---

### Method 2: cURL Test

```bash
curl -X OPTIONS https://api.kassa-one.com/api/login \
  -H "Origin: https://kassa-one.vercel.app" \
  -H "Access-Control-Request-Method: POST" \
  -v
```

**Look for this header in response:**
```
< Access-Control-Allow-Origin: https://kassa-one.vercel.app
< Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
< Access-Control-Allow-Headers: *
```

---

### Method 3: Online CORS Tester

Use: https://www.test-cors.org/

1. **Remote URL:** `https://api.kassa-one.com/api/login`
2. **Method:** POST
3. Click "Send Request"
4. Should see: ✅ "CORS enabled"

---

## 🔐 Security Considerations

### ✅ Recommended: Specific Origins

```env
# GOOD - Only allow your actual frontend domains
CORS_ALLOWED_ORIGINS=https://kassa-one.com,https://www.kassa-one.com
```

### ⚠️ Not Recommended: Wildcard in Production

```php
// DON'T DO THIS IN PRODUCTION
'allowed_origins' => ['*'],  // Allows ALL domains - security risk!
```

**Why?**
- Any website can call your API
- Risk of CSRF attacks
- No control over who accesses your backend

### ✅ When to Use Wildcard

Only for:
- Public APIs (intentionally open to everyone)
- Development/testing environments
- APIs with other authentication mechanisms

---

## 🐛 Troubleshooting

### Issue 1: Still Getting CORS Error After Update

**Cause:** Config cache not cleared

**Solution:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

---

### Issue 2: Works on One Domain, Not Another

**Cause:** Missing domain in `CORS_ALLOWED_ORIGINS`

**Check:**
```bash
# On server, check current .env
cat .env | grep CORS_ALLOWED_ORIGINS
```

**Fix:** Add missing domain:
```env
CORS_ALLOWED_ORIGINS=https://domain1.com,https://domain2.com,https://missing-domain.com
```

---

### Issue 3: Error Only on POST/PUT/DELETE, Not GET

**Cause:** Preflight OPTIONS request failing

**Check middleware:**
```bash
php artisan route:list | grep api
```

**Ensure middleware:**
```php
// In routes/api.php or app/Http/Kernel.php
'api' => [
    \Fruitcake\Cors\HandleCors::class,  // MUST be here
    // ... other middleware
],
```

---

### Issue 4: "Credentials Include" Error

**Error:**
```
The value of the 'Access-Control-Allow-Origin' header must not be '*' 
when the request's credentials mode is 'include'.
```

**Cause:** Frontend using `credentials: 'include'` but backend allows `'*'`

**Solution:** Specify exact origins in `.env`, don't use `'*'`

**Frontend (axios example):**
```javascript
axios.defaults.withCredentials = true;
```

**Backend config/cors.php:**
```php
'supports_credentials' => true,  // ✅ Already set
'allowed_origins' => [...],      // ✅ Must be specific domains, not '*'
```

---

## 📊 CORS Configuration Reference

**File:** `config/cors.php`

**Current Settings:**
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],  // Apply CORS to these paths
    
    'allowed_methods' => ['*'],  // Allow all HTTP methods
    
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '...')),  // From .env
    
    'allowed_origins_patterns' => [],  // Regex patterns (advanced)
    
    'allowed_headers' => ['*'],  // Allow all headers
    
    'exposed_headers' => [],  // Headers exposed to frontend
    
    'max_age' => 0,  // Preflight cache duration
    
    'supports_credentials' => true,  // Allow cookies/auth headers
];
```

---

## 🌐 Common Frontend URLs to Allow

### Vercel Deployments
```env
# Production
https://your-app.vercel.app

# Preview/Branch deployments
https://your-app-git-main-username.vercel.app
```

### Netlify Deployments
```env
# Production
https://your-app.netlify.app

# Custom domain
https://kassa-one.com
```

### Custom Domains
```env
# With and without www
https://kassa-one.com
https://www.kassa-one.com

# Subdomain
https://app.kassa-one.com
```

### Development
```env
# Common dev ports
http://localhost:3000
http://localhost:5173
http://127.0.0.1:3000
http://127.0.0.1:5173
```

---

## ✅ Checklist for Production Deployment

- [ ] Update `.env` with production frontend URL
- [ ] Format: `CORS_ALLOWED_ORIGINS=https://domain.com,https://www.domain.com`
- [ ] No spaces between URLs
- [ ] Include protocol (https://)
- [ ] Include all domain variations (www, non-www, subdomains)
- [ ] Clear config cache: `php artisan config:clear`
- [ ] Cache config: `php artisan config:cache`
- [ ] Restart web server
- [ ] Test login from production frontend
- [ ] Check browser console for CORS errors
- [ ] Test API calls (GET, POST, PUT, DELETE)

---

## 📝 Quick Reference

**Add Origin to Existing List:**
```bash
# On server
nano .env

# Add new domain to existing comma-separated list
CORS_ALLOWED_ORIGINS=existing-domain.com,new-domain.com

# Save and clear cache
php artisan config:clear
```

**Check Current CORS Config:**
```bash
php artisan tinker
>>> config('cors.allowed_origins');
```

**Test CORS Header:**
```bash
curl -I https://api.kassa-one.com/api/login \
  -H "Origin: https://kassa-one.com"
```

---

## 🎯 Summary

**Problem:** CORS error in production because backend only allowed `localhost`

**Solution:** 
1. ✅ Changed `config/cors.php` to read from `.env`
2. ✅ Added `CORS_ALLOWED_ORIGINS` to `.env.example`
3. ✅ Now can configure different origins per environment

**Action Required:**
1. Update `.env` on production server with actual frontend URL
2. Clear config cache
3. Test login from production

**Format:**
```env
CORS_ALLOWED_ORIGINS=https://your-production-domain.com,https://www.your-production-domain.com
```

---

**Status:** ✅ Fixed - Ready for Production Configuration  
**Next Step:** Add production frontend URL to `.env` on server
