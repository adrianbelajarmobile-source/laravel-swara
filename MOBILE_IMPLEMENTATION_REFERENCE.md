# EVENT MANAGEMENT - MOBILE IMPLEMENTATION REFERENCE

**Backend Status**: ✅ Production Ready  
**Total Endpoints**: 13  
**Time to Implement**: 3-4 days (all modules)

---

## 📋 13 ENDPOINTS SUMMARY

### EVENT PARTICIPATION (4)
```
POST   /api/events/{id}/join                → Join event
POST   /api/events/{id}/check-in            → Check-in (body: qr_token)
POST   /api/events/{id}/participants/{id}/check-out  → Check-out
GET    /api/events/{id}/participants        → List participants (filter: status=all|joined|checked_in|checked_out)
```

### MEDIA MANAGEMENT (3)
```
POST   /api/events/{id}/media               → Upload photo/video (multipart: media_type, file, participant_id, description)
GET    /api/events/{id}/media               → List media (filter: media_type=photo|video, participant_id, page, per_page)
DELETE /api/events/{id}/media/{media_id}    → Delete media
```

### MONITORING (2)
```
GET    /api/events/{id}/progress            → Dashboard stats (total_registered, checked_in, checked_out, total_waste_kg, media_uploads, phase)
GET    /api/events/{id}/participants        → Already listed above (enhanced with status filter & sorting)
```

### IMPACT REPORTS (5) - NEW
```
POST   /api/events/{id}/impact-reports           → Submit report (body: activity_report, suggestions)
GET    /api/events/{id}/impact-reports           → List reports (pagination, sorting)
GET    /api/events/{id}/impact-reports/{id}      → Get single report
PUT    /api/events/{id}/impact-reports/{id}      → Edit report (creator only)
DELETE /api/events/{id}/impact-reports/{id}      → Delete report (creator only)
```

---

## 🔑 AUTHENTICATION

All requests need:
```
Header: Authorization: Bearer {token}
```

Token dari: `POST /api/auth/login` (email + password)

---

## 📊 KEY DATA MODELS

**Participant Object**
```json
{
  "id": int,
  "status": "joined|checked_in|checked_out",
  "checked_in_at": "ISO8601|null",
  "checked_out_at": "ISO8601|null",
  "check_in_duration_minutes": int|null,
  "points_earned": int,
  "media_uploads": int,
  "user": { "id": int, "name": str, "photo_profile": str }
}
```

**Media Object**
```json
{
  "id": int,
  "media_type": "photo|video",
  "file_url": "string",
  "file_size_mb": float,
  "uploaded_at": "ISO8601",
  "uploader": { "id": int, "name": str }
}
```

**Progress Object**
```json
{
  "total_registered": int,
  "checked_in": int,
  "checked_out": int,
  "total_waste_kg": float,
  "media_uploads": int,
  "phase": "not_started|in_progress|finished"
}
```

**Impact Report Object**
```json
{
  "id": int,
  "activity_report": "string",
  "suggestions": "string",
  "reported_by": { "id": int, "name": str, "photo_profile": str },
  "created_at": "ISO8601"
}
```

---

## 🎬 MOBILE SCREEN FLOWS

### 1. EVENT DETAIL SCREEN
```
[Back] Event Name [Share]
├─ Event Info (date, time, location, quota)
├─ [Join Event] button
│
Tab Bar:
├─ Info
├─ Participants
├─ Gallery
├─ Progress
└─ Reports (NEW)
```

### 2. CHECK-IN FLOW
```
Button "Check-In" (after join)
  ↓
Open QR Scanner
  ↓
POST /api/events/{id}/check-in (qr_token)
  ↓
Show: "Check-in berhasil" + timestamps
```

### 3. MEDIA UPLOAD FLOW
```
Button "Ambil Foto/Video"
  ↓
Gallery picker
  ↓
POST /api/events/{id}/media (multipart)
  ↓
Show in gallery list
```

### 4. PROGRESS DASHBOARD
```
Poll GET /api/events/{id}/progress every 5-10 sec
  ↓
Show bars:
  - Total Registered: XX
  - ✅ Checked-in: XX (XX%)
  - ✅✅ Checked-out: XX (XX%)
  - 📦 Total Waste: XX kg
  - 📸 Media Uploads: XX
  - Phase badge: (Gray|Green|Blue)
```

### 5. PARTICIPANTS LIST
```
Tab: Participants
├─ [All] [Joined] [Checked-in] [Checked-out]
├─ Sort dropdown: name, joined_at, checked_in_at
│
Card per participant:
├─ Avatar + name
├─ Status badge + timestamp
├─ Media count + duration (if checked-out)
├─ Points earned
```

### 6. IMPACT REPORTS (NEW)
```
Tab: Reports
├─ After checkout → Auto-show modal
│
Modal "Pelaporan Dampak":
├─ Text Area 1: "Report Kegiatan" (0/5000)
├─ Text Area 2: "Saran bagi Peserta" (0/5000)
├─ [Selesai] button
│
After submit:
├─ POST /api/events/{id}/impact-reports
├─ Toast: "Report berhasil disimpan"
├─ Close modal
```

---

## ⚡ QUICK IMPLEMENTATION BREAKDOWN

| Module | Task | Time | Endpoint(s) |
|--------|------|------|-----------|
| 1 | Auth Setup | 30m | `/auth/login` |
| 2 | Join Event | 1h | `POST join` |
| 3 | Check-in (QR) | 1.5h | `POST check-in` |
| 4 | Media Upload | 1.5h | `POST/GET/DELETE media` |
| 5 | Media Gallery | 1h | `GET media` + UI |
| 6 | Progress Dashboard | 1h | `GET progress` (polling) |
| 7 | Participants List | 1h | `GET participants` (filters) |
| 8 | Check-out | 1h | `POST check-out` |
| 9 | Impact Reports | 1.5h | `POST/GET/PUT/DELETE reports` |
| 10 | Error/Offline | 1h | Global handlers |

**Total**: ~11-12 hours ≈ 2-3 days

---

## 🔧 VALIDATION RULES

**Media Upload**
- Max size: 100MB
- Formats: JPG, PNG (photo) | MP4, MOV, AVI, MKV (video)
- Required: media_type, file

**Impact Report**
- activity_report: required, max 5000 chars
- suggestions: required, max 5000 chars

**Time Format**
- Event times: HH:mm only (e.g., "09:00")
- All timestamps: ISO8601 (e.g., "2026-04-16T09:15:00Z")

---

## 📝 ERROR HANDLING

**Status Codes**
- 200/201: Success
- 400: Bad Request (validation error)
- 401: Unauthorized (token invalid)
- 403: Forbidden (not creator, can't edit/delete)
- 404: Not Found
- 500: Server error

**Response Format**
```json
Success: { "success": true, "message": "...", "data": {...} }
Error: { "success": false, "message": "Error description" }
```

---

## 🧪 TESTING

1. Import Postman: `POSTMAN_MOBILE_EVENT_MANAGEMENT.json`
2. Has all 13 endpoints + example requests/responses
3. Set variables: base_url, token, event_id, participant_id, media_id, report_id

---

## 📚 FULL REFERENCE DOCS

- `MOBILE_EVENT_MANAGEMENT_IMPLEMENTATION.md` - Detailed API reference
- `EVENT_IMPACT_REPORTS_API.md` - Impact Reports detailed reference
- `MODULE_11_IMPACT_REPORTS.md` - Reports implementation guide

---

## ✅ READY FOR MOBILE

✅ All 13 endpoints tested & working  
✅ Database migrations applied  
✅ Full error handling  
✅ Response formatting complete  
✅ Postman collection ready  

**Backend is 100% production-ready. Start implementing!**
