<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 5: Reports & Analytics System
     * ระบบรายงานและวิเคราะห์ข้อมูล
     */
    public function up(): void
    {
        // =====================================================
        // Report Definitions - การกำหนดรายงาน
        // =====================================================
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category'); // enrollment, attendance, academic, financial, staff, demographic
            $table->string('report_type')->default('table'); // table, chart, summary, detailed
            $table->json('data_source'); // { tables: [], joins: [], filters: [] }
            $table->json('columns')->nullable(); // { field, label, type, aggregate, format }
            $table->json('filters')->nullable(); // user-configurable filters
            $table->json('grouping')->nullable(); // group by fields
            $table->json('sorting')->nullable(); // order by fields
            $table->json('chart_config')->nullable(); // chart type and options
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('academy_id');
            $table->index('category');
        });

        // =====================================================
        // Saved Reports - รายงานที่บันทึกไว้
        // =====================================================
        Schema::create('saved_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('definition_id')->nullable()->constrained('report_definitions')->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('parameters')->nullable(); // applied filters/parameters
            $table->json('cached_data')->nullable(); // last generated data
            $table->timestamp('generated_at')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_shared')->default(false);
            $table->timestamps();

            $table->index(['academy_id', 'user_id']);
        });

        // =====================================================
        // Report Schedules - ตารางการสร้างรายงานอัตโนมัติ
        // =====================================================
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('report_id')->constrained('saved_reports')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('frequency'); // daily, weekly, monthly, quarterly, yearly
            $table->string('day_of_week')->nullable(); // 1-7 for weekly
            $table->integer('day_of_month')->nullable(); // 1-31 for monthly
            $table->time('scheduled_time')->default('08:00');
            $table->string('export_format')->default('pdf'); // pdf, excel, csv
            $table->json('recipients')->nullable(); // email addresses
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();

            $table->index('academy_id');
            $table->index('next_run_at');
        });

        // =====================================================
        // Report Exports - การส่งออกรายงาน
        // =====================================================
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('report_id')->nullable()->constrained('saved_reports')->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type'); // pdf, xlsx, csv
            $table->integer('file_size')->default(0);
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['academy_id', 'user_id']);
            $table->index('status');
        });

        // =====================================================
        // Dashboard Widgets - วิดเจ็ตแดชบอร์ด
        // =====================================================
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('code');
            $table->string('name');
            $table->string('widget_type'); // counter, chart, table, progress, list
            $table->string('category'); // overview, academic, financial, attendance, staff
            $table->json('data_source'); // query configuration
            $table->json('display_config'); // size, color, icon, format
            $table->integer('refresh_interval')->default(300); // seconds
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['academy_id', 'code']);
            $table->index('category');
        });

        // =====================================================
        // User Dashboard Layouts - เลย์เอาต์แดชบอร์ดผู้ใช้
        // =====================================================
        Schema::create('user_dashboard_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('dashboard_type')->default('main'); // main, academic, financial, staff
            $table->json('widget_layout'); // { widgets: [{id, position, size}] }
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'academy_id', 'dashboard_type']);
        });

        // =====================================================
        // Analytics Snapshots - สแนปชอตข้อมูลวิเคราะห์
        // =====================================================
        Schema::create('analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('snapshot_type'); // daily, weekly, monthly, academic_year
            $table->string('category'); // enrollment, attendance, academic, financial
            $table->date('snapshot_date');
            $table->string('period_start')->nullable();
            $table->string('period_end')->nullable();
            $table->json('metrics'); // { metric_name: value, ... }
            $table->json('breakdown')->nullable(); // by grade, department, etc.
            $table->timestamps();

            $table->index(['academy_id', 'snapshot_date']);
            $table->index('snapshot_type');
            $table->index('category');
        });

        // =====================================================
        // KPI Definitions - การกำหนด KPI
        // =====================================================
        Schema::create('kpi_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category'); // academic, financial, operational, staff
            $table->string('metric_type'); // percentage, count, currency, ratio
            $table->json('calculation'); // formula or query
            $table->decimal('target_value', 15, 4)->nullable();
            $table->decimal('warning_threshold', 15, 4)->nullable();
            $table->decimal('critical_threshold', 15, 4)->nullable();
            $table->string('comparison_direction')->default('higher_is_better'); // higher_is_better, lower_is_better
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['academy_id', 'code']);
            $table->index('category');
        });

        // =====================================================
        // KPI Values - ค่า KPI ที่บันทึก
        // =====================================================
        Schema::create('kpi_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained('kpi_definitions')->onDelete('cascade');
            $table->date('period_date');
            $table->string('period_type'); // daily, weekly, monthly, quarterly, yearly
            $table->decimal('value', 15, 4);
            $table->decimal('previous_value', 15, 4)->nullable();
            $table->decimal('change_percent', 8, 2)->nullable();
            $table->string('status')->nullable(); // on_track, warning, critical
            $table->json('breakdown')->nullable();
            $table->timestamps();

            $table->unique(['kpi_id', 'period_date', 'period_type']);
            $table->index(['kpi_id', 'period_date']);
        });

        // =====================================================
        // Comparative Analytics - การวิเคราะห์เปรียบเทียบ
        // =====================================================
        Schema::create('comparative_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('comparison_type'); // year_over_year, period_comparison, cohort
            $table->string('category'); // enrollment, academic, financial, attendance
            $table->json('period_1'); // { start, end, label }
            $table->json('period_2'); // { start, end, label }
            $table->json('metrics'); // { metric: { period_1: val, period_2: val, change: % } }
            $table->json('breakdown')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['academy_id', 'category']);
        });

        // =====================================================
        // Trend Analysis - การวิเคราะห์แนวโน้ม
        // =====================================================
        Schema::create('trend_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('metric_name');
            $table->string('category');
            $table->string('time_granularity'); // daily, weekly, monthly
            $table->date('start_date');
            $table->date('end_date');
            $table->json('data_points'); // [{ date, value }, ...]
            $table->json('trend_info')->nullable(); // { direction, slope, r_squared }
            $table->json('forecast')->nullable(); // predicted values
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['academy_id', 'category']);
        });

        // =====================================================
        // Alert Rules - กฎการแจ้งเตือน
        // =====================================================
        Schema::create('analytics_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('kpi_id')->nullable()->constrained('kpi_definitions')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('condition_type'); // threshold, change_percent, anomaly
            $table->string('operator'); // gt, lt, eq, gte, lte
            $table->decimal('threshold_value', 15, 4)->nullable();
            $table->string('alert_level'); // info, warning, critical
            $table->json('notification_channels'); // [email, sms, in_app]
            $table->json('recipients')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->index('academy_id');
        });

        // =====================================================
        // Analytics Alert History - ประวัติการแจ้งเตือน
        // =====================================================
        Schema::create('analytics_alert_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_rule_id')->constrained('analytics_alert_rules')->onDelete('cascade');
            $table->decimal('trigger_value', 15, 4);
            $table->decimal('threshold_value', 15, 4);
            $table->string('alert_level');
            $table->text('message');
            $table->json('notification_sent')->nullable();
            $table->boolean('is_acknowledged')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index('alert_rule_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_alert_history');
        Schema::dropIfExists('analytics_alert_rules');
        Schema::dropIfExists('trend_analyses');
        Schema::dropIfExists('comparative_analytics');
        Schema::dropIfExists('kpi_values');
        Schema::dropIfExists('kpi_definitions');
        Schema::dropIfExists('analytics_snapshots');
        Schema::dropIfExists('user_dashboard_layouts');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('saved_reports');
        Schema::dropIfExists('report_definitions');
    }
};
