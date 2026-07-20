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
}
