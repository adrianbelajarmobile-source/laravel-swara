# River Condition - Dokumentasi Endpoint Update

## Kolom Baru yang Ditambahkan

**Kolom**: `condition` (tinyInteger)

**Nilai yang dimungkinkan:**
- `1` = Normal (rata-rata urgency reports < 2.5)
- `2` = Warning (rata-rata urgency reports 2.5 - < 4)
- `3` = Urgent (rata-rata urgency reports >= 4)

**Default**: `1` (Normal)

**PENTING**: `condition` adalah **AUTO-CALCULATED** dari river reports, bukan di-set manual!

---

## 📊 Cara Kalkulasi `condition`:

1. Ambil semua reports untuk sebuah river
2. Konversi urgency setiap report ke nilai numerik:
   - `normal` → 1.0
   - `warning` → 2.0
   - `urgent` → 3.0
3. Hitung rata-rata urgency: `averageUrgency`
4. Map ke condition berdasarkan range:
   ```
   if averageUrgency is null or < 2.5 → condition = 1 (normal)
   if averageUrgency >= 2.5 and < 4 → condition = 2 (warning)
   if averageUrgency >= 4 → condition = 3 (urgent)
   ```

**Contoh:**
- Sungai A punya 2 reports: urgency "normal" (1.0) + "warning" (2.0)
- Average = (1.0 + 2.0) / 2 = 1.5
- Condition = 1 (normal)

---

## 📡 ENDPOINT YANG MENGGUNAKAN RIVER

### 1️⃣ **GET /api/rivers** 
- **Deskripsi**: Mengambil list semua sungai
- **Method**: GET
- **Auth Required**: Ya (middleware auth:sanctum)
- **Response includes**: 
  - `id`, `name`, `description`, `latitude`, `longitude`, **`condition`** ✅ (auto-calculated)
  - `created_at`, `updated_at`

**Response Example:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Sungai Citarum",
      "description": "Sungai di Jawa Barat",
      "latitude": -6.9175,
      "longitude": 107.6412,
      "condition": 2,
      "created_at": "2026-04-15T10:00:00Z",
      "updated_at": "2026-04-15T10:00:00Z"
    }
  ]
}
```

**Flutter Implementation:**
```dart
// GET request untuk ambil list river
final response = await http.get(
  Uri.parse('http://localhost:8000/api/rivers'),
  headers: {'Authorization': 'Bearer $token'},
);

// Parse response
final rivers = jsonDecode(response.body)['data'];
for(var river in rivers) {
  print('River: ${river['name']}, Condition: ${river['condition']}');
  // condition = 1 (normal), 2 (warning), 3 (urgent)
}
```

---

### 2️⃣ **POST /api/rivers**
- **Deskripsi**: Membuat sungai baru (condition auto jadi 1 karena belum ada reports)
- **Method**: POST
- **Auth Required**: Ya (middleware auth:sanctum)
- **Request Body**:
```json
{
  "name": "Sungai Ciliwung",
  "description": "Sungai di Jakarta",
  "latitude": -6.2088,
  "longitude": 106.8456
}
```

**Required fields**: `name`
**Optional fields**: `description`, `latitude`, `longitude`
**⚠️ CATATAN**: `condition` TIDAK dikirim di request - auto-calculated!

**Response:**
```json
{
  "message": "River berhasil ditambahkan",
  "data": {
    "id": 1,
    "name": "Sungai Ciliwung",
    "condition": 1,
    "created_at": "2026-04-15T10:00:00Z"
  }
}
```

**Flutter Implementation:**
```dart
// POST request untuk buat river baru
final response = await http.post(
  Uri.parse('http://localhost:8000/api/rivers'),
  headers: {
    'Authorization': 'Bearer $token',
    'Content-Type': 'application/json',
  },
  body: jsonEncode({
    'name': 'Sungai Baru',
    'description': 'Deskripsi sungai',
    'latitude': -6.5,
    'longitude': 107.0,
    // ⚠️ JANGAN kirim 'condition' - auto-calculated!
  }),
);
```

---

### 3️⃣ **POST /api/river-reports** 🔄 TRIGGER UPDATE
- **Deskripsi**: Menambah laporan kondisi sungai + AUTO-UPDATE river's condition
- **Method**: POST
- **Auth Required**: Ya (middleware auth:sanctum)
- **Trigger**: Saat report berhasil dibuat, river's condition otomatis di-recalculate!
- **Request Body**:
```json
{
  "river_id": 1,
  "description": "Sungai tercemar limbah pabrik",
  "latitude": -6.9175,
  "longitude": 107.6412,
  "urgency": "warning",
  "photo": "<file>"
}
```

**Alur:**
1. Report dibuat dengan urgency = "warning" (nilai 2.0)
2. Hitung rata-rata urgency dari semua reports sungai itu
3. Map ke condition baru
4. Update river.condition secara otomatis ✅

---

### 4️⃣ **GET /api/river-reports**
- **Deskripsi**: Mengambil list laporan sungai
- **Method**: GET
- **Auth Required**: Ya (middleware auth:sanctum)
- **Response includes**: 
  - Setiap report return object `river` dengan: `id`, `name`, **`condition`** ✅

**Response Example:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "description": "Sungai tercemar",
      "urgency": "warning",
      "river": {
        "id": 1,
        "name": "Sungai Citarum",
        "condition": 2
      },
      "profile": {
        "name": "John Doe"
      }
    }
  ]
}
```

---

### 5️⃣ **GET /api/rivers-conclusion**
- **Deskripsi**: Mengambil kesimpulan status sungai (auto-updated saat GET)
- **Method**: GET
- **Auth Required**: Ya (middleware auth:sanctum)
- **Response includes**: 
  - River object dengan: `id`, `name`, `latitude`, `longitude`, **`condition`** ✅
  - Status (string: normal/warning/urgent)
  - Average urgency (float)
  - Reporter count

**Response Example:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "river_id": 1,
      "status": "warning",
      "average_urgency": 2.5,
      "reporter_count": 3,
      "river": {
        "id": 1,
        "name": "Sungai Citarum",
        "latitude": -6.9175,
        "longitude": 107.6412,
        "condition": 2
      },
      "reporters": [...]
    }
  ]
}
```

---

### 6️⃣ **GET /api/rivers-conclusion/{id}**
- **Deskripsi**: Detail kesimpulan sungai by ID
- **Method**: GET
- **Auth Required**: Ya (middleware auth:sanctum)
- **Response includes**: Detail lengkap river dengan `condition` ✅

---

## 🎯 YANG PERLU DIPERBAIKI DI FLUTTER

### Update List:

1. **River List Screen** ❌ 
   - Endpoint: `GET /api/rivers`
   - Tampilkan: `condition` (1=normal 🟢, 2=warning 🟡, 3=urgent 🔴)
   - UI: Gunakan warna/icon sesuai condition
   - Note: Condition otomatis update saat ada report baru

2. **Create River Form** ✅ 
   - Endpoint: `POST /api/rivers`
   - JANGAN kirim: `condition` (remove dari form)
   - Condition auto jadi 1 saat create

3. **Submit River Report** ⚠️ 
   - Endpoint: `POST /api/river-reports`
   - Setelah report berhasil: River's condition akan update otomatis
   - Suggestion: Refresh river list atau show toast "River condition updated!"

4. **River Reports List** ❌
   - Endpoint: `GET /api/river-reports`
   - Tampilkan: river's `condition` untuk setiap report
   - Bisa di-highlight dengan warna berdasarkan condition

5. **River Conclusion List** ❌
   - Endpoint: `GET /api/rivers-conclusion`
   - Tampilkan: river's `condition` 
   - Tampilkan: `average_urgency` dan `status` untuk konteks

6. **River Conclusion Detail** ❌
   - Endpoint: `GET /api/rivers-conclusion/{id}`
   - Tampilkan: river's `condition` dan detail lengkapnya

---

## 🚀 Migration Command

Run migration untuk apply kolom baru ke database:

```bash
php artisan migrate
```

---

## 💡 Tips for Flutter Development

```dart
// Helper function untuk map condition ke label/warna
String getConditionLabel(int condition) {
  switch(condition) {
    case 1: return 'Normal';
    case 2: return 'Warning';
    case 3: return 'Urgent';
    default: return 'Unknown';
  }
}

Color getConditionColor(int condition) {
  switch(condition) {
    case 1: return Colors.green;
    case 2: return Colors.orange;
    case 3: return Colors.red;
    default: return Colors.grey;
  }
}

IconData getConditionIcon(int condition) {
  switch(condition) {
    case 1: return Icons.check_circle; // Normal
    case 2: return Icons.warning; // Warning
    case 3: return Icons.error; // Urgent
    default: return Icons.help;
  }
}

// Gunakan di UI
Container(
  decoration: BoxDecoration(
    color: getConditionColor(river['condition']).withOpacity(0.2),
    border: Border.all(color: getConditionColor(river['condition'])),
    borderRadius: BorderRadius.circular(8),
  ),
  child: Row(
    children: [
      Icon(
        getConditionIcon(river['condition']),
        color: getConditionColor(river['condition']),
      ),
      SizedBox(width: 8),
      Text(
        getConditionLabel(river['condition']),
        style: TextStyle(color: getConditionColor(river['condition'])),
      ),
    ],
  ),
)
```

---

## 📋 Summary Perubahan

| Aspek | Sebelum | Sesudah |
|-------|---------|--------|
| `condition` value | N/A (kolom baru) | 1, 2, atau 3 |
| Cara set condition | Di-set manual saat create river | AUTO-calculated dari reports |
| Kapan update | Saat create | Otomatis setiap ada report baru |
| Kalkulasi | N/A | Average urgency dari semua reports |
| POST /rivers | Terima `condition` | ❌ JANGAN terima `condition` |
| GET /rivers | Tidak include | ✅ Include `condition` |
| POST /river-reports | Hanya create report | ✅ Auto-update river.condition |
