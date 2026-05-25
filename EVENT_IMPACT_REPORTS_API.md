# EVENT IMPACT REPORTS API - Documentation

**Status**: ✅ Production Ready  
**Added**: April 16, 2026

---

## 🎯 Overview

Endpoint untuk submit, list, edit, delete **Impact Reports** / **Pelaporan Dampak** setelah event selesai.

Peserta bisa nulis:
- **Report Kegiatan** - Rangkuman aktivitas apa yang dilakukan
- **Saran bagi Peserta** - Feedback/saran untuk peserta event berikutnya

---

## 📋 ENDPOINTS (5 Total)

### 1️⃣ SUBMIT IMPACT REPORT

**POST** `/api/events/{id}/impact-reports`

Submit laporan dampak setelah event.

**Request:**
```json
{
  "activity_report": "Hari ini kami membersihkan sungai... (dapat 2 ton sampah plastik)",
  "suggestions": "Lebih banyak sarung tangan, waterproof bag yang lebih besar"
}
```

**Response (201 CREATED):**
```json
{
  "success": true,
  "message": "Report dampak berhasil disimpan",
  "data": {
    "id": 1,
    "event_id": 3,
    "activity_report": "Hari ini kami membersihkan sungai...",
    "suggestions": "Lebih banyak sarung tangan...",
    "reported_by": {
      "id": 1,
      "name": "Budi Santoso",
      "photo_profile": "https://..."
    },
    "created_at": "2026-04-16T12:00:00Z",
    "updated_at": "2026-04-16T12:00:00Z"
  }
}
```

**Validation:**
- activity_report: required, max 5000 characters
- suggestions: required, max 5000 characters

**Error Cases:**
- 400: Validation error (field required atau terlalu panjang)
- 404: Event not found
- 401: Unauthorized

---

### 2️⃣ LIST ALL IMPACT REPORTS

**GET** `/api/events/{id}/impact-reports`

Get semua impact reports untuk sebuah event.

**Query Parameters:**
```
sort_by=created_at    // field untuk sorting (created_at, updated_at)
sort_order=desc       // asc atau desc
page=1                // pagination (default 1)
```

**Request Example:**
```
GET /api/events/3/impact-reports?sort_by=created_at&sort_order=desc&page=1
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "event_id": 3,
      "activity_report": "Hari ini kami membersihkan sungai...",
      "suggestions": "Lebih banyak sarung tangan...",
      "reported_by": {
        "id": 1,
        "name": "Budi Santoso",
        "photo_profile": "https://..."
      },
      "created_at": "2026-04-16T12:00:00Z",
      "updated_at": "2026-04-16T12:00:00Z"
    },
    {
      "id": 2,
      "event_id": 3,
      "activity_report": "Pengalaman yang menyenangkan...",
      "suggestions": "Perlu lebih banyak air minum...",
      "reported_by": {
        "id": 2,
        "name": "Ani Wijaya",
        "photo_profile": "https://..."
      },
      "created_at": "2026-04-16T11:30:00Z",
      "updated_at": "2026-04-16T11:30:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 12,
    "last_page": 1
  }
}
```

---

### 3️⃣ GET SINGLE IMPACT REPORT

**GET** `/api/events/{id}/impact-reports/{report_id}`

Get detail satu impact report.

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "event_id": 3,
    "activity_report": "Hari ini kami membersihkan sungai...",
    "suggestions": "Lebih banyak sarung tangan...",
    "reported_by": {
      "id": 1,
      "name": "Budi Santoso",
      "photo_profile": "https://..."
    },
    "created_at": "2026-04-16T12:00:00Z",
    "updated_at": "2026-04-16T12:00:00Z"
  }
}
```

---

### 4️⃣ UPDATE IMPACT REPORT

**PUT** `/api/events/{id}/impact-reports/{report_id}`

Update report (hanya si pembuat yang bisa edit).

**Request:**
```json
{
  "activity_report": "Updated activity report...",
  "suggestions": "Updated suggestions..."
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Report dampak berhasil diupdate",
  "data": {
    "id": 1,
    "event_id": 3,
    "activity_report": "Updated activity report...",
    "suggestions": "Updated suggestions...",
    "reported_by": { ... },
    "created_at": "2026-04-16T12:00:00Z",
    "updated_at": "2026-04-16T13:00:00Z"
  }
}
```

**Error Cases:**
- 403: "Tidak punya akses untuk update report ini" (bukan pembuat)
- 404: Report not found
- 422: Validation error

---

### 5️⃣ DELETE IMPACT REPORT

**DELETE** `/api/events/{id}/impact-reports/{report_id}`

Delete report (hanya si pembuat yang bisa delete).

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Report dampak berhasil dihapus"
}
```

**Error Cases:**
- 403: "Tidak punya akses untuk delete report ini"
- 404: Report not found

---

## 📊 DATA MODEL

**Impact Report Object**
```json
{
  "id": number,
  "event_id": number,
  "activity_report": string (max 5000 chars),
  "suggestions": string (max 5000 chars),
  "reported_by": {
    "id": number,
    "name": string,
    "photo_profile": string (URL)
  },
  "created_at": string (ISO8601),
  "updated_at": string (ISO8601)
}
```

---

## 🎬 TYPICAL FLOW

```
1. Event selesai (after checkout)
   
2. Show modal "Pelaporan Dampak"
   - Button "Isi Laporan"
   
3. User isi form:
   - Text area 1: "Report Kegiatan" (apa yang dilakukan)
   - Text area 2: "Saran bagi Peserta" (feedback)
   
4. User klik "Selesai"
   POST /api/events/{id}/impact-reports
   
5. On success:
   - Show toast: "Report berhasil disimpan"
   - Close modal
   
6. Optional: Show list semua reports
   GET /api/events/{id}/impact-reports
   - Admin/organizer bisa lihat semua feedback
   - Gunakan untuk improve event selanjutnya
```

---

## 💻 MOBILE IMPLEMENTATION NOTES

### Login/Auth Notes
- All requests need: `Authorization: Bearer {token}`
- Only creator dapat edit/delete own reports

### UI Component
```
Modal "Pelaporan Dampak":
  [Close]
  
  Judul: "Pelaporan Dampak"
  
  [Text Area 1]
  Label: "Report Kegiatan"
  Placeholder: "Isi report kegiatan mu disini!"
  Max: 5000 chars
  Show char counter: "0 / 5000"
  
  [Text Area 2]
  Label: "Saran bagi Peserta"
  Placeholder: "Isi saran mu disini!"
  Max: 5000 chars
  Show char counter: "0 / 5000"
  
  [Button] "Selesai" (20% gray if empty, green if filled)
```

### Validation
- Both fields required
- Show validation error if either is empty
- Disable submit button until both filled

### Loading State
- Show loading indicator during POST request
- Disable button while loading

### Error Handling
- 400: Show toast dengan field yang error
- 403: Show alert "Hanya pembuat report yang bisa edit/delete"
- 500: Show toast "Terjadi error, silahkan coba lagi"

### Optional Features
- Show list of all reports (for organizers/admins)
- Allow edit existing report before event ends
- Vote/comment on reports (future enhancement)

---

## 🧪 TEST DENGAN POSTMAN

**Create Report**
```bash
POST http://api.dev/api/events/1/impact-reports
Authorization: Bearer token
Content-Type: application/json

{
  "activity_report": "Test activity report content",
  "suggestions": "Test suggestions content"
}
```

**List Reports**
```bash
GET http://api.dev/api/events/1/impact-reports?sort_by=created_at&sort_order=desc
Authorization: Bearer token
```

**Update Report**
```bash
PUT http://api.dev/api/events/1/impact-reports/1
Authorization: Bearer token
Content-Type: application/json

{
  "activity_report": "Updated content",
  "suggestions": "Updated suggestions"
}
```

**Delete Report**
```bash
DELETE http://api.dev/api/events/1/impact-reports/1
Authorization: Bearer token
```

---

## ✅ IMPLEMENTATION CHECKLIST

- [ ] Read this document
- [ ] Test all 5 endpoints dengan Postman
- [ ] Implement modal "Pelaporan Dampak" di mobile
- [ ] Create form dengan 2 text areas
- [ ] Add validation (both required, max 5000)
- [ ] Call POST endpoint on submit
- [ ] Show loading indicator
- [ ] Show success/error toast
- [ ] Optionally: Implement list view untuk reports
- [ ] Test on real device

---

## 📌 DATABASE SCHEMA (Reference)

```sql
CREATE TABLE event_impact_reports (
  id BIGINT PRIMARY KEY,
  event_id BIGINT FOREIGN KEY,
  reported_by BIGINT FOREIGN KEY (users),
  activity_report LONGTEXT,
  suggestions LONGTEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

## 🚀 INTEGRATION POINTS

- **After Event Checkout**: Show "Pelaporan Dampak" modal
- **Event Detail Page**: Add tab untuk view all reports
- **Admin Dashboard**: List all reports untuk analysis

---

**Status**: ✅ Production Ready  
**Migration Applied**: 2026_04_16_080000  
**Routes**: 5 endpoints live  
**Ready for Mobile**: YES
