# Community Chat System - File Structure & Summary

## Database Migrations (dalam `database/migrations/`)

### 1. `2026_03_01_100000_create_communities_table.php`
Membuat tabel `communities` untuk menyimpan data komunitas yang dibuat oleh influencer.

**Kolom:**
- `id` (PK)
- `name` - Nama komunitas
- `description` - Deskripsi komunitas (optional)
- `created_by` - User ID influencer pembuat (FK)
- `timestamps`

**Indexing:**
- Foreign key: `created_by`

---

### 2. `2026_03_01_100100_create_community_members_table.php`
Membuat tabel `community_members` untuk menyimpan anggota komunitas dengan role mereka.

**Kolom:**
- `id` (PK)
- `community_id` - FK ke communities
- `user_id` - FK ke users
- `role` - Enum: 'influencer' atau 'pegiat'
- `timestamps`

**Constraints:**
- Unique: `(community_id, user_id)` - Satu user hanya bisa join sekali per komunitas
- Foreign keys dengan cascade delete

**Indexing:**
- `community_id`, `user_id`, unique constraint

---

### 3. `2026_03_01_100200_create_messages_table.php`
Membuat tabel `messages` untuk menyimpan pesan chat dalam komunitas.

**Kolom:**
- `id` (PK)
- `community_id` - FK ke communities
- `user_id` - FK ke users (pengirim pesan)
- `message` - Konten pesan (longText)
- `timestamps`

**Constraints:**
- Foreign keys dengan cascade delete

**Indexing:**
- `community_id`, `user_id`, `(community_id, created_at)`

---

## Models (dalam `app/Models/`)

### 1. `Community.php`
Model untuk tabel communities dengan relasi:
- `creator()` - BelongsTo User (pembuat komunitas)
- `members()` - HasMany CommunityMember
- `messages()` - HasMany Message
- Helper methods:
  - `isMember($user)` - Cek apakah user adalah member
  - `getMemberRole($user)` - Dapatkan role user dalam komunitas

**Fillable:** `name`, `description`, `created_by`

---

### 2. `CommunityMember.php`
Model untuk tabel community_members dengan relasi:
- `community()` - BelongsTo Community
- `user()` - BelongsTo User
- Helper methods:
  - `isInfluencer()` - Cek apakah member adalah influencer
  - `isPegiat()` - Cek apakah member adalah pegiat

**Fillable:** `community_id`, `user_id`, `role`

---

### 3. `Message.php`
Model untuk tabel messages dengan relasi:
- `community()` - BelongsTo Community
- `user()` - BelongsTo User

**Fillable:** `community_id`, `user_id`, `message`

---

### 4. `User.php` (Updated)
Ditambahkan 3 relasi baru:
- `createdCommunities()` - HasMany Community (komunitas yang dibuat user)
- `communityMemberships()` - HasMany CommunityMember
- `messages()` - HasMany Message

---

## Events (dalam `app/Events/`)

### 1. `CommunityMessageSent.php`
Event untuk real-time broadcasting pesan.

**Implements:** `ShouldBroadcast`

**Channel:** `PrivateChannel('community.{community_id}')`

**Broadcast Data:**
```php
[
    'id' => Message ID,
    'community_id' => Community ID,
    'message' => Message content,
    'user' => [
        'id' => User ID,
        'email' => User email,
        'profile' => User profile object
    ],
    'created_at' => ISO 8601 format,
    'updated_at' => ISO 8601 format,
]
```

**Event Name:** `message.sent` (via `broadcastAs()`)

---

## Controllers (dalam `app/Http/Controllers/Api/`)

### 1. `ChatController.php`
Controller utama untuk chat functionality.

**Methods:**

#### `sendMessage(Request $request, Community $community)`
- **Route:** `POST /api/communities/{community}/messages`
- **Auth:** Requires `auth:sanctum`
- **Validation:**
  - `message` (required): string, min:1, max:5000
- **Returns:**
  - 201 Created - Message terkirim dengan data lengkap
  - 403 Forbidden - User bukan member komunitas
- **Broadcasting:** `broadcast(new CommunityMessageSent($message))->toOthers()`

#### `getMessagesByCommunity(Request $request, Community $community)`
- **Route:** `GET /api/communities/{community}/messages?page=1`
- **Auth:** Requires `auth:sanctum`
- **Query Params:**
  - `page` (optional): integer, min:1
- **Returns:**
  - 200 OK - Array of 20 messages with pagination info
  - 403 Forbidden - User bukan member komunitas
- **Features:**
  - Per page: 20 messages
  - Order: ascending by `created_at`
  - Eager loading: `user.profile`
  - Pagination metadata included

#### `formatMessage(Message $message)` (Private)
- Helper method untuk format message response
- Mengembalikan array dengan struktur yang konsisten

---

## Routes (dalam `routes/`)

### 1. `api.php` (Updated)
Ditambahkan:

```php
use App\Http\Controllers\Api\ChatController;

// CHAT COMMUNITY
Route::post('/communities/{community}/messages', [ChatController::class, 'sendMessage']);
Route::get('/communities/{community}/messages', [ChatController::class, 'getMessagesByCommunity']);
```

Semua route dalam `auth:sanctum` middleware.

---

### 2. `channels.php` (Created)
File baru untuk authorization private channels.

**Channel Authorization:**
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

**Features:**
- User hanya bisa subscribe ke channel jika member komunitas
- Returns user info jika authorized, false jika tidak

---

## Configuration (dalam `config/`)

### 1. `broadcasting.php` (Created)
File konfigurasi untuk broadcasting dengan WebSockets support.

**Default Driver:** `env('BROADCAST_DRIVER', 'log')`

**Connections:**
- `pusher` - Pusher service
- `ably` - Ably service
- `redis` - Redis
- `log` - Log driver
- `null` - Null driver
- `websockets` - Laravel WebSockets (beyondcode/laravel-websockets)

**WebSockets Configuration:**
```php
'websockets' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY', 'laravel-websockets-key'),
    'secret' => env('PUSHER_APP_SECRET', 'laravel-websockets-secret'),
    'app_id' => env('PUSHER_APP_ID', '12345'),
    'options' => [
        'host' => env('WEBSOCKETS_HOST', 'localhost'),
        'port' => env('WEBSOCKETS_PORT', 6001),
        'scheme' => env('WEBSOCKETS_SCHEME', 'http'),
        'encrypted' => false,
    ],
]
```

---

## Environment Configuration (`.env.example` - Updated)

Ditambahkan WebSockets configuration:

```env
BROADCAST_CONNECTION=log                          # Change to 'websockets' untuk production
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

---

## Documentation Files (Root)

### 1. `CHAT_SYSTEM_SETUP.md`
Panduan lengkap setup sistem chat mulai dari instalasi, konfigurasi, hingga troubleshooting.

**Sections:**
- Instalasi dependencies
- Konfigurasi environment
- Database migrations
- Files overview
- API endpoints
- Authorization & security
- Features
- Running WebSocket server
- Client-side implementation
- Best practices
- Troubleshooting
- Next steps (optional features)

### 2. `CHAT_API_REFERENCE.md`
Quick reference untuk API endpoints dengan contoh request/response.

**Sections:**
- Authentication
- Endpoints detail
- Real-time broadcasting
- Notes
- Example usage (cURL, JavaScript/Fetch)
- Status codes
- Validation rules

---

## Installation Checklist

- [ ] Run `composer require beyondcode/laravel-websockets`
- [ ] Run `php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --force`
- [ ] Copy `.env.example` ke `.env`
- [ ] Setup database connection di `.env`
- [ ] Run `php artisan migrate`
- [ ] Set `BROADCAST_DRIVER=websockets` di `.env`
- [ ] Run `php artisan websockets:serve`
- [ ] Test endpoints menggunakan Postman atau cURL

---

## Key Features Implemented

✅ **Real-time Broadcasting** - Pesan langsung diterima anggota komunitas lain
✅ **Pagination** - Efisien untuk data besar (20 pesan/halaman)
✅ **Authorization** - Channel & API authorization
✅ **Eager Loading** - User profile dimuat sekaligus
✅ **Validation** - Input validation dengan pesan error jelas
✅ **Standard Response** - JSON response format konsisten
✅ **Clean Code** - Well-documented dan organized
✅ **Database Optimization** - Proper indexing dan foreign keys
✅ **Error Handling** - Proper status codes dan error messages
✅ **toOthers()** - Pengirim tidak menerima event sendiri

---

## Architecture Diagrams

### Database Relationships
```
Users
  ├── 1:N → Communities (via created_by)
  ├── 1:N → CommunityMembers
  └── 1:N → Messages

Communities
  ├── N:1 → Users (via created_by)
  ├── 1:N → CommunityMembers
  └── 1:N → Messages

CommunityMembers
  ├── N:1 → Communities
  └── N:1 → Users

Messages
  ├── N:1 → Communities
  └── N:1 → Users
```

### Message Flow (Broadcasting)
```
1. Client POST /api/communities/{id}/messages
   ↓
2. ChatController::sendMessage()
   ├── Validate membership ✓
   ├── Validate message ✓
   ├── Create message in DB
   ├── Load user relationship
   └── broadcast(new CommunityMessageSent($message))->toOthers()
   ↓
3. WebSocket Server (Laravel WebSockets)
   └── Broadcast to PrivateChannel('community.{id}')
   ↓
4. Connected Clients
   ├── Listen on private channel
   ├── Receive event data
   └── Update UI
```

---

## Next Steps for Frontend

1. Install `laravel-echo` dan `pusher-js`
2. Configure Echo client dengan WebSocket server
3. Listen ke `community.{id}` channel
4. Handle message events
5. Implement UI components
6. Test dengan multiple clients

---

## Performance Considerations

- **Database:** Indexed queries untuk fast retrieval
- **Broadcasting:** Private channels untuk security dan efficiency
- **Pagination:** 20 messages per page untuk optimal UX
- **Eager Loading:** User profiles loaded dengan messages
- **Message Limit:** Max 5000 characters untuk prevent abuse

---

## Security Features

- ✅ Sanctum authentication
- ✅ Channel authorization (membership check)
- ✅ Foreign key constraints
- ✅ Input validation
- ✅ Private channels (only members can listen)
- ✅ toOthers() prevents echo back
