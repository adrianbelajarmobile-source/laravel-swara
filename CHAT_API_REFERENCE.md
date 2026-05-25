# Community Chat API Quick Reference

## Authentication
Semua endpoint memerlukan token Sanctum di header:
```
Authorization: Bearer {sanctum_token}
```

## Endpoints

### 1. Send Message to Community

**Endpoint:**
```
POST /api/communities/{community}/messages
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "message": "Konten pesan di sini"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "community_id": 5,
    "message": "Konten pesan di sini",
    "user": {
      "id": 10,
      "email": "user@example.com",
      "profile": {
        "id": 1,
        "user_id": 10,
        "bio": "Bio pengguna",
        "photo_profile": "path/to/photo.jpg"
      }
    },
    "created_at": "2026-03-03T15:30:45.000000Z",
    "updated_at": "2026-03-03T15:30:45.000000Z"
  }
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "You are not a member of this community"
}
```

---

### 2. Get Messages from Community

**Endpoint:**
```
GET /api/communities/{community}/messages?page=1
```

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional, default: 1): Halaman yang ingin diambil

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "community_id": 5,
      "message": "Pesan pertama",
      "user": {
        "id": 10,
        "email": "user@example.com",
        "profile": {
          "id": 1,
          "user_id": 10,
          "bio": "Bio pengguna",
          "photo_profile": "path/to/photo.jpg"
        }
      },
      "created_at": "2026-03-03T15:25:00.000000Z",
      "updated_at": "2026-03-03T15:25:00.000000Z"
    },
    {
      "id": 2,
      "community_id": 5,
      "message": "Pesan kedua",
      "user": {
        "id": 11,
        "email": "user2@example.com",
        "profile": {
          "id": 2,
          "user_id": 11,
          "bio": "Bio pengguna 2",
          "photo_profile": "path/to/photo2.jpg"
        }
      },
      "created_at": "2026-03-03T15:30:45.000000Z",
      "updated_at": "2026-03-03T15:30:45.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 105,
    "last_page": 6,
    "from": 1,
    "to": 20
  }
}
```

**Error Response (403):**
```json
{
  "success": false,
  "message": "You are not a member of this community"
}
```

---

## Real-Time Broadcasting

Ketika pesan baru dikirim, event `CommunityMessageSent` akan di-broadcast ke channel `private-community.{community_id}`.

### Event Structure:
```javascript
{
  "id": 1,
  "community_id": 5,
  "message": "Konten pesan",
  "user": {
    "id": 10,
    "email": "user@example.com",
    "profile": {...}
  },
  "created_at": "2026-03-03T15:30:45Z",
  "updated_at": "2026-03-03T15:30:45Z"
}
```

### Listening dari Frontend:
```javascript
// Dengan Laravel Echo
window.Echo.private(`community.${communityId}`)
    .listen('CommunityMessageSent', (data) => {
        console.log('Pesan baru:', data);
    });
```

---

## Notes

- ✅ Pesan diurutkan ascending berdasarkan `created_at`
- ✅ Pagination: 20 pesan per halaman
- ✅ Pengirim pesan tidak akan menerima event sendiri (menggunakan `toOthers()`)
- ✅ User hanya bisa melihat/mengirim pesan jika mereka member komunitas
- ✅ Relasi user dengan profile sudah di-eager load
- ✅ Message validation: minimal 1 karakter, maksimal 5000 karakter

---

## Example Usage

### cURL
```bash
# Send Message
curl -X POST http://localhost:8000/api/communities/5/messages \
  -H "Authorization: Bearer token_here" \
  -H "Content-Type: application/json" \
  -d '{"message": "Hello community!"}'

# Get Messages
curl -X GET http://localhost:8000/api/communities/5/messages?page=1 \
  -H "Authorization: Bearer token_here"
```

### JavaScript/Fetch
```javascript
// Send Message
fetch(`/api/communities/5/messages`, {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        message: 'Hello community!'
    })
})
.then(res => res.json())
.then(json => console.log(json));

// Get Messages
fetch(`/api/communities/5/messages?page=1`, {
    headers: {
        'Authorization': `Bearer ${token}`
    }
})
.then(res => res.json())
.then(json => console.log(json));
```

---

## Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request berhasil |
| 201 | Created - Pesan berhasil dibuat |
| 400 | Bad Request - Validasi gagal |
| 403 | Forbidden - User bukan member komunitas |
| 401 | Unauthorized - Token tidak valid |
| 500 | Internal Server Error - Error server |

---

## Validation Rules

### Send Message
- `message` (required): String, min 1 karakter, max 5000 karakter

### Get Messages
- `page` (optional): Integer, min value 1
