<?php


// ==========================================
// Crossref Topics
// ==========================================

function crossref_topics()
{
    return [
        'Computer Science',
        'Artificial Intelligence',
        'Machine Learning',
        'Computer Vision',
        'Natural Language Processing',
        'Cybersecurity',
        'Software Engineering',
        'Networking',
        'Databases',
        'Algorithms and Data Structures',
        'Robotics'
    ];
}



// ==========================================
// Get Papers From Crossref
// ==========================================

function fetch_crossref_papers($topic, $search, $page)
{

    // Make search text
    $query = $topic . ' ' . $search;


    // Number of papers per page
    $rows = 10;


    // Calculate starting position
    $offset = ($page - 1) * $rows;


    // Crossref API URL
    $url = "https://api.crossref.org/works"
         . "?query.bibliographic=" . urlencode($query)
         . "&rows=" . $rows
         . "&offset=" . $offset;


    // Start CURL
    $ch = curl_init();


    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


    // Get data from Crossref
    $response = curl_exec($ch);


    // Close CURL
    curl_close($ch);


    // If API does not return anything
    if (!$response) {

        return [
            'papers' => [],
            'total' => 0,
            'message' => 'Could not load papers.',
            'failed' => true
        ];
    }


    // Convert JSON into PHP array
    $data = json_decode($response, true);


    // Check Crossref result
    if (!isset($data['message']['items'])) {

        return [
            'papers' => [],
            'total' => 0,
            'message' => 'No papers found.',
            'failed' => true
        ];
    }


    $papers = [];


    // ==========================================
    // Read Every Paper
    // ==========================================

    foreach ($data['message']['items'] as $item) {


        // --------------------------
        // Paper Title
        // --------------------------

        $title = 'Untitled Paper';

        if (isset($item['title'][0])) {

            $title = $item['title'][0];
        }



        // --------------------------
        // Authors
        // --------------------------

        $authorNames = [];


        if (isset($item['author'])) {

            foreach ($item['author'] as $author) {

                $name = '';


                if (isset($author['given'])) {

                    $name = $author['given'];
                }


                if (isset($author['family'])) {

                    $name = $name . ' ' . $author['family'];
                }


                $authorNames[] = $name;
            }
        }


        $authors = implode(', ', $authorNames);


        if ($authors == '') {

            $authors = 'Authors not provided';
        }



        // --------------------------
        // Published Date
        // --------------------------

        $date = 'Date not provided';


        if (isset($item['published']['date-parts'][0][0])) {

            $date = $item['published']['date-parts'][0][0];
        }



        // --------------------------
        // Abstract
        // --------------------------

        $abstract = 'Abstract not provided';


        if (isset($item['abstract'])) {

            $abstract = strip_tags($item['abstract']);
        }



        // --------------------------
        // DOI Link
        // --------------------------

        $paperUrl = '#';


        if (isset($item['DOI'])) {

            $paperUrl = 'https://doi.org/' . $item['DOI'];
        }



        // --------------------------
        // Journal / Venue
        // --------------------------

        $venue = '';


        if (isset($item['container-title'][0])) {

            $venue = $item['container-title'][0];
        }



        // --------------------------
        // Paper Type
        // --------------------------

        $category = 'Research Paper';


        if (isset($item['type'])) {

            $category = $item['type'];
        }



        // Add paper into array
        $papers[] = [

            'title' => $title,

            'authors' => $authors,

            'abstract' => $abstract,

            'date' => $date,

            'categories' => $category,

            'venue' => $venue,

            'url' => $paperUrl,

            'pdf' => ''
        ];
    }



    // ==========================================
    // Total Results
    // ==========================================

    $total = 0;


    if (isset($data['message']['total-results'])) {

        $total = $data['message']['total-results'];
    }



    // ==========================================
    // Return Result
    // ==========================================

    return [

        'papers' => $papers,

        'total' => $total,

        'message' => '',

        'failed' => false,

        'fetched_at' => time()
    ];
}

?>