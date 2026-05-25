# 🚀 Backend Update - Flutter Implementation Guide

## 📢 PERHATIAN: Ada Perubahan di Backend!

Backend telah ditambahkan kolom **`condition`** di model **River** yang merupakan **auto-calculated** berdasarkan river reports. Berikut adalah struktur lengkap yang perlu Anda implementasikan di Flutter.

---

## 🔄 Perubahan Backend Summary

### ✅ Kolom Baru: `condition` (River Model)
```
Type: Integer (1-3)
Values:
  1 = Normal (average urgency < 2.5)
  2 = Warning (average urgency 2.5 - < 4)
  3 = Urgent (average urgency >= 4)
Default: 1
Auto-calculated: YES (dari river reports urgency)
```

### 🔗 Data Flow:
```
User submit report dengan urgency → POST /api/river-reports
  ↓
Backend hitung average urgency dari semua reports
  ↓
Map ke condition (1/2/3)
  ↓
Update river.condition otomatis
  ↓
GET /api/rivers return river dengan condition terbaru
  ↓
GET /api/rivers/{id} return detail river + reporters + latest report photo
```

---

## 📡 Endpoints yang Berubah

### 1. GET /api/rivers
**Response sekarang include `condition`:**
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

### 2. POST /api/river-reports
**Trigger auto-update river.condition**
```json
// Request (NO condition field!)
{
  "river_id": 1,
  "description": "Sungai tercemar",
  "urgency": "warning",
  "latitude": -6.9175,
  "longitude": 107.6412,
  "photo": <file>
}

// Response: Report berhasil, river.condition auto-updated di backend
```

### 3. GET /api/river-reports
**River object sekarang include `condition`:**
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
        "condition": 2  // ← BARU!
      },
      "profile": {...}
    }
  ]
}
```

### 4. GET /api/rivers-conclusion
**River object sekarang include `condition`:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "river_id": 1,
      "status": "warning",
      "average_urgency": 2.5,
      "river": {
        "id": 1,
        "name": "Sungai Citarum",
        "latitude": -6.9175,
        "longitude": 107.6412,
        "condition": 2  // ← BARU!
      }
    }
  ]
}
```

### 5. GET /api/rivers/{id} (BARU - River Detail)
**Response include detail river + user pelapor + foto profil + foto report terbaru:**
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
      "updated_at": "2026-04-15T10:00:00Z",
      "latest_report_photo": "river_reports/latest.jpg",
      "reporter_user_ids": [4, 7, 12],
      "reporters": [
        {
          "user_id": 4,
          "name": "Budi",
          "photo_profile": "profiles/budi.jpg"
        },
        {
          "user_id": 7,
          "name": "Sari",
          "photo_profile": "profiles/sari.jpg"
        }
      ]
    }
  ]
}
```

**Catatan penting mapping Flutter:**
- `latest_report_photo` = foto sungai dari report terbaru
- `reporter_user_ids` = list ID user pelapor (unik, bisa banyak)
- `reporters[].photo_profile` = foto profil user pelapor (untuk avatar/list)

---

## 🎯 CHECKLIST - Yang Perlu Diupdate di Flutter

### ✅ 1. River Model/Entity
- [ ] Tambahkan field: `int condition`
- [ ] Tambahkan constants:
  ```dart
  static const int CONDITION_NORMAL = 1;
  static const int CONDITION_WARNING = 2;
  static const int CONDITION_URGENT = 3;
  ```
- [ ] Update fromJson/toJson untuk map `condition`

**Contoh:**
```dart
class River {
  final int id;
  final String name;
  final String? description;
  final double? latitude;
  final double? longitude;
  final int condition; // ← BARU
  final DateTime createdAt;
  final DateTime updatedAt;

  static const int CONDITION_NORMAL = 1;
  static const int CONDITION_WARNING = 2;
  static const int CONDITION_URGENT = 3;

  factory River.fromJson(Map<String, dynamic> json) {
    return River(
      id: json['id'],
      name: json['name'],
      description: json['description'],
      latitude: json['latitude'],
      longitude: json['longitude'],
      condition: json['condition'] ?? 1, // ← BARU
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }
}
```

---

### ✅ 2. River List Screen
**Data:** GET `/api/rivers`

**Perubahan UI:**
- [ ] Tambahkan tampilan `condition` (badge/icon/color card)
- [ ] Gunakan color berdasarkan condition:
  - Condition 1 (Normal) → 🟢 Green
  - Condition 2 (Warning) → 🟡 Orange/Yellow
  - Condition 3 (Urgent) → 🔴 Red

**Contoh Implementation:**
```dart
// Helper function
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
    case 1: return Icons.check_circle;
    case 2: return Icons.warning;
    case 3: return Icons.error;
    default: return Icons.help;
  }
}

// UI Widget
Widget buildConditionBadge(int condition) {
  return Container(
    padding: EdgeInsets.symmetric(horizontal: 12, vertical: 6),
    decoration: BoxDecoration(
      color: getConditionColor(condition).withOpacity(0.2),
      border: Border.all(color: getConditionColor(condition)),
      borderRadius: BorderRadius.circular(20),
    ),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(
          getConditionIcon(condition),
          color: getConditionColor(condition),
          size: 16,
        ),
        SizedBox(width: 6),
        Text(
          getConditionLabel(condition),
          style: TextStyle(
            color: getConditionColor(condition),
            fontWeight: FontWeight.bold,
            fontSize: 12,
          ),
        ),
      ],
    ),
  );
}

// Di ListView/GridView:
ListTile(
  title: Text(river.name),
  subtitle: Text(river.description ?? ''),
  trailing: buildConditionBadge(river.condition),
)
```

---

### ✅ 3. River List - Map View
**Data:** GET `/api/rivers`

**Perubahan untuk Google Maps/Leaflet:**
- [ ] Tambahkan marker color berdasarkan `condition`
- [ ] Update InfoWindow/PopUp untuk tampilkan condition badge
- [ ] Cluster markers berdasarkan condition

**Contoh Implementation:**
```dart
// Generate marker dari river data
List<Marker> generateRiverMarkers(List<River> rivers) {
  return rivers.map((river) {
    Color markerColor;
    switch(river.condition) {
      case 1: markerColor = Colors.green; break;
      case 2: markerColor = Colors.orange; break;
      case 3: markerColor = Colors.red; break;
      default: markerColor = Colors.grey;
    }

    return Marker(
      markerId: MarkerId('river_${river.id}'),
      position: LatLng(river.latitude!, river.longitude!),
      infoWindow: InfoWindow(
        title: river.name,
        snippet: getConditionLabel(river.condition),
      ),
      icon: BitmapDescriptor.defaultMarkerWithHue(
        markerColor == Colors.green ? BitmapDescriptor.hueGreen :
        markerColor == Colors.orange ? BitmapDescriptor.hueOrange :
        markerColor == Colors.red ? BitmapDescriptor.hueRed :
        BitmapDescriptor.hueGrey
      ),
    );
  }).toList();
}

// Di GoogleMap widget:
GoogleMap(
  initialCameraPosition: CameraPosition(
    target: LatLng(-6.9175, 107.6412),
    zoom: 12,
  ),
  markers: Set.from(generateRiverMarkers(rivers)),
)
```

---

### ✅ 4. Create River Screen
**Endpoint:** POST `/api/rivers`

**Perubahan:**
- [ ] ❌ JANGAN kirim `condition` (auto-initialized jadi 1 di backend)
- [ ] Hapus dropdown/input field untuk `condition`

**Contoh:**
```dart
// ❌ WRONG:
final body = {
  'name': nameController.text,
  'condition': selectedCondition, // ← HAPUS!
};

// ✅ CORRECT:
final body = {
  'name': nameController.text,
  'description': descController.text,
  'latitude': lat,
  'longitude': lng,
  // condition tidak dikirim - auto jadi 1 di backend
};
```

---

### ✅ 5. Submit River Report Screen
**Endpoint:** POST `/api/river-reports`

**Perubahan:**
- [ ] Tetap kirim `urgency` (normal/warning/urgent)
- [ ] Setelah report berhasil:
  - Refresh river list (untuk update condition yang baru)
  - Atau call GET `/api/rivers-conclusion/{id}` untuk detail river
  - Show toast: "Report submitted! River condition updated"
  - Update UI map marker

**Contoh:**
```dart
// POST report
final response = await http.post(
  Uri.parse('$baseUrl/api/river-reports'),
  headers: {
    'Authorization': 'Bearer $token',
    'Content-Type': 'application/json',
  },
  body: jsonEncode({
    'river_id': river.id,
    'description': description,
    'urgency': urgency, // normal, warning, urgent
    'latitude': lat,
    'longitude': lng,
    // photo juga bisa di-attach
  }),
);

if (response.statusCode == 201) {
  // ✅ SUCCESS - River condition sudah auto-update di backend
  
  // Refresh river data
  await fetchRiversList(); // GET /api/rivers
  
  // Update map markers
  updateMapMarkers();
  
  // Show notification
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      content: Text('Report submitted! River condition updated.'),
      backgroundColor: Colors.green,
    ),
  );
}
```

---

### ✅ 6. River Reports List Screen
**Endpoint:** GET `/api/river-reports`

**Perubahan:**
- [ ] Tampilkan `river.condition` untuk setiap report
- [ ] Gunakan color/badge sesuai condition
- [ ] Optional: Highlight river dengan condition urgent

**Contoh:**
```dart
Card(
  child: ListTile(
    title: Text(report.river.name),
    subtitle: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Urgency: ${report.urgency}'),
        SizedBox(height: 4),
        Text('River Condition: ${getConditionLabel(report.river.condition)}'),
      ],
    ),
    trailing: buildConditionBadge(report.river.condition),
    tileColor: report.river.condition == 3 
      ? Colors.red.withOpacity(0.1) 
      : null,
  ),
)
```

---

### ✅ 7. River Conclusion/Analysis Screen
**Endpoint:** GET `/api/rivers-conclusion`

**Perubahan:**
- [ ] Tampilkan `river.condition` di setiap item
- [ ] Tampilkan `average_urgency` dan `status` untuk konteks
- [ ] Tambahkan chart/graph untuk visualisasi urgency trend
- [ ] Update map dengan river markers

**Contoh:**
```dart
Column(
  children: [
    Text(
      conclusion.river.name,
      style: Theme.of(context).textTheme.headlineSmall,
    ),
    SizedBox(height: 8),
    Row(
      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
      children: [
        Column(
          children: [
            Text('Condition'),
            buildConditionBadge(conclusion.river.condition),
          ],
        ),
        Column(
          children: [
            Text('Status'),
            Text(conclusion.status ?? 'N/A'),
          ],
        ),
        Column(
          children: [
            Text('Avg Urgency'),
            Text(conclusion.averageUrgency?.toString() ?? 'N/A'),
          ],
        ),
      ],
    ),
    Divider(),
    // Reporter info
    Text('Reported by ${conclusion.reporterCount} users'),
  ],
)
```

---

### ✅ 8. River Detail Screen (Optional)
**Endpoint utama:** GET `/api/rivers/{id}`
**Endpoint alternatif analitik:** GET `/api/rivers-conclusion/{id}`

**Perubahan:**
- [ ] Tampilin detail river dengan `condition` prominent
- [ ] Tampilkan `latest_report_photo` sebagai header/cover
- [ ] Tampilkan list avatar dari `reporters[].photo_profile`
- [ ] Simpan juga list `reporter_user_ids` jika perlu navigasi ke profile user
- [ ] Link ke map view dengan marker highlighted

**Contoh Layout:**
```
┌─────────────────────────────┐
│  SUNGAI CITARUM             │
│  🟡 Status: Warning         │
├─────────────────────────────┤
│  [latest_report_photo]      │
├─────────────────────────────┤
│  Condition: 2 (Warning)     │
│  Avg Urgency: 2.5           │
│  Reported by: 5 users       │
│  Avatars: [o] [o] [o]       │
├─────────────────────────────┤
│  📍 Map View                │
│  [Google Map with marker]   │
└─────────────────────────────┘
```

---

## 📱 UI/UX Recommendations

### Color Scheme
```
Condition 1 (Normal)   → #4CAF50 (Green)
Condition 2 (Warning)  → #FF9800 (Orange)
Condition 3 (Urgent)   → #F44336 (Red)
```

### Icon Set (recommended: flutter_icons)
```
Condition 1 → Icons.check_circle (Normal)
Condition 2 → Icons.warning (Warning)
Condition 3 → Icons.error (Urgent)
```

### Badge Component
- Use rounded container dengan border
- Include icon + label
- Size: 16-20px font untuk list, 20-24px untuk detail

### Map Markers
- Color-code berdasarkan condition
- Add infoWindow dengan:
  - River name
  - Condition status
  - Click untuk open detail

---

## 🧪 Testing Checklist

- [ ] GET /api/rivers returns river dengan `condition`
- [ ] GET /api/rivers/{id} returns `latest_report_photo`
- [ ] GET /api/rivers/{id} returns `reporter_user_ids` (unique IDs)
- [ ] GET /api/rivers/{id} returns `reporters[].photo_profile`
- [ ] POST /api/river-reports auto-update river.condition
- [ ] GET /api/river-reports show river.condition untuk setiap report
- [ ] GET /api/rivers-conclusion include river.condition
- [ ] UI badges menampilkan color yang benar berdasarkan condition
- [ ] Map markers warna-warna sesuai condition
- [ ] Mobile responsif di berbagai ukuran screen
- [ ] Performance: loading 100+ rivers tidak lag

---

## 🔧 Implementation Priority

1. **HIGH (Do First):**
   - Update River model dengan field `condition`
   - Update River List Screen untuk tampilkan condition badge
   - Update POST report untuk auto-refresh list

2. **MEDIUM (Do Next):**
   - Update map markers dengan colors
   - Update River Reports List Screen
   - Update Conclusion Screen

3. **LOW (Optional):**
   - Detail screen enhancements
   - Analytics/charts
   - Advanced filtering by condition

---

## 📞 Questions?

Jika ada yang tidak jelas atau ada error:
1. Check API response di Postman/Insomnia
2. Verify `condition` value ada di response
3. Check Flutter console untuk error messages
4. Sync ulang dengan backend team

**Backend sudah siap! Enjoy implementing! 🚀**
