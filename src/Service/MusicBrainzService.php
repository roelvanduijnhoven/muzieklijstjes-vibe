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

    public function searchArtist(string $artistName): ?string
    {
        sleep(1); // Rate limit

        try {
            $response = $this->client->request('GET', 'https://musicbrainz.org/ws/2/artist', [
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'query' => sprintf('artist:"%s"', $artistName),
                    'fmt' => 'json',
                    'limit' => 5,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray();

            if (empty($data['artists'])) {
                return null;
            }

            // Look for an exact match (case-insensitive) OR a very high score
            foreach ($data['artists'] as $artist) {
                // 1. Exact name match
                if (strcasecmp($artist['name'], $artistName) === 0) {
                    return $artist['id'];
                }

                // 2. High score match (MusicBrainz usually returns score: "100" for exact matches)
                if (isset($artist['score']) && (int)$artist['score'] >= 95) {
                    return $artist['id'];
                }
            }
            
            // If no exact match or high score, just return the first one if it's somewhat relevant?
            // For now, let's stick to safer matching. But maybe we can be a bit more lenient.
            // If the query was specific (artist:"Name"), the first result is usually the best one.
            if (!empty($data['artists'][0])) {
                 return $data['artists'][0]['id'];
            }
            
            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    public function searchAlbumByArtist(string $artistMbid, string $albumTitle): ?string
    {
        sleep(1); // Rate limit

        // Lucene query syntax for MusicBrainz
        // arid:<uuid> AND releasegroup:<title> AND primarytype:Album
        // We use releasegroup (rg) search because we want the abstract album, not a specific CD release.
        $query = sprintf('arid:%s AND releasegroup:"%s" AND primarytype:Album', $artistMbid, $albumTitle);

        try {
            $response = $this->client->request('GET', 'https://musicbrainz.org/ws/2/release-group', [
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'query' => $query,
                    'fmt' => 'json',
                    'limit' => 5,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray();

            if (empty($data['release-groups'])) {
                return null;
            }

            foreach ($data['release-groups'] as $group) {
                // 1. Exact title match
                if (strcasecmp($group['title'], $albumTitle) === 0) {
                    return $group['id'];
                }

                // 2. High score match
                if (isset($group['score']) && (int)$group['score'] >= 90) {
                    return $group['id'];
                }
                
                // 3. Similar name match using levenshtein if available, or just trust the score.
                // MusicBrainz search is usually good.
            }

            // Fallback: Return first result if it looks reasonably close (e.g. part of the title matches)
            // Or just return the first one as "best guess" like we did for Artist.
            if (!empty($data['release-groups'][0])) {
                return $data['release-groups'][0]['id'];
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
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

    public function fetchCoverArtUrl(string $mbid): ?string
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

