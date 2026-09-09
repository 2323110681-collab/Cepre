<?php

declare(strict_types=1);

const RECAPTCHA_SITE_KEY = '6LfpZLEtAAAAALdrOLRFQMt0VJckoC9q82zmrriC';
const RECAPTCHA_SECRET_KEY = '6LfpZLEtAAAAADyjTFv_oeE_giHvYo_uI-rGQIYl';

function verifyRecaptcha(string $response, ?string $remoteIp = null): bool
{
    if ($response === '') {
        return false;
    }

    $postData = http_build_query([
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $response,
        'remoteip' => $remoteIp ?? '',
    ]);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $postData,
            'timeout' => 8,
            'ignore_errors' => true,
        ],
    ]);
    $result = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    if ($result === false) {
        return false;
    }

    $verification = json_decode($result, true);
    return is_array($verification) && ($verification['success'] ?? false) === true;
}
