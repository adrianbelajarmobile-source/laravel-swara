# Laravel Reverb Setup Guide (Updated)

⚠️ **Important:** Karena project Anda menggunakan Laravel 12, kami **menggunakan Laravel Reverb** (WebSocket solution resmi Laravel) **bukan beyondcode/laravel-websockets** (yang hanya support sampai Laravel 10).

---

## ✅ Installation Complete

### Packages Installed
- ✅ `laravel/reverb` - WebSocket server resmi Laravel

### Configuration Done
- ✅ BROADCAST_CONNECTION set ke `reverb`
- ✅ config/broadcasting.php configured
- ✅ .env variables added

---

## 🚀 Quick Start

### 1. Start Reverb Server

```bash
php artisan reverb:start
```

Server akan berjalan di `ws://localhost:8080`

### 2. Run Migrations

```bash
php artisan migrate
```

### 3. Test API Endpoints

Semua endpoints yang sudah dibuat akan bekerja sama dengan Reverb:

```bash
# Send Message
curl -X POST http://localhost:8000/api/communities/1/messages \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"message": "Hello!"}'

# Get Messages
curl -X GET "http://localhost:8000/api/communities/1/messages?page=1" \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN"
```

---

## 📝 Channel Authorization

File `routes/channels.php` already configured untuk private channel authorization:

```php
Broadcast::channel('community.{communityId}', function ($user, $communityId) {
    $community = Community::find($communityId);
    
    if ($community && $community->isMember($user)) {
        return [
            'id' => $user->id,
            'email' => $user->email,
        ];
    }
    
    return false;
});
```

---

## 🎯 Frontend Setup

### Install Dependencies

```bash
npm install laravel-echo
```

### Configure Laravel Echo (app.js atau bootstrap.js)

```javascript
import Echo from 'laravel-echo';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

### Add Reverb to .env for Frontend

```env
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Listen to Messages

```javascript
// Subscribe to private community channel
window.Echo.private(`community.${communityId}`)
    .listen('CommunityMessageSent', (event) => {
        console.log('New message:', event);
        // Update UI dengan pesan baru
    });
```

---

## 📊 Comparison: Reverb vs beyondcode/laravel-websockets

| Feature | Reverb | beyondcode/websockets |
|---------|--------|------------------------|
| Laravel Support | 10, 11, 12 | Up to 10 |
| Official | ✅ Official Laravel | ✅ Community |
| Maintenance | ✅ Active | ⚠️ Limited |
| Performance | ✅ High | ✅ Good |
| Setup | ✅ Easy (native) | ⚠️ Extra package |
| Free | ✅ Yes | ✅ Yes |
| Cloud Version | ✅ Laravel.io | ✗ N/A |

**Reverb adalah pilihan terbaik untuk Laravel 12!**

---

## 🔧 Configuration

### .env Variables

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=12345
REVERB_APP_KEY=laravel-reverb-key
REVERB_APP_SECRET=laravel-reverb-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Development vs Production

**Development:**
```env
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

**Production:**
```env
REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SCHEME=https
```

---

## 📡 Real-time Flow

```
1. Client POST /api/communities/{id}/messages
   ↓
2. ChatController::sendMessage()
   ├── Validate membership
   ├── Save to database
   └── broadcast(new CommunityMessageSent($message))->toOthers()
   ↓
3. Reverb WebSocket Server
   └── Broadcast to private-community.{id}
   ↓
4. Connected Clients (Laravel Echo)
   ├── Listen on private channel
   ├── Receive 'CommunityMessageSent' event
   └── Update UI in real-time
```

---

## ✨ Key Features (All Still Working!)

✅ Real-time messaging dengan private channels  
✅ Membership authorization  
✅ Sanctum authentication  
✅ Message pagination (20 per page)  
✅ Message history (ascending order)  
✅ Standard JSON response format  
✅ Eager loading relasi user  
✅ Broadcasting dengan toOthers()  
✅ Clean code & best practices  

---

## 🚀 Running the System

### Terminal 1: Start Reverb Server
```bash
php artisan reverb:start
```

Output akan terlihat seperti:
```
Starting Reverb server...
 INFO  Reverb server started successfully.
 INFO  Listening on http://localhost:8080
```

### Terminal 2: Run Laravel Dev Server
```bash
php artisan serve
```

### Terminal 3: Build Frontend Assets (jika pakai Vite)
```bash
npm run dev
```

---

## 🧪 Testing with Postman

### Setup
1. Get Sanctum token dari `/api/auth/login`
2. Copy token ke Authorization header

### Test Send Message
```
POST http://localhost:8000/api/communities/1/messages
Authorization: Bearer {token}
Content-Type: application/json

Body:
{
  "message": "Hello community!"
}
```

### Test Get Messages
```
GET http://localhost:8000/api/communities/1/messages?page=1
Authorization: Bearer {token}
```

---

## 📚 Documentation Files

Semua dokumentasi yang sudah dibuat tetap berlaku dan kompatibel:

- ✅ **CHAT_SYSTEM_SETUP.md** - Panduan lengkap (update untuk Reverb)
- ✅ **CHAT_API_REFERENCE.md** - API reference
- ✅ **CHAT_SYSTEM_FILES.md** - File structure
- ✅ **FRONTEND_IMPLEMENTATION.md** - Frontend examples
- ✅ **IMPLEMENTATION_SUMMARY.md** - Summary
- ✅ **VERIFICATION_CHECKLIST.md** - Testing checklist

---

## 🔥 Next Steps

1. **Run Reverb Server**
   ```bash
   php artisan reverb:start
   ```

2. **Test API in Postman**
   - Create community and members
   - Send message
   - Get messages

3. **Setup Frontend**
   - Install laravel-echo
   - Configure Reverb credentials
   - Implement chat component

4. **Deploy (Optional)**
   - Setup Reverb on production
   - Configure SSL/TLS
   - Setup domain

---

## 🆘 Troubleshooting

### Reverb Server Won't Start
```bash
# Check port 8080 is available
lsof -i :8080

# If port in use, kill the process
kill -9 <PID>
```

### WebSocket Connection Failed
- Verify Reverb server running: `php artisan reverb:start`
- Check port 8080 open
- Check BROADCAST_CONNECTION=reverb in .env
- Check browser console for errors

### Authorization Error
- Verify user is community member
- Check channel authorization in routes/channels.php
- Verify Sanctum token is valid

### Messages Not Broadcasting
- Check Reverb server logs
- Verify event dispatched: `broadcast(new CommunityMessageSent($message))->toOthers()`
- Check Laravel logs: `storage/logs/laravel.log`

---

## 📖 Official Documentation

- **Laravel Reverb Docs:** https://laravel.com/docs/11/broadcasting#reverb
- **Laravel Broadcasting:** https://laravel.com/docs/11/broadcasting
- **Laravel Echo:** https://github.com/laravel/echo

---

## 🎉 You're All Set!

Sistem real-time chat Anda sekarang fully configured dengan Laravel Reverb (WebSocket solution resmi Laravel).

**Next: Start Reverb Server!**
```bash
php artisan reverb:start
```

Happy coding! 🚀
