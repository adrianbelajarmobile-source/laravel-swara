<?php

namespace Database\Seeders;

use App\Models\Reward;
use Illuminate\Database\Seeder;

class RewardSeeder extends Seeder
{
    public function run(): void
    {
        $rewards = [
            [
                'name' => 'Gopay Cashback 10K',
                'category' => Reward::CATEGORY_VOUCHER,
                'description' => 'Tukarkan voucher ini di aplikasi Gopay. Masukkan code saat checkout. Pin opsional jika diminta sistem.',
                'points_required' => 5000,
                'quantity' => 100,
                'code' => 'GOPAY10K-APR2026',
                'pin' => '1026',
                'image' => 'rewards/gopay-cashback-10k.png',
                'status' => Reward::STATUS_AVAILABLE,
            ],
            [
                'name' => 'Voucher Belanja 50K',
                'category' => Reward::CATEGORY_VOUCHER,
                'description' => 'Gunakan code voucher saat pembayaran di merchant rekanan. Pin tidak wajib.',
                'points_required' => 15000,
                'quantity' => 50,
                'code' => 'SHOP50K-APR2026',
                'pin' => null,
                'image' => 'rewards/voucher-belanja-50k.png',
                'status' => Reward::STATUS_AVAILABLE,
            ],
            [
                'name' => 'Beras 5 Kg',
                'category' => Reward::CATEGORY_PRODUCT,
                'description' => 'Tunjukkan bukti redeem ke petugas untuk pengambilan produk.',
                'points_required' => 15000,
                'quantity' => 30,
                'code' => null,
                'pin' => null,
                'image' => 'rewards/beras-5kg.png',
                'status' => Reward::STATUS_AVAILABLE,
            ],
            [
                'name' => 'Telur Ayam 1/4 Kg',
                'category' => Reward::CATEGORY_PRODUCT,
                'description' => 'Redeem berhasil jika stok tersedia. Ambil produk di lokasi mitra terdekat.',
                'points_required' => 500,
                'quantity' => 80,
                'code' => null,
                'pin' => null,
                'image' => 'rewards/telur-ayam-250gr.png',
                'status' => Reward::STATUS_AVAILABLE,
            ],
        ];

        foreach ($rewards as $item) {
            Reward::updateOrCreate(
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                ],
                $item
            );
        }
    }
}
