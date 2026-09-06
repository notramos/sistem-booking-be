<?php

namespace Tests\Feature;

use App\Exports\IntensiMisaExport;
use App\Http\Requests\Api\IntensiMisaReportRequest;
use App\Http\Requests\Api\StoreManualCongregationServiceRequest;
use App\Models\CongregationService;
use App\Services\IntensiMisaReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class IntensiMisaReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Explicitly isolated: never migrate, seed, or query a configured remote database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('congregation_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('service_type');
            $table->string('applicant_name');
            $table->string('region')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('status');
            $table->json('dynamic_fields')->nullable();
            $table->timestamps();
        });
        $migration = require database_path('migrations/2026_09_06_120000_add_received_at_to_congregation_services.php');
        $migration->up();
    }

    private function entry(string $date = '2026-09-06', string $status = 'approved', string $type = 'intensi_misa'): CongregationService
    {
        return CongregationService::create([
            'service_type' => $type, 'applicant_name' => '=1+1', 'status' => $status,
            'received_at' => '2026-09-05 10:00:00',
            'dynamic_fields' => ['tanggal_misa' => $date, 'jadwal_misa' => '08:30', 'ucapan_syukur' => "Syukur\nBaris kedua", 'stipendium_amount' => '50000'],
        ]);
    }

    public function test_filters_and_excel_include_every_matching_row_and_literal_text(): void
    {
        for ($i = 0; $i < 27; $i++) {
            $this->entry();
        }
        $this->entry('2026-09-07');
        $this->entry('2026-09-06', 'pending');
        $this->entry('2026-09-06', 'approved', 'permohonan_misa');
        $filters = ['start_date' => '2026-09-06', 'end_date' => '2026-09-06', 'status' => 'approved', 'schedule' => '08:30'];
        $report = app(IntensiMisaReportService::class);
        $this->assertSame(27, $report->query($filters)->count());
        $this->assertCount(25, $report->query($filters)->paginate(25)->items());
        $this->assertSame(0, $report->query([...$filters, 'schedule' => '17:30'])->count());
        $this->assertSame(28, $report->query([...$filters, 'status' => ''])->count());

        $file = tempnam(sys_get_temp_dir(), 'intensi-test-');
        try {
            file_put_contents($file, Excel::raw(new IntensiMisaExport($filters), \Maatwebsite\Excel\Excel::XLSX));
            $sheet = IOFactory::load($file)->getActiveSheet();
            $this->assertSame(28, $sheet->getHighestRow()); // 27 entries + heading
            $this->assertSame('=1+1', $sheet->getCell('E2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('E2')->getDataType());
            $this->assertSame("Syukur\nBaris kedua", $sheet->getCell('H2')->getValue());
            $this->assertEquals(50000, $sheet->getCell('K2')->getValue());
            $this->assertTrue($sheet->getStyle('H2')->getAlignment()->getWrapText());
            $this->assertSame('A2', $sheet->getFreezePane());
        } finally {
            unlink($file);
        }
    }

    public function test_time_conversion_and_unknown_receipt_time_are_preserved(): void
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        try {
            $service = new CongregationService(['received_at' => '2026-09-05 03:00:00']);
            $service->created_at = '2026-09-05 05:00:00';
            $row = app(IntensiMisaReportService::class)->row($service);
            $this->assertSame('2026-09-05T10:00:00+07:00', $row['received_at']);
            $this->assertSame('2026-09-05T12:00:00+07:00', $row['submitted_at']);
            $service->received_at = null;
            $this->assertNull(app(IntensiMisaReportService::class)->row($service)['received_at']);
        } finally {
            date_default_timezone_set($originalTimezone);
        }
    }

    public function test_invalid_filters_and_future_receipt_time_are_rejected(): void
    {
        $rules = (new IntensiMisaReportRequest)->rules();
        $this->assertTrue(Validator::make(['start_date' => '2026-09-07', 'end_date' => '2026-09-06'], $rules)->fails());
        $this->assertTrue(Validator::make(['start_date' => '2026-09-06', 'end_date' => '2026-09-06', 'schedule' => '25:00'], $rules)->fails());
        $this->assertFalse(Validator::make(['start_date' => '2020-01-01', 'end_date' => '2020-01-31'], $rules)->fails());
        $receipt = (new StoreManualCongregationServiceRequest)->rules()['received_at'];
        $this->assertTrue(Validator::make(['received_at' => now()->addDay()->toIso8601String()], ['received_at' => $receipt])->fails());
    }

    public function test_report_routes_keep_separate_view_and_export_permissions(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())->keyBy(fn ($route) => $route->uri());
        $this->assertContains('can:reports.view', $routes['api/reports/intensi-misa']->gatherMiddleware());
        $this->assertContains('can:reports.export', $routes['api/reports/intensi-misa/excel']->gatherMiddleware());
    }
}
