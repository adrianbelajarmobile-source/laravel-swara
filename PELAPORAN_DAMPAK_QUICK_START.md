# 🚀 PELAPORAN DAMPAK - QUICK START (2 minutes)

---

## 📱 WHAT IS THIS?

Modal form untuk peserta submit feedback setelah event selesai:
- **Field 1**: "Report Kegiatan" - Apa yang dilakukan di event
- **Field 2**: "Saran bagi Peserta" - Saran untuk peserta next event

---

## 🔧 API ENDPOINT

**Create Report (yang perlu mobile)**
```
POST /api/events/{event_id}/impact-reports
Authorization: Bearer {token}
Content-Type: application/json

Request:
{
  "activity_report": "Kami membersihkan sungai, dapat 2 ton sampah",
  "suggestions": "Perlu lebih banyak sarung tangan"
}

Response (201):
{
  "success": true,
  "message": "Report dampak berhasil disimpan",
  "data": {
    "id": 1,
    "activity_report": "...",
    "suggestions": "...",
    "reported_by": { "id": 1, "name": "Budi..." }
  }
}
```

---

## ✅ VALIDATION

- ✅ Both fields **required** (tidak boleh kosong)
- ✅ Max **5000 characters** each
- ✅ Type: **string**

---

## 🎬 FLOW

```
User checks out
    ↓
Show modal "Pelaporan Dampak"
    ↓
User isi 2 textarea
    ↓
Click "Selesai" button
    ↓
POST /api/events/{id}/impact-reports
    ↓
Success → Toast "Report berhasil disimpan" → Close modal
```

---

## 💻 CODE SAMPLE (JavaScript)

```javascript
// 1. Validate
if (!activityReport || !suggestions) {
  showError("Kedua field harus diisi!");
  return;
}

// 2. Call API
const response = await fetch(
  `/api/events/${eventId}/impact-reports`,
  {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      activity_report: activityReport,
      suggestions: suggestions
    })
  }
);

// 3. Handle Response
if (response.status === 201) {
  showToast("Report berhasil disimpan!");
  closeModal();
} else {
  showError("Gagal submit report");
}
```

---

## 🧪 TEST

1. Open **Postman**
2. Import `POSTMAN_MOBILE_EVENT_MANAGEMENT.json`
3. Go to section **"5. Impact Reports"**
4. Click **"Submit Impact Report"** → Send
5. Should get **201 Created**

---

## 📚 FULL DOCS

- `EVENT_IMPACT_REPORTS_API.md` - Full API reference
- `MODULE_11_IMPACT_REPORTS.md` - Detailed implementation guide
- `PELAPORAN_DAMPAK_FEATURE_SUMMARY.md` - Feature overview

---

## ⚡ TL;DR

| What | Details |
|------|---------|
| **Endpoint** | POST /api/events/{id}/impact-reports |
| **Auth** | Bearer token required |
| **Body** | activity_report, suggestions (both required) |
| **Response** | 201 Created with report data |
| **Show** | After checkout completed |
| **Modal** | 2 textareas + Selesai button |
| **Max length** | 5000 chars each |

---

**Status**: ✅ READY  
**Time to implement**: 1-2 hours  
**Difficulty**: Easy ⭐

Ready? Let's code! 🚀
