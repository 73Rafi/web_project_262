<?php
// Free API: https://www.crossref.org/documentation/retrieve-metadata/rest-api/
// No API key is needed. Searches combine a CSE topic with the user's keywords.
function crossref_topics()
{
    return [
        'cs.*' => 'Computer Science',
        'cs.AI' => 'Artificial Intelligence',
        'cs.LG' => 'Machine Learning',
        'cs.CV' => 'Computer Vision',
        'cs.CL' => 'Natural Language Processing',
        'cs.CR' => 'Cybersecurity',
        'cs.SE' => 'Software Engineering',
        'cs.NI' => 'Networking',
        'cs.DB' => 'Databases',
        'cs.DS' => 'Algorithms and Data Structures',
        'cs.RO' => 'Robotics'
    ];
}

function crossref_query($topic, $search)
{
    $topics = crossref_topics();
    $label = $topics[$topic] ?? 'Computer Science';
    return trim($label . ' ' . substr($search, 0, 150));
}

function parse_crossref_response($json)
{
    $data = json_decode($json, true);

    if (!is_array($data)
        || ($data['status'] ?? '') !== 'ok'
        || !isset($data['message']['items'], $data['message']['total-results'])
        || !is_array($data['message']['items'])
        || !is_numeric($data['message']['total-results'])) {
        throw new RuntimeException('Invalid Crossref response.');
    }

    $papers = [];

    foreach ($data['message']['items'] as $item) {
        $doi = $item['DOI'] ?? '';

        if (!is_string($doi) || !preg_match('~^10\.\d{4,9}/\S+$~', $doi)) {
            continue;
        }

        $authors = [];
        foreach ($item['author'] ?? [] as $author) {
            $name = trim(($author['given'] ?? '') . ' ' . ($author['family'] ?? ''));
            if ($name !== '') {
                $authors[] = $name;
            }
        }

        $date = $item['published']['date-parts'][0] ?? [];
        $pdf = '';

        foreach ($item['link'] ?? [] as $link) {
            $url = $link['URL'] ?? '';
            $parts = parse_url($url);

            if (($link['content-type'] ?? '') === 'application/pdf'
                && filter_var($url, FILTER_VALIDATE_URL)
                && ($parts['scheme'] ?? '') === 'https'
                && !isset($parts['user'])
                && !isset($parts['pass'])) {
                $pdf = $url;
                break;
            }
        }

        $papers[] = [
            'title' => strip_tags($item['title'][0] ?? 'Untitled paper'),
            'authors' => implode(', ', $authors) ?: 'Authors not provided',
            'abstract' => trim(html_entity_decode(strip_tags($item['abstract'] ?? ''), ENT_QUOTES, 'UTF-8')),
            'date' => $date ? implode('-', $date) : 'Date not provided',
            'categories' => ucfirst(str_replace('-', ' ', $item['type'] ?? 'Research paper')),
            'venue' => strip_tags($item['container-title'][0] ?? ''),
            'url' => 'https://doi.org/' . str_replace('%2F', '/', rawurlencode($doi)),
            'pdf' => $pdf
        ];
    }

    return [
        'papers' => $papers,
        'total' => (int) $data['message']['total-results'],
        'fetched_at' => time()
    ];
}

function fetch_crossref_papers($topic, $search, $page)
{
    $query = crossref_query($topic, $search);
    $page = max(1, min(100, (int) $page));
    $folder = __DIR__ . '/cache';
    $cached = null;
    $lock = false;

    try {
        if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
            throw new RuntimeException('Could not create the cache folder.');
        }

        $file = $folder . '/' . hash('sha256', 'crossref-v1|' . $query . '|' . $page) . '.json';

        if (is_file($file)) {
            $cached = json_decode(file_get_contents($file), true);
            if (!is_array($cached) || !isset($cached['papers'], $cached['total'], $cached['fetched_at'])) {
                $cached = null;
            }
        }

        if ($cached && time() - $cached['fetched_at'] < 1800) {
            return $cached + ['message' => '', 'failed' => false];
        }

        if (!function_exists('curl_init')) {
            throw new RuntimeException('Enable the PHP curl extension.');
        }

        $lock = fopen($folder . '/request.lock', 'c+');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('Another Crossref request is running.');
        }

        $next_request = (int) stream_get_contents($lock);
        if (time() < $next_request) {
            throw new RuntimeException('Waiting before the next Crossref request.');
        }

        $url = 'https://api.crossref.org/works?' . http_build_query([
            'query.bibliographic' => $query,
            'offset' => ($page - 1) * 10,
            'rows' => 10,
            'sort' => 'score',
            'order' => 'desc',
            'filter' => 'type:journal-article,until-pub-date:' . date('Y-m-d'),
            'select' => 'DOI,title,author,abstract,published,type,container-title,link'
        ]);

        rewind($lock);
        ftruncate($lock, 0);
        fwrite($lock, (string) (time() + 60));
        fflush($lock);

        $request = curl_init($url);
        curl_setopt_array($request, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_USERAGENT => 'UIUResearchPortal/1.0 (CSE research discovery)'
        ]);

        $json = curl_exec($request);
        $status = curl_getinfo($request, CURLINFO_HTTP_CODE);
        $error = curl_error($request);
        curl_close($request);

        if ($json === false || $status !== 200) {
            throw new RuntimeException('Crossref HTTP ' . $status . ': ' . $error);
        }

        $result = parse_crossref_response($json);
        file_put_contents($file, json_encode($result), LOCK_EX);

        rewind($lock);
        ftruncate($lock, 0);
        fwrite($lock, (string) (time() + 3));
        fflush($lock);

        foreach (glob($folder . '/*.json') as $old_file) {
            if (filemtime($old_file) < time() - 86400) {
                unlink($old_file);
            }
        }

        return $result + ['message' => '', 'failed' => false];
    } catch (Throwable $error) {
        error_log('Crossref: ' . $error->getMessage());

        if ($cached) {
            return $cached + [
                'message' => 'Crossref is temporarily unavailable. Showing previously fetched results.',
                'failed' => false
            ];
        }

        return [
            'papers' => [],
            'total' => 0,
            'fetched_at' => null,
            'failed' => true,
            'message' => 'Could not load Crossref papers right now. Please try again in a minute, or browse UIU Papers.'
        ];
    } finally {
        if (is_resource($lock)) {
            fclose($lock);
        }
    }
}
