# EVENT MANAGEMENT BE IMPLEMENTATION PLAN

## PHASE 1: Database Schema (Critical)
### 1.1 Migration: Add timestamps ke event_participants
- `checked_in_at` (timestamp nullable)
- `checked_out_at` (timestamp nullable)

### 1.2 New Table: event_media (for photo/video upload tracking)
- id, event_id, participant_id, media_type (photo/video)
- file_path, file_size, uploaded_by, created_at

---

## PHASE 2: Models & Relationships
### 2.1 Update EventParticipant Model
- Add checked_in_at, checked_out_at to casts
- Add relationship to EventMedia
- Add helper methods: isCheckedIn(), isCheckedOut()

### 2.2 New EventMedia Model
- Relationships to Event, User, EventParticipant
- Scopes: photo(), video()

---

## PHASE 3: Endpoints (7 new/enhanced)

### ✅ Check-in/Check-out Flow
**POST /events/{id}/participants/check-in**
- Input: qr_token
- Output: participant info + checked_in_at timestamp
- Status: update participant.status → 'checked_in'

**POST /events/{id}/participants/{participant_id}/check-out**
- Input: (optional) media uploads
- Output: confirmation + checked_out_at timestamp
- Status: update participant.status → 'checked_out'

### ✅ Media Management
**POST /events/{id}/media**
- Input: type (photo/video), file upload
- Output: media_id, file_url, upload timestamp
- Store: file_path + metadata

**GET /events/{id}/media**
- Query: ?participant_id=X, ?type=photo|video
- Output: list with uploader name, timestamp, file_url

### ✅ Progress Dashboard
**GET /events/{id}/progress**
- Output:
```json
{
  "total_registered": 100,
  "checked_in": 45,
  "checked_out": 30,
  "total_waste_kg": 125.5,
  "media_uploads": 65,
  "phase": "in_progress"
}
```

### ✅ Enhanced Participant List
**GET /events/{id}/participants?status=all|joined|checked_in|checked_out**
- Include: name, photo, checked_in_at, checked_out_at, media_count
- Filter by status
- Sorting options

---

## PHASE 4: Response Contract

### Participant Object (Enhanced)
```json
{
  "id": 1,
  "event_id": 1,
  "user_id": 1,
  "user": {
    "name": "John Doe",
    "email": "john@example.com",
    "photo_profile": "url"
  },
  "status": "checked_out",
  "joined_at": "2026-04-16T08:00:00Z",
  "checked_in_at": "2026-04-16T09:00:00Z",
  "checked_out_at": "2026-04-16T11:30:00Z",
  "media_uploads": 3,
  "points_earned": 10
}
```

### Event Progress
- Registered: total join
- Checked-in: status='checked_in'
- Checked-out: status='checked_out'
- Phase: automatic based on current_time vs event_date/start_time/end_time

---

## TIMELINE
- **30 min** - Migrations & Models
- **40 min** - Controllers & Endpoints
- **20 min** - Routes & Testing
- **10 min** - Documentation

**Total: ~2 hours**

---

## MOBILE READINESS CHECKLIST
- [x] Check-in with QR code
- [x] Check-out endpoint
- [x] Photo/video upload tracking
- [x] Progress dashboard (registered vs checked-in)
- [x] Attendance list with details
- [x] Status filtering

**Ready for Mobile Integration: YES ✅**
