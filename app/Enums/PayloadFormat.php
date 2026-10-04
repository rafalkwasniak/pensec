<?php

namespace App\Enums;

/**
 * What the stored file holds, because it has not always been the same thing.
 */
enum PayloadFormat: string
{
    /**
     * The `report` object alone, re-encoded by the API. Everything stored
     * before raw submissions were kept is in this form.
     */
    case Report = 'report';

    /**
     * The request body exactly as the device sent it - the `report_id` and
     * `report` envelope - with only gzip transfer compression removed.
     */
    case Submission = 'submission';
}
