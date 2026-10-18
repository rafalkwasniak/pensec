<?php

namespace Tests\Unit;

use App\Enums\ReportStatus;
use PHPUnit\Framework\TestCase;

class ReportStatusTest extends TestCase
{
    public function test_every_case_has_a_polish_label(): void
    {
        foreach (ReportStatus::cases() as $status) {
            $this->assertNotSame('', $status->label());
        }
    }

    public function test_in_progress_covers_the_states_a_job_still_moves(): void
    {
        $this->assertTrue(ReportStatus::Received->inProgress());
        $this->assertTrue(ReportStatus::Parsing->inProgress());
        $this->assertFalse(ReportStatus::Parsed->inProgress());
        $this->assertFalse(ReportStatus::Failed->inProgress());
    }
}
