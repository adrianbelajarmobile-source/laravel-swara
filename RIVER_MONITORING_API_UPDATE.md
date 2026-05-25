# River Monitoring API Update - 16 April 2026

## 📢 Update untuk Mobile Team

Database dan API sudah diperbarui untuk support **Monitoring Sungai by Influencer** dengan video upload.

---

## 🔄 Perubahan di Endpoint POST /river-reports

### Database Changes
Tabel `river_reports` ditambah 3 kolom baru:
- `video_path` (opsional) - untuk video monitoring
- `monitoring_date` (opsional) - tanggal monitoring (default: hari ini)
- `reported_by_type` (enum: community|influencer) - tipe pelapor (default: community)

---

## 📝 Request Body Terbaru

### URL
```
POST /api/river-reports
```

### Headers
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

### Body Parameters

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `river_id` | integer | ✅ YES | ID sungai yang dilaporkan |
| `description` | string | ✅ YES | Deskripsi kondisi sungai |
| `urgency` | enum | ❌ NO | `normal`, `warning`, `urgent` (default: normal) |
| `photo` | file | ❌ NO | Foto (jpg/png, max 2MB) |
| `video` | file | ❌ NO | Video (mp4/mov/avi, max 10MB) - **BARU** |
| `monitoring_date` | date | ❌ NO | Format: YYYY-MM-DD (default: hari ini) - **BARU** |
| `reported_by_type` | enum | ❌ NO | `community` atau `influencer` (default: community) - **BARU** |
| `latitude` | float | ❌ NO | Koordinat latitude |
| `longitude` | float | ❌ NO | Koordinat longitude |

---

## 📦 Contoh Request

### Community Reporter (Lap Kondisi Sungai)
```bash
curl -X POST http://localhost:8000/api/river-reports \
  -H "Authorization: Bearer {token}" \
  -F "river_id=1" \
  -F "description=Air sungai warna coklat, terlihat ada sampah" \
  -F "urgency=normal" \
  -F "photo=@condisi_sungai.jpg" \
  -F "latitude=-6.2" \
  -F "longitude=106.8"
```

### Influencer Reporter (Monitoring Sungai)
```bash
curl -X POST http://localhost:8000/api/river-reports \
  -H "Authorization: Bearer {token}" \
  -F "river_id=1" \
  -F "description=Monitoring rutin sungai Ciliwung" \
  -F "urgency=warning" \
  -F "photo=@monitoring_photo.jpg" \
  -F "video=@monitoring_video.mp4" \
  -F "monitoring_date=2026-04-16" \
  -F "reported_by_type=influencer" \
  -F "latitude=-6.2" \
  -F "longitude=106.8"
```

---

## ✅ Response Success

### Status: 201 Created
```json
{
  "message": "Report berhasil dikirim",
  "data": {
    "id": 5,
    "user_id": 4,
    "river_id": 1,
    "description": "Monitoring rutin sungai Ciliwung",
    "photo_path": "river_reports/abc123.jpg",
    "video_path": "river_reports/videos/def456.mp4",
    "monitoring_date": "2026-04-16",
    "reported_by_type": "influencer",
    "latitude": "-6.20000000",
    "longitude": "106.81666600",
    "urgency": "warning",
    "status": "pending",
    "created_at": "2026-04-16T10:30:00+00:00",
    "updated_at": "2026-04-16T10:30:00+00:00"
  }
}
```

---

## 📋 Important Notes untuk Mobile

### 1. **Video Upload** (Opsional)
- Format: MP4, MOV, atau AVI
- Max size: 10MB
- Gunakan untuk monitoring by influencer
- Disimpan di: `storage/app/public/river_reports/videos/`

### 2. **Monitoring Date** (Opsional)
- Format: `YYYY-MM-DD`
- Jika tidak dikirim, otomatis jadi hari ini
- Gunakan untuk recording tanggal monitoring

### 3. **Reported By Type** (Opsional)
- `community` = laporan regular dari user
- `influencer` = laporan monitoring dari influencer
- Default: `community`
- Gunakan untuk filter/analytics

### 4. **River Condition Update**
- ✅ Semua reports (community + influencer) **tetap mengupdate** `river.condition`
- ✅ Perhitungan urgency sama untuk semua tipe reporter

---

## 🔄 Impact ke River Condition

Sama seperti sebelumnya, reporter type tidak mempengaruhi perhitungan:

```
condition = average(all_reports.urgency)

normal   → condition = 1
warning  → condition = 2
urgent   → condition = 3
```

---

## 🚀 Implementasi di Mobile

### Flutter/Dart - Form Submission

```dart
Future<void> submitMonitoring() async {
  final uri = Uri.parse('$baseUrl/api/river-reports');
  
  final request = http.MultipartRequest('POST', uri)
    ..headers['Authorization'] = 'Bearer $token'
    ..fields['river_id'] = riverId.toString()
    ..fields['description'] = descriptionController.text
    ..fields['urgency'] = selectedUrgency
    ..fields['reported_by_type'] = 'influencer' // untuk influencer
    ..fields['monitoring_date'] = selectedDate.toString().split(' ')[0]
    ..fields['latitude'] = latitude.toString()
    ..fields['longitude'] = longitude.toString();

  // Add photo
  if (photoFile != null) {
    request.files.add(
      await http.MultipartFile.fromPath(
        'photo',
        photoFile!.path,
      ),
    );
  }

  // Add video - BARU
  if (videoFile != null) {
    request.files.add(
      await http.MultipartFile.fromPath(
        'video',
        videoFile!.path,
      ),
    );
  }

  final response = await request.send();
  // ... handle response
}
```

---

## 📱 Penyesuaian UI

### Monitoring Form (By Influencer) - Additions:

1. **Video Upload Button** ✨ BARU
   - Tangkap video dari camera/gallery
   - Preview video sebelum upload
   - Show file size (max 10MB)

2. **Monitoring Date Picker** ✨ BARU
   - Pilih tanggal monitoring (default: hari ini)
   - Format: YYYY-MM-DD

3. **Reporter Type** ✨ BARU
   - Radio button: Community / Influencer
   - Set ke "Influencer" untuk monitoring

---

## ✔️ Backward Compatibility

⚠️ **Important:** Endpoint masih support request lama tanpa field baru
- Video, monitoring_date, reported_by_type = opsional
- Existing community reporters tidak perlu update
- Default values memastikan backward compatible

---

## 🔗 File Penyimpanan Media

Upload disimpan di:
```
storage/app/public/
├── river_reports/
│   ├── abc123.jpg (photos)
│   ├── def456.jpg
│   └── videos/
│       ├── ghi789.mp4 (videos)
│       └── jkl012.mp4
```

URL akses:
```
/storage/river_reports/{filename}
/storage/river_reports/videos/{filename}
```

---

## 🐛 Error Handling

### Video Too Large
```json
{
  "message": "The video field must not be greater than 10240 kilobytes.",
  "errors": {
    "video": ["The video field must not be greater than 10240 kilobytes."]
  }
}
```

### Invalid Urgency
```json
{
  "message": "The urgency field must be one of: normal, warning, urgent.",
  "errors": {
    "urgency": ["The urgency field must be one of: normal, warning, urgent."]
  }
}
```

---

## 📞 Questions?

- API sudah ready dan tested ✅
- Database sudah migrate ✅
- Video upload functional ✅
- All backward compatible ✅

Hubungi backend team jika ada issue!

---

**Last Updated:** 16 April 2026
**Status:** ✅ Production Ready
