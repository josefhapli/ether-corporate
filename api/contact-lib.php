<?php
// Pure validation/payload helpers and private rate limiting; no submitted values are logged.
declare(strict_types=1);
final class InquiryError extends RuntimeException {
    public int $status;
    public function __construct(int $status, string $message) { parent::__construct($message); $this->status = $status; }
}
function inquiry_validate(array $data): array {
    $limits = ['name' => 480, 'email' => 254, 'organization' => 720, 'message' => 20000, 'website' => 500];
    $clean = [];
    foreach ($limits as $field => $limit) {
        $value = $data[$field] ?? '';
        if (!is_string($value) || strlen($value) > $limit || !preg_match('//u', $value)) throw new InquiryError(400, 'Please check the form fields and try again.');
        $clean[$field] = trim($value);
    }
    if ($clean['website'] !== '') throw new InquiryError(400, 'We couldn’t accept this submission. Please email hello@etherstudios.net.');
    if ($clean['name'] === '' || !filter_var($clean['email'], FILTER_VALIDATE_EMAIL) || strlen($clean['message']) < 10) throw new InquiryError(400, 'Please provide your name, a valid email and a message of at least 10 characters.');
    foreach (['name', 'email', 'organization'] as $field) if (preg_match('/[\x00-\x1f\x7f]/', $clean[$field])) throw new InquiryError(400, 'Please check your contact details.');
    if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $clean['message'])) throw new InquiryError(400, 'Please check your message.');
    unset($clean['website']);
    return $clean;
}
function inquiry_payload(array $data): array {
    return [
        'fields' => [
            ['objectTypeId' => '0-1', 'name' => 'firstname', 'value' => $data['name']],
            ['objectTypeId' => '0-1', 'name' => 'email', 'value' => $data['email']],
            ['objectTypeId' => '0-1', 'name' => 'company', 'value' => $data['organization']],
            ['objectTypeId' => '0-1', 'name' => 'message', 'value' => $data['message']],
        ],
        'context' => ['pageUri' => 'https://www.etherstudios.net/contact/', 'pageName' => 'Ether corporate inquiry'],
    ];
}
function inquiry_rate_limit(string $directory, string $salt, string $ip, int $now): void {
    if (strlen($salt) < 32 || !is_dir($directory) || !is_writable($directory)) throw new InquiryError(503, 'The inquiry service is temporarily unavailable. Please email hello@etherstudios.net.');
    $file = $directory . '/' . hash_hmac('sha256', $ip, $salt) . '.json';
    $handle = @fopen($file, 'c+');
    if (!$handle || !flock($handle, LOCK_EX)) throw new InquiryError(503, 'The inquiry service is temporarily unavailable. Please email hello@etherstudios.net.');
    try {
        @chmod($file, 0600);
        $old = json_decode(stream_get_contents($handle), true);
        $attempts = is_array($old) ? array_values(array_filter($old, static fn($time) => is_int($time) && $time > $now - 900)) : [];
        if (count($attempts) >= 5) throw new InquiryError(429, 'You’ve sent several inquiries recently. Please wait 15 minutes or email hello@etherstudios.net.');
        $attempts[] = $now;
        rewind($handle);
        if (!ftruncate($handle, 0) || fwrite($handle, json_encode($attempts)) === false || !fflush($handle)) throw new InquiryError(503, 'The inquiry service is temporarily unavailable.');
    } finally { flock($handle, LOCK_UN); fclose($handle); }
}
function inquiry_send(array $payload, array $config): int {
    if (!function_exists('curl_init')) throw new InquiryError(503, 'The inquiry service is temporarily unavailable. Please email hello@etherstudios.net.');
    $url = 'https://api.hsforms.com/submissions/v3/integration/submit/' . $config['portal_id'] . '/' . $config['form_id'];
    $curl = curl_init($url);
    curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR), CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15, CURLOPT_FOLLOWLOCATION => false]);
    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    // Never log response bodies: HubSpot can echo submitted contact information.
    return $response === false ? 0 : $status;
}
function inquiry_deliver(array $data, array $config, callable $transport): void {
    $status = $transport(inquiry_payload($data), $config);
    if ($status < 200 || $status >= 300) {
        error_log('Ether inquiry: HubSpot response status=' . $status);
        throw new InquiryError(502, 'We couldn’t confirm delivery. Your message is still here. Please email hello@etherstudios.net to confirm receipt.');
    }
}
