<?php

namespace Tests\Feature\Academy;

use App\Models\Academy;
use App\Models\ReportExport;
use App\Models\Role;
use App\Models\SavedReport;
use App\Models\User;
use App\Services\ReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    private function makeReport(?User $owner = null): SavedReport
    {
        $academy = Academy::factory()->create();

        return SavedReport::create([
            'academy_id' => $academy->id,
            'definition_id' => null,
            'user_id' => ($owner ?? User::factory()->create())->id,
            'name' => 'รายงานทดสอบ',
            'parameters' => [],
            'cached_data' => [
                ['student_name' => 'สมชาย', 'score' => 80],
                ['student_name' => 'สมหญิง', 'score' => 95],
            ],
            'generated_at' => now(),
        ]);
    }

    public function test_service_generates_file_for_each_format(): void
    {
        Storage::fake('public');
        $service = app(ReportExportService::class);

        foreach (['xlsx', 'csv', 'pdf'] as $format) {
            $report = $this->makeReport();
            $meta = $service->generate($report, $format);

            $this->assertSame($format, $meta['file_type']);
            $this->assertStringEndsWith('.'.$format, $meta['file_name']);
            $this->assertGreaterThan(0, $meta['file_size'], "ไฟล์ $format ต้องมีขนาด > 0");
            Storage::disk('public')->assertExists($meta['file_path']);
        }
    }

    public function test_export_endpoint_creates_completed_export_row(): void
    {
        Storage::fake('public');

        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $actor = User::factory()->create();
        $actor->assignRole('SUPER_ADMIN'); // bypass academy.permission (CheckAcademyPermission line 32)

        $report = $this->makeReport($actor);

        $response = $this->actingAs($actor, 'api')->postJson(
            "/api/academies/{$report->academy_id}/reports/saved/{$report->id}/export",
            ['format' => 'xlsx']
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertNotNull($response->json('data.download_url'), 'download_url ต้องไม่ null หลัง export เสร็จ');

        $this->assertDatabaseHas('report_exports', [
            'report_id' => $report->id,
            'user_id' => $actor->id,
            'academy_id' => $report->academy_id,
            'file_type' => 'xlsx',
            'status' => ReportExport::STATUS_COMPLETED,
        ]);
    }

    public function test_export_endpoint_rejects_invalid_format(): void
    {
        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        $actor = User::factory()->create();
        $actor->assignRole('SUPER_ADMIN');

        $report = $this->makeReport($actor);

        $this->actingAs($actor, 'api')->postJson(
            "/api/academies/{$report->academy_id}/reports/saved/{$report->id}/export",
            ['format' => 'docx']
        )->assertStatus(422);
    }
}
