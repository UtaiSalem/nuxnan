<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('course_members_user_id_foreign');
            $table->string('member_name')->nullable();
            $table->unsignedBigInteger('course_id')->index('course_members_course_id_foreign');
            $table->tinyInteger('course_member_status')->nullable()->default(1);
            $table->unsignedBigInteger('group_id')->nullable()->index('course_members_group_id_foreign');
            $table->tinyInteger('group_member_status')->nullable()->default(1);
            $table->timestamp('enrollment_date')->useCurrent();
            $table->tinyInteger('status')->nullable()->default(0)->comment('\'0\',\'1\',\'2\',\'3\',\'4\',\'5\'');
            $table->tinyInteger('role')->nullable()->default(1)->comment('1=>\'student\',2=>\'student_leader\',3=>\'teacher\',4=>\'admin\'');
            $table->integer('order_number')->nullable()->default(0);
            $table->integer('member_code')->nullable();
            $table->integer('achieved_score')->nullable()->default(0);
            $table->integer('bonus_points')->nullable()->default(0);
            $table->integer('efficiency')->nullable()->default(0);
            $table->integer('grade_progress')->nullable()->default(0);
            $table->tinyInteger('member_status')->nullable()->comment('\'0\',\'1\',\'2\',\'3\',\'4\',\'5\'');
            $table->timestamp('completion_date')->nullable();
            $table->timestamp('access_expiry_date')->nullable();
            $table->text('notes_comments')->nullable();
            $table->tinyInteger('last_accessed_tab')->nullable()->default(0);
            $table->tinyInteger('last_accessed_group_tab')->nullable()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_members');
    }
};
