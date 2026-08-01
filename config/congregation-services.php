<?php

return [
    'religion_options' => ['Katolik', 'Kristen', 'Islam', 'Hindu', 'Buddha', 'Khonghucu'],
    'gender_options' => ['L', 'P'],
    'marital_status_options' => ['Kawin', 'Belum Kawin', 'Cerai Hidup', 'Cerai Mati'],
    'equipment_options' => ['TOA', 'LCD', 'NEC', 'ACER'],

    'types' => [
        'baptis_balita' => [
            'label' => 'Baptis Balita',
            'description' => 'Pendaftaran baptis untuk anak usia 0-7 tahun',
            'required_fields' => [
                'applicant_name', 'applicant_gender', 'baptismal_name', 'birth_place',
                'birth_date', 'father_name', 'father_religion', 'mother_name',
                'mother_religion', 'address', 'contact', 'neighborhood',
            ],
            'dynamic_fields' => [
                'godparent_name', 'parent_marriage_church_date', 'parent_marriage_church',
                'parent_civil_registry_date', 'priest_name', 'baptism_date',
            ],
        ],
        'baptis_dewasa' => [
            'label' => 'Baptis Dewasa',
            'description' => 'Pendaftaran baptis untuk usia 8 tahun ke atas',
            'required_fields' => [
                'applicant_name', 'applicant_gender', 'baptismal_name', 'birth_place',
                'birth_date', 'father_name', 'father_religion', 'mother_name',
                'mother_religion', 'address', 'contact', 'neighborhood',
            ],
            'dynamic_fields' => [
                'godparent_name', 'catechumen_duration', 'mentor_name', 'guidance_place',
                'previous_baptism_date', 'previous_baptism_church', 'previous_baptism_pastor',
                'marriage_partner_name', 'marriage_date', 'marriage_status',
            ],
        ],
        'komuni_pertama' => [
            'label' => 'Komuni Pertama',
            'description' => 'Pendaftaran penerimaan komuni pertama',
            'required_fields' => [
                'applicant_name', 'birth_place', 'birth_date', 'address', 'contact',
                'neighborhood', 'father_name', 'mother_name', 'school',
            ],
            'dynamic_fields' => [
                'baptism_date', 'baptism_church', 'baptism_book_volume', 'baptism_book_page',
                'baptism_book_number', 'baptism_parish_name', 'baptism_parish_address',
                'communion_date', 'communion_parish', 'readiness_status',
            ],
        ],
        'krisma' => [
            'label' => 'Krisma',
            'description' => 'Pendaftaran penerimaan sakramen krisma',
            'required_fields' => [
                'applicant_name', 'applicant_gender', 'birth_place', 'birth_date',
                'father_name', 'father_religion', 'mother_name', 'mother_religion',
                'address', 'contact', 'neighborhood',
            ],
            'dynamic_fields' => [
                'baptism_date', 'baptism_place', 'baptism_church', 'baptism_book_volume',
                'baptism_book_page', 'baptism_book_number', 'communion_date', 'communion_place',
                'confirmation_date', 'confirmation_priest', 'confirmation_name',
                'confirmation_book_volume', 'confirmation_book_page', 'confirmation_book_number',
            ],
        ],
        'putra_putri_altar' => [
            'label' => 'Putra Putri Altar',
            'description' => 'Pendaftaran menjadi putra/putri altar (misdinar)',
            'required_fields' => [
                'applicant_name', 'birth_place', 'birth_date', 'father_name', 'mother_name',
                'school', 'grade', 'address', 'contact', 'neighborhood',
            ],
            'dynamic_fields' => [
                'baptism_date', 'baptism_place', 'communion_date', 'communion_place',
            ],
        ],
        'surat_keterangan_perkawinan' => [
            'label' => 'Surat Keterangan Lingkungan untuk Perkawinan',
            'description' => 'Permohonan surat keterangan untuk pemberkatan nikah',
            'required_fields' => ['address', 'contact'],
            'dynamic_fields' => [
                'couples', 'marital_status',
            ],
        ],
        'surat_keterangan_warga' => [
            'label' => 'Surat Keterangan Warga',
            'description' => 'Permohonan surat keterangan warga paroki',
            'required_fields' => [
                'applicant_name', 'birth_place', 'birth_date', 'address',
                'contact', 'occupation',
            ],
            'dynamic_fields' => ['purpose'],
        ],
        'surat_rekomendasi' => [
            'label' => 'Surat Rekomendasi',
            'description' => 'Permohonan surat rekomendasi',
            'required_fields' => [
                'applicant_name', 'birth_place', 'birth_date', 'address',
                'contact',
            ],
            'dynamic_fields' => ['parent_name', 'recommendation_purpose', 'consideration'],
        ],
        'laporan_kematian' => [
            'label' => 'Laporan Kematian',
            'description' => 'Laporan kematian untuk pelayanan pemakaman',
            'required_fields' => [
                'applicant_name', 'address', 'contact',
            ],
            'dynamic_fields' => [
                'death_day', 'death_date', 'death_time', 'death_place',
                'death_cause', 'burial_date', 'burial_place',
            ],
        ],
        'laporan_kematian_yusup' => [
            'label' => 'Laporan Kematian (Pemakaman Santo Yusup)',
            'description' => 'Laporan kematian dengan detail pemakaman Santo Yusup',
            'required_fields' => [
                'applicant_name', 'birth_place', 'birth_date', 'address',
                'contact', 'neighborhood',
            ],
            'dynamic_fields' => [
                'baptismal_name', 'death_day', 'death_date', 'death_time', 'death_place',
                'coffin_size', 'burial_schedule', 'additional_notes',
            ],
        ],
        'sakramen_minyak_suci' => [
            'label' => 'Sakramen Minyak Suci',
            'description' => 'Pendaftaran penerimaan sakramen minyak suci',
            'required_fields' => [
                'applicant_name', 'address', 'contact', 'birth_place', 'birth_date',
            ],
            'dynamic_fields' => [
                'baptismal_name', 'baptism_date', 'baptism_place', 'baptism_church',
                'reception_date', 'reception_time', 'reception_place', 'priest_name',
            ],
        ],
        'peminjaman_ruangan' => [
            'label' => 'Peminjaman Ruangan',
            'description' => 'Permohonan peminjaman ruangan gereja',
            'required_fields' => ['applicant_name', 'contact'],
            'dynamic_fields' => [
                'neighborhood_group', 'room_name', 'date', 'start_time', 'end_time', 'purpose',
            ],
        ],
        'peminjaman_alat' => [
            'label' => 'Peminjaman Alat Elektronik',
            'description' => 'Permohonan peminjaman alat elektronik gereja',
            'required_fields' => ['applicant_name', 'contact'],
            'dynamic_fields' => [
                'neighborhood_group', 'equipment_type', 'date', 'start_time', 'end_time', 'purpose',
            ],
        ],
        'intensi_misa' => [
            'label' => 'Intensi Misa',
            'description' => 'Permohonan intensi misa: ucapan syukur, doa arwah, atau permohonan lainnya',
            'required_fields' => ['applicant_name', 'neighborhood', 'contact'],
            'dynamic_fields' => [
                'jadwal_misa', 'tanggal_misa', 'ucapan_syukur', 'doa_arwah',
                'permohonan_lainnya', 'stipendium_amount', 'stipendium_terbilang',
            ],
        ],

        'permohonan_misa' => [
            'label' => 'Permohonan Misa',
            'description' => 'Permohonan agar misa diadakan di luar jadwal tetap (misa lingkungan, rumah, arwah, syukur)',
            'required_fields' => ['applicant_name', 'contact', 'neighborhood'],
            'dynamic_fields' => [
                'jenis_misa', 'tanggal_misa_diminta', 'jam_misa_diminta',
                'tempat_misa', 'alamat_misa', 'perkiraan_umat', 'kebutuhan_petugas',
            ],
        ],

        'konsultasi_romo' => [
            'label' => 'Konsultasi Romo',
            'description' => 'Permohonan jadwal konsultasi/pendampingan dengan romo',
            'required_fields' => ['applicant_name', 'contact', 'neighborhood'],
            'dynamic_fields' => [
                'topik_konsultasi', 'tanggal_diminta', 'waktu_preferensi', 'sifat',
            ],
        ],
    ],

    'statuses' => ['pending', 'approved', 'rejected', 'cancelled', 'completed'],

    'allowed_status_transitions' => [
        'pending' => ['approved', 'rejected', 'cancelled'],
        'approved' => ['completed'],
        'rejected' => [],
        'cancelled' => [],
        'completed' => [],
    ],
];
