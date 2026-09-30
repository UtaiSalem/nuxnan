<?php

namespace Tests\Unit;

use App\Support\BankAccountNameMatcher;
use PHPUnit\Framework\TestCase;

class BankAccountNameMatcherTest extends TestCase
{
    public function test_normalize_strips_longest_and_glued_thai_prefixes(): void
    {
        $this->assertSame('พัชรี หนูวงค์', BankAccountNameMatcher::normalize('นางสาวพัชรี หนูวงค์'));
        $this->assertSame('พัชรี หนูวงค์', BankAccountNameMatcher::normalize('ด.ญ.พัชรี หนูวงค์'));
        $this->assertSame('นายิกา หนูวงค์', BankAccountNameMatcher::normalize('นายิกา หนูวงค์'));
    }

    public function test_matches_full_name_supports_equality_containment_and_empty_values(): void
    {
        $this->assertTrue(BankAccountNameMatcher::matchesFullName('พัชรี หนูวงค์', 'นางสาวพัชรี หนูวงค์'));
        $this->assertTrue(BankAccountNameMatcher::matchesFullName('พัชรี หนูวงค์', 'บัญชี พัชรี หนูวงค์'));
        $this->assertFalse(BankAccountNameMatcher::matchesFullName('', 'พัชรี หนูวงค์'));
        $this->assertFalse(BankAccountNameMatcher::matchesFullName('พัชรี หนูวงค์', null));
    }

    public function test_matches_accepts_account_containing_both_first_and_last_name(): void
    {
        // Prefix on the account is stripped, both names present.
        $this->assertTrue(BankAccountNameMatcher::matches('สมชาย', 'ใจดี', 'นายสมชาย ใจดี'));
        // Extra tokens around the name are tolerated.
        $this->assertTrue(BankAccountNameMatcher::matches('สมชาย', 'ใจดี', 'บัญชี สมชาย ใจดี ออมทรัพย์'));
        // Order is not enforced (some banks store last-first).
        $this->assertTrue(BankAccountNameMatcher::matches('สมชาย', 'ใจดี', 'ใจดี สมชาย'));
        // Case-insensitive with an English prefix.
        $this->assertTrue(BankAccountNameMatcher::matches('John', 'Doe', 'MR. JOHN DOE'));
    }

    public function test_matches_rejects_when_a_name_part_is_missing(): void
    {
        // First name differs.
        $this->assertFalse(BankAccountNameMatcher::matches('สมชาย', 'ใจดี', 'สมหญิง ใจดี'));
        // Last name differs.
        $this->assertFalse(BankAccountNameMatcher::matches('สมชาย', 'ใจดี', 'สมชาย รวยทรัพย์'));
    }

    public function test_matches_rejects_empty_inputs(): void
    {
        $this->assertFalse(BankAccountNameMatcher::matches('', 'ใจดี', 'สมชาย ใจดี'));
        $this->assertFalse(BankAccountNameMatcher::matches('สมชาย', '', 'สมชาย ใจดี'));
        $this->assertFalse(BankAccountNameMatcher::matches('สมชาย', 'ใจดี', ''));
        $this->assertFalse(BankAccountNameMatcher::matches(null, null, null));
    }
}
