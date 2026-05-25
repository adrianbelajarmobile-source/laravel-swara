# Real-Time Community Chat System Setup Guide

Sistem real-time group chat menggunakan Laravel WebSockets telah berhasil diimplementasikan.

## Instalasi Dependencies

### 1. Install Laravel WebSockets Package
```bash
composer require beyondcode/laravel-websockets
```

### 2. Publish Configuration
```bash
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --force
```

### 3. Generate WebSockets Configuration
```bash
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider"
```

## Konfigurasi Environment

Tambahkan ke `.env`:

```env
# Broadcasting Configuration
BROADCAST_DRIVER=websockets

# WebSockets Configuration
PUSHER_APP_ID=12345
PUSHER_APP_KEY=laravel-websockets-key
PUSHER_APP_SECRET=laravel-websockets-secret
PUSHER_HOST=localhost
PUSHER_PORT=6001
PUSHER_SCHEME=http

WEBSOCKETS_HOST=localhost
WEBSOCKETS_PORT=6001
WEBSOCKETS_SCHEME=http
```

## Database Migrations

Jalankan migrations untuk membuat tabel-tabel baru:

```bash
php artisan migrate
```

Tabel yang dibuat:
- `communities` - Menyimpan komunitas yang dibuat oleh influencer
- `community_members` - Menyimpan anggota komunitas dengan role mereka
- `messages` - Menyimpan pesan chat dalam komunitas

## Files yang Dibuat

### Models
- `app/Models/Community.php` - Model komunitas dengan relasi
- `app/Models/CommunityMember.php` - Model anggota komunitas
- `app/Models/Message.php` - Model pesan chat
- `app/Models/User.php` - Updated dengan relasi baru

### Events
- `app/Events/CommunityMessageSent.php` - Event untuk broadcast pesan

### Controllers
- `app/Http/Controllers/Api/ChatController.php` - Controller untuk chat dengan 2 method:
  - `sendMessage()` - POST /api/communities/{id}/messages
  - `getMessagesByCommunity()` - GET /api/communities/{id}/messages

### Routes
- `routes/api.php` - Ditambahkan 2 route untuk chat
- `routes/channels.php` - Authorization untuk private channel

### Config
- `config/broadcasting.php` - Konfigurasi broadcasting dengan WebSockets support

## Struktur API Endpoints

### Send Message
```
POST /api/communities/{community}/messages
Authorization: Bearer {sanctum_token}

Body:
{
  "message": "Konten pesan"
}

Response:
{
  "success": true,
  "data": {
    "id": 1,
    "community_id": 1,
    "message": "Konten pesan",
    "user": {
      "id": 1,
      "email": "user@example.com",
      "profile": {...}
    },
    "created_at": "2026-03-03T10:00:00Z",
    "updated_at": "2026-03-03T10:00:00Z"
  }
}
```

### Get Messages
```
GET /api/communities/{community}/messages?page=1
Authorization: Bearer {sanctum_token}

Response:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "community_id": 1,
      "message": "Konten pesan",
      "user": {
        "id": 1,
        "email": "user@example.com",
        "profile": {...}
      },
      "created_at": "2026-03-03T10:00:00Z",
      "updated_at": "2026-03-03T10:00:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5,
    "from": 1,
    "to": 20
  }
}
```

## Authorization & Security

### Channel Authorization
User hanya bisa subscribe ke `community.{community_id}` jika mereka adalah anggota komunitas tersebut. Implementasi di `routes/channels.php`.

### API Authorization
Semua endpoints menggunakan `auth:sanctum` middleware untuk memastikan user sudah login.

## Fitur-Fitur

1. **Real-time Broadcasting**: Pesan dikirim ke semua anggota komunitas secara real-time
2. **Pagination**: Pesan diambil dengan pagination (per page: 20)
3. **Message History**: Pesan diurutkan ascending berdasarkan created_at
4. **User Information**: Setiap pesan mencakup informasi lengkap pengirim
5. **Private Channels**: Hanya anggota komunitas yang bisa menerima pesan
6. **toOthers()**: Pengirim pesan tidak menerima event sendiri (UX lebih baik)

## Menjalankan WebSocket Server

```bash
php artisan websockets:serve
```

Server akan berjalan di `http://localhost:6001`

Untuk development dengan SSL:
```bash
php artisan websockets:serve --host=0.0.0.0 --port=6001
```

## Client-Side Implementation

### Menggunakan Laravel Echo (JavaScript)

```javascript
// Register listener di browser
window.Echo.private(`community.${communityId}`)
    .listen('CommunityMessageSent', (event) => {
        console.log('Pesan diterima:', event);
        // Update UI dengan pesan baru
    });

// Mengirim pesan
fetch(`/api/communities/${communityId}/messages`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
        message: 'Konten pesan'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Pesan terkirim:', data.data);
    }
});
```

## Best Practices Implemented

✅ Clean Code - Kode terstruktur dengan baik
✅ Eager Loading - Relasi user dimuat sekaligus
✅ Pagination - Menghindari overload data
✅ Authorization - Channel authorization di routes/channels.php
✅ Response Format - Konsisten dengan response JSON standard
✅ Error Handling - Validasi input dan membership check
✅ Indexing - Database queries dioptimasi dengan indexing
✅ Broadcasting - Menggunakan toOthers() untuk UX lebih baik

## Troubleshooting

### WebSocket Connection Failed
- Pastikan `php artisan websockets:serve` berjalan
- Cek port 6001 tidak terpakai
- Cek BROADCAST_DRIVER di .env adalah `websockets`

### Messages Not Broadcasting
- Verifikasi user adalah member komunitas
- Check logs: `storage/logs/laravel.log`
- Pastikan event di-broadcast dengan `broadcast(new CommunityMessageSent($message))->toOthers()`

### Database Errors
- Jalankan `php artisan migrate`
- Check koneksi database di .env
- Verifikasi foreign key constraints

## Next Steps (Optional Features)

1. **Message Reactions** - Tambahkan emoji reactions
2. **File Upload** - Support upload gambar/file dalam chat
3. **Message Search** - Search functionality
4. **Archive Community** - Soft delete untuk komunitas
5. **User Typing Indicator** - Tampilkan "user sedang mengetik"
6. **Message Editing** - Edit pesan yang sudah dikirim
7. **Message Deletion** - Hapus pesan (soft delete)
8. **Read Receipts** - Tanda pesan sudah dibaca
9. **Admin Features** - Mute/kick members
10. **Notifications** - Push notifications untuk pesan baru
