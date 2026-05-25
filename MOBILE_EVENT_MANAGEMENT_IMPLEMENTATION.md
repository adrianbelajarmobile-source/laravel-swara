# 📱 Event Management - Mobile Implementation Guide

**Status**: ✅ BACKEND READY - April 16, 2026

---

## 🎯 Overview

Backend sudah menyiapkan **7 endpoint utama** untuk fitur event management yang mendukung:
- ✅ Check-in/Check-out dengan QR code
- ✅ Upload foto/video during event
- ✅ Real-time progress dashboard
- ✅ Tracking peserta dan attendance

---

## 🔐 Authentication

**Semua endpoint memerlukan Bearer token Sanctum**

```
Headers:
Authorization: Bearer {user-token}
Content-Type: application/json
```

---

## 📋 ENDPOINT REFERENCE

### 1️⃣ JOIN EVENT

**POST** `/api/events/{id}/join`

Peserta bergabung dengan event sebelum check-in.

**Request:**
```json
// Body kosong, hanya gunakan token
```

**Response (201 CREATED):**
```json
{
  "success": true,
  "message": "Berhasil bergabung dengan event",
  "data": {
    "id": 5,
    "event_id": 3,
    "user_id": 1,
    "user": {
      "id": 1,
      "email": "user@example.com",
      "name": "Budi Santoso",
      "photo_profile": "https://..."
    },
    "status": "joined",
    "joined_at": "2026-04-16T08:30:00Z",
    "checked_in_at": null,
    "checked_out_at": null,
    "check_in_duration_minutes": null,
    "media_uploads": 0,
    "points_earned": 0
  }
}
```

**Error Cases:**
- 400: "Anda sudah bergabung dengan event ini"
- 400: "Kuota event sudah penuh"
- 404: Event not found
- 401: Unauthorized

---

### 2️⃣ CHECK-IN DENGAN QR CODE

**POST** `/api/events/{id}/check-in`

Peserta scan QR code untuk check-in. QR code setiap event sudah di-generate di backend.

**Request:**
```json
{
  "qr_token": "abc123def456"  // dari QR code atau manual input
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Check-in berhasil",
  "data": {
    "id": 5,
    "event_id": 3,
    "user_id": 1,
    "user": {
      "id": 1,
      "email": "user@example.com",
      "name": "Budi Santoso",
      "photo_profile": "https://..."
    },
    "status": "checked_in",
    "joined_at": "2026-04-16T08:30:00Z",
    "checked_in_at": "2026-04-16T09:15:00Z",    // ← BARU
    "checked_out_at": null,
    "check_in_duration_minutes": null,
    "media_uploads": 0,
    "points_earned": 10
  },
  "qr_code": "abc123def456"  // untuk verifikasi checkout
}
```

**Error Cases:**
- 400: "Anda belum bergabung dengan event ini"
- 400: "Anda sudah check-in" (jika sudah checked-in sebelumnya)
- 400: "QR token invalid"
- 404: Event not found

**Mobile Implementation Tips:**
- Gunakan camera library untuk scan QR
- Simpan `qr_token` dari response untuk checkout verification
- Update status UI dari "joined" → "checked_in"
- Display `checked_in_at` timestamp

---

### 3️⃣ CHECK-OUT

**POST** `/api/events/{id}/participants/{participant_id}/check-out`

Peserta checkout untuk mengakhiri kehadiran. Durasi attendance akan auto-calculate.

**Request:**
```json
// Body kosong
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Check-out berhasil",
  "data": {
    "id": 5,
    "event_id": 3,
    "user_id": 1,
    "user": {
      "id": 1,
      "email": "user@example.com",
      "name": "Budi Santoso",
      "photo_profile": "https://..."
    },
    "status": "checked_out",
    "joined_at": "2026-04-16T08:30:00Z",
    "checked_in_at": "2026-04-16T09:15:00Z",
    "checked_out_at": "2026-04-16T11:45:00Z",   // ← BARU
    "check_in_duration_minutes": 150,           // ← AUTO-CALCULATED (2.5 jam)
    "media_uploads": 3,
    "points_earned": 10
  }
}
```

**Error Cases:**
- 400: "Belum check-in atau sudah check-out"
- 404: Participant not found
- 401: Unauthorized

**Mobile Implementation Tips:**
- Tombol checkout hanya visible ketika status='checked_in'
- Display duration: `check_in_duration_minutes` (convert to "2h 30m" format)
- Tampilkan total points earned
- Show media uploads count

---

### 4️⃣ UPLOAD FOTO/VIDEO

**POST** `/api/events/{id}/media`

Upload foto atau video selama event. Max file size 100MB.

**Request (multipart/form-data):**
```
media_type: "photo"                    // "photo" atau "video" (required)
file: <file_binary>                    // JPG/PNG/MP4/MOV/AVI/MKV (required, max 100MB)
participant_id: 5                      // optional, untuk attach ke peserta
description: "Dokumentasi pembersihan" // optional
```

**Response (201 CREATED):**
```json
{
  "success": true,
  "message": "Media berhasil di-upload",
  "data": {
    "id": 127,
    "event_id": 3,
    "participant_id": 5,
    "uploaded_by": 1,
    "media_type": "photo",
    "file_path": "events/3/media/photo_1713249600.jpg",
    "file_url": "https://yourapi.com/storage/events/3/media/photo_1713249600.jpg",
    "file_size_kb": 2048,
    "file_size_mb": 2.0,
    "original_name": "IMG_1234.jpg",
    "description": "Dokumentasi pembersihan",
    "uploaded_at": "2026-04-16T09:30:00Z",
    "uploader": {
      "id": 1,
      "name": "Budi Santoso",
      "photo_profile": "https://..."
    }
  }
}
```

**Error Cases:**
- 400: "media_type required (photo|video)"
- 400: "File size exceeds 100MB"
- 400: "Invalid file format (jpg, jpeg, png, mp4, mov, avi, mkv only)"
- 422: Validation error
- 401: Unauthorized

**Mobile Implementation Tips:**
- Implementasi progress bar untuk upload besar
- Validasi file type & size di client sebelum upload
- Cache `file_url` untuk preview
- Tampilkan upload confirmation dialog
- Support batch upload (loop POST requests)

---

### 5️⃣ LIST MEDIA

**GET** `/api/events/{id}/media`

Get semua media yang di-upload untuk event. Support filtering & pagination.

**Query Parameters:**
```
media_type=photo     // optional: "photo" atau "video"
participant_id=5     // optional: filter by peserta
page=1               // pagination (default 1)
per_page=20          // per page (default 20)
```

**Request Example:**
```
GET /api/events/3/media?media_type=photo&participant_id=5&page=1
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 127,
      "event_id": 3,
      "participant_id": 5,
      "media_type": "photo",
      "file_url": "https://yourapi.com/storage/events/3/media/photo_1713249600.jpg",
      "file_size_kb": 2048,
      "file_size_mb": 2.0,
      "original_name": "IMG_1234.jpg",
      "description": "Dokumentasi pembersihan",
      "uploaded_at": "2026-04-16T09:30:00Z",
      "uploader": {
        "id": 1,
        "name": "Budi Santoso",
        "photo_profile": "https://..."
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "last_page": 3
  }
}
```

**Mobile Implementation Tips:**
- Implement lazy loading dengan infinite scroll
- Cache media list locally
- Show filter UI untuk media_type & participant_id
- Implement swipe gallery view
- Load thumbnails first, then full res on click
- Support download untuk offline viewing

---

### 6️⃣ DELETE MEDIA

**DELETE** `/api/events/{id}/media/{media_id}`

Delete media file (hanya bisa delete media milik sendiri atau admin).

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Media deleted successfully"
}
```

**Error Cases:**
- 403: "Unauthorized to delete this media"
- 404: Media not found
- 401: Unauthorized

---

### 7️⃣ PROGRESS DASHBOARD

**GET** `/api/events/{id}/progress`

Get real-time progress dashboard untuk event. Gunakan untuk display progress bar.

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "event_id": 3,
    "event_name": "Bersih Sungai Brantas",
    "event_date": "2026-04-16",
    "start_time": "08:00",         // format HH:mm
    "end_time": "12:00",
    "phase": "in_progress",        // "not_started" | "in_progress" | "finished"
    "progress": {
      "total_registered": 100,     // sudah join
      "checked_in": 45,            // sudah check-in
      "checked_out": 30,           // sudah check-out
      "total_waste_kg": 125.5,     // dari reports
      "media_uploads": 65          // total uploads
    },
    "percentage": {
      "check_in_rate": 45.0,       // (checked_in/total_registered)*100
      "check_out_rate": 30.0       // (checked_out/total_registered)*100
    }
  }
}
```

**Mobile Implementation Tips:**
- Poll endpoint setiap 5-10 detik untuk real-time update
- Gunakan data untuk progress bars:
  - Check-in progress: `checked_in / total_registered`
  - Check-out progress: `checked_out / total_registered`
- Display phase status dengan color coding:
  - "not_started" → Gray
  - "in_progress" → Green
  - "finished" → Blue
- Show total waste collected: `total_waste_kg` kg
- Show media uploads count

---

### 8️⃣ ENHANCED PARTICIPANT LIST

**GET** `/api/events/{id}/participants`

Get list peserta dengan filter status.

**Query Parameters:**
```
status=all              // "all" | "joined" | "checked_in" | "checked_out"
sort_by=created_at      // "created_at" | "checked_in_at" | "name"
sort_order=desc         // "asc" | "desc"
page=1
per_page=20
```

**Request Example:**
```
GET /api/events/3/participants?status=checked_in&sort_by=checked_in_at&sort_order=desc
```

**Response (200 OK):**
```json
{
  "success": true,
  "stats": {
    "total": 100,
    "joined": 70,
    "checked_in": 45,
    "checked_out": 30
  },
  "data": [
    {
      "id": 5,
      "event_id": 3,
      "user_id": 1,
      "user": {
        "id": 1,
        "email": "user@example.com",
        "name": "Budi Santoso",
        "photo_profile": "https://..."
      },
      "status": "checked_in",
      "joined_at": "2026-04-16T08:30:00Z",
      "checked_in_at": "2026-04-16T09:15:00Z",
      "checked_out_at": null,
      "check_in_duration_minutes": 5,
      "media_uploads": 1,
      "points_earned": 10
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "last_page": 3
  }
}
```

**Mobile Implementation Tips:**
- Display stats UI: badges untuk total/joined/checked_in/checked_out
- Implementasi tab filter untuk switch antara status
- Support sorting dropdown
- Show participant avatar & name clickable untuk detail
- Highlight peserta yang baru checked-in
- Show media_uploads badge

---

## 🎬 TYPICAL FLOW DIAGRAM

```
1. JOIN EVENT (optional)
   POST /api/events/{id}/join
   ↓ status: joined
   
2. DURING EVENT - CHECK-IN
   POST /api/events/{id}/check-in + QR token
   ↓ status: checked_in, checked_in_at: now()
   
3. DURING EVENT - UPLOAD MEDIA (multiple times)
   POST /api/events/{id}/media
   GET /api/events/{id}/media (list)
   ↓ media_uploads count increases
   
4. DURING EVENT - MONITOR PROGRESS
   GET /api/events/{id}/progress (poll every 5s)
   GET /api/events/{id}/participants (list with filters)
   ↓ show real-time bars/stats
   
5. END OF EVENT - CHECK-OUT
   POST /api/events/{id}/participants/{id}/check-out
   ↓ status: checked_out, checked_out_at: now()
   ↓ check_in_duration_minutes: auto-calculated
```

---

## 💾 DATA MODELS

### Participant Object
```json
{
  "id": number,
  "event_id": number,
  "user_id": number,
  "user": {
    "id": number,
    "email": string,
    "name": string,
    "photo_profile": string (URL)
  },
  "status": "joined" | "checked_in" | "checked_out",
  "joined_at": string (ISO8601),
  "checked_in_at": string (ISO8601) | null,
  "checked_out_at": string (ISO8601) | null,
  "check_in_duration_minutes": number | null,
  "media_uploads": number,
  "points_earned": number
}
```

### Media Object
```json
{
  "id": number,
  "event_id": number,
  "participant_id": number | null,
  "media_type": "photo" | "video",
  "file_url": string (URL),
  "file_size_kb": number,
  "file_size_mb": number,
  "original_name": string,
  "description": string | null,
  "uploaded_at": string (ISO8601),
  "uploader": {
    "id": number,
    "name": string,
    "photo_profile": string (URL)
  }
}
```

### Event Progress Object
```json
{
  "event_id": number,
  "event_name": string,
  "event_date": string (YYYY-MM-DD),
  "start_time": string (HH:mm),
  "end_time": string (HH:mm),
  "phase": "not_started" | "in_progress" | "finished",
  "progress": {
    "total_registered": number,
    "checked_in": number,
    "checked_out": number,
    "total_waste_kg": number,
    "media_uploads": number
  },
  "percentage": {
    "check_in_rate": number,
    "check_out_rate": number
  }
}
```

---

## 🛠️ COMMON IMPLEMENTATION PATTERNS

### Pattern 1: QR Code Scanner Integration
```javascript
// Pseudo-code untuk mobile app
async function scanAndCheckIn(eventId) {
  const qrCode = await openQRScanner(); // QR scanner library
  const response = await api.post(`/events/${eventId}/check-in`, {
    qr_token: qrCode
  });
  
  if (response.success) {
    showNotification("Check-in berhasil!");
    updateParticipantStatus(response.data);
  }
}
```

### Pattern 2: Real-time Progress Dashboard
```javascript
// Polling untuk dashboard update
const pollProgress = setInterval(async () => {
  const progress = await api.get(`/events/${eventId}/progress`);
  updateDashboardUI(progress.data);
  
  if (progress.data.phase === 'finished') {
    clearInterval(pollProgress);
  }
}, 5000); // Poll every 5 seconds
```

### Pattern 3: Media Upload with Progress
```javascript
async function uploadMedia(eventId, file, participantId) {
  const formData = new FormData();
  formData.append('media_type', file.type.includes('video') ? 'video' : 'photo');
  formData.append('file', file);
  formData.append('participant_id', participantId);
  
  const response = await api.post(`/events/${eventId}/media`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
    onUploadProgress: (progressEvent) => {
      const percentComplete = (progressEvent.loaded / progressEvent.total) * 100;
      updateProgressBar(percentComplete);
    }
  });
  
  return response.data;
}
```

### Pattern 4: Participant List with Status Filter
```javascript
async function getParticipantsByStatus(eventId, status = 'all') {
  const response = await api.get(`/events/${eventId}/participants`, {
    params: {
      status: status,
      sort_by: 'checked_in_at',
      sort_order: 'desc'
    }
  });
  
  return response.data;
}
```

---

## ⚠️ ERROR HANDLING

Semua endpoint bisa return:

**Standard Error Response:**
```json
{
  "success": false,
  "message": "Error message in Indonesian"
}
```

**Status Codes:**
- 200: Success
- 201: Created
- 400: Bad Request (validation error)
- 401: Unauthorized (token invalid/expired)
- 403: Forbidden (tidak punya akses)
- 404: Not Found
- 422: Unprocessable Entity
- 500: Server Error

**Mobile Implementation Tips:**
- Implement centralized error handler
- Show toast/snackbar untuk error messages
- Retry dengan exponential backoff untuk network errors
- Cache data locally untuk offline fallback

---

## 🔄 TOKEN & AUTHENTICATION

**Sanctum Token Setup:**

```javascript
// Login terlebih dahulu, dapat token
POST /api/auth/login
Response: { "token": "..." }

// Simpan token
localStorage.setItem('auth_token', response.token);

// Attach ke semua requests
headers: {
  'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
  'Content-Type': 'application/json'
}
```

**Token Expiry:**
- Default: 30 hari
- Jika expired, user perlu login ulang
- Implementasi token refresh (optional, belum ready di BE)

---

## 📊 RECOMMENDED UI COMPONENTS

1. **QR Scanner Modal** - Untuk check-in
2. **Progress Bars** - Registered vs Check-in vs Check-out
3. **Media Gallery** - Untuk list/preview uploads
4. **Participant List** - Dengan status filter tabs
5. **Attendance Summary Card** - Durasi + points + media count
6. **Duration Timer** - Menampilkan berapa lama checked-in (real-time)

---

## 🚀 READY-TO-INTEGRATE

✅ Semua endpoint sudah live dan tested  
✅ Database migrations sudah applied  
✅ Models & relationships sudah siap  
✅ Validation & error handling implemented  
✅ File upload infrastructure ready (100MB max)  

**Backend is production-ready for mobile integration!**

---

## 📞 INTEGRATION SUPPORT

Untuk questions/issues:
1. Check dokumentasi endpoint di atas
2. Review error message response
3. Debug dengan token validation
4. Test dengan Postman collection

**Last Updated**: April 16, 2026
**Backend Status**: ✅ PRODUCTION READY
