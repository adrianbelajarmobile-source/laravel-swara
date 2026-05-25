# ✅ IMPACT REPORTS MODULE - Mobile Implementation Addendum

**Status**: ✅ BACKEND READY  
**Added**: April 16, 2026  
**Difficulty**: Easy (1-2 hours)

---

## 📱 MODULE 11: IMPACT REPORTS (PELAPORAN DAMPAK)

**Dependency**: Module 8 (Check-Out) - Show this modal after checkout

**Estimated Time**: 1-2 hours

---

## 🎯 UI REQUIREMENTS

### Modal: "Pelaporan Dampak"

```
┌─────────────────────────────────┐
│ < Pelaporan Dampak              │ ← Back button
├─────────────────────────────────┤
│                                 │
│ Report Kegiatan                 │
│ ┌─────────────────────────────┐ │
│ │ Isi report kegiatan mu      │ │
│ │ disini! (text area)         │ │
│ │                             │ │
│ │                             │ │
│ └─────────────────────────────┘ │
│ 0 / 5000                        │ ← Character counter
│                                 │
│ Saran bagi Peserta              │
│ ┌─────────────────────────────┐ │
│ │ Isi saran mu disini!        │ │
│ │ (text area)                 │ │
│ │                             │
│ │                             │ │
│ └─────────────────────────────┘ │
│ 0 / 5000                        │ ← Character counter
│                                 │
│          [Selesai]              │ ← Green button
│                                 │
└─────────────────────────────────┘
```

---

## ✅ IMPLEMENTATION CHECKLIST

### 1. UI Components
- [ ] Create modal widget untuk "Pelaporan Dampak"
- [ ] Add 2 text area inputs:
  - [ ] "Report Kegiatan" (placeholder: "Isi report kegiatan mu disini!")
  - [ ] "Saran bagi Peserta" (placeholder: "Isi saran mu disini!")
- [ ] Show character counter: "0 / 5000" di bawah masing-masing
- [ ] Add "Selesai" button (disabled until both fields filled)
- [ ] Add loading indicator saat submit

### 2. Validation
- [ ] Both fields required (tidak boleh kosong)
- [ ] Max 5000 characters per field
- [ ] Show error message jika validation gagal
- [ ] Disable submit button jika ada error

### 3. API Integration
- [ ] Call `POST /api/events/{id}/impact-reports` endpoint
- [ ] Send:
  ```json
  {
    "activity_report": "user input",
    "suggestions": "user input"
  }
  ```
- [ ] Handle response 201 Created
- [ ] Show success toast: "Report berhasil disimpan"

### 4. Error Handling
- [ ] Validation error (400): Show in-form error message
- [ ] Unauthorized (401): Redirect to login
- [ ] Server error (500): Show toast "Terjadi error"
- [ ] Network error: Show "Koneksi gagal, coba lagi"

### 5. UX Flow
- [ ] Show modal after check-out
- [ ] Optional: Add skip button untuk that don't want to report
- [ ] Close modal after successful submit
- [ ] Clear form after submit
- [ ] Show success confirmation

### 6. Optional Features
- [ ] Allow user to view & edit their own report
- [ ] Show list of all reports (for organizers)
- [ ] Delete button dengan confirmation
- [ ] Character counter real-time (0/5000)

---

## 📊 API RESPONSE FORMAT

**Success (201 CREATED)**
```json
{
  "success": true,
  "message": "Report dampak berhasil disimpan",
  "data": {
    "id": 1,
    "event_id": 3,
    "activity_report": "...",
    "suggestions": "...",
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

**Error (400 BAD REQUEST)**
```json
{
  "success": false,
  "message": "activity_report is required"
}
```

---

## 💻 CODE EXAMPLE (Pseudo-code)

### React Native / Flutter Style

```javascript
// Submit report
async function submitReport() {
  if (!activityReport.trim() || !suggestions.trim()) {
    showError("Kedua field harus diisi!");
    return;
  }
  
  setLoading(true);
  try {
    const response = await api.post(
      `/events/${eventId}/impact-reports`,
      {
        activity_report: activityReport,
        suggestions: suggestions
      }
    );
    
    showToast("Report berhasil disimpan!");
    closeModal();
    
  } catch (error) {
    if (error.response?.status === 400) {
      showError(error.response.data.message);
    } else {
      showError("Terjadi error, silahkan coba lagi");
    }
  } finally {
    setLoading(false);
  }
}
```

---

## 🧪 TEST DENGAN POSTMAN

**Submit Report**
```bash
POST http://localhost:8000/api/events/1/impact-reports
Authorization: Bearer <token>
Content-Type: application/json

{
  "activity_report": "Test activity report content",
  "suggestions": "Test suggestions content"
}
```

**Expected Response**: 201 Created dengan report data

---

## 🎬 INTEGRATION FLOW

```
Event Detail Page
  ↓
User clicks Check-Out
  ↓
POST /events/{id}/participants/{id}/check-out
  ↓
Show Modal: "Pelaporan Dampak"
  ↓
User fills 2 text areas
  ↓
User clicks "Selesai"
  ↓
POST /api/events/{id}/impact-reports
  ↓
Success → Show toast → Close modal → Back to event detail
```

---

## 🔄 ADDITIONAL ENDPOINTS (Optional)

Jika mobile butuh show list reports:

**List All Reports**
```bash
GET /api/events/{id}/impact-reports
Authorization: Bearer <token>

Response: Array of report objects dengan pagination
```

**Get Single Report**
```bash
GET /api/events/{id}/impact-reports/{report_id}
Authorization: Bearer <token>
```

**Update Report** (only creator)
```bash
PUT /api/events/{id}/impact-reports/{report_id}
Authorization: Bearer <token>
Content-Type: application/json

{
  "activity_report": "updated content",
  "suggestions": "updated content"
}
```

**Delete Report** (only creator)
```bash
DELETE /api/events/{id}/impact-reports/{report_id}
Authorization: Bearer <token>
```

---

## 📞 LOCATION IN APP

### Main Flow
1. **Event Detail Page** → Show tab/button untuk "Pelaporan Dampak"
2. **After Check-Out** → Auto-show modal untuk submit report
3. **Optional**: Add "View Reports" section to show all reports

### Navigation
```
Event Detail
  ├── Participants tab
  ├── Gallery tab
  ├── Progress tab
  └── Reports tab (optional) → Show all reports
```

---

## 🚀 READINESS CHECKLIST

**Before Coding:**
- [ ] Backend endpoint tested (✓ ready)
- [ ] Postman collection updated (✓ ready)
- [ ] This document read
- [ ] UI design approved

**During Coding:**
- [ ] Follow validation rules
- [ ] Test with Postman first
- [ ] Test on real device

**After Coding:**
- [ ] Submit successful
- [ ] Error handling works
- [ ] Loading state visible
- [ ] Character counter working
- [ ] Modal closes after submit

---

## 📝 NOTES

- **Modal Timing**: Show **after checkout is complete**
- **Required Fields**: Both fields required, no skip option initially
- **Max Length**: 5000 chars each (show counter)
- **Only Creator**: Can edit/delete own report
- **Character Counter**: Update in real-time as user types
- **Loading**: Disable button while posting

---

**Backend Status**: ✅ PRODUCTION READY  
**Routes**: 5 endpoints verified (✓ all working)  
**Migration**: Applied successfully  
**Postman**: Integration testing ready  

**Ready to implement? Let's go! 🚀**
