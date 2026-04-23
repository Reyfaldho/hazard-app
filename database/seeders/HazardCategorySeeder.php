<?php

namespace Database\Seeders;

use App\Models\HazardCategory;
use App\Models\HazardSubcategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class HazardCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Ambil user demo untuk jadi pengusul
        $demoUser = User::where('role', 'user')->first();
        $userId = $demoUser ? $demoUser->id : null;

        $categories = [
            [
                'name' => 'TTA (Tindakan Tidak Aman)',
                'code' => 'TTA',
                'subcategories' => [
                    [
                        'name' => 'Tidak Menggunakan APD',
                        'abbr' => 'NO-APD',
                        'desc' => 'Bekerja tanpa menggunakan alat pelindung diri yang diwajibkan.',
                        'status' => 'approved'
                    ],
                    [
                        'name' => 'Mengoperasikan Peralatan Tanpa Izin',
                        'abbr' => 'NO-AUTH',
                        'desc' => 'Mengoperasikan mesin atau kendaraan tanpa memiliki lisensi/izin.',
                        'status' => 'approved'
                    ],
                    [
                        'name' => 'Posisi/Sikap Kerja Tidak Aman',
                        'abbr' => 'BAD-POS',
                        'desc' => 'Melakukan pekerjaan dengan posisi tubuh yang berisiko cedera.',
                        'status' => 'approved'
                    ],
                    [
                        'name' => 'Bekerja di Bawah Pengaruh Alkohol/Obat',
                        'abbr' => 'INTOX',
                        'desc' => 'Bekerja dalam kondisi tidak sadar penuh atau mabuk.',
                        'status' => 'approved'
                    ],
                    [
                        'name' => 'Mengabaikan Prosedur Keselamatan',
                        'abbr' => 'BYPASS',
                        'desc' => 'Sengaja tidak mengikuti SOP atau JSA yang berlaku.',
                        'status' => 'approved'
                    ],
                    [
                        'name' => 'Bermain HP Saat Bekerja',
                        'abbr' => 'HP-USE',
                        'desc' => 'Distraksi penggunaan ponsel di area kerja aktif.',
                        'status' => 'pending',
                        'proposed_by' => $userId
                    ],
                ]
            ],
            [
                'name' => 'KTA (Kondisi Tidak Aman)',
                'code' => 'KTA',
                'subcategories' => [
                     [
                         'name' => 'Kondisi Lantai/Jalan Berbahaya',
                         'abbr' => 'SLIP',
                         'desc' => 'Lantai licin, berlubang, atau tidak rata.',
                         'status' => 'approved'
                     ],
                     [
                         'name' => 'Peralatan Rusak/Tidak Layak Pakai',
                         'abbr' => 'DAMAGED',
                         'desc' => 'Alat kerja yang sudah aus atau tidak berfungsi normal.',
                         'status' => 'approved'
                     ],
                     [
                         'name' => 'Pencemaran/Tumpahan B3',
                         'abbr' => 'SPILL',
                         'desc' => 'Tumpahan oli, bahan kimia, atau limbah B3 ke lingkungan.',
                         'status' => 'approved'
                     ],
                     [
                         'name' => 'Pencahayaan Tidak Memadai',
                         'abbr' => 'DARK',
                         'desc' => 'Area kerja terlalu gelap atau silau berlebihan.',
                         'status' => 'approved'
                     ],
                    [
                        'name' => 'Kabel Terkelupas',
                        'abbr' => 'CABLE',
                        'desc' => 'Bahaya tersengat listrik akibat isolasi kabel rusak.',
                        'status' => 'pending',
                        'proposed_by' => $userId
                    ],
                    [
                        'name' => 'Ufo Mendarat di Pit',
                        'abbr' => 'UFO',
                        'desc' => 'Ada piring terbang menghalangi jalan angkut.',
                        'status' => 'rejected',
                        'proposed_by' => $userId
                    ],
                ]
            ],
        ];

        foreach ($categories as $catData) {
            $category = HazardCategory::updateOrCreate(
                ['code' => $catData['code']],
                ['name' => $catData['name']]
            );

            foreach ($catData['subcategories'] as $sub) {
                HazardSubcategory::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $sub['name']
                    ],
                    [
                        'abbreviation' => $sub['abbr'] ?? null,
                        'description' => $sub['desc'] ?? null,
                        'status' => $sub['status'] ?? 'approved',
                        'proposed_by' => $sub['proposed_by'] ?? null,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
