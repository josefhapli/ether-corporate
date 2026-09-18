<?php
declare(strict_types=1);
require dirname(__DIR__) . '/api/contact-lib.php';
function check(bool $condition, string $label): void { if (!$condition) throw new RuntimeException($label); }
function rejects(callable $callback, int $expected): void {
    try { $callback(); } catch (InquiryError $error) { check($error->status === $expected, 'Unexpected status'); return; }
    throw new RuntimeException('Expected rejection ' . $expected);
}
$valid = ['name' => ' Example Person ', 'email' => 'person@example.com', 'organization' => 'Example organization', 'message' => "We are exploring a new service.\nCould we discuss it?", 'website' => ''];
$data = inquiry_validate($valid);
check($data['name'] === 'Example Person', 'Trim name');
check(inquiry_validate(array_replace($valid, ['organization' => '']))['organization'] === '', 'Organization optional');
foreach ([['name' => ''], ['email' => 'invalid'], ['message' => 'short'], ['name' => ['array']], ['website' => 'spam'], ['name' => "Name\r\nInjected"], ['message' => str_repeat('a', 20001)], ['message' => "bad\0message value"]] as $changes) rejects(fn() => inquiry_validate(array_replace($valid, $changes)), 400);
$payload = inquiry_payload($data);
check(array_column($payload['fields'], 'name') === ['firstname', 'email', 'company', 'message'], 'Field mapping');
check($payload['fields'][3]['value'] === $valid['message'], 'Full inquiry in message field');
check(!isset($payload['legalConsentOptions']), 'No marketing subscription invented');
foreach ([200, 204] as $status) inquiry_deliver($data, [], static fn() => $status);
foreach ([0, 400, 429, 500] as $status) rejects(fn() => inquiry_deliver($data, [], static fn() => $status), 502);
$temp = sys_get_temp_dir() . '/ether-inquiry-test-' . bin2hex(random_bytes(6));
mkdir($temp, 0700);
try {
    for ($i = 0; $i < 5; $i++) inquiry_rate_limit($temp, str_repeat('x', 32), '192.0.2.1', 1000);
    rejects(fn() => inquiry_rate_limit($temp, str_repeat('x', 32), '192.0.2.1', 1000), 429);
    inquiry_rate_limit($temp, str_repeat('x', 32), '192.0.2.2', 1000);
    inquiry_rate_limit($temp, str_repeat('x', 32), '192.0.2.1', 1901);
    rejects(fn() => inquiry_rate_limit($temp, 'short', '192.0.2.1', 1901), 503);
    foreach (glob($temp . '/*') as $file) { check(!str_contains(file_get_contents($file), '192.0.2.'), 'No raw IP stored'); }
} finally { foreach (glob($temp . '/*') as $file) unlink($file); rmdir($temp); }
echo "PASS: validation, optional organization, honeypot, HubSpot field mapping, delivery failures, rate limit and expiry. No external submissions.\n";
