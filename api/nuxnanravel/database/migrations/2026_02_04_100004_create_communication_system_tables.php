<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 4: Communication System - ระบบการสื่อสาร
     */
    public function up(): void
    {
        // =====================================================
        // School Announcements - ประกาศโรงเรียน
        // =====================================================
        Schema::create('school_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->string('announcement_type')->default('general'); // general, academic, financial, event, emergency
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->json('target_audience')->nullable(); // { roles: [], grades: [], departments: [] }
            $table->json('attachments')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['academy_id', 'is_published']);
            $table->index('announcement_type');
            $table->index('priority');
        });

        // Announcement Read Tracking
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('school_announcements')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at');

            $table->unique(['announcement_id', 'user_id']);
        });

        // =====================================================
        // School Events - กิจกรรมโรงเรียน
        // =====================================================
        Schema::create('school_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('event_type')->default('activity'); // meeting, holiday, exam, activity, sports, ceremony, other
            $table->string('category')->nullable();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->string('location')->nullable();
            $table->string('location_type')->default('onsite'); // onsite, online, hybrid
            $table->string('meeting_url')->nullable();
            $table->integer('max_participants')->nullable();
            $table->boolean('requires_registration')->default(false);
            $table->timestamp('registration_deadline')->nullable();
            $table->decimal('registration_fee', 10, 2)->default(0);
            $table->json('target_audience')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('attachments')->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->json('recurrence_pattern')->nullable();
            $table->string('status')->default('draft'); // draft, published, cancelled, completed
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['academy_id', 'start_datetime']);
            $table->index('status');
            $table->index('event_type');
        });

        // Event Registrations
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('school_events')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, confirmed, cancelled, attended, no_show
            $table->integer('guests')->default(0);
            $table->json('guest_names')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('payment_required')->default(false);
            $table->boolean('payment_completed')->default(false);
            $table->foreignId('payment_id')->nullable()->constrained('payments');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index('status');
        });

        // =====================================================
        // Emergency Alerts - การแจ้งเตือนฉุกเฉิน
        // =====================================================
        Schema::create('emergency_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->string('alert_type'); // fire, earthquake, flood, lockdown, medical, weather, other
            $table->string('severity')->default('high'); // low, medium, high, critical
            $table->json('target_audience')->nullable();
            $table->boolean('send_sms')->default(false);
            $table->boolean('send_email')->default(true);
            $table->boolean('send_push')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->json('delivery_stats')->nullable(); // { sms_sent, email_sent, push_sent }
            $table->timestamps();

            $table->index(['academy_id', 'is_active']);
            $table->index('alert_type');
            $table->index('severity');
        });

        // Alert Acknowledgements
        Schema::create('alert_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained('emergency_alerts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('channel')->default('app'); // app, sms, email
            $table->timestamp('acknowledged_at');
            $table->string('response')->nullable(); // safe, need_help, etc.

            $table->unique(['alert_id', 'user_id']);
        });

        // =====================================================
        // Message Threads - กลุ่มสนทนา (Parent-Teacher)
        // =====================================================
        Schema::create('message_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('subject')->nullable();
            $table->string('thread_type')->default('direct'); // direct, group, parent_teacher, class
            $table->foreignId('related_student_id')->nullable()->constrained('users');
            $table->foreignId('class_id')->nullable()->constrained('academy_groups');
            $table->boolean('is_archived')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['academy_id', 'is_archived']);
            $table->index('thread_type');
        });

        // Thread Participants
        Schema::create('thread_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('message_threads')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role')->default('member'); // admin, moderator, member
            $table->boolean('is_muted')->default(false);
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();

            $table->unique(['thread_id', 'user_id']);
        });

        // Thread Messages
        Schema::create('thread_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('message_threads')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->string('message_type')->default('text'); // text, file, image, system
            $table->json('attachments')->nullable();
            $table->foreignId('reply_to_id')->nullable()->constrained('thread_messages');
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['thread_id', 'created_at']);
        });

        // Message Read Receipts
        Schema::create('message_read_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('thread_messages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at');

            $table->unique(['message_id', 'user_id']);
        });

        // =====================================================
        // Notification Templates - เทมเพลตการแจ้งเตือน
        // =====================================================
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category'); // academic, financial, attendance, event, system
            $table->string('channel'); // email, sms, push, in_app
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('variables')->nullable(); // available placeholders
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('academy_id');
            $table->index('category');
            $table->index('channel');
        });

        // =====================================================
        // Parent-Teacher Meeting Slots
        // =====================================================
        Schema::create('meeting_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->date('meeting_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('slot_duration')->default(15); // minutes per slot
            $table->string('location')->nullable();
            $table->string('meeting_type')->default('in_person'); // in_person, video_call
            $table->string('meeting_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index(['academy_id', 'teacher_id', 'meeting_date']);
        });

        // Meeting Bookings
        Schema::create('meeting_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained('meeting_slots')->onDelete('cascade');
            $table->foreignId('parent_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->time('booked_time');
            $table->text('purpose')->nullable();
            $table->string('status')->default('pending'); // pending, confirmed, cancelled, completed
            $table->text('teacher_notes')->nullable();
            $table->text('parent_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->timestamps();

            $table->index(['slot_id', 'status']);
            $table->index(['parent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_bookings');
        Schema::dropIfExists('meeting_slots');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('message_read_receipts');
        Schema::dropIfExists('thread_messages');
        Schema::dropIfExists('thread_participants');
        Schema::dropIfExists('message_threads');
        Schema::dropIfExists('alert_acknowledgements');
        Schema::dropIfExists('emergency_alerts');
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('school_events');
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('school_announcements');
    }
};
