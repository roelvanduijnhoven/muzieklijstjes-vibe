<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MusicBrainzService
{
    private const USER_AGENT = 'RegisterVibe/1.0 ( roel@register-vibe.com )'; // TODO: Use configurable contact info

    public function __construct(
        private HttpClientInterface $client
    ) {
    }

    public function getCoverArtUrl(string $artistName, string $albumTitle): ?string
    {
        // 1. Search for Release Group
        $mbid = $this->searchReleaseGroup($artistName, $albumTitle);

        if (!$mbid) {
            return null;
        }

        // 2. Get Cover Art from Cover Art Archive
        return $this->fetchCoverArtUrl($mbid);
    }

    private function searchReleaseGroup(string $artistName, string $albumTitle): ?string
    {
        // Rate limit: MusicBrainz allows ~1 req/sec.
        // In a real background worker, we might want a more robust rate limiter,
        // but for this command, a simple sleep is effective enough.
        sleep(1); 

        $query = sprintf('artist:"%s" AND release:"%s" AND primarytype:Album', $artistName, $albumTitle);
        
        try {
            $response = $this->client->request('GET', 'https://musicbrainz.org/ws/2/release-group', [
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'query' => $query,
                    'fmt' => 'json',
                    'limit' => 1,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray();
            
            if (empty($data['release-groups'])) {
                return null;
            }

            // Basic fuzzy check could go here, but we'll trust the top result for now
            return $data['release-groups'][0]['id'] ?? null;

        } catch (\Exception $e) {
            // Log error?
            return null;
        }
    }

    private function fetchCoverArtUrl(string $mbid): ?string
    {
        // Cover Art Archive does not have the same strict rate limits as MusicBrainz API,
        // but we should still be polite.
        sleep(1);

        $url = sprintf('https://coverartarchive.org/release-group/%s/front', $mbid);

        try {
            // We just want to check if it exists and get the final URL (it redirects)
            $response = $this->client->request('GET', $url, [
                'max_redirects' => 5,
            ]);

            if ($response->getStatusCode() === 200) {
                // The response content is the image itself. 
                // But we want to return the URL so we can download/process it in the other service.
                // Actually, since we already made the request, we might as well return the content or the info.
                // But strictly, the user asked for a URL to download.
                // Let's just return the URL we constructed, assuming it works if 200 OK.
                // Or better, return the effective URL after redirects.
                return $response->getInfo('url');
            }
        } catch (\Exception $e) {
            // 404 means no cover art
        }

        return null;
    }
}

