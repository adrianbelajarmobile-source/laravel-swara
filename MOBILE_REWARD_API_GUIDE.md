# Mobile Reward API Guide

Dokumentasi ringkas endpoint reward untuk aplikasi mobile.
Semua endpoint memakai prefix `/api` dan membutuhkan `Authorization: Bearer <token>`.

## 1) List Reward untuk User

### Request
- Method: `GET`
- URL: `/api/rewards`
- Query (opsional):
  - `category`: `voucher|product`
  - `search`: keyword nama reward
  - `per_page`: default 10

### Success Response (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Gopay Cashback 10K",
      "category": "voucher",
      "description": "Tukarkan voucher ini di aplikasi Gopay.",
      "points_required": 5000,
      "quantity": 100,
      "image": "rewards/gopay-cashback-10k.png",
      "status": "available"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 1,
    "last_page": 1
  }
}
```

## 2) Redeem Reward

### Request
- Method: `POST`
- URL: `/api/rewards/{reward_id}/redeem`
- Body:
```json
{
  "quantity": 1
}
```

### Success Response (201)
```json
{
  "success": true,
  "message": "Redeem berhasil",
  "data": {
    "redeem_id": 10,
    "reward": {
      "id": 1,
      "name": "Gopay Cashback 10K",
      "category": "voucher",
      "image": "rewards/gopay-cashback-10k.png"
    },
    "quantity": 1,
    "points_used": 5000,
    "status": "completed",
    "redeemed_at": "2026-04-16T10:00:00+07:00",
    "voucher": {
      "code": "GOPAY10K-APR2026",
      "pin": "1026"
    }
  }
}
```

Catatan:
- Jika reward category = `product`, field `voucher` akan bernilai `null`.
- `pin` bersifat opsional. Bisa `null` meskipun category `voucher`.

### Error Response (422) - contoh poin tidak cukup
```json
{
  "success": false,
  "message": "Redeem gagal",
  "errors": {
    "points": [
      "Poin user tidak cukup untuk redeem reward ini."
    ]
  }
}
```

## 3) Riwayat Redeem User

### Request
- Method: `GET`
- URL: `/api/rewards/redeem-histories`
- Query (opsional):
  - `per_page`: default 10

### Success Response (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "reward": {
        "id": 1,
        "name": "Gopay Cashback 10K",
        "category": "voucher",
        "image": "rewards/gopay-cashback-10k.png"
      },
      "quantity": 1,
      "points_used": 5000,
      "status": "completed",
      "created_at": "2026-04-16T10:00:00+07:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 1,
    "last_page": 1
  }
}
```

## 4) Admin CRUD Reward

Endpoint ini hanya untuk akun admin (dilindungi middleware `admin`).

### 4.1 Create Reward
- Method: `POST`
- URL: `/api/admin/rewards`
- Body:
```json
{
  "name": "Voucher Belanja 50K",
  "category": "voucher",
  "description": "Gunakan code saat checkout di merchant.",
  "points_required": 15000,
  "quantity": 50,
  "code": "SHOP50K-APR2026",
  "pin": null,
  "image": "rewards/voucher-belanja-50k.png"
}
```

### 4.2 Update Reward
- Method: `PUT` atau `PATCH`
- URL: `/api/admin/rewards/{id}`

### 4.3 List Reward
- Method: `GET`
- URL: `/api/admin/rewards`
- Query opsional: `category`, `status`, `search`, `per_page`

### 4.4 Detail Reward
- Method: `GET`
- URL: `/api/admin/rewards/{id}`

### 4.5 Delete Reward
- Method: `DELETE`
- URL: `/api/admin/rewards/{id}`

## Logic Sistem Redeem

Backend menjalankan redeem dalam database transaction dengan row lock untuk mencegah race condition:
1. Lock data user dan reward.
2. Validasi stok (`quantity`) dan poin user (`total_points`).
3. Kurangi stok reward sesuai quantity redeem.
4. Jika stok habis, set status reward menjadi `out_of_stock`.
5. Kurangi `total_points` user.
6. Simpan data ke `redeem_histories`.
7. Kembalikan `code` dan `pin` jika reward category `voucher`.
