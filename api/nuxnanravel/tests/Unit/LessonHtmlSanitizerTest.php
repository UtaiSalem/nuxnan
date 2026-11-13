<?php

namespace Tests\Unit;

use App\Services\Import\LessonHtmlSanitizer;
use PHPUnit\Framework\TestCase;

class LessonHtmlSanitizerTest extends TestCase
{
    private LessonHtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new LessonHtmlSanitizer;
    }

    public function test_img_onerror_is_removed(): void
    {
        $out = $this->sanitizer->sanitize('<img src=x onerror=alert(1)>');

        $this->assertStringNotContainsStringIgnoringCase('onerror', $out);
        $this->assertStringNotContainsString('alert', $out);
        $this->assertStringContainsString('<img src="x">', $out);
    }

    public function test_javascript_href_is_removed(): void
    {
        $out = $this->sanitizer->sanitize('<a href="javascript:alert(1)">click</a>');

        $this->assertStringNotContainsStringIgnoringCase('javascript', $out);
        $this->assertStringNotContainsString('href', $out);
        $this->assertStringContainsString('click', $out);
    }

    public function test_svg_is_removed_with_content(): void
    {
        $out = $this->sanitizer->sanitize('<p>ok</p><svg onload="alert(1)"><text>evil</text></svg>');

        $this->assertSame('<p>ok</p>', $out);
    }

    public function test_style_attribute_is_removed(): void
    {
        $out = $this->sanitizer->sanitize('<p style="background:url(javascript:alert(1))" class="x" id="y" onclick="z()">text</p>');

        $this->assertSame('<p>text</p>', $out);
    }

    public function test_script_tag_is_removed_with_content(): void
    {
        $out = $this->sanitizer->sanitize('<p>a</p><script>alert(1)</script><style>p{}</style>');

        $this->assertSame('<p>a</p>', $out);
    }

    public function test_safe_links_get_target_and_rel(): void
    {
        $out = $this->sanitizer->sanitize('<a href="https://example.com/x">x</a><a href="mailto:a@b.co">m</a><a href="#top">t</a>');

        $this->assertStringContainsString('<a href="https://example.com/x" target="_blank" rel="noopener noreferrer">x</a>', $out);
        $this->assertStringContainsString('<a href="mailto:a@b.co">m</a>', $out);
        $this->assertStringContainsString('<a href="#top">t</a>', $out);
    }

    public function test_thai_text_is_preserved(): void
    {
        $thai = str_repeat('การเรียนรู้ภาษาไทยเป็นสิ่งสำคัญ ผู้เรียนควรฝึกอ่านทุกวัน ', 200);
        $out = $this->sanitizer->sanitize('<h1>บทที่ ๑ ภาษาไทย</h1><p>'.$thai.'</p>');

        $this->assertSame('<h1>บทที่ ๑ ภาษาไทย</h1><p>'.$thai.'</p>', $out);
    }

    public function test_table_colspan_is_kept_and_invalid_values_removed(): void
    {
        $out = $this->sanitizer->sanitize(
            '<table><tbody><tr><td colspan="2" style="color:red">a</td><td rowspan="999">b</td><th colspan="abc">c</th></tr></tbody></table>'
        );

        $this->assertStringContainsString('<td colspan="2">a</td>', $out);
        $this->assertStringContainsString('<td>b</td>', $out);
        $this->assertStringContainsString('<th>c</th>', $out);
        $this->assertStringNotContainsString('style', $out);
    }

    public function test_unknown_tags_are_unwrapped(): void
    {
        $out = $this->sanitizer->sanitize('<p><font color="red">สวัสดี <b>โลก</b></font></p><div><section>x</section></div>');

        $this->assertSame('<p>สวัสดี <b>โลก</b></p>x', $out);
    }

    public function test_ol_start_is_kept_only_when_numeric(): void
    {
        $this->assertSame('<ol start="3"><li>a</li></ol>', $this->sanitizer->sanitize('<ol start="3" type="a"><li>a</li></ol>'));
        $this->assertSame('<ol><li>a</li></ol>', $this->sanitizer->sanitize('<ol start="x"><li>a</li></ol>'));
    }

    public function test_empty_html_returns_empty_string(): void
    {
        $this->assertSame('', $this->sanitizer->sanitize(''));
        $this->assertSame('', $this->sanitizer->sanitize('   '));
        $this->assertSame('', $this->sanitizer->sanitize('<script>x</script>'));
    }

    public function test_comments_are_removed(): void
    {
        $this->assertSame('<p>a</p>', $this->sanitizer->sanitize('<!-- secret --><p>a</p>'));
    }
}
