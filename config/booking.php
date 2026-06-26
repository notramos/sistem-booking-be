<?php

return [
    'min_duration_minutes' => 30,
    'max_duration_hours' => 8,
    'max_advance_days' => 30,
    'reminder_hours_before' => 24,
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
        'pembinaan' => 'Pembinaan',
        'rapat' => 'Rapat Pelayanan',
        'seminar' => 'Seminar & Training',
        'publik' => 'Acara Publik',
    ],
    'rooms' => [
        'max_images_per_room' => 10,
        'allowed_image_mimes' => 'jpeg,png,jpg,webp',
        'max_image_size_kb' => 2048,
    ],
];
