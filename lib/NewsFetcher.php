<?php
class NewsFetcher {
    public static function fetchGoogleNews($companyName, $country = 'US') {
        $cl = strtolower($country);
        $gl = ($cl === 'in') ? 'IN' : (($cl === 'au') ? 'AU' : (in_array($cl, ['uk','gb']) ? 'GB' : 'US'));
        $q = urlencode('"' . $companyName . '" (merger OR acquisition OR expansion OR "digital transformation" OR "ERP" OR "SAP" OR hiring OR "new plant" OR contract)');
        $url = "https://news.google.com/rss/search?q={$q}&hl=en-{$gl}&gl={$gl}&ceid={$gl}:en";
        return self::parseRSS($url, 'GoogleNews');
    }

    public static function fetchAdzunaJobs($companyName, $country = 'US') {
        if (!ADZUNA_APP_ID || !ADZUNA_APP_KEY) return [];

        $countryMap = ['us' => 'us', 'in' => 'in', 'gb' => 'gb', 'uk' => 'gb', 'au' => 'au'];
        $cl = strtolower($country);
        $cc = isset($countryMap[$cl]) ? $countryMap[$cl] : 'us';

        // Use 'company' param so we only get jobs posted BY this company, not jobs mentioning it
        $company = urlencode($companyName);
        $url = 'https://api.adzuna.com/v1/api/jobs/' . $cc . '/search/1'
             . '?app_id=' . ADZUNA_APP_ID
             . '&app_key=' . ADZUNA_APP_KEY
             . '&company=' . $company
             . '&results_per_page=20'
             . '&content-type=application/json';

        $ctx = stream_context_create(['http' => [
            'timeout'    => 15,
            'user_agent' => 'Mozilla/5.0 (compatible; ISE/1.0)',
            'header'     => "Accept: application/json\r\n",
        ]]);

        $raw = @file_get_contents($url, false, $ctx);
        if (!$raw) return [];

        $data = json_decode($raw, true);
        if (!isset($data['results'])) return [];

        $items = [];
        foreach ($data['results'] as $job) {
            $items[] = [
                'title'          => isset($job['title'])        ? $job['title']        : '',
                'snippet'        => isset($job['description'])  ? strip_tags($job['description']) : '',
                'url'            => isset($job['redirect_url']) ? $job['redirect_url'] : '',
                'published_date' => isset($job['created'])      ? $job['created']      : '',
                'source'         => 'Adzuna',
            ];
        }
        return array_slice($items, 0, 20);
    }

    private static function parseRSS($url, $source) {
        $ctx = stream_context_create(['http' => [
            'timeout'    => 15,
            'user_agent' => 'Mozilla/5.0 (compatible; ISE/1.0)',
            'header'     => "Accept: application/rss+xml, application/xml, text/xml\r\n",
        ]]);

        $xml = @file_get_contents($url, false, $ctx);
        if (!$xml) return [];

        $feed = @simplexml_load_string($xml);
        if (!$feed) return [];

        $items = [];
        $channel = isset($feed->channel) ? $feed->channel : $feed;
        foreach ($channel->item as $item) {
            $items[] = [
                'title'          => (string)(isset($item->title)       ? $item->title       : ''),
                'snippet'        => strip_tags((string)(isset($item->description) ? $item->description : '')),
                'url'            => (string)(isset($item->link)        ? $item->link        : ''),
                'published_date' => (string)(isset($item->pubDate)     ? $item->pubDate     : ''),
                'source'         => $source,
            ];
        }
        return array_slice($items, 0, 15);
    }
}
