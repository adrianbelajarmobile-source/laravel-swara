# 🚀 Quick Integration Guide - Mobile Team

**Bacaan ini**: 5 menit  
**Status**: ✅ Backend READY TO INTEGRATE

---

## YANG HARUS DISAMPAIKAN KE MOBILE TEAM

### 1. DOCUMENTS (3 dokumen utama)

**📄 MOBILE_EVENT_MANAGEMENT_IMPLEMENTATION.md**
- Dokumentasi lengkap semua 8 endpoint
- Request/response examples (JSON)
- Error handling guide
- Authentication setup
- Data models
- **Baca ini dulu sebelum mulai coding**

**📄 MOBILE_IMPLEMENTATION_CHECKLIST.md**
- Step-by-step checklist untuk 10 modules
- Depensi antar modules
- Acceptance criteria
- Estimated time per module
- Troubleshooting guide
- **Gunakan ini untuk project planning**

**📋 POSTMAN_MOBILE_EVENT_MANAGEMENT.json**
- Pre-configured Postman collection
- Semua 8 endpoint + login
- Variables untuk base_url, token, event_id, dll
- **Import ini untuk testing endpoints**

---

## YANG UDAH SIAP DI BACKEND

### ✅ 8 Endpoints

```
1. POST /api/events/{id}/join
   → Join event

2. POST /api/events/{id}/check-in
   → Check-in dengan QR token
   → Sets checked_in_at + status='checked_in'

3. POST /api/events/{id}/participants/{id}/check-out
   → Check-out dan auto-calculate duration
   → Sets checked_out_at + status='checked_out'

4. POST /api/events/{id}/media
   → Upload photo/video (max 100MB)
   → Supported: JPG, PNG, MP4, MOV, AVI, MKV

5. GET /api/events/{id}/media
   → List media dengan filtering & pagination
   → Filter by media_type, participant_id

6. DELETE /api/events/{id}/media/{id}
   → Delete media file

7. GET /api/events/{id}/progress
   → Real-time dashboard stats
   → Bar chart data: total_registered, checked_in, checked_out

8. GET /api/events/{id}/participants
   → List peserta dengan status filter
   → Sortable, paginated, includes all person data
```

### ✅ Database Schema
- `checked_in_at` timestamp for event_participants
- `checked_out_at` timestamp for event_participants
- `event_media` table for photo/video tracking
- All migrations applied ✅ (0 errors)

### ✅ Models & Relationships
- EventParticipant model: media() relationship, isCheckedIn() helper
- EventMedia model: Relationships to Event, User, Participant
- Event model: formatEventResponse() untuk response consistency

### ✅ Validation & Error Handling
- QR token validation
- File upload validation (size, format)
- Status progression validation (joined → checked_in → checked_out)
- User-friendly error messages (Indonesian)

### ✅ Rate Limits & Quotas
- No rate limit (not implemented, adjust if needed)
- File size max: 100MB
- Media types: photo, video

---

## QUICK SETUP STEPS

### Step 1: Clone Repo
```bash
git clone <repo>
cd laravel-swara
```

### Step 2: Read Docs (5 mins)
1. MOBILE_EVENT_MANAGEMENT_IMPLEMENTATION.md
2. MOBILE_IMPLEMENTATION_CHECKLIST.md

### Step 3: Test Endpoints (10 mins)
1. Import POSTMAN_MOBILE_EVENT_MANAGEMENT.json
2. Set variables: base_url, token
3. Click "Send" untuk setiap endpoint
4. Verify responses sesuai documentation

### Step 4: Start Coding
1. Setup HTTP client di mobile app
2. Follow MODULE sections di checklist
3. Integrate endpoints satu per satu
4. Test dengan Postman collections

---

## API RESPONSE FORMAT

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { /* response data */ },
  "pagination": { /* if applicable */ }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message in Indonesian"
}
```

### Status Codes
- 200: OK
- 201: Created
- 400: Bad Request (validation error)
- 401: Unauthorized (token invalid)
- 404: Not Found
- 422: Unprocessable Entity
- 500: Server Error

---

## QUICK FLOW (Typical Event Day)

```
① User joins event
   POST /api/events/{id}/join
   
② User scans QR code at event venue
   POST /api/events/{id}/check-in
   Status: joined → checked_in
   
③ During event, user uploads photos
   POST /api/events/{id}/media (multiple times)
   GET /api/events/{id}/media (view gallery)
   
④ Organizer monitors progress (real-time)
   GET /api/events/{id}/progress (poll every 5-10s)
   GET /api/events/{id}/participants (list with filters)
   
⑤ User leaves event
   POST /api/events/{id}/participants/{id}/check-out
   Status: checked_in → checked_out
   Duration auto-calculated
```

---

## KEY POINTS FOR MOBILE TEAM

### Authentication
- All requests need: `Authorization: Bearer {token}`
- Token dari login endpoint: `POST /api/auth/login`
- Store token securely di device

### Time Format
- All timestamps: ISO8601 format (e.g., "2026-04-16T09:15:00Z")
- Event times (start_time, end_time): HH:mm only (e.g., "09:00", "11:30")

### File Upload
- Use `multipart/form-data` (not JSON)
- Max file size: 100MB
- Supported formats:
  - Photo: JPG, PNG
  - Video: MP4, MOV, AVI, MKV

### Real-Time Updates
- Progress dashboard: Poll every 5-10 seconds
- Stop polling ketika phase='finished'
- Consider WebSocket untuk future real-time updates (not yet implemented)

### Error Handling
- All error messages already in Indonesian
- Show toast/snackbar untuk user feedback
- Implement retry logic dengan exponential backoff
- Handle token expiry (30 days)

### Data Caching
- Cache GET /progress untuk offline fallback
- Cache media list untuk infinite scroll
- Clear cache ketika user logout

---

## ESTIMATED TIMELINE

| Task | Duration | Effort |
|------|----------|--------|
| Setup & Testing | 1 hour | Low |
| Modules 1-3 (Auth, Join, Check-in) | 8-10h | Medium |
| Modules 4-5 (Media Upload) | 6-8h | Medium |
| Modules 6-7 (Progress, List) | 4-6h | Low |
| Modules 8-10 (Checkout, Error, Polish) | 5-8h | Medium |
| **TOTAL** | **24-32 hours** | **3-4 days** |

---

## INTEGRATION CHECKLIST

- [ ] Read all 3 documentation files
- [ ] Setup Postman collection
- [ ] Test all 8 endpoints with Postman
- [ ] Setup HTTP client di mobile app (axios/dio/retrofit)
- [ ] Implement authentication (Bearer token)
- [ ] Implement error handling (401, 400, etc)
- [ ] Integrate endpoints one by one (follow checklist)
- [ ] Test on real device
- [ ] Performance testing (<2s per screen)
- [ ] Deploy to staging for QA

---

## BACKEND SUPPORT

**Questions about API?**
→ Check MOBILE_EVENT_MANAGEMENT_IMPLEMENTATION.md first

**Endpoint not working?**
→ Test with Postman collection, then report issue

**Need to modify API?**
→ Contact backend team with clear requirements

**Status**: ✅ Production Ready - No more backend changes needed

---

## USEFUL LINKS

📚 **Documentation Hierarchy**:
1. This file (quick overview)
2. MOBILE_EVENT_MANAGEMENT_IMPLEMENTATION.md (detailed reference)
3. MOBILE_IMPLEMENTATION_CHECKLIST.md (step-by-step tasks)
4. POSTMAN_MOBILE_EVENT_MANAGEMENT.json (endpoint testing)

🔗 **Backend Repo**: [Your repo link]  
🔗 **API Base URL**: [Your API URL]  
🔗 **Contact**: [Backend developer contact]  

---

**Backend Status**: ✅ PRODUCTION READY  
**Mobile Ready**: ✅ YES  
**Let's integrate! 🚀**
