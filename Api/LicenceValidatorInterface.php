<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Api;

/**
 * Checks a subscription key with the subscription service.
 */
interface LicenceValidatorInterface
{
    public const RESULT_VALID = 'valid';
    public const RESULT_INVALID = 'invalid';
    public const RESULT_UNKNOWN = 'unknown';

    /**
     * Validate a key.
     *
     * @param string $key
     * @return string One of the RESULT_* constants; unknown when the service could not give an answer
     */
    public function validate(string $key): string;
}
