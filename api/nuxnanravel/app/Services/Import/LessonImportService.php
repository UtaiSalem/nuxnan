<?php

namespace App\Services\Import;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Services\Course\LessonCreationService;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Two-step lesson import from .docx (converted to HTML by mammoth.js in the browser).
 *
 *  preview(): sanitize → extract data-URI images to a temp folder → split by H1/H2 → return editable structure
 *  commit():  re-sanitize teacher-edited structure → copy temp images to permanent folder → create Lesson + Topics
 */
class LessonImportService
{
    public const MAX_IMAGES = 15;

    public const MAX_TOPICS = 10;

    public const MAX_HTML_LENGTH = 20971520;

    public const MAX_IMAGE_BYTES = 10485760;

    public const TMP_DIR = 'lesson-imports/tmp';

    public const FINAL_DIR = 'images/courses/lessons/content';

    public const ALLOWED_MIME = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    private const YOUTUBE_REGEX = '~^https?://(www\.|m\.)?(youtube\.com/watch\?v=|youtu\.be/|youtube\.com/shorts/)[A-Za-z0-9_-]{6,}\S*$~';

    private const ROOT_ID = '__root';

    public function __construct(
        private readonly LessonHtmlSanitizer $sanitizer,
        private readonly LessonCreationService $creator,
    ) {}

    // ------------------------------------------------------------------
    // 6.1 preview
    // ------------------------------------------------------------------

    public function preview(string $html, ?string $fallbackTitle = null): array
    {
        if (mb_strlen($html) > self::MAX_HTML_LENGTH) {
            throw new LessonImportException('ไฟล์มีขนาดใหญ่เกินไป (เกิน '.number_format(self::MAX_HTML_LENGTH).' ตัวอักษร)');
        }

        $importId = (string) Str::uuid();
        $tmpDir = self::TMP_DIR.'/'.$importId;
        $warnings = [];

        try {
            $clean = $this->sanitizer->sanitize($html);
            [$doc, $root] = $this->loadFragment($clean);

            $imageCount = $this->extractImages($root, $tmpDir, $warnings);

            $structure = $this->splitStructure($doc, $root, $fallbackTitle, $warnings);
        } catch (Throwable $e) {
            Storage::disk('public')->deleteDirectory($tmpDir);

            throw $e;
        }

        return [
            'import_id' => $importId,
            'lesson' => $structure['lesson'],
            'topics' => $structure['topics'],
            'images' => $imageCount,
            'warnings' => $warnings,
        ];
    }

    /**
     * Replace data-URI images with files stored in the temp folder.
     * Validates everything first so nothing is written when the image limit is exceeded.
     */
    private function extractImages(DOMElement $root, string $tmpDir, array &$warnings): int
    {
        $images = iterator_to_array($root->getElementsByTagName('img'));
        $accepted = [];

        foreach ($images as $index => $img) {
            $n = $index + 1;
            $src = trim($img->getAttribute('src'));

            // Parse with string functions, not a regex: a multi-MB base64 payload can hit the
            // PCRE backtrack/JIT limit, preg_match() then returns false and a real image would be dropped.
            $marker = strpos($src, ';base64,');
            $declaredMime = $marker !== false ? strtolower(substr($src, 5, $marker - 5)) : '';
            if (! str_starts_with(strtolower($src), 'data:image/') || $marker === false
                || ! preg_match('~^image/[a-z0-9.+-]+$~', $declaredMime)) {
                $this->removeNode($img);
                $warnings[] = 'ลบรูปที่ลิงก์จากภายนอก';

                continue;
            }

            $payload = str_replace(["\r", "\n", "\t", ' '], '', substr($src, $marker + 8));

            if ((int) floor(strlen($payload) * 3 / 4) > self::MAX_IMAGE_BYTES + 3) {
                $this->removeNode($img);
                $warnings[] = "ลบรูปที่ {$n} เพราะมีขนาดเกิน 10 MB";

                continue;
            }

            $bytes = base64_decode($payload, true);
            if ($bytes === false || $bytes === '') {
                $this->removeNode($img);
                $warnings[] = "ลบรูปที่ {$n} เพราะชนิดไฟล์ไม่รองรับ ({$declaredMime})";

                continue;
            }

            if (strlen($bytes) > self::MAX_IMAGE_BYTES) {
                $this->removeNode($img);
                $warnings[] = "ลบรูปที่ {$n} เพราะมีขนาดเกิน 10 MB";

                continue;
            }

            // Never trust the MIME type written in the data URI.
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = $finfo ? (string) finfo_buffer($finfo, $bytes) : '';
            if ($finfo) {
                finfo_close($finfo);
            }

            if (! isset(self::ALLOWED_MIME[$realMime])) {
                $this->removeNode($img);
                $shownMime = $realMime !== '' ? $realMime : $declaredMime;
                $warnings[] = "ลบรูปที่ {$n} เพราะชนิดไฟล์ไม่รองรับ ({$shownMime})";

                continue;
            }

            $accepted[] = ['node' => $img, 'bytes' => $bytes, 'ext' => self::ALLOWED_MIME[$realMime]];
        }

        $count = count($accepted);
        if ($count > self::MAX_IMAGES) {
            Storage::disk('public')->deleteDirectory($tmpDir);

            throw new LessonImportException("ไฟล์มีรูป {$count} รูป เกินขีดจำกัด ".self::MAX_IMAGES.' รูปต่อไฟล์');
        }

        $disk = Storage::disk('public');
        foreach ($accepted as $i => $item) {
            $path = $tmpDir.'/img-'.($i + 1).'.'.$item['ext'];
            $disk->put($path, $item['bytes']);
            $item['node']->setAttribute('src', '/storage/'.$path);
        }

        return $count;
    }

    /**
     * Split top-level nodes into lesson intro + topics (H1 = lesson title, H2 = topic).
     */
    private function splitStructure(DOMDocument $doc, DOMElement $root, ?string $fallbackTitle, array &$warnings): array
    {
        $title = null;
        $introNodes = [];
        /** @var list<array{title: string, nodes: list<DOMNode>}> $topics */
        $topics = [];

        foreach (iterator_to_array($root->childNodes) as $node) {
            if ($node->nodeType === XML_TEXT_NODE && $this->cleanText($node->textContent) === '') {
                continue;
            }

            $tag = $node instanceof DOMElement ? strtolower($node->tagName) : null;

            if ($tag === 'h1' && $title === null) {
                $title = mb_substr($this->cleanText($node->textContent), 0, 255);

                continue;
            }

            if ($tag === 'h1') {
                $text = $this->cleanText($node->textContent);
                $warnings[] = "พบหัวข้อระดับ 1 มากกว่าหนึ่งหัวข้อ จึงแปลง \"{$text}\" เป็นหัวข้อย่อย";
                $tag = 'h2';
            }

            if ($tag === 'h2') {
                $n = count($topics) + 1;
                $topicTitle = mb_substr($this->cleanText($node->textContent), 0, 255);
                if ($topicTitle === '') {
                    $topicTitle = "หัวข้อที่ {$n}";
                    $warnings[] = "หัวข้อย่อยที่ {$n} ไม่มีชื่อ จึงตั้งชื่อเป็น \"{$topicTitle}\"";
                }
                $topics[] = ['title' => $topicTitle, 'nodes' => []];

                continue;
            }

            if ($topics === []) {
                $introNodes[] = $node;
            } else {
                $topics[count($topics) - 1]['nodes'][] = $node;
            }
        }

        if ($title === null || $title === '') {
            $fallback = $fallbackTitle !== null ? $this->cleanText(preg_replace('/\.docx$/i', '', $fallbackTitle)) : '';
            $title = mb_substr($fallback !== '' ? $fallback : 'บทเรียนใหม่', 0, 255);
            $warnings[] = 'ไม่พบหัวข้อระดับ 1 จึงใช้ชื่อไฟล์เป็นชื่อบทเรียน';
        }

        if ($topics === []) {
            $warnings[] = 'ไม่พบหัวข้อระดับ 2 เนื้อหาทั้งหมดจะอยู่ในบทเรียน';
        }

        $topicCount = count($topics);
        if ($topicCount > self::MAX_TOPICS) {
            throw new LessonImportException("ไฟล์มี {$topicCount} หัวข้อย่อย เกินขีดจำกัด ".self::MAX_TOPICS.' หัวข้อต่อบท');
        }

        // Lesson intro
        $introYoutube = $this->takeYoutube($introNodes);
        $description = $this->firstParagraphText($introNodes);
        $introHtml = $this->serialize($doc, $introNodes);

        $topicRows = [];
        foreach ($topics as $topic) {
            $nodes = $topic['nodes'];
            $youtube = $this->takeYoutube($nodes);
            $content = $this->serialize($doc, $nodes);

            $topicRows[] = [
                'title' => $topic['title'],
                'content' => $content,
                'youtube_url' => $youtube,
                'min_read' => $content === '' ? 0 : $this->minRead($content),
            ];
        }

        if ($introHtml === '' && $topicRows === []) {
            throw new LessonImportException('ไม่พบเนื้อหาในไฟล์');
        }

        return [
            'lesson' => [
                'title' => $title,
                'description' => $description,
                'content' => $introHtml,
                'youtube_url' => $introYoutube,
                'min_read' => $this->minRead($introHtml),
            ],
            'topics' => $topicRows,
        ];
    }

    /**
     * First top-level <p> whose whole text is a YouTube URL becomes the section's youtube_url
     * and is removed from the node list. Later ones stay in the content.
     *
     * @param  list<DOMNode>  $nodes
     */
    private function takeYoutube(array &$nodes): ?string
    {
        foreach ($nodes as $i => $node) {
            if (! $node instanceof DOMElement || strtolower($node->tagName) !== 'p') {
                continue;
            }

            $text = $this->cleanText($node->textContent);
            if (preg_match(self::YOUTUBE_REGEX, $text)) {
                array_splice($nodes, $i, 1);

                // commit() validates youtube_url with url:https — normalise old http:// links.
                return mb_substr((string) preg_replace('~^http://~i', 'https://', $text), 0, 2000);
            }
        }

        return null;
    }

    /**
     * @param  list<DOMNode>  $nodes
     */
    private function firstParagraphText(array $nodes): ?string
    {
        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $paragraphs = strtolower($node->tagName) === 'p' ? [$node] : iterator_to_array($node->getElementsByTagName('p'));
            foreach ($paragraphs as $p) {
                $text = $this->cleanText($p->textContent);
                if ($text !== '') {
                    return mb_substr($text, 0, 500);
                }
            }
        }

        return null;
    }

    private function minRead(string $html): int
    {
        return max(1, (int) ceil(mb_strlen(strip_tags($html)) / 1000));
    }

    // ------------------------------------------------------------------
    // 6.2 commit
    // ------------------------------------------------------------------

    public function commit(Course $course, User $user, array $data): Lesson
    {
        $importId = (string) $data['import_id'];
        if (! Str::isUuid($importId)) {
            throw new LessonImportException('รหัสการนำเข้าไม่ถูกต้อง');
        }

        $topicsInput = array_values($data['topics'] ?? []);
        $topicCount = count($topicsInput);
        if ($topicCount > self::MAX_TOPICS) {
            throw new LessonImportException("บทเรียนมี {$topicCount} หัวข้อย่อย เกินขีดจำกัด ".self::MAX_TOPICS.' หัวข้อต่อบท');
        }

        $tmpDir = self::TMP_DIR.'/'.$importId;
        $tmpPrefix = '/storage/'.$tmpDir.'/';
        $lessonUuid = (string) Str::uuid();
        $finalDir = self::FINAL_DIR.'/'.$lessonUuid;
        $disk = Storage::disk('public');

        $lessonInput = $data['lesson'] ?? [];

        // Pass 1: sanitize + drop images that do not belong to this import.
        $sections = [];
        $sections['lesson'] = $this->prepareCommitSection((string) ($lessonInput['content'] ?? ''), $tmpPrefix, $tmpDir);
        foreach ($topicsInput as $i => $topic) {
            $sections['topic_'.$i] = $this->prepareCommitSection((string) ($topic['content'] ?? ''), $tmpPrefix, $tmpDir);
        }

        $uniqueSources = [];
        foreach ($sections as $section) {
            foreach ($section['images'] as $img) {
                $uniqueSources[$img->getAttribute('src')] = true;
            }
        }

        $imageCount = count($uniqueSources);
        if ($imageCount > self::MAX_IMAGES) {
            throw new LessonImportException("บทเรียนมีรูป {$imageCount} รูป เกินขีดจำกัด ".self::MAX_IMAGES.' รูปต่อบท');
        }

        // Pass 2: copy each temp file once into the permanent folder and rewrite src.
        $map = [];
        try {
            foreach (array_keys($uniqueSources) as $src) {
                $basename = substr($src, strlen($tmpPrefix));
                $newPath = $finalDir.'/'.$basename;
                $disk->copy($tmpDir.'/'.$basename, $newPath);
                $map[$src] = '/storage/'.$newPath;
            }

            $html = [];
            foreach ($sections as $key => $section) {
                foreach ($section['images'] as $img) {
                    $img->setAttribute('src', $map[$img->getAttribute('src')]);
                }
                $html[$key] = $this->serialize($section['doc'], iterator_to_array($section['root']->childNodes));
            }

            $lesson = DB::transaction(function () use ($course, $user, $data, $lessonInput, $topicsInput, $html) {
                $lesson = $this->creator->createLesson($course, $user, [
                    'title' => $lessonInput['title'],
                    'description' => $this->nullIfEmpty($lessonInput['description'] ?? null),
                    'content' => $this->nullIfEmpty($html['lesson']),
                    'youtube_url' => $this->nullIfEmpty($lessonInput['youtube_url'] ?? null),
                    'min_read' => (int) ($lessonInput['min_read'] ?? 0),
                    'status' => '1',
                    'publication_status' => $data['publication_status'],
                    'access_type' => 'free',
                    'point_tuition_fee' => 0,
                    'require_completion_before_exercises' => false,
                ]);

                // createTopic() already adds each topic's min_read to the lesson.
                foreach ($topicsInput as $i => $topic) {
                    $this->creator->createTopic($lesson, $user, [
                        'title' => $topic['title'],
                        'content' => $this->nullIfEmpty($html['topic_'.$i]),
                        'youtube_url' => $this->nullIfEmpty($topic['youtube_url'] ?? null),
                        'min_read' => (int) ($topic['min_read'] ?? 0),
                    ]);
                }

                return $lesson;
            });
        } catch (Throwable $e) {
            $disk->deleteDirectory($finalDir);

            throw $e;
        }

        $disk->deleteDirectory($tmpDir);

        return $lesson->fresh();
    }

    /**
     * @return array{doc: DOMDocument, root: DOMElement, images: list<DOMElement>}
     */
    private function prepareCommitSection(string $html, string $tmpPrefix, string $tmpDir): array
    {
        $clean = $this->sanitizer->sanitize($html);
        [$doc, $root] = $this->loadFragment($clean);
        $disk = Storage::disk('public');

        $kept = [];
        foreach (iterator_to_array($root->getElementsByTagName('img')) as $img) {
            $src = trim($img->getAttribute('src'));

            $valid = str_starts_with($src, $tmpPrefix);
            if ($valid) {
                $basename = substr($src, strlen($tmpPrefix));
                // Plain file name only — blocks "../" traversal to other imports.
                $valid = preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9]+$/', $basename) === 1
                    && $disk->exists($tmpDir.'/'.$basename);
            }

            if (! $valid) {
                $this->removeNode($img);

                continue;
            }

            $img->setAttribute('src', $src);
            $kept[] = $img;
        }

        return ['doc' => $doc, 'root' => $root, 'images' => $kept];
    }

    // ------------------------------------------------------------------
    // 6.3 prune
    // ------------------------------------------------------------------

    public function pruneExpired(int $hours = 24): int
    {
        $disk = Storage::disk('public');
        $cutoff = now()->subHours($hours)->getTimestamp();
        $deleted = 0;

        foreach ($disk->directories(self::TMP_DIR) as $dir) {
            $newest = null;
            foreach ($disk->allFiles($dir) as $file) {
                $modified = $disk->lastModified($file);
                $newest = $newest === null ? $modified : max($newest, $modified);
            }

            if ($newest === null || $newest <= $cutoff) {
                $disk->deleteDirectory($dir);
                $deleted++;
            }
        }

        return $deleted;
    }

    // ------------------------------------------------------------------
    // DOM helpers
    // ------------------------------------------------------------------

    /**
     * @return array{0: DOMDocument, 1: DOMElement}
     */
    private function loadFragment(string $html): array
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $doc = new DOMDocument('1.0', 'UTF-8');
            $doc->loadHTML(
                '<?xml encoding="UTF-8"><div id="'.self::ROOT_ID.'">'.$html.'</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
            );
            libxml_clear_errors();
        } finally {
            libxml_use_internal_errors($previous);
        }

        $root = (new DOMXPath($doc))->query('//div[@id="'.self::ROOT_ID.'"]')->item(0);
        if (! $root instanceof DOMElement) {
            $root = $doc->createElement('div');
            $doc->appendChild($root);
        }

        return [$doc, $root];
    }

    /**
     * @param  iterable<DOMNode>  $nodes
     */
    private function serialize(DOMDocument $doc, iterable $nodes): string
    {
        $out = '';
        foreach ($nodes as $node) {
            $out .= $doc->saveHTML($node);
        }

        return trim($out);
    }

    private function removeNode(DOMNode $node): void
    {
        $node->parentNode?->removeChild($node);
    }

    private function cleanText(string $text): string
    {
        return (string) preg_replace('/^[\s\x{00A0}\x{200B}\x{FEFF}]+|[\s\x{00A0}\x{200B}\x{FEFF}]+$/u', '', $text);
    }

    private function nullIfEmpty(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return trim($value) === '' ? null : $value;
    }
}
