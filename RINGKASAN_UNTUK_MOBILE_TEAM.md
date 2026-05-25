# 📱 YANG HARUS DISAMPAIKAN KE MOBILE TEAM

**Bacaan**: 2 menit  
**Status**: ✅ Siap digunakan

---

## 🎯 RINGKASAN

Backend sudah siap **8 endpoint lengkap** untuk fitur **Event Management** di aplikasi mobile:

### ✅ Apa yang sudah dicoding di backend:

1. **JOIN EVENT** - Peserta join event
2. **CHECK-IN** - Peserta scan QR code saat tiba di lokasi event
3. **CHECK-OUT** - Peserta pergi, sistem hitung durasi hadir
4. **UPLOAD FOTO/VIDEO** - Peserta upload dokumentasi event
5. **LIHAT GALERI** - Lihat semua foto/video dari event
6. **HAPUS MEDIA** - Hapus foto/video yang salah upload
7. **DASHBOARD PROGRESS** - Lihat grafik: sudah check-in berapa orang, total sampah, dll (real-time)
8. **DAFTAR PESERTA** - Lihat siapa aja yang hadir, sudah check-in, dll

---

## 📚 DOKUMEN YANG DISIAPKAN

### 1. **QUICK_INTEGRATION_GUIDE.md** ⭐ MULAI DARI SINI
- Bacaan: 5 menit
- Penjelasan singkat apa aja yang siap
- Langkah setup cepat
- Flow diagram

### 2. **MOBILE_EVENT_MANAGEMENT_IMPLEMENTATION.md** 📖 REFERENSI UTAMA
- Dokumentasi lengkap semua 8 endpoint
- Contoh request (input apa)
- Contoh response (output apa)
- Error handling (kalau salah apa yang terjadi)
- Kode sample untuk JavaScript/TypeScript

### 3. **MOBILE_IMPLEMENTATION_CHECKLIST.md** ✅ TASK LIST
- Breakdown menjadi 10 module kecil
- Checklist apa yang harus dikoding
- Estimasi waktu per module: 1-4 jam
- Total estimasi: 3-4 hari development
- Troubleshooting guide kalau ada error

### 4. **POSTMAN_MOBILE_EVENT_MANAGEMENT.json** 🧪 TESTING TOOL
- File untuk Postman (API testing tool)
- Sudah ada semua 8 endpoint siap test
- Tinggal import dan klik "Send"
- Cocok untuk verify endpoint bekerja

### 5. **API_QUICK_REFERENCE.md** 🔍 QUICK LOOKUP
- Cheat sheet singkat
- Semua 8 endpoint summary
- Request example (curl command)
- Response format (JSON)

---

## 🚀 CARA PAKAI / NEXT STEPS

### 1️⃣ MOBILE TEAM BACA DULU
```
Waktu: 10-15 menit total

Step 1: Buka & baca QUICK_INTEGRATION_GUIDE.md (5 menit)
Step 2: Buka MOBILE_IMPLEMENTATION_CHECKLIST.md (5 menit)
Step 3: Lihat API_QUICK_REFERENCE.md jika ada pertanyaan quick lookup (2 menit)
```

### 2️⃣ TEST ENDPOINTS DENGAN POSTMAN
```
Waktu: 15 menit

Step 1: Download Postman (https://www.postman.com/downloads/)
Step 2: Import file: POSTMAN_MOBILE_EVENT_MANAGEMENT.json
Step 3: Isi variables: base_url, token
Step 4: Klik "Send" untuk test tiap endpoint
Step 5: Lihat response apa yang balik
```

### 3️⃣ MULAI CODING DI MOBILE APP
```
Ikuti checklist di MOBILE_IMPLEMENTATION_CHECKLIST.md

Urutan coding (roughly):
- Module 1: Setup auth/login (2-3 jam)
- Module 2: Join event (1-2 jam)
- Module 3: QR check-in (3-4 jam) ← Bagian kompleks
- Module 4: Upload media (3-4 jam) ← Bagian kompleks
- Module 5: Galeri/list media (2-3 jam)
- Module 6: Progress dashboard (2-3 jam)
- Module 7: Daftar peserta (2-3 jam)
- Module 8: Check-out (1-2 jam)
- Module 9: Error handling & offline mode (2-3 jam)
- Module 10: UI polish & animations (2 jam)

Total: ~24-32 jam = 3-4 hari kerja
```

---

## 📊 TIMELINE DI MOBILE TEAM

```
Hari 1:
  - Setup & read docs (1 jam)
  - Test endpoints dengan Postman (1 jam)
  - Module 1-2 (setup auth, join) = 4 jam
  
Hari 2:
  - Module 3 (check-in QR) = 4 jam
  - Module 4 (media upload) = 4 jam
  
Hari 3:
  - Module 5-7 (gallery, dashboard, participants) = 6-8 jam
  
Hari 4:
  - Module 8-10 (checkout, error, polish) = 5-7 jam
  - Testing & QA = 1-2 jam

Total: 3-4 hari development + testing
```

---

## 💡 YANG PERLU MOBILE TEAM TAHUIN

### Data Structure (JSON Format)

**Peserta (Participant)**
```json
{
  "id": 5,
  "name": "Budi Santoso",
  "status": "checked_in",  // joined atau checked_in atau checked_out
  "joined_at": "2026-04-16T08:30:00Z",
  "checked_in_at": "2026-04-16T09:15:00Z",  // waktu scan QR
  "checked_out_at": null,
  "check_in_duration_minutes": 30,  // durasi hadir (auto-hitung)
  "points_earned": 10,
  "media_uploads": 3
}
```

**Media / Foto**
```json
{
  "id": 127,
  "type": "photo",  // atau "video"
  "file_url": "https://api.com/storage/events/3/photo.jpg",
  "uploaded_by": "Budi Santoso",
  "uploaded_at": "2026-04-16T09:30:00Z",
  "file_size_mb": 2.0
}
```

**Progress Dashboard**
```json
{
  "total_registered": 100,    // total peserta join
  "checked_in": 45,           // sudah scan QR
  "checked_out": 30,          // sudah pergi
  "total_waste_kg": 125.5,    // total limbah dikumpulkan
  "media_uploads": 65,        // total foto/video
  "phase": "in_progress"      // not_started, in_progress, finished
}
```

---

## 🔑 AUTHENTICATION

Semua request perlu token dari login:

```
Langkah 1: Login
  POST /api/auth/login
  Input: email, password
  Output: token

Langkah 2: Simpan token di secure storage

Langkah 3: Attach token ke semua request
  Header: Authorization: Bearer {token}
```

---

## 🎬 TYPICAL USER FLOW

```
1. User login
   ↓
2. User lihat event, klik "Join"
   POST /api/events/1/join
   Status peserta: "joined"
   ↓
3. User tiba di lokasi event, scan QR code
   POST /api/events/1/check-in?qr_token=abc123
   Status peserta: "joined" → "checked_in"
   ↓
4. User lihat progress dashboard (real-time update setiap 5-10 detik)
   GET /api/events/1/progress
   ↓
5. User ambil foto, upload ke app
   POST /api/events/1/media
   ↓
6. User lihat galeri foto dari peserta lain
   GET /api/events/1/media
   ↓
7. User pergi dari event
   POST /api/events/1/participants/5/check-out
   Duration otomatis: "2 jam 30 menit"
   Status peserta: "checked_in" → "checked_out"
```

---

## 📋 QUICK SETUP (MOBILE TEAM)

```
1. Clone backend repo
   git clone <repo> && cd laravel-swara

2. Install backend (jika belum)
   composer install && php artisan migrate

3. Start backend
   php artisan serve

4. Buka file ini di mobile project:
   - QUICK_INTEGRATION_GUIDE.md
   - MOBILE_EVENT_MANAGEMENT_IMPLEMENTATION.md
   - MOBILE_IMPLEMENTATION_CHECKLIST.md

5. Import file Postman di Postman app:
   - POSTMAN_MOBILE_EVENT_MANAGEMENT.json

6. Test endpoints:
   - No errors = ✅ Backend ready
   - Ada error = Check documentation atau tanya team

7. Mulai coding dengan Module 1 di checklist
```

---

## ❓ FAQ YANG SERING DITANYA

**Q: Backend harus di-setup di mana?**  
A: Depends. Bisa local machine, atau server. Minimal kasih URL ke mobile team.

**Q: Berapa lama development di mobile?**  
A: Estimasi 3-4 hari untuk 1 developer (24-32 jam).

**Q: Backend bisa berubah lagi?**  
A: Tidak perlu. Semua endpoint sudah final & tested.

**Q: Kalau mobile butuh endpoint baru?**  
A: Hubungi backend team dengan requirement jelas.

**Q: Bagaimana handle real-time update?**  
A: Sekarang: Polling (request setiap 5-10 detik). Future: WebSocket.

**Q: File upload berapa max size?**  
A: Max 100MB per file.

**Q: Token berlaku berapa lama?**  
A: 30 hari. Setelah itu perlu login lagi.

---

## ✅ FINAL CHECKLIST SEBELUM MOBILE START

- [ ] Team mobile sudah baca QUICK_INTEGRATION_GUIDE.md
- [ ] Postman collection sudah di-import di Postman
- [ ] Semua 8 endpoint sudah di-test di Postman (response OK)
- [ ] Backend URL & auth setup di mobile app
- [ ] HTTP client library sudah setup (axios/dio/retrofit)
- [ ] Bearer token implementation sudah siap
- [ ] Error handler boilerplate sudah ada
- [ ] Ready to code Module 1!

---

## 📞 CONTACT / SUPPORT

Jika ada pertanyaan:

**Referensi Dokumen**:
1. QUICK_INTEGRATION_GUIDE.md - Jawab pertanyaan umum
2. MOBILE_EVENT_MANAGEMENT_IMPLEMENTATION.md - Referensi endpoint detail
3. API_QUICK_REFERENCE.md - Lookup cepat

**Kalau masih ga jelas**:
- Hubungi backend developer
- Share error message
- Test ulang dengan Postman

---

## 🎉 KESIMPULAN

Backend sudah **100% siap**:
- ✅ 8 endpoints implemented & tested
- ✅ Database schema ready
- ✅ Error handling ready
- ✅ Documentation lengkap
- ✅ Postman collection ready
- ✅ Production-ready code

**Mobile team bisa langsung start coding hari ini!**

---

**Tanggal**: 16 April 2026  
**Status**: ✅ PRODUCTION READY  
**Ready Go?** LET'S GO! 🚀
