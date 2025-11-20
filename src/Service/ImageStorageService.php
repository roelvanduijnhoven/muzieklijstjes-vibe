<?php

namespace App\Service;

use Aws\S3\S3Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ImageStorageService
{
    public function __construct(
        private S3Client $s3Client,
        private HttpClientInterface $httpClient,
        #[Autowire('%env(DO_SPACES_BUCKET)%')]
        private string $bucketName,
    ) {
    }

    public function fetchAndStore(string $imageUrl, string $destinationPath): bool
    {
        try {
            // 1. Download Image
            $response = $this->httpClient->request('GET', $imageUrl);
            if ($response->getStatusCode() !== 200) {
                return false;
            }
            $imageContent = $response->getContent();

            // 2. Resize Image (Max 300x300)
            $resizedContent = $this->resizeImage($imageContent, 300, 300);
            if (!$resizedContent) {
                return false;
            }

            // 3. Upload to S3/Dropbox
            $this->s3Client->putObject([
                'Bucket' => $this->bucketName,
                'Key'    => $destinationPath,
                'Body'   => $resizedContent,
                'ACL'    => 'public-read', // Assuming we want them public
                'ContentType' => 'image/jpeg',
            ]);

            return true;

        } catch (\Exception $e) {
            // Log error
            return false;
        }
    }

    private function resizeImage(string $imageContent, int $maxWidth, int $maxHeight): ?string
    {
        $src = @imagecreatefromstring($imageContent);
        if (!$src) {
            return null;
        }

        $width = imagesx($src);
        $height = imagesy($src);

        // Calculate new dimensions keeping aspect ratio
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        
        // If image is smaller than target, don't upscale
        if ($ratio >= 1) {
            $newWidth = $width;
            $newHeight = $height;
        } else {
            $newWidth = (int) ($width * $ratio);
            $newHeight = (int) ($height * $ratio);
        }

        $dst = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG/GIF if needed, but we'll convert to JPG
        // So fill with white background
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $white);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagejpeg($dst, null, 85); // 85% quality
        $data = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $data ?: null;
    }
}

