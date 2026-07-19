<?php

namespace Tests\Feature\Academy;

use Tests\TestCase;

class AcademyMemberFiltersTest extends TestCase
{
    public function test_classroom_key_filter_returns_only_matching_students(): void
    {
        $this->markTestIncomplete('Requires the project classroom enrollment fixture set.');
    }

    public function test_date_joined_range_filter(): void
    {
        $this->markTestIncomplete('Requires the project academy member fixture set.');
    }
}
