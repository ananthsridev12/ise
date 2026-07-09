<?php
class NewsFetcher {
    public static function fetchGoogleNews($companyName, $country = 'US') {
        $cl = strtolower($country);
        $gl = ($cl === 'in') ? 'IN' : (($cl === 'au') ? 'AU' : (in_array($cl, ['uk','gb']) ? 'GB' : 'US'));
        $q = urlencode('"' . $companyName . '" (merger OR acquisition OR expansion OR "digital transformation" OR "ERP" OR "SAP" OR hiring OR "new plant" OR contract)');
        $url = "https://news.google.com/rss/search?q={$q}&hl=en-{$gl}&gl={$gl}&ceid={$gl}:en";
        return self::parseRSS($url, 'GoogleNews');
    }

    public static function fetchIndeedJobs($companyName, $country = 'US') {
        $q = urlencode($companyName);
        $url = "https://www.indeed.com/rss?q={$q}&sort=date&limit=20";
        return self::parseRSS($url, 'Indeed');
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
