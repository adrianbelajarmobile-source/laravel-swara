# ✅ Mobile Event Management - Implementation Checklist

**Status**: READY FOR MOBILE IMPLEMENTATION  
**Date**: April 16, 2026  
**Backend**: ✅ PRODUCTION READY

---

## 📱 MOBILE TEAM - QUICK START

### 1️⃣ SETUP (30 mins)

- [ ] Clone backend repository
- [ ] Read `MOBILE_EVENT_MANAGEMENT_IMPLEMENTATION.md` - Dokumentasi lengkap endpoints
- [ ] Import `POSTMAN_MOBILE_EVENT_MANAGEMENT.json` ke Postman
- [ ] Configure backend URL + auth token di Postman variables
- [ ] Test semua endpoints dengan Postman (5-10 menit per endpoint)
- [ ] Setup HTTP client library di mobile app (axios/dio/retrofit)
- [ ] Create `.env` file dengan `BACKEND_BASE_URL`

### 2️⃣ MODULE 1: AUTHENTICATION (2-3 hours)

**Dependency**: None

**Tasks**:
- [ ] Implement login page dengan email + password input
- [ ] Call `POST /api/auth/login` endpoint
- [ ] Store token ke secure storage (`flutter_secure_storage` / `Keychain`)
- [ ] Setup global HTTP interceptor untuk attach Bearer token
- [ ] Implement token refresh logic (optional, token valid 30 hari)
- [ ] Test dengan invalid credentials (error handling)
- [ ] Add auto-logout ketika token expired

**Testing**:
- [ ] Success login dengan valid credentials
- [ ] Fail login dengan invalid email
- [ ] Fail login dengan invalid password
- [ ] Token stored securely

### 3️⃣ MODULE 2: EVENT JOIN (1-2 hours)

**Dependency**: Module 1 (Authentication)

**Tasks**:
- [ ] Add "Join Event" button di event detail page
- [ ] Call `POST /api/events/{id}/join` endpoint
- [ ] Show loading indicator during request
- [ ] On success: Update UI to show "Bergabung" ✓
- [ ] On error: Show toast dengan error message
- [ ] Handle error case: "Sudah bergabung", "Kuota penuh"
- [ ] Auto-hide button jika sudah joined

**Testing**:
- [ ] Successfully join event
- [ ] Cannot join twice (error handling)
- [ ] Cannot join full event (error handling)
- [ ] UI updates after join

### 4️⃣ MODULE 3: QR CHECK-IN (3-4 hours)

**Dependency**: Module 1, 2

**UI Components**: QR scanner modal

**Tasks**:
- [ ] Add "Check-In" button di event detail (visible after joining)
- [ ] Implement QR code scanner:
  - [ ] Use `qr_flutter` (Flutter) atau `react-native-camera` (React Native)
  - [ ] Open camera on button press
  - [ ] Parse QR code data → extract `qr_token`
- [ ] Call `POST /api/events/{id}/check-in` dengan qr_token
- [ ] On success:
  - [ ] Show success toast: "Check-in berhasil!"
  - [ ] Update status badge: "joined" → "checked_in"
  - [ ] Display `checked_in_at` timestamp
  - [ ] Show `points_earned`
  - [ ] Disable "Check-In" button
  - [ ] Enable "Check-Out" button
- [ ] On error: Show error toast + close scanner
- [ ] Handle case: "Belum join", "Sudah check-in"

**Testing**:
- [ ] Scan valid QR code → check-in success
- [ ] Cannot check-in without joining first
- [ ] Cannot check-in twice
- [ ] Timestamp recorded correctly
- [ ] Points displayed

### 5️⃣ MODULE 4: MEDIA UPLOAD (3-4 hours)

**Dependency**: Module 1, 3 (ideally after check-in)

**UI Components**: Camera/gallery picker, upload progress bar

**Tasks**:
- [ ] Add "Upload Photo/Video" button di event detail (visible when checked-in)
- [ ] Implement media picker:
  - [ ] Use `image_picker` (Flutter) atau `react-native-image-picker`
  - [ ] Allow select from camera atau gallery
  - [ ] Support both photo (JPG, PNG) dan video (MP4, MOV, AVI, MKV)
- [ ] Validate before upload:
  - [ ] File size ≤ 100MB
  - [ ] Format is supported (JPG/PNG/MP4/MOV/AVI/MKV)
  - [ ] Show error if invalid
- [ ] Implement upload:
  - [ ] Use multipart/form-data
  - [ ] Include fields: media_type, file, participant_id, description (optional)
  - [ ] Show upload progress bar (% complete)
  - [ ] Disable button during upload
- [ ] On success:
  - [ ] Show success toast: "Upload berhasil"
  - [ ] Add media to list (grid view)
  - [ ] Update media count badge
  - [ ] Reset form
- [ ] On error: Show error toast, keep file selected for retry

**Testing**:
- [ ] Upload photo successfully
- [ ] Upload video successfully
- [ ] Upload progress bar works
- [ ] File size validation works (reject > 100MB)
- [ ] File format validation works
- [ ] Multiple uploads work

### 6️⃣ MODULE 5: MEDIA LIST GALLERY (2-3 hours)

**Dependency**: Module 1, 4

**UI Components**: Grid gallery, image viewer/video player

**Tasks**:
- [ ] Add "Gallery" tab/section di event detail
- [ ] Fetch `GET /api/events/{id}/media` on page load
- [ ] Display media in grid layout:
  - [ ] Show thumbnail + media type badge (foto/video)
  - [ ] Show uploader name + timestamp
  - [ ] Show file size in MB
- [ ] Implement filtering:
  - [ ] Filter by media_type (photo/video)
  - [ ] Filter by participant_id (optional)
  - [ ] Show switch/tabs untuk toggle filter
- [ ] Implement infinite scroll / pagination:
  - [ ] Load more items sebagai user scrolls
  - [ ] Show loading indicator saat load more
- [ ] On click:
  - [ ] Open image viewer (untuk photo)
  - [ ] Open video player (untuk video)
  - [ ] Show full details: uploader, timestamp, description
  - [ ] Add "Delete" button dengan confirmation
- [ ] Implement delete:
  - [ ] `DELETE /api/events/{id}/media/{media_id}`
  - [ ] Show confirmation dialog sebelum delete
  - [ ] Remove dari grid setelah delete

**Testing**:
- [ ] Gallery loads dengan all media
- [ ] Filter by photo works
- [ ] Filter by video works
- [ ] Infinite scroll loads more items
- [ ] Image viewer opens correctly
- [ ] Video player works
- [ ] Delete works dengan confirmation
- [ ] Pagination shows correct items

### 7️⃣ MODULE 6: PROGRESS DASHBOARD (2-3 hours)

**Dependency**: Module 1

**UI Components**: Progress bars, stat cards, real-time updates

**Tasks**:
- [ ] Add "Progress" tab/section di event detail
- [ ] Fetch `GET /api/events/{id}/progress` endpoint
- [ ] Implement polling: refresh every 5-10 seconds untuk real-time
- [ ] Display progress stats:
  - [ ] **Registered**: total_registered (card dengan number)
  - [ ] **Check-in rate**: progress bar (checked_in / total_registered)
  - [ ] **Check-out rate**: progress bar (checked_out / total_registered)
  - [ ] **Total Waste**: total_waste_kg collected (large stat card)
  - [ ] **Media Uploads**: media_uploads count
  - [ ] **Event Phase**: "not_started" | "in_progress" | "finished" (colored badge)
  - [ ] **Event Time**: start_time - end_time (HH:mm format)
- [ ] Color coding:
  - [ ] "not_started" → Gray
  - [ ] "in_progress" → Green
  - [ ] "finished" → Blue
- [ ] Animations:
  - [ ] Animate progress bars sebagai values change
  - [ ] Fade in new values when updating
- [ ] Handle polling:
  - [ ] Stop polling ketika phase = "finished"
  - [ ] Resume polling jika user kembali ke tab
  - [ ] Clear polling saat leave page

**Testing**:
- [ ] Progress loads on page load
- [ ] Progress updates every 5-10 seconds
- [ ] Progress bars show correct percentages
- [ ] All stats visible
- [ ] Color coding correct
- [ ] Polling stops when event finished
- [ ] No memory leaks dari polling

### 8️⃣ MODULE 7: PARTICIPANT LIST (2-3 hours)

**Dependency**: Module 1, 3

**UI Components**: List with filters, status badges

**Tasks**:
- [ ] Add "Participants" tab/section di event detail
- [ ] Fetch `GET /api/events/{id}/participants` endpoint
- [ ] Display participant list:
  - [ ] Show stats box: Total, Joined, Checked-in, Checked-out
  - [ ] Show participan cards/rows dengan:
    - [ ] Avatar + name (clickable untuk profile)
    - [ ] Status badge (joined/checked_in/checked_out)
    - [ ] Timestamps (joined_at, checked_in_at, checked_out_at)
    - [ ] Check-in duration (if checked-out)
    - [ ] Points earned
    - [ ] Media uploads count
- [ ] Implement filtering:
  - [ ] Tabs untuk switch status: All, Joined, Checked-in, Checked-out
  - [ ] Query backend dengan `?status=all|joined|checked_in|checked_out`
- [ ] Implement sorting:
  - [ ] Dropdown/menu untuk choose sort field: name, joined_at, checked_in_at
  - [ ] Toggle sort order: ascending/descending
- [ ] Implement pagination:
  - [ ] Infinite scroll atau "Load More" button
  - [ ] Show current page / total pages
- [ ] On participant click:
  - [ ] Show participant detail modal/sheet
  - [ ] Show full profile info
  - [ ] Show participant's media uploads

**Testing**:
- [ ] Participant list loads
- [ ] Stats show correct counts
- [ ] Filter by status works
- [ ] Sorting works
- [ ] Pagination/infinite scroll works
- [ ] Participant detail modal opens
- [ ] Media list for participant shows

### 9️⃣ MODULE 8: CHECK-OUT (1-2 hours)

**Dependency**: Module 1, 3

**Tasks**:
- [ ] Add "Check-Out" button di event detail (visible when checked-in)
- [ ] On click:
  - [ ] Show confirmation dialog: "Apakah yakin ingin check-out?"
- [ ] Call `POST /api/events/{id}/participants/{participant_id}/check-out`
- [ ] On success:
  - [ ] Show success toast: "Check-out berhasil"
  - [ ] Update status badge: "checked_in" → "checked_out"
  - [ ] Display `checked_out_at` timestamp
  - [ ] Display `check_in_duration_minutes` (convert to "2h 30m" format)
  - [ ] Disable "Check-Out" button
  - [ ] Can't upload media after check-out
- [ ] On error: Show error toast

**Testing**:
- [ ] Successfully check-out when checked-in
- [ ] Cannot check-out twice
- [ ] Cannot check-out if not checked-in
- [ ] Duration calculated correctly
- [ ] Timestamp recorded

### 🔟 MODULE 9: ERROR HANDLING & OFFLINE (2-3 hours)

**Dependency**: All modules

**Tasks**:
- [ ] Implement centralized error handler:
  - [ ] 401 errors: Show login required toast, redirect to login
  - [ ] 400 errors: Show error message from response
  - [ ] 404 errors: Show "Not found" message
  - [ ] 5xx errors: Show "Server error" message
  - [ ] Network errors: Show "No internet connection" message
- [ ] Implement retry logic:
  - [ ] Exponential backoff untuk failed requests
  - [ ] Max retry attempts: 3
  - [ ] Show retry notification to user
- [ ] Implement offline fallback:
  - [ ] Cache GET responses locally (SQLite/Hive)
  - [ ] Show cached data jika offline
  - [ ] Show "Offline mode" indicator
  - [ ] Queue POST/PUT/DELETE requests untuk upload ketika online
- [ ] Add loading states:
  - [ ] Show skeleton/shimmer loading di semua list views
  - [ ] Show circular progress untuk modal actions

**Testing**:
- [ ] 401 error handling
- [ ] 400 error handling
- [ ] Network error handling
- [ ] Retry works correctly
- [ ] Offline mode shows cached data
- [ ] Queued requests upload when online

### 1️⃣1️⃣ MODULE 10: UI/UX POLISH (2 hours)

**Tasks**:
- [ ] Add animations:
  - [ ] Page transitions (slide, fade)
  - [ ] Button interactions (ripple effect, scale)
  - [ ] Progress bar animations
- [ ] Add haptic feedback (vibration) untuk:
  - [ ] Successful check-in
  - [ ] Successful media upload
  - [ ] Button press
- [ ] Implement dark mode support (if supported)
- [ ] Optimize performance:
  - [ ] Use lazy loading untuk images
  - [ ] Cache media thumbnails
  - [ ] Implement image compression sebelum upload
- [ ] Accessibility:
  - [ ] Add content descriptions untuk images
  - [ ] Ensure sufficient color contrast
  - [ ] Support screen readers

**Testing**:
- [ ] All animations smooth
- [ ] Haptic feedback works
- [ ] Dark mode looks good
- [ ] App responsive di berbagai ukuran screen
- [ ] Performance smooth (60 FPS)

---

## 📊 IMPLEMENTATION TIMELINE

| Phase | Modules | Duration | Dependencies |
|-------|---------|----------|--------------|
| Week 1 | 1, 2, 3 | 8-10h | None |
| Week 1 | 4, 5 | 6-8h | Modules 1-3 |
| Week 2 | 6, 7 | 4-6h | Module 1 |
| Week 2 | 8, 9, 10 | 5-8h | Modules 1-7 |

**Total Estimated**: 23-32 hours (3-4 hari development)

---

## 🎯 ACCEPTANCE CRITERIA

### For Each Module:
✅ All tasks completed  
✅ All testing scenarios pass  
✅ No crashes atau exceptions  
✅ Error messages user-friendly (Indonesian)  
✅ Loading states visible  
✅ Offline fallback works  

### Overall:
✅ All 8 endpoints successfully integrated  
✅ Real-time progress updates setiap 5-10 detik  
✅ Media uploads work (photo + video)  
✅ QR check-in/check-out working  
✅ Participant list with filters working  
✅ App tested on real device (not just emulator)  
✅ Performance acceptable (<2s load time per screen)  

---

## 🔗 REFERENCE DOCUMENTS

1. **MOBILE_EVENT_MANAGEMENT_IMPLEMENTATION.md** - Full API documentation
2. **POSTMAN_MOBILE_EVENT_MANAGEMENT.json** - Postman collection untuk testing
3. **EVENT_MANAGEMENT_PLAN.md** - Backend implementation details (reference only)

---

## 🆘 TROUBLESHOOTING

### Common Issues

**Issue**: "QR token invalid"
- **Solution**: Ensure QR code dari event di backend, bukan random token

**Issue**: "Belum bergabung dengan event"
- **Solution**: Call join endpoint sebelum check-in

**Issue**: "Check-in duration_minutes null"
- **Solution**: Only available after check-out. During checked-in akan null.

**Issue**: Media upload stuck / slow
- **Solution**: Check file size, max 100MB. Compress video ketika diperlukan.

**Issue**: Progress not updating
- **Solution**: Check polling interval (5-10s). Stop polling ketika phase='finished'.

**Issue**: Token expired mid-session
- **Solution**: Implement token refresh atau ask user to re-login after 30 days.

---

## 📞 SUPPORT

**Backend Contact**: [Backend Developer]  
**API Documentation**: `MOBILE_EVENT_MANAGEMENT_IMPLEMENTATION.md`  
**Status**: ✅ PRODUCTION READY

---

**Ready to start implementation? Let's go! 🚀**
