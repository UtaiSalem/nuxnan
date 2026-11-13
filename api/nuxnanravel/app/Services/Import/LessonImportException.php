<?php

namespace App\Services\Import;

/**
 * Thrown when an imported lesson breaks a limit or has no usable content.
 * The message is Thai and safe to show to the teacher (HTTP 422).
 */
class LessonImportException extends \RuntimeException {}
