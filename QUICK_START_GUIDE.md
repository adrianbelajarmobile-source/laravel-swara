# 🚀 Quick Start Guide

**Panduan cepat untuk mulai menggunakan sistem chat real-time!**

---

## 1️⃣ SETUP (5 Menit)

### Step 1: Run Migrations
```bash
php artisan migrate
```

**Output yang diharapkan:**
```
Migrating: 2026_03_01_100000_create_communities_table
Migrated: 2026_03_01_100000_create_communities_table (xxx ms)
Migrating: 2026_03_01_100100_create_community_members_table
Migrated: 2026_03_01_100100_create_community_members_table (xxx ms)
Migrating: 2026_03_01_100200_create_messages_table
Migrated: 2026_03_01_100200_create_messages_table (xxx ms)
```

### Step 2: Start Services (3 Terminals)

**Terminal 1: WebSocket Server**
```bash
php artisan reverb:start
```

**Terminal 2: Laravel Server**
```bash
php artisan serve
```

**Terminal 3: (Optional) NPM Dev**
```bash
npm run dev
```

✅ All services running!

---

## 2️⃣ CREATE TEST USERS

### Register User A (Influencer)
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "User A",
    "email": "usera@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "User A",
    "email": "usera@example.com"
  },
  "token": "TOKEN_A" // SAVE THIS!
}
```

### Register User B (Pegiat)
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "User B",
    "email": "userb@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Response:**
```json
{
  "token": "TOKEN_B" // SAVE THIS!
}
```

### Setup Environment Variables
```bash
# Create .env.test or save these for reference
TOKEN_A="<token_dari_user_a>"
TOKEN_B="<token_dari_user_b>"
BASE_URL="http://localhost:8000"
```

---

## 3️⃣ TEST COMMUNITY MANAGEMENT

### A. User A creates community
```bash
TOKEN_A="<paste_token_a>"

curl -X POST http://localhost:8000/api/communities \
  -H "Authorization: Bearer $TOKEN_A" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Komunitas Peduli Lingkungan",
    "description": "Mari jaga lingkungan bersama"
  }'
```

**Response:**
```json
{
  "success": true,
  "community": {
    "id": 1,
    "name": "Komunitas Peduli Lingkungan",
    "description": "Mari jaga lingkungan bersama",
    "created_by": 1,
    "members_count": 1
  }
}
```

✅ **Note:** `COMMUNITY_ID = 1`

---

### B. List all communities (User B)
```bash
TOKEN_B="<paste_token_b>"

curl -X GET "http://localhost:8000/api/communities?page=1" \
  -H "Authorization: Bearer $TOKEN_B"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Komunitas Peduli Lingkungan",
      "description": "Mari jaga lingkungan bersama",
      "creator": {
        "id": 1,
        "name": "User A"
      },
      "members_count": 1,
      "is_member": false,
      "user_role": null
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

✅ User B sees the community!

---

### C. User B joins community
```bash
TOKEN_B="<paste_token_b>"

curl -X POST http://localhost:8000/api/communities/1/join \
  -H "Authorization: Bearer $TOKEN_B"
```

**Response:**
```json
{
  "success": true,
  "message": "Berhasil join komunitas",
  "membership": {
    "community_id": 1,
    "user_id": 2,
    "role": "pegiat"
  }
}
```

✅ User B is now member with role "pegiat"!

---

### D. View all members
```bash
TOKEN_A="<paste_token_a>"

curl -X GET http://localhost:8000/api/communities/1/members \
  -H "Authorization: Bearer $TOKEN_A"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "name": "User A",
        "email": "usera@example.com"
      },
      "role": "influencer",
      "joined_at": "2026-03-03T10:00:00Z"
    },
    {
      "id": 2,
      "user": {
        "id": 2,
        "name": "User B",
        "email": "userb@example.com"
      },
      "role": "pegiat",
      "joined_at": "2026-03-03T10:05:00Z"
    }
  ]
}
```

✅ See all members!

---

## 4️⃣ TEST MESSAGING

### A. Send message (User A)
```bash
TOKEN_A="<paste_token_a>"

curl -X POST http://localhost:8000/api/communities/1/messages \
  -H "Authorization: Bearer $TOKEN_A" \
  -H "Content-Type: application/json" \
  -d '{"message": "Halo semua! Mari jaga lingkungan kita"}'
```

**Response:**
```json
{
  "success": true,
  "message": {
    "id": 1,
    "community_id": 1,
    "user": {
      "id": 1,
      "name": "User A",
      "email": "usera@example.com",
      "profile": {
        "photo": null
      }
    },
    "message": "Halo semua! Mari jaga lingkungan kita",
    "created_at": "2026-03-03T10:10:00Z"
  }
}
```

✅ Message sent!
✨ **Also broadcast to WebSocket to User B!**

---

### B. Send message (User B)
```bash
TOKEN_B="<paste_token_b>"

curl -X POST http://localhost:8000/api/communities/1/messages \
  -H "Authorization: Bearer $TOKEN_B" \
  -H "Content-Type: application/json" \
  -d '{"message": "Setuju! Kita harus mulai dari hal kecil"}'
```

**Response:**
```json
{
  "success": true,
  "message": {
    "id": 2,
    "community_id": 1,
    "user": {
      "id": 2,
      "name": "User B",
      "email": "userb@example.com",
      "profile": {
        "photo": null
      }
    },
    "message": "Setuju! Kita harus mulai dari hal kecil",
    "created_at": "2026-03-03T10:12:00Z"
  }
}
```

✅ Message sent!

---

### C. Get message history
```bash
TOKEN_A="<paste_token_a>"

curl -X GET "http://localhost:8000/api/communities/1/messages?page=1" \
  -H "Authorization: Bearer $TOKEN_A"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "community_id": 1,
      "user": {
        "id": 1,
        "name": "User A",
        "email": "usera@example.com"
      },
      "message": "Halo semua! Mari jaga lingkungan kita",
      "created_at": "2026-03-03T10:10:00Z"
    },
    {
      "id": 2,
      "community_id": 1,
      "user": {
        "id": 2,
        "name": "User B",
        "email": "userb@example.com"
      },
      "message": "Setuju! Kita harus mulai dari hal kecil",
      "created_at": "2026-03-03T10:12:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 2,
    "last_page": 1
  }
}
```

✅ See all messages!

---

## 5️⃣ TEST MEMBER MANAGEMENT

### A. Promote User B to Influencer
```bash
TOKEN_A="<paste_token_a>"

# Get member_id dari list members (dari step 4D, User B punya id=2)
curl -X PATCH http://localhost:8000/api/communities/1/members/2 \
  -H "Authorization: Bearer $TOKEN_A" \
  -H "Content-Type: application/json" \
  -d '{"role": "influencer"}'
```

**Response:**
```json
{
  "success": true,
  "message": "Member role updated",
  "membership": {
    "community_id": 1,
    "user_id": 2,
    "role": "influencer"
  }
}
```

✅ User B is now "influencer"!

---

### B. Demote back to Pegiat
```bash
curl -X PATCH http://localhost:8000/api/communities/1/members/2 \
  -H "Authorization: Bearer $TOKEN_A" \
  -H "Content-Type: application/json" \
  -d '{"role": "pegiat"}'
```

✅ User B is back to "pegiat"!

---

## 6️⃣ TEST POSTMAN COLLECTION

### Import Collection
1. Open Postman
2. File → Import
3. Select `postman_collection.json`
4. Accept

### Setup Variables
1. Click "Environments"
2. Create new: "Laravel Chat Dev"
3. Set variables:
   ```
   base_url = http://localhost:8000
   token = <TOKEN_A>
   community_id = 1
   member_id = 2
   ```
4. Select environment: "Laravel Chat Dev"

### Run Requests
- Double-click request to open
- Click "Send"
- View response

**All endpoints ready to test!** ✅

---

## 🎯 COMPLETE FLOW TEST CHECKLIST

- [ ] User A registers & gets TOKEN_A
- [ ] User B registers & gets TOKEN_B
- [ ] User A creates community
- [ ] User B lists communities & sees it
- [ ] User B joins community
- [ ] User A views members (should see 2)
- [ ] User A sends message
- [ ] User B sends message
- [ ] View message history (should see 2 messages)
- [ ] User A promotes User B to influencer
- [ ] User A demotes User B back to pegiat
- [ ] Check WebSocket real-time (should see messages pop up)

✅ All checked? **System is working perfectly!**

---

## 🧪 ADVANCED TESTS

### Test Authorization
```bash
# Try to send message as non-member
TOKEN_C="<some_other_user_token>"

curl -X POST http://localhost:8000/api/communities/1/messages \
  -H "Authorization: Bearer $TOKEN_C" \
  -d '{"message": "Hacker!"}'

# Expected: 403 Forbidden
```

### Test Pagination
```bash
# Send many messages (>20) then test pagination
curl -X GET "http://localhost:8000/api/communities/1/messages?page=2" \
  -H "Authorization: Bearer $TOKEN_A"

# Should show messages 21-40
```

### Test Leave Community
```bash
curl -X POST http://localhost:8000/api/communities/1/leave \
  -H "Authorization: Bearer $TOKEN_B"

# User B left, cannot send messages anymore
```

---

## 📊 EXPECTED DATABASE STATE

After all tests:

### users table
```
id | name   | email
1  | User A | usera@example.com
2  | User B | userb@example.com
```

### communities table
```
id | name                        | created_by | timestamps
1  | Komunitas Peduli Lingkungan | 1          | 2026-03-03...
```

### community_members table
```
id | community_id | user_id | role      | timestamps
1  | 1            | 1       | influencer| 2026-03-03...
2  | 1            | 2       | pegiat    | 2026-03-03...
```

### messages table
```
id | community_id | user_id | message                            | timestamps
1  | 1            | 1       | Halo semua! Mari jaga lingkungan..| 2026-03-03...
2  | 1            | 2       | Setuju! Kita harus mulai dari...  | 2026-03-03...
```

---

## 🐛 TROUBLESHOOTING

### Migrations fail
```bash
# Check migration status
php artisan migrate:status

# Reset (WARNING: deletes data!)
php artisan migrate:reset
php artisan migrate
```

### Token invalid
- Make sure you copied token correctly
- Check if token is expired
- Register again & get new token

### Cannot join community
- Make sure you're logged in (have valid token)
- Check if you're already a member

### WebSocket not working
- `php artisan reverb:start` is running?
- Check REVERB_HOST & REVERB_PORT in .env
- Try: `curl http://localhost:8080` (should return HTML)

### 404 errors
- Make sure routes/api.php is updated
- Run: `php artisan route:list | grep communities`

---

## 📚 DOCUMENTATION REFERENCE

| Need | File |
|------|------|
| Feature overview | COMMUNITY_MANAGEMENT_COMPLETE.md |
| Practical flows | COMMUNITY_CHAT_GUIDE.md |
| Postman collection | postman_collection.json |
| API reference | CHAT_API_REFERENCE.md |
| Reverb setup | LARAVEL_REVERB_SETUP.md |
| Complete system | CHAT_SYSTEM_SETUP.md |

---

## ✅ VERIFICATION COMPLETED

✅ All 13 endpoints implemented  
✅ Database migrations created & ready  
✅ Authorization checks in place  
✅ Real-time messaging ready  
✅ Documentation complete  

**Ready to test!** 🚀

---

**Next Step:** Run migrations and start servers above! 👆

