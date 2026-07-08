<?php

namespace Tests\Unit;

use App\Enums\StudentCardRequestStatus;
use App\Models\AcademyRole;
use PHPUnit\Framework\TestCase;

class StudentCardRequestTest extends TestCase
{
    public function test_open_statuses_are_classified_correctly(): void
    {
        $this->assertTrue(StudentCardRequestStatus::Pending->isOpen());
        $this->assertTrue(StudentCardRequestStatus::Approved->isOpen());
        $this->assertTrue(StudentCardRequestStatus::InProgress->isOpen());
        $this->assertFalse(StudentCardRequestStatus::Completed->isOpen());
        $this->assertFalse(StudentCardRequestStatus::Rejected->isOpen());
        $this->assertFalse(StudentCardRequestStatus::Cancelled->isOpen());
    }

    public function test_card_permissions_are_assigned_to_expected_system_roles(): void
    {
        $this->assertContains('students.cards.request', AcademyRole::SYSTEM_ROLES['teacher']['permissions']);
        $this->assertContains('students.cards.produce', AcademyRole::SYSTEM_ROLES['admin']['permissions']);
        $this->assertContains('students.cards.produce', AcademyRole::SYSTEM_ROLES['director']['permissions']);
        $this->assertContains('students.cards.produce', AcademyRole::SYSTEM_ROLES['card_admin']['permissions']);
    }
}
