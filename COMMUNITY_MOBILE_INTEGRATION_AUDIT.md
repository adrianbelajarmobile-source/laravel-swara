# Community Mobile Integration Audit

Tanggal: 2026-04-16
Scope: Audit khusus fitur community agar tim mobile bisa cek endpoint yang belum diimplementasi dan perbaikan kode yang dibutuhkan.

## 1. Ringkasan Arsitektur Community

Backend community sekarang mendukung:
- Privacy komunitas: Publik atau Privat
- Permission join: Bebas atau Perizinan Admin
- Membership role: influencer, admin, pegiat
- Membership status: pending, invited, approved

Aturan inti:
- **Creator otomatis admin**: Saat membuat komunitas, creator langsung jadi member dengan role `admin` dan status `approved`.
- Privat: user harus diundang dulu, lalu user menekan join untuk jadi approved.
- Publik + Perizinan Admin: user join menghasilkan pending, admin atau creator harus approve.
- Publik + Bebas: user join langsung approved.

## 2. Struktur Data Penting

### Tabel communities
Kolom yang dipakai mobile:
- id
- name
- description
- capacity
- location
- privacy (Publik atau Privat)
- permission (Perizinan Admin atau Bebas)
- cover_image
- created_by
- created_at
- updated_at

Catatan kompatibilitas:
- Response API juga mengirim creator_id sebagai alias created_by untuk memudahkan mobile.

### Tabel community_members
Kolom yang dipakai alur join:
- id
- community_id
- user_id
- role (admin, influencer, pegiat)
  - `admin`: creator dan member yang dipromote dengan privilege manage community
  - `influencer`: member level menengah (reserved)
  - `pegiat`: regular member
- status (pending, invited, approved)
- invited_by
- approved_by
- approved_at
- created_at
- updated_at

## 3. Daftar Endpoint Community

Semua endpoint di bawah auth:sanctum.

### A. Discovery dan detail komunitas
1. GET /api/communities
Tujuan:
- List semua komunitas untuk halaman discovery.

Field penting di data item:
- id, name, description
- capacity, location, privacy, permission, cover_image
- created_by, creator_id
- members_count
- is_member, user_role

2. GET /api/communities/{community}
Tujuan:
- Detail satu komunitas.

Field penting:
- Informasi komunitas lengkap
- members_count
- members (approved members)
- is_member dan user_role untuk user login

3. GET /api/communities/my/created
Tujuan:
- List komunitas yang dibuat user.

4. GET /api/communities/my/joined
Tujuan:
- List komunitas yang sudah approved untuk user.

Catatan penting:
- Membership pending atau invited tidak muncul di endpoint ini.

### B. Lifecycle komunitas
5. POST /api/communities
Tujuan:
- Buat komunitas baru.

Body minimal:
- name

Body lengkap:
- name
- description
- capacity
- location
- privacy (Publik atau Privat)
- permission (Perizinan Admin atau Bebas)
- cover_image

6. DELETE /api/communities/{community}
Tujuan:
- Hapus komunitas (creator saja).

### C. Join dan membership
7. POST /api/communities/{community}/join
Tujuan:
- Join komunitas.

Perilaku berdasarkan konfigurasi komunitas:
- Privat:
  - jika user tidak invited: 403
  - jika user invited: 201 approved
- Publik + Perizinan Admin:
  - 202 pending
- Publik + Bebas:
  - 201 approved

8. POST /api/communities/{community}/leave
Tujuan:
- Keluar komunitas (approved member non-creator).

9. GET /api/communities/{community}/members
Tujuan:
- Ambil list approved members.

Akses:
- Creator atau admin atau influencer komunitas.

### D. Moderasi dan manajemen anggota
10. POST /api/communities/{community}/invite
Tujuan:
- Mengundang user ke komunitas.

Body:
- user_id

Akses:
- Creator atau admin atau influencer komunitas.

11. GET /api/communities/{community}/join-requests
Tujuan:
- Ambil daftar pending join requests.

Akses:
- Creator atau admin atau influencer komunitas.

12. POST /api/communities/{community}/members/{member}/approve
Tujuan:
- Approve pending request.

Akses:
- Creator atau admin atau influencer komunitas.

13. DELETE /api/communities/{community}/members/{member}/reject
Tujuan:
- Reject pending request.

Akses:
- Creator atau admin atau influencer komunitas.

14. PATCH /api/communities/{community}/members/{member}
Tujuan:
- Ubah role member approved.

Role target valid:
- influencer
- admin
- pegiat

Akses:
- Creator atau admin atau influencer komunitas.

15. DELETE /api/communities/{community}/members/{member}
Tujuan:
- Remove member.

Akses:
- Creator atau admin atau influencer komunitas.

### E. Chat komunitas
16. GET /api/communities/{community}/messages
17. POST /api/communities/{community}/messages

Akses:
- Hanya approved member.

## 4. Checklist Implementasi Mobile

Centang endpoint yang sudah dipakai di mobile.

### Wajib untuk user umum
- GET /api/communities
- GET /api/communities/{id}
- POST /api/communities/{id}/join
- POST /api/communities/{id}/leave
- GET /api/communities/ (create)
- POST /api/communities/{id}/invite (undang member)
- GET /api/communities/{id}/join-requests (lihat pending join requests)
- POST /api/communities/{id}/members/{memberId}/approve (approve pending)
- DELETE /api/communities/{id}/members/{memberId}/reject (reject pending)
- PATCH /api/communities/{id}/members/{memberId} (update role: bisa jadi admin atau pegiat)
- DELETE /api/communities/{id}/members/{memberId} (remove member)
- DELETE /api/communities/{id} (delete community)pprove
- DELETE /api/communities/{id}/members/{memberId}/reject
- PATCH /api/communities/{id}/members/{memberId}
- DELETE /api/communities/{id}/members/{memberId}

### Wajib untuk chat
- GET /api/communities/{id}/messages
- POST /api/communities/{id}/messages

## 5. Gap Umum di Mobile dan Perbaikan

Jika mobile masih pakai flow lama, biasanya ada gap berikut.

1. Gap: Menganggap join selalu langsung sukses member.
Perbaikan:
- Tangani 202 sebagai pending.
- Simpan state membership_status di client state.

2. Gap: Tidak ada UI invitation untuk komunitas privat.
Perbaikan:
- Tambah screen invite user untuk admin atau creator.
- Jika join private dan 403, tampilkan pesan butuh undangan.

3. Gap: Tidak ada halaman approval request.
Perbaikan:
- Tambah tab Join Requests untuk admin atau creator.
- Role admin sudah tersedia dan creator otomatis admin.
- Di UI edit role, admin bisa mengubah member menjadi admin atau pegiat.
- Creator tidak bisa dihapus atau diubah role-nya.

4. Gap: Tidak ada role admin di dropdown role.
Perbaikan:
- Tambahkan role admin di UI edit role member.

5. Gap: Mapping field creator hanya creator_id atau hanya created_by.
Perbaikan:
- Gunakan fallback: creatorId = creator_id ?? created_by.

6. Gap: Menampilkan member count dari list member lokal.
Perbaikan:
- Pakai members_count dari API list atau detail.

7. Gap: Chat dibuka walau status pending atau invited.
Perbaikan:
- Cek is_member atau user_role dari detail sebelum membuka chat.

## 6. Kontrak Status Code yang Harus Ditangani Mobile

- 201: berhasil create atau join approved
- 202: join request diterima sebagai pending
- 400: bad request bisnis, contoh sudah member
- 403: forbidden, contoh private tanpa undangan atau bukan manager
- 409: konflik, contoh request masih pending
- 422: validasi gagal

## 7. Rekomendasi Refactor Client

1. Buat enum lokal:
- CommunityPrivacadmin, influencer, pegiat

2. Buat helper kemampuan user:
- canManageCommunity = role in [admin, influencer] or user is creator
- canOpenChat = membershipStatus == approved && status != (pending or invited)
- canApproveJoin = canManageCommunity

3. Buat handling join terpusat:
- jika 201: langsung masuk komunitas
- jika 202: tampilkan badge menunggu persetujuan
- jika 403 private: tampilkan instruksi minta undangan

4. Member list harus tampilkan:
- name (dari profile)
- photo_profile
- role (admin, influencer, pegiat)
- status (approved, pending, invited)
- joined_at
- jika 201: langsung masuk komunitas
- jika 202: tampilkan badge menunggu persetujuan
- jika 403 private: tampilkan instruksi minta undangan

## 8. Test Scenario Minimum (QA Mobile)

1. Public + Bebas
- User join
- Expected: 201, langsung muncul di my joined

2. Public + Perizinan Admin
- User join
- Expected: 202 pending
- Admin approve
- Expected: user muncul di my joined

3. Private
- User join tanpa undangan
- Expected: 403
- Admin invite user
- User join
- Expected: 201 approved

4. Role admin
- Creator promote member jadi admin
- Admin bisa buka join requests dan approve

5. Chat protection
- User pending coba buka messages
- Expected: 403
- Setelah approved
- Expected: bisa get dan send messages

## 9. Catatan Integrasi Cepat untuk Tim Mobile

Prioritas implementasi yang paling sering belum ada:
1. Invite flow untuk private community.
2. Pending approval flow untuk public with admin permission.
3. Management role admin.
4. Penanganan response 202 dan 403 join.

Jika empat hal di atas sudah ada, integrasi community biasanya sudah stabil.
