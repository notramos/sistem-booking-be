<?php

return [
    'min_duration_minutes' => 30,
    'max_duration_hours' => 8,
    'min_advance_days' => 7,
    'reminder_hours_before' => 24,
    'operating_hours' => ['open' => '06:00', 'close' => '22:00'],
    'allowed_status_transitions' => [
        'pending' => ['approved', 'rejected', 'cancelled'],
        'approved' => ['cancelled', 'completed'],
        'rejected' => [],
        'cancelled' => [],
        'completed' => [],
    ],
    'purpose_types' => [
        'ibadah' => 'Ibadah & Persekutuan',
        'acara_keluarga' => 'Acara Keluarga',
        'latihan_musik' => 'Latihan Musik',
        'latihan_koor' => 'Latihan Koor',
        'latihan_lektor_pemazmur' => 'Latihan Lektor & Pemazmur',
        'pembinaan' => 'Pembinaan',
        'rapat' => 'Rapat Pelayanan',
        'seminar' => 'Seminar & Training',
        'publik' => 'Acara Publik',
        'acara_lainnya' => 'Acara Lainnya',
    ],
    'rooms' => [
        'max_images_per_room' => 10,
        'allowed_image_mimes' => 'jpeg,png,jpg,webp',
        'max_image_size_kb' => 2048,
    ],
];
