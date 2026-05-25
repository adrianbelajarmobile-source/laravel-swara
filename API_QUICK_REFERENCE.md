# EVENT MANAGEMENT API - QUICK REFERENCE

**Status**: ✅ Production Ready - April 16, 2026

---

## 📍 8 ENDPOINTS AT A GLANCE

| # | Method | Endpoint | Purpose |
|----|--------|----------|---------|
| 1 | POST | `/api/events/{id}/join` | Join event |
| 2 | POST | `/api/events/{id}/check-in` | Check-in dengan QR |
| 3 | POST | `/api/events/{id}/participants/{id}/check-out` | Check-out |
| 4 | POST | `/api/events/{id}/media` | Upload photo/video |
| 5 | GET | `/api/events/{id}/media` | List media |
| 6 | DELETE | `/api/events/{id}/media/{id}` | Delete media |
| 7 | GET | `/api/events/{id}/progress` | Dashboard stats |
| 8 | GET | `/api/events/{id}/participants` | List participants |

---

## 📊 RESPONSE MODELS

**Participant Object**
```json
{
  "id": int,
  "status": "joined|checked_in|checked_out",
  "joined_at": "ISO8601",
  "checked_in_at": "ISO8601|null",
  "checked_out_at": "ISO8601|null",
  "check_in_duration_minutes": int|null,
  "points_earned": int,
  "media_uploads": int
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
  "uploader": { "id": int, "name": "string" }
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

---

## 🔑 AUTH FLOW

```javascript
1. POST /api/auth/login
   → Get token
   
2. Store token securely
   
3. All requests:
   Header: Authorization: Bearer {token}
```

---

## 📝 REQUEST EXAMPLES

**Join Event**
```bash
curl -X POST http://api.dev/api/events/1/join \
  -H "Authorization: Bearer token"
```

**Check-In**
```bash
curl -X POST http://api.dev/api/events/1/check-in \
  -H "Authorization: Bearer token" \
  -H "Content-Type: application/json" \
  -d '{"qr_token": "abc123"}'
```

**Upload Media**
```bash
curl -X POST http://api.dev/api/events/1/media \
  -H "Authorization: Bearer token" \
  -F "media_type=photo" \
  -F "file=@image.jpg" \
  -F "participant_id=5"
```

**Get Progress**
```bash
curl -X GET http://api.dev/api/events/1/progress \
  -H "Authorization: Bearer token"
```

---

## ⚙️ QUERY PARAMETERS

**Media List**
```
GET /api/events/{id}/media
  ?media_type=photo|video
  &participant_id=X
  &page=1
  &per_page=20
```

**Participants List**
```
GET /api/events/{id}/participants
  ?status=all|joined|checked_in|checked_out
  &sort_by=created_at|checked_in_at|name
  &sort_order=asc|desc
  &page=1
  &per_page=20
```

---

## 🚨 ERROR CODES

| Code | Meaning | Solution |
|------|---------|----------|
| 200 | Success | ✓ OK |
| 201 | Created | ✓ OK |
| 400 | Bad Request | Check request format |
| 401 | Unauthorized | Re-login, check token |
| 404 | Not Found | Verify ID exists |
| 422 | Validation Error | Check field values |
| 500 | Server Error | Report issue |

---

## ✅ COMMON PATTERNS

**Poll Progress Every 5s**
```javascript
setInterval(async () => {
  const data = await get(`/events/${id}/progress`);
  updateUI(data);
}, 5000);
```

**Upload with Progress**
```javascript
const formData = new FormData();
formData.append('media_type', 'photo');
formData.append('file', file);
post(`/events/${id}/media`, formData, {
  onUploadProgress: (e) => updateBar(e.loaded/e.total)
});
```

**List with Pagination**
```javascript
const page1 = await get(`/events/${id}/participants?page=1`);
const page2 = await get(`/events/${id}/participants?page=2`);
```

---

## 📋 CHECKLIST BEFORE MOBILE START

- [ ] Backend repo cloned
- [ ] 3 docs read (QUICK_INTEGRATION_GUIDE, IMPLEMENTATION, CHECKLIST)
- [ ] Postman collection imported & all endpoints tested
- [ ] HTTP client library setup (axios/dio/retrofit)
- [ ] Bearer token interceptor implemented
- [ ] Error handler implemented (401, 400, etc)
- [ ] Ready to code!

---

## 📚 DOCUMENTATION FILES

1. **QUICK_INTEGRATION_GUIDE.md** ← Read this first!
2. **MOBILE_EVENT_MANAGEMENT_IMPLEMENTATION.md** ← Full reference
3. **MOBILE_IMPLEMENTATION_CHECKLIST.md** ← Task breakdown
4. **POSTMAN_MOBILE_EVENT_MANAGEMENT.json** ← API testing

---

## 🎯 KEY NOTES

✅ All 8 endpoints tested & working  
✅ Database migrations applied  
✅ Models & relationships complete  
✅ Error messages in Indonesian  
✅ File upload: max 100MB (photo/video)  
✅ Time format: ISO8601 for timestamps, HH:mm for event times  
✅ Real-time: Poll progress every 5-10s  
✅ Status flow: joined → checked_in → checked_out  

---

## 🚀 READY FOR MOBILE INTEGRATION!

**Start with**: QUICK_INTEGRATION_GUIDE.md  
**Timeline**: 3-4 days (~30 hours)  
**Support**: Check docs first, then contact backend  

Let's build! 🎯
