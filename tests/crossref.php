<?php
// Run: php tests/crossref.php (no database or network needed).
require __DIR__ . '/../backend/crossref.php';
function check_crossref($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
    echo 'PASS: ' . $message . PHP_EOL;
}
$fixture = ['status' => 'ok', 'message' => ['total-results' => 2, 'items' => [
    ['DOI' => '10.1234/example', 'title' => ['Computer Science'], 'author' => [['given' => 'Alice', 'family' => 'Smith']],
     'published' => ['date-parts' => [[2026, 9, 1]]], 'type' => 'journal-article', 'container-title' => ['Test Journal'],
     'abstract' => '<jats:p>Research &amp; results</jats:p>',
     'link' => [['content-type' => 'application/pdf', 'URL' => 'javascript:alert(1)'], ['content-type' => 'application/pdf', 'URL' => 'https://publisher.example/paper.pdf']]],
    ['DOI' => 'javascript:bad', 'title' => ['Invalid DOI']]
]]];
$result = parse_crossref_response(json_encode($fixture));
check_crossref($result['total'] === 2, 'Total results parsed');
check_crossref(count($result['papers']) === 1, 'Invalid DOI ignored');
$paper = $result['papers'][0];
check_crossref($paper['title'] === 'Computer Science', 'Title parsed');
check_crossref($paper['authors'] === 'Alice Smith', 'Author parsed');
check_crossref($paper['date'] === '2026-9-1', 'Publication date parsed');
check_crossref($paper['abstract'] === 'Research & results', 'Abstract markup removed');
check_crossref($paper['url'] === 'https://doi.org/10.1234/example', 'DOI uses trusted HTTPS resolver');
check_crossref($paper['pdf'] === 'https://publisher.example/paper.pdf', 'Unsafe PDF link ignored');
check_crossref(crossref_query('cs.AI', 'medical imaging') === 'Artificial Intelligence medical imaging', 'Search includes CSE topic');
check_crossref(crossref_query('invalid', '') === 'Computer Science', 'Unknown topic uses Computer Science');
$minimal = parse_crossref_response(json_encode(['status' => 'ok', 'message' => ['total-results' => 1, 'items' => [['DOI' => '10.1234/minimal']]]]));
check_crossref($minimal['papers'][0]['pdf'] === '' && $minimal['papers'][0]['abstract'] === '', 'Missing optional metadata handled');
$empty = parse_crossref_response('{"status":"ok","message":{"total-results":0,"items":[]}}');
check_crossref($empty['papers'] === [] && $empty['total'] === 0, 'Empty results handled');
foreach (['not JSON', '<html>Unavailable</html>', '{"status":"error"}'] as $bad) {
    $rejected = false;
    try { parse_crossref_response($bad); } catch (RuntimeException $error) { $rejected = true; }
    check_crossref($rejected, 'Malformed/error response rejected');
}
