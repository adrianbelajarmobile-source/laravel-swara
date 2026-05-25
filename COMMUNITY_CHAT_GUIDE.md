# 🎯 Community Management & Chat - Complete Guide

Panduan lengkap untuk membuat komunitas, join, manage members, dan chat.

---

## 🏗️ MEMBUAT KOMUNITAS BARU

### Endpoint
```
POST /api/communities
```

### Headers
```
Authorization: Bearer YOUR_SANCTUM_TOKEN
Content-Type: application/json
```

### Request Body
```json
{
  "name": "Komunitas Peduli Lingkungan",
  "description": "Komunitas untuk berbagi tips menjaga lingkungan sekitar"
}
```

### Validation
- `name` (required): string, min 3 chars, max 100 chars
- `description` (optional): string, max 500 chars

### Success Response (201 Created)
```json
{
  "success": true,
  "message": "Community created successfully",
  "data": {
    "id": 1,
    "name": "Komunitas Peduli Lingkungan",
    "description": "Komunitas untuk berbagi tips menjaga lingkungan sekitar",
    "created_by": 10,
    "created_at": "2026-03-03T15:30:45.000000Z"
  }
}
```

### Catatan
- ✅ Creator otomatis menjadi member dengan role **influencer**
- ✅ Creator bisa manage members dan delete komunitas
- ❌ Non-influencer juga bisa buat? Tergantung business logic (sekarang semua user bisa)

### Example cURL
```bash
curl -X POST http://localhost:8000/api/communities \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Komunitas Peduli",
    "description": "Komunitas untuk peduli lingkungan"
  }'
```

---

## 👥 JOIN KOMUNITAS

### Endpoint
```
POST /api/communities/{community}/join
```

**Parameter:**
- `{community}` = Community ID

### Headers
```
Authorization: Bearer YOUR_SANCTUM_TOKEN
```

### Request Body
```json
{}
// (Tidak ada body, hanya auth token)
```

### Success Response (201 Created)
```json
{
  "success": true,
  "message": "Successfully joined the community",
  "data": {
    "community_id": 1,
    "community_name": "Komunitas Peduli Lingkungan",
    "role": "pegiat",
    "joined_at": "2026-03-03T15:35:20.000000Z"
  }
}
```

### Error Response (400)
```json
{
  "success": false,
  "message": "You are already a member of this community"
}
```

### Catatan
- ✅ User yang join otomatis dengan role **pegiat**
- ✅ User bisa join komunitas apapun (no approval needed)
- 📌 Role: `influencer` = creator/admin, `pegiat` = member biasa

### Example cURL
```bash
curl -X POST http://localhost:8000/api/communities/1/join \
  -H "Authorization: Bearer TOKEN"
```

---

## ✅ MENYETUJUI / MANAGE MEMBERS

### 1. UPDATE MEMBER ROLE (Ubah pegiat jadi influencer)

**Endpoint:**
```
PATCH /api/communities/{community}/members/{member_id}
```

**Headers:**
```
Authorization: Bearer CREATOR_TOKEN
Content-Type: application/json
```

**Request Body:**
```json
{
  "role": "influencer"
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Member role updated",
  "data": {
    "member_id": 5,
    "user_id": 11,
    "role": "influencer"
  }
}
```

**Validation:**
- `role` (required): only "influencer" or "pegiat"

**Authorization:**
- ✅ Hanya creator komunitas yang bisa
- ❌ Member biasa tidak bisa

### Example cURL
```bash
curl -X PATCH http://localhost:8000/api/communities/1/members/5 \
  -H "Authorization: Bearer CREATOR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"role": "influencer"}'
```

---

### 2. REMOVE MEMBER (Kick member dari komunitas)

**Endpoint:**
```
DELETE /api/communities/{community}/members/{member_id}
```

**Headers:**
```
Authorization: Bearer CREATOR_TOKEN
```

**Success Response:**
```json
{
  "success": true,
  "message": "Member removed from community"
}
```

**Authorization:**
- ✅ Hanya creator komunitas yang bisa
- ❌ Member tidak bisa remove sendiri

### Example cURL
```bash
curl -X DELETE http://localhost:8000/api/communities/1/members/5 \
  -H "Authorization: Bearer CREATOR_TOKEN"
```

---

### 3. LEAVE KOMUNITAS (User keluar sendiri)

**Endpoint:**
```
POST /api/communities/{community}/leave
```

**Headers:**
```
Authorization: Bearer USER_TOKEN
```

**Success Response:**
```json
{
  "success": true,
  "message": "Successfully left the community"
}
```

**Catatan:**
- ✅ Semua member bisa keluar
- ❌ Creator tidak bisa keluar (harus delete komunitas)

### Example cURL
```bash
curl -X POST http://localhost:8000/api/communities/1/leave \
  -H "Authorization: Bearer TOKEN"
```

---

## 📋 GET KOMUNITAS

### 1. LIST SEMUA KOMUNITAS (dengan info member count)

**Endpoint:**
```
GET /api/communities?page=1
```

**Headers:**
```
Authorization: Bearer YOUR_SANCTUM_TOKEN
```

**Success Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Komunitas Peduli Lingkungan",
      "description": "Komunitas untuk berbagi tips",
      "created_by": 10,
      "creator": {
        "id": 10,
        "email": "influencer@example.com"
      },
      "members_count": 15,
      "is_member": true,
      "user_role": "pegiat",
      "created_at": "2026-03-03T15:30:45.000000Z"
    },
    {
      "id": 2,
      "name": "Komunitas Daur Ulang",
      "description": "Berbagi ide daur ulang",
      "created_by": 11,
      "creator": {
        "id": 11,
        "email": "user2@example.com"
      },
      "members_count": 8,
      "is_member": false,
      "user_role": null,
      "created_at": "2026-03-03T14:20:10.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "last_page": 3
  }
}
```

**Catatan:**
- `is_member` = apakah user sudah jadi member
- `user_role` = role user jika sudah member
- Per page: 20 komunitas

### Example cURL
```bash
curl -X GET "http://localhost:8000/api/communities?page=1" \
  -H "Authorization: Bearer TOKEN"
```

---

### 2. KOMUNITAS YANG DIBUAT USER

**Endpoint:**
```
GET /api/communities/my/created
```

**Headers:**
```
Authorization: Bearer USER_TOKEN
```

**Success Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Komunitas Peduli Lingkungan",
      "description": "Komunitas untuk berbagi tips",
      "members_count": 15,
      "created_at": "2026-03-03T15:30:45.000000Z"
    },
    {
      "id": 3,
      "name": "Komunitas Hemat Energi",
      "description": "Tips hemat energi rumah",
      "members_count": 10,
      "created_at": "2026-03-02T10:15:30.000000Z"
    }
  ]
}
```

### Example cURL
```bash
curl -X GET http://localhost:8000/api/communities/my/created \
  -H "Authorization: Bearer TOKEN"
```

---

### 3. KOMUNITAS YANG DIIKUTI USER

**Endpoint:**
```
GET /api/communities/my/joined
```

**Headers:**
```
Authorization: Bearer USER_TOKEN
```

**Success Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Komunitas Peduli Lingkungan",
      "description": "Komunitas untuk berbagi tips",
      "creator_id": 10,
      "user_role_in_community": "pegiat",
      "joined_at": "2026-03-03T15:30:45.000000Z"
    },
    {
      "id": 2,
      "name": "Komunitas Daur Ulang",
      "description": "Berbagi ide daur ulang",
      "creator_id": 11,
      "user_role_in_community": "influencer",
      "joined_at": "2026-03-01T08:20:10.000000Z"
    }
  ]
}
```

### Example cURL
```bash
curl -X GET http://localhost:8000/api/communities/my/joined \
  -H "Authorization: Bearer TOKEN"
```

---

### 4. DETAIL KOMUNITAS + LIST MEMBERS

**Endpoint:**
```
GET /api/communities/{community}
```

**Headers:**
```
Authorization: Bearer YOUR_SANCTUM_TOKEN
```

**Success Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Komunitas Peduli Lingkungan",
    "description": "Komunitas untuk berbagi tips",
    "created_by": 10,
    "creator_email": "influencer@example.com",
    "members_count": 3,
    "members": [
      {
        "id": 1,
        "user_id": 10,
        "email": "influencer@example.com",
        "role": "influencer",
        "joined_at": "2026-03-03T15:30:45.000000Z"
      },
      {
        "id": 2,
        "user_id": 11,
        "email": "user1@example.com",
        "role": "pegiat",
        "joined_at": "2026-03-03T15:35:20.000000Z"
      },
      {
        "id": 3,
        "user_id": 12,
        "email": "user2@example.com",
        "role": "pegiat",
        "joined_at": "2026-03-03T16:00:00.000000Z"
      }
    ],
    "is_member": true,
    "user_role": "pegiat",
    "created_at": "2026-03-03T15:30:45.000000Z"
  }
}
```

### Example cURL
```bash
curl -X GET http://localhost:8000/api/communities/1 \
  -H "Authorization: Bearer TOKEN"
```

---

### 5. LIST MEMBERS (alternatif, tanpa detail komunitas)

**Endpoint:**
```
GET /api/communities/{community}/members
```

**Headers:**
```
Authorization: Bearer YOUR_SANCTUM_TOKEN
```

**Success Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 10,
      "email": "influencer@example.com",
      "role": "influencer",
      "joined_at": "2026-03-03T15:30:45.000000Z"
    },
    {
      "id": 2,
      "user_id": 11,
      "email": "user1@example.com",
      "role": "pegiat",
      "joined_at": "2026-03-03T15:35:20.000000Z"
    }
  ]
}
```

### Example cURL
```bash
curl -X GET http://localhost:8000/api/communities/1/members \
  -H "Authorization: Bearer TOKEN"
```

---

## 💬 KIRIM DAN GET MESSAGES

Sudah dijelaskan di dokumentasi sebelumnya, tapi ringkasnya:

### KIRIM PESAN

**Endpoint:**
```
POST /api/communities/{community}/messages
```

**Headers:**
```
Authorization: Bearer YOUR_SANCTUM_TOKEN
Content-Type: application/json
```

**Request Body:**
```json
{
  "message": "Halo semua! Ayo jaga lingkungan kita"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "community_id": 1,
    "message": "Halo semua! Ayo jaga lingkungan kita",
    "user": {
      "id": 10,
      "email": "user@example.com",
      "profile": { ... }
    },
    "created_at": "2026-03-03T16:45:30.000000Z",
    "updated_at": "2026-03-03T16:45:30.000000Z"
  }
}
```

**✨ Real-time:** Pesan akan di-broadcast ke semua members melalui WebSocket!

### Example cURL
```bash
curl -X POST http://localhost:8000/api/communities/1/messages \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"message": "Halo semua!"}'
```

---

### GET MESSAGES (dengan pagination)

**Endpoint:**
```
GET /api/communities/{community}/messages?page=1
```

**Headers:**
```
Authorization: Bearer YOUR_SANCTUM_TOKEN
```

**Success Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "community_id": 1,
      "message": "Halo semua! Ayo jaga lingkungan",
      "user": {
        "id": 10,
        "email": "user1@example.com",
        "profile": { ... }
      },
      "created_at": "2026-03-03T16:45:30.000000Z",
      "updated_at": "2026-03-03T16:45:30.000000Z"
    },
    {
      "id": 2,
      "community_id": 1,
      "message": "Setuju! Mari kita mulai dari hal kecil",
      "user": {
        "id": 11,
        "email": "user2@example.com",
        "profile": { ... }
      },
      "created_at": "2026-03-03T16:50:15.000000Z",
      "updated_at": "2026-03-03T16:50:15.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8,
    "from": 1,
    "to": 20
  }
}
```

**Catatan:**
- Messages diurutkan ascending (tertua duluan)
- Per page: 20 messages
- Eager load user & profile

### Example cURL
```bash
curl -X GET "http://localhost:8000/api/communities/1/messages?page=1" \
  -H "Authorization: Bearer TOKEN"
```

---

## 🗑️ DELETE KOMUNITAS

**Endpoint:**
```
DELETE /api/communities/{community}
```

**Headers:**
```
Authorization: Bearer CREATOR_TOKEN
```

**Success Response:**
```json
{
  "success": true,
  "message": "Community deleted successfully"
}
```

**Authorization:**
- ✅ Hanya creator yang bisa
- ❌ Member biasa tidak bisa delete

**Catatan:**
- ✅ Cascade delete semua members dan messages
- ⚠️ PERMANENT - tidak bisa di-undo

### Example cURL
```bash
curl -X DELETE http://localhost:8000/api/communities/1 \
  -H "Authorization: Bearer CREATOR_TOKEN"
```

---

## 🔄 COMPLETE FLOW EXAMPLE

### Skenario: User A membuat komunitas, User B join, kirim message

### Step 1: User A Create Komunitas
```bash
# User A membuat komunitas
curl -X POST http://localhost:8000/api/communities \
  -H "Authorization: Bearer USER_A_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Komunitas Peduli Lingkungan",
    "description": "Mari jaga lingkungan bersama"
  }'

# Response: Community ID = 1
```

### Step 2: Get List Komunitas (User B)
```bash
# User B list komunitas
curl -X GET "http://localhost:8000/api/communities?page=1" \
  -H "Authorization: Bearer USER_B_TOKEN"

# Terlihat Komunitas ID 1 dibuat User A
# is_member: false
# user_role: null
```

### Step 3: User B Join Komunitas
```bash
# User B join komunitas 1
curl -X POST http://localhost:8000/api/communities/1/join \
  -H "Authorization: Bearer USER_B_TOKEN"

# Response: role = "pegiat"
```

### Step 4: User B Kirim Pesan
```bash
# User B kirim pesan
curl -X POST http://localhost:8000/api/communities/1/messages \
  -H "Authorization: Bearer USER_B_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"message": "Halo, senang bergabung!"}'

# ✨ Pesan langsung di-broadcast ke User A secara real-time!
```

### Step 5: User A Get Messages
```bash
# User A get message history
curl -X GET "http://localhost:8000/api/communities/1/messages?page=1" \
  -H "Authorization: Bearer USER_A_TOKEN"

# Terlihat pesan dari User B
```

### Step 6: User A Promote User B
```bash
# User A promote User B jadi influencer
# Client dulu dapat member ID dari GET /api/communities/1/members
# Dari response, member_id User B = 2

curl -X PATCH http://localhost:8000/api/communities/1/members/2 \
  -H "Authorization: Bearer USER_A_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"role": "influencer"}'

# Response: User B sekarang role = "influencer"
```

---

## 📊 AUTHORIZATION SUMMARY

| Action | Creator | Member | Non-Member |
|--------|---------|--------|------------|
| Create komunitas | ✅ Ya | ❌ No | ❌ No |
| Join komunitas | ❌ N/A | ❌ No | ✅ Ya |
| Leave komunitas | ❌ No | ✅ Ya | ❌ N/A |
| Send message | ✅ Ya | ✅ Ya | ❌ No |
| Get messages | ✅ Ya | ✅ Ya | ❌ No |
| View members | ✅ Ya | ✅ Ya | ⚠️ Yes |
| Update member role | ✅ Ya | ❌ No | ❌ No |
| Remove member | ✅ Ya | ❌ No | ❌ No |
| Delete komunitas | ✅ Ya | ❌ No | ❌ No |

---

## 🚀 API ENDPOINTS SUMMARY

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/communities` | Create komunitas |
| GET | `/api/communities` | List semua komunitas |
| GET | `/api/communities/my/created` | Komunitas yang dibuat user |
| GET | `/api/communities/my/joined` | Komunitas yang diikuti user |
| GET | `/api/communities/{id}` | Detail komunitas + members |
| POST | `/api/communities/{id}/join` | Join komunitas |
| POST | `/api/communities/{id}/leave` | Leave komunitas |
| GET | `/api/communities/{id}/members` | List members |
| PATCH | `/api/communities/{id}/members/{member_id}` | Update member role |
| DELETE | `/api/communities/{id}/members/{member_id}` | Remove member |
| DELETE | `/api/communities/{id}` | Delete komunitas |
| POST | `/api/communities/{id}/messages` | Send message |
| GET | `/api/communities/{id}/messages` | Get messages |

---

## ✨ KEY FEATURES

✅ **Membuat Komunitas** - Creator otomatis jadi influencer  
✅ **Join Komunitas** - Tanpa approval, instant join dengan role pegiat  
✅ **Manage Members** - Creator bisa promote/demote/remove  
✅ **Real-time Chat** - Messages langsung di-broadcast via WebSocket  
✅ **Authorization** - Hanya members yang bisa chat dan access  
✅ **Pagination** - Semua list endpoint support pagination  
✅ **Error Handling** - Clear error messages dengan status codes  

---

## 🔐 SECURITY

✅ Only authenticated users (auth:sanctum)  
✅ Authorization checks di setiap endpoint  
✅ Membership verification  
✅ Creator verification on protected actions  
✅ Input validation  

---

## 📚 NEXT STEPS

1. Test semua endpoints dengan Postman
2. Setup frontend dengan Laravel Echo untuk real-time
3. Build UI component untuk:
   - List komunitas
   - Join komunitas
   - Manage members (for creators)
   - Chat interface
4. Deploy ke production

---

Happy coding! 🚀
