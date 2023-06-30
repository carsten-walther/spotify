<?php

namespace CarstenWalther\Spotify\Resource\OnlineMedia\Helpers;

use JsonException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\AbstractOEmbedHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SpotifyHelper
 *
 * @package CarstenWalther\Spotify\Resource\OnlineMedia\Helpers
 */
class SpotifyHelper extends AbstractOEmbedHelper
{
    /**
     * @param string $url
     * @param Folder $targetFolder
     *
     * @return File|null
     */
    public function transformUrlToFile($url, Folder $targetFolder): ?File
    {
        $mediaId = null;

        if (preg_match('/^https?:\/\/open\.spotify\.com\/embed\/playlist\/(.*)/i', $url, $match)) {
            $mediaId = $match[1];
        } else if (preg_match('/^https?:\/\/open\.spotify\.com\/embed\/album\/(.*)/i', $url, $match)) {
            $mediaId = $match[1];
        } else if (preg_match('/^https?:\/\/open\.spotify\.com\/embed-podcast\/episode\/(.*)/i', $url, $match)) {
            $mediaId = $match[1];
        } else if (preg_match('/^https?:\/\/open\.spotify\.com\/show\/(.*)/i', $url, $match)) {
            $mediaId = $match[1];
        }

        if (empty($mediaId)) {
            return null;
        }

        return $this->transformMediaIdToFile($mediaId, $targetFolder, $this->extension);
    }

    /**
     * @param File $file
     *
     * @return string
     * @throws JsonException
     */
    public function getPreviewImage(File $file): string
    {
        $mediaId = $this->getOnlineMediaId($file);
        $temporaryFileName = $this->getTempFolderPath() . 'spotify_' . md5($mediaId) . '.jpg';

        if (!file_exists($temporaryFileName)) {
            $tryNames = ['maxresdefault.jpg', 'mqdefault.jpg', '0.jpg'];
            foreach ($tryNames as $tryName) {

                $emmbedUrl = $this->getOEmbedUrl($mediaId);
                $data = GeneralUtility::getUrl($emmbedUrl);
                $jsonData = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

                $previewImage = GeneralUtility::getUrl($jsonData['thumbnail_url']);
                if ($previewImage !== false) {
                    file_put_contents($temporaryFileName, $previewImage);
                    GeneralUtility::fixPermissions($temporaryFileName);
                    break;
                }
            }
        }

        return $temporaryFileName;
    }

    /**
     * @param File $file
     *
     * @return array
     */
    public function getMetaData(File $file): array
    {
        $metadata = [];

        $oEmbed = $this->getOEmbedData($this->getOnlineMediaId($file));

        if ($oEmbed) {
            $metadata['width'] = (int)$oEmbed['width'];
            $metadata['height'] = (int)$oEmbed['height'];
            if (empty($file->getProperty('title'))) {
                $metadata['title'] = strip_tags($oEmbed['title']);
            }
            $metadata['author'] = $oEmbed['provider_name'];
        }

        return $metadata;
    }

    /**
     * @param File $file
     * @param bool $relativeToCurrentScript
     *
     * @return string|NULL
     */
    public function getPublicUrl(File $file, bool $relativeToCurrentScript = false): ?string
    {
        $mediaId = $this->getOnlineMediaId($file);
        return sprintf(
            'https://open.spotify.com/embed/show/%s?utm_source=oembed',
            rawurlencode($mediaId)
        );
    }

    /**
     * @param string $mediaId
     * @param string $format
     *
     * @return string
     */
    protected function getOEmbedUrl($mediaId, $format = 'json'): string
    {
        return sprintf(
            'https://open.spotify.com/oembed?url=%s',
            rawurlencode(sprintf('https://open.spotify.com/show/%s', rawurlencode($mediaId))),
        );
    }
}
