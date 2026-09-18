<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
require __DIR__ . '/contact-lib.php';
try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); throw new InquiryError(405, 'Please submit the contact form.'); }
    if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') throw new InquiryError(415, 'Please submit the contact form or email hello@etherstudios.net.');
    $raw = file_get_contents('php://input', false, null, 0, 32769);
    if ($raw === false || strlen($raw) > 32768) throw new InquiryError(413, 'Your message is too long. Please shorten it and try again.');
    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || array_is_list($decoded)) throw new InquiryError(400, 'Please check the form fields and try again.');
    $data = inquiry_validate($decoded);
    // The config lives OUTSIDE public_html. No config means no external submission.
    $configFile = getenv('ETHER_CONTACT_CONFIG') ?: dirname(__DIR__, 2) . '/ether-private/contact-config.php';
    $config = is_file($configFile) ? require $configFile : [];
    if (!is_array($config) || ($config['enabled'] ?? false) !== true || !preg_match('/^\d+$/', (string)($config['portal_id'] ?? '')) || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', (string)($config['form_id'] ?? ''))) {
        throw new InquiryError(503, 'The inquiry form isn’t connected yet. Please email hello@etherstudios.net.');
    }
    if (!in_array($_SERVER['HTTP_ORIGIN'] ?? '', $config['allowed_origins'] ?? [], true)) throw new InquiryError(403, 'Please open this form on the Ether website and try again.');
    $directory = realpath($config['rate_directory'] ?? '') ?: '';
    $publicRoot = realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR;
    if ($directory === '' || str_starts_with($directory . DIRECTORY_SEPARATOR, $publicRoot)) throw new InquiryError(503, 'The inquiry service is temporarily unavailable.');
    inquiry_rate_limit($directory, (string)($config['rate_salt'] ?? ''), $_SERVER['REMOTE_ADDR'] ?? 'unknown', time());
    inquiry_deliver($data, $config, 'inquiry_send');
    http_response_code(200);
    echo json_encode(['ok' => true]);
} catch (InquiryError $error) {
    http_response_code($error->status);
    if ($error->status === 429) header('Retry-After: 900');
    echo json_encode(['error' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('Ether inquiry: unexpected service error');
    http_response_code(503);
    echo json_encode(['error' => 'The inquiry service is temporarily unavailable. Please email hello@etherstudios.net.']);
}
