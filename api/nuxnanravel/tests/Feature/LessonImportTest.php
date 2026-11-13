<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LessonImportTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function setUpCourse(): array
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);

        return [$user, $course];
    }

    private function preview(User $user, Course $course, string $html, ?string $filename = null)
    {
        return $this->actingAs($user, 'api')->postJson("/api/courses/{$course->id}/lessons/import/preview", array_filter([
            'html' => $html,
            'filename' => $filename,
        ], fn ($v) => $v !== null));
    }

    private function commit(User $user, Course $course, array $payload)
    {
        return $this->actingAs($user, 'api')->postJson("/api/courses/{$course->id}/lessons/import", $payload);
    }

    private function pngImg(string $alt = 'รูป'): string
    {
        return '<img src="data:image/png;base64,'.self::PNG_1X1.'" alt="'.$alt.'">';
    }

    private function tmpFiles(): array
    {
        return Storage::disk('public')->allFiles('lesson-imports/tmp');
    }

    // 1
    public function test_preview_splits_h1_and_h2_into_lesson_and_topics(): void
    {
        [$user, $course] = $this->setUpCourse();

        $html = '<h1>บทเรียนเรื่องพืช</h1><p>พืชเป็นสิ่งมีชีวิต</p>'
            .'<h2>ราก</h2><p>รากดูดน้ำและแร่ธาตุ</p>'
            .'<h2>ใบ</h2><p>ใบสังเคราะห์แสง</p><ul><li>คลอโรฟิลล์</li></ul>';

        $response = $this->preview($user, $course, $html);

        $response->assertOk()->assertJson(['success' => true]);
        $data = $response->json();

        $this->assertTrue(Str::isUuid($data['import_id']));
        $this->assertSame('บทเรียนเรื่องพืช', $data['lesson']['title']);
        $this->assertSame('พืชเป็นสิ่งมีชีวิต', $data['lesson']['description']);
        $this->assertSame('<p>พืชเป็นสิ่งมีชีวิต</p>', $data['lesson']['content']);
        $this->assertNull($data['lesson']['youtube_url']);
        $this->assertSame(1, $data['lesson']['min_read']);
        $this->assertCount(2, $data['topics']);
        $this->assertSame('ราก', $data['topics'][0]['title']);
        $this->assertSame('<p>รากดูดน้ำและแร่ธาตุ</p>', $data['topics'][0]['content']);
        $this->assertSame('ใบ', $data['topics'][1]['title']);
        $this->assertSame('<p>ใบสังเคราะห์แสง</p><ul><li>คลอโรฟิลล์</li></ul>', $data['topics'][1]['content']);
        $this->assertSame(1, $data['topics'][1]['min_read']);
        $this->assertSame(0, $data['images']);
        $this->assertSame([], $data['warnings']);
    }

    public function test_preview_without_headings_uses_filename_and_warns(): void
    {
        [$user, $course] = $this->setUpCourse();

        $response = $this->preview($user, $course, '<p>เนื้อหาอย่างเดียว</p>', 'บทที่ 2 ดิน.docx');

        $response->assertOk();
        $this->assertSame('บทที่ 2 ดิน', $response->json('lesson.title'));
        $this->assertSame([], $response->json('topics'));
        $this->assertContains('ไม่พบหัวข้อระดับ 1 จึงใช้ชื่อไฟล์เป็นชื่อบทเรียน', $response->json('warnings'));
        $this->assertContains('ไม่พบหัวข้อระดับ 2 เนื้อหาทั้งหมดจะอยู่ในบทเรียน', $response->json('warnings'));
    }

    public function test_preview_with_no_content_returns_422(): void
    {
        [$user, $course] = $this->setUpCourse();

        $this->preview($user, $course, '<h1>มีแต่ชื่อ</h1>')
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'ไม่พบเนื้อหาในไฟล์']);
    }

    // 2
    public function test_preview_extracts_youtube_url_from_link_only_paragraph(): void
    {
        [$user, $course] = $this->setUpCourse();

        $html = '<h1>วิดีโอ</h1>'
            .'<p>https://www.youtube.com/watch?v=dQw4w9WgXcQ</p><p>บทนำ</p>'
            .'<h2>ตอนที่ 1</h2>'
            .'<p><a href="https://youtu.be/abcdefg">https://youtu.be/abcdefg</a></p>'
            .'<p>เนื้อหา</p><p>https://youtu.be/second1</p>';

        $response = $this->preview($user, $course, $html);

        $response->assertOk();
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $response->json('lesson.youtube_url'));
        $this->assertSame('<p>บทนำ</p>', $response->json('lesson.content'));
        $this->assertSame('บทนำ', $response->json('lesson.description'));

        $topic = $response->json('topics.0');
        $this->assertSame('https://youtu.be/abcdefg', $topic['youtube_url']);
        $this->assertStringNotContainsString('abcdefg', $topic['content']);
        $this->assertStringContainsString('https://youtu.be/second1', $topic['content']);
    }

    // 3
    public function test_preview_stores_data_uri_images_in_temp_folder(): void
    {
        [$user, $course] = $this->setUpCourse();

        $response = $this->preview($user, $course, '<h1>รูปภาพ</h1><p>ดูรูป</p><p>'.$this->pngImg().'</p>');

        $response->assertOk();
        $importId = $response->json('import_id');
        $content = $response->json('lesson.content');

        $this->assertSame(1, $response->json('images'));
        $this->assertStringContainsString('src="/storage/lesson-imports/tmp/'.$importId.'/img-1.png"', $content);
        $this->assertStringNotContainsString('data:image', $content);
        Storage::disk('public')->assertExists("lesson-imports/tmp/{$importId}/img-1.png");
    }

    // 4
    public function test_preview_removes_unsupported_image_types_with_warning(): void
    {
        [$user, $course] = $this->setUpCourse();

        $emf = '<img src="data:image/x-emf;base64,'.base64_encode('not-an-image').'">';
        $response = $this->preview($user, $course, '<h1>ไฟล์</h1><p>ข้อความ</p><p>'.$emf.'</p>');

        $response->assertOk();
        $this->assertStringNotContainsString('<img', $response->json('lesson.content'));
        $this->assertSame(0, $response->json('images'));

        $warnings = implode("\n", $response->json('warnings'));
        $this->assertStringContainsString('ลบรูปที่ 1 เพราะชนิดไฟล์ไม่รองรับ', $warnings);
        $this->assertSame([], $this->tmpFiles());
    }

    public function test_preview_removes_external_images_with_warning(): void
    {
        [$user, $course] = $this->setUpCourse();

        $response = $this->preview($user, $course, '<h1>ไฟล์</h1><p>a<img src="https://evil.example/x.png"></p>');

        $response->assertOk();
        $this->assertStringNotContainsString('<img', $response->json('lesson.content'));
        $this->assertContains('ลบรูปที่ลิงก์จากภายนอก', $response->json('warnings'));
    }

    // 5
    public function test_preview_with_more_than_15_images_returns_422_and_leaves_no_files(): void
    {
        [$user, $course] = $this->setUpCourse();

        $html = '<h1>รูปเยอะ</h1>'.str_repeat('<p>'.$this->pngImg().'</p>', 16);

        $this->preview($user, $course, $html)
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'ไฟล์มีรูป 16 รูป เกินขีดจำกัด 15 รูปต่อไฟล์',
            ]);

        $this->assertSame([], $this->tmpFiles());
        $this->assertSame([], Storage::disk('public')->directories('lesson-imports/tmp'));
    }

    // 6
    public function test_preview_with_more_than_10_topics_returns_422(): void
    {
        [$user, $course] = $this->setUpCourse();

        $html = '<h1>หัวข้อเยอะ</h1><p>'.$this->pngImg().'</p>';
        for ($i = 1; $i <= 11; $i++) {
            $html .= "<h2>หัวข้อ {$i}</h2><p>เนื้อหา {$i}</p>";
        }

        $this->preview($user, $course, $html)
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'ไฟล์มี 11 หัวข้อย่อย เกินขีดจำกัด 10 หัวข้อต่อบท',
            ]);

        $this->assertSame([], $this->tmpFiles());
    }

    // 7
    public function test_other_user_gets_403_on_preview_and_store(): void
    {
        [, $course] = $this->setUpCourse();
        $stranger = User::factory()->create();

        $this->preview($stranger, $course, '<h1>x</h1><p>y</p>')->assertForbidden();

        $this->commit($stranger, $course, [
            'import_id' => (string) Str::uuid(),
            'publication_status' => 'draft',
            'lesson' => ['title' => 'x', 'content' => '<p>y</p>'],
            'topics' => [],
        ])->assertForbidden();

        $this->assertSame(0, Lesson::count());
    }

    // 8
    public function test_preview_sanitizes_dangerous_html(): void
    {
        [$user, $course] = $this->setUpCourse();

        $html = '<h1>XSS<script>alert(0)</script></h1>'
            .'<p onclick="alert(1)">ข้อความ<script>alert(1)</script></p>'
            .'<p><img src=x onerror=alert(1)></p>'
            .'<p><a href="javascript:alert(2)">ลิงก์</a></p>'
            .'<svg onload="alert(3)"></svg>'
            .'<p style="background:url(javascript:alert(4))">สไตล์</p>';

        $response = $this->preview($user, $course, $html);

        $response->assertOk();
        $json = json_encode($response->json(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->assertStringNotContainsStringIgnoringCase('<script', $json);
        $this->assertStringNotContainsStringIgnoringCase('onerror', $json);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $json);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $json);
        $this->assertStringNotContainsStringIgnoringCase('<svg', $json);
        $this->assertStringNotContainsStringIgnoringCase('style=', $json);
        $this->assertSame('XSS', $response->json('lesson.title'));
    }

    // 9
    public function test_commit_creates_draft_lesson_with_topics_and_moves_images(): void
    {
        $this->assertCommitCreatesLesson('draft');
    }

    public function test_commit_creates_published_lesson(): void
    {
        $this->assertCommitCreatesLesson('published');
    }

    private function assertCommitCreatesLesson(string $status): void
    {
        [$user, $course] = $this->setUpCourse();
        $lessonsBefore = (int) $course->fresh()->lessons;

        $preview = $this->preview($user, $course,
            '<h1>บทเรียนรูปภาพ</h1><p>บทนำ</p><p>'.$this->pngImg().'</p>'
            .'<h2>หัวข้อหนึ่ง</h2><p>เนื้อหาหนึ่ง</p><p>'.$this->pngImg('สอง').'</p>'
            .'<h2>หัวข้อสอง</h2><p>เนื้อหาสอง</p>'
        )->assertOk();

        $importId = $preview->json('import_id');
        $lessonData = $preview->json('lesson');
        $lessonData['min_read'] = 3;
        $topics = $preview->json('topics');
        $topics[0]['min_read'] = 2;
        $topics[1]['min_read'] = 4;
        // Same image used twice in one commit must be copied only once.
        $topics[1]['content'] .= '<p><img src="/storage/lesson-imports/tmp/'.$importId.'/img-1.png" alt="ซ้ำ"></p>';

        $response = $this->commit($user, $course, [
            'import_id' => $importId,
            'publication_status' => $status,
            'lesson' => $lessonData,
            'topics' => $topics,
        ]);

        $response->assertCreated()
            ->assertJson(['success' => true, 'message' => 'นำเข้าบทเรียนสำเร็จ', 'topics_count' => 2]);

        $lesson = Lesson::findOrFail($response->json('newLesson.id'));
        $this->assertSame($status, $lesson->publication_status);
        $this->assertSame('บทเรียนรูปภาพ', $lesson->title);
        $this->assertSame($user->id, (int) $lesson->user_id);
        $this->assertSame(2, $lesson->topics()->count());
        $this->assertSame($lessonsBefore + 1, (int) $course->fresh()->lessons);
        $this->assertSame(3 + 2 + 4, (int) $lesson->min_read);

        $this->assertMatchesRegularExpression(
            '~src="/storage/images/courses/lessons/content/[0-9a-f-]{36}/img-1\.png"~',
            $lesson->content
        );
        $this->assertStringNotContainsString('lesson-imports/tmp', $lesson->content);

        $topicOne = $lesson->topics()->where('title', 'หัวข้อหนึ่ง')->firstOrFail();
        $this->assertStringContainsString('/storage/images/courses/lessons/content/', $topicOne->content);
        $this->assertSame($course->id, (int) $topicOne->course_id);

        preg_match('~src="/storage/(images/courses/lessons/content/[0-9a-f-]{36})/img-1\.png"~', $lesson->content, $m);
        $finalDir = $m[1];
        Storage::disk('public')->assertExists($finalDir.'/img-1.png');
        Storage::disk('public')->assertExists($finalDir.'/img-2.png');
        $this->assertCount(2, Storage::disk('public')->allFiles($finalDir));

        Storage::disk('public')->assertMissing("lesson-imports/tmp/{$importId}");
        $this->assertSame([], $this->tmpFiles());
    }

    // 10
    public function test_commit_drops_images_from_other_imports_or_external_urls(): void
    {
        [$user, $course] = $this->setUpCourse();

        $importA = $this->preview($user, $course, '<h1>A</h1><p>'.$this->pngImg().'</p>')->json('import_id');
        $importB = $this->preview($user, $course, '<h1>B</h1><p>'.$this->pngImg().'</p>')->json('import_id');

        $content = '<p>ข้อความ</p>'
            .'<p><img src="/storage/lesson-imports/tmp/'.$importA.'/img-1.png"></p>'
            .'<p><img src="https://evil.example/x.png"></p>'
            .'<p><img src="/storage/lesson-imports/tmp/'.$importB.'/../'.$importA.'/img-1.png"></p>'
            .'<p><img src="/storage/lesson-imports/tmp/'.$importB.'/missing.png"></p>';

        $response = $this->commit($user, $course, [
            'import_id' => $importB,
            'publication_status' => 'draft',
            'lesson' => ['title' => 'B', 'content' => $content],
            'topics' => [['title' => 'T', 'content' => '<p><img src="/storage/lesson-imports/tmp/'.$importA.'/img-1.png"></p><p>x</p>']],
        ]);

        $response->assertCreated();
        $lesson = Lesson::findOrFail($response->json('newLesson.id'));

        $this->assertStringNotContainsString('<img', $lesson->content);
        $this->assertStringContainsString('<p>ข้อความ</p>', $lesson->content);
        $this->assertStringNotContainsString('<img', $lesson->topics()->first()->content);

        // Import A's file was not touched.
        Storage::disk('public')->assertExists("lesson-imports/tmp/{$importA}/img-1.png");
        Storage::disk('public')->assertMissing("lesson-imports/tmp/{$importB}");
    }

    public function test_commit_validates_payload(): void
    {
        [$user, $course] = $this->setUpCourse();

        $this->commit($user, $course, [
            'import_id' => 'not-a-uuid',
            'publication_status' => 'archived',
            'lesson' => ['title' => ''],
        ])->assertStatus(422)->assertJsonValidationErrors(['import_id', 'publication_status', 'lesson.title', 'topics']);

        $this->commit($user, $course, [
            'import_id' => (string) Str::uuid(),
            'publication_status' => 'draft',
            'lesson' => ['title' => 'x'],
            'topics' => array_fill(0, 11, ['title' => 't']),
        ])->assertStatus(422)->assertJsonValidationErrors(['topics']);
    }

    // 11
    public function test_commit_saves_long_thai_content_over_70000_characters(): void
    {
        [$user, $course] = $this->setUpCourse();

        $content = '<p>'.str_repeat('ภาษาไทย', 10001).'</p>';
        $this->assertGreaterThan(70000, mb_strlen($content));

        $response = $this->commit($user, $course, [
            'import_id' => (string) Str::uuid(),
            'publication_status' => 'published',
            'lesson' => ['title' => 'ยาว', 'content' => $content],
            'topics' => [['title' => 'หัวข้อยาว', 'content' => $content]],
        ]);

        $response->assertCreated();
        $lesson = Lesson::findOrFail($response->json('newLesson.id'));
        $this->assertSame($content, $lesson->fresh()->content);
        $this->assertSame($content, Topic::where('lesson_id', $lesson->id)->firstOrFail()->content);
    }

    // 12
    public function test_commit_keeps_thai_text_intact(): void
    {
        [$user, $course] = $this->setUpCourse();

        $title = 'บทเรียนที่ ๑: การอ่านออกเสียง “สระ” และวรรณยุกต์';
        $description = 'คำอธิบาย ภาษาไทย ๑๒๓';
        $content = '<p>สวัสดีครับ นักเรียนทุกคน <strong>ก ข ค</strong> เกี๊ยะ ญ ฎ ฏ</p>';
        $topicTitle = 'หัวข้อย่อย: ไม้เอก ไม้โท ไม้ตรี ไม้จัตวา';
        $topicContent = '<ul><li>ไม้เอก ่</li><li>ไม้โท ้</li></ul>';

        $response = $this->commit($user, $course, [
            'import_id' => (string) Str::uuid(),
            'publication_status' => 'draft',
            'lesson' => ['title' => $title, 'description' => $description, 'content' => $content],
            'topics' => [['title' => $topicTitle, 'content' => $topicContent]],
        ]);

        $response->assertCreated();
        $lesson = Lesson::findOrFail($response->json('newLesson.id'));
        $this->assertSame($title, $lesson->title);
        $this->assertSame($description, $lesson->description);
        $this->assertSame($content, $lesson->content);

        $topic = $lesson->topics()->firstOrFail();
        $this->assertSame($topicTitle, $topic->title);
        $this->assertSame($topicContent, $topic->content);
    }

    // 13
    public function test_existing_lesson_and_topic_store_endpoints_still_work(): void
    {
        [$user, $course] = $this->setUpCourse();
        $lessonsBefore = (int) $course->fresh()->lessons;

        $response = $this->actingAs($user, 'api')->postJson("/api/courses/{$course->id}/lessons", [
            // LessonForm.vue always sends these keys; store() reads them without a default.
            'title' => 'บทเรียนปกติ',
            'description' => '',
            'content' => '',
            'youtube_url' => '',
            'status' => 1,
        ]);

        $response->assertCreated()->assertJson(['success' => true]);
        $lesson = Lesson::findOrFail($response->json('newLesson.id'));
        $this->assertSame($lessonsBefore + 1, (int) $course->fresh()->lessons);
        $this->assertSame($user->id, (int) $lesson->user_id);
        $minReadBefore = (int) $lesson->min_read;

        $topicResponse = $this->actingAs($user, 'api')->postJson("/api/lessons/{$lesson->id}/topics", [
            'title' => 'หัวข้อปกติ',
            'content' => '<p>เนื้อหา</p>',
            'min_read' => 5,
        ]);

        $topicResponse->assertOk()->assertJson(['success' => true]);
        $this->assertSame($minReadBefore + 5, (int) $lesson->fresh()->min_read);

        $topic = $lesson->topics()->firstOrFail();
        $this->assertSame($user->id, (int) $topic->user_id);
        $this->assertSame($course->id, (int) $topic->course_id);
        $this->assertSame($course->academy_id, $topic->academy_id);
    }

    // 14
    public function test_prune_command_deletes_old_temp_folders(): void
    {
        [$user, $course] = $this->setUpCourse();

        $importId = $this->preview($user, $course, '<h1>ลบ</h1><p>'.$this->pngImg().'</p>')->json('import_id');
        Storage::disk('public')->makeDirectory('lesson-imports/tmp/'.Str::uuid());
        Storage::disk('public')->assertExists("lesson-imports/tmp/{$importId}/img-1.png");

        // Fresh files survive the default 24h window (empty folder is removed).
        $this->artisan('lesson-imports:prune')
            ->expectsOutput('Pruned 1 folder(s).')
            ->assertSuccessful();
        Storage::disk('public')->assertExists("lesson-imports/tmp/{$importId}/img-1.png");

        $this->artisan('lesson-imports:prune', ['--hours' => 0])
            ->expectsOutput('Pruned 1 folder(s).')
            ->assertSuccessful();

        Storage::disk('public')->assertMissing("lesson-imports/tmp/{$importId}");
        $this->assertSame([], Storage::disk('public')->directories('lesson-imports/tmp'));
    }
}
