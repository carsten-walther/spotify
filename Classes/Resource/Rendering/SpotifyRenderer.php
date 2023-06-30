<?php

namespace CarstenWalther\Spotify\Resource\Rendering;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SpotifyRenderer
 *
 * @package CarstenWalther\Spotify\Resource\Rendering
 */
class SpotifyRenderer implements FileRendererInterface
{
    /**
     * @var bool|OnlineMediaHelperInterface
     */
    protected OnlineMediaHelperInterface|bool $onlineMediaHelper = false;

    /**
     * Returns the priority of the renderer
     * This way it is possible to define/overrule a renderer
     * for a specific file type/context.
     * For example create a video renderer for a certain storage/driver type.
     * Should be between 1 and 100, 100 is more important than 1
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 100;
    }

    /**
     * @param FileInterface $file
     *
     * @return bool
     */
    public function canRender(FileInterface $file): bool
    {
        return ($file->getMimeType() === 'audio/spotify' || $file->getExtension() === 'spotify') && $this->getOnlineMediaHelper($file) !== false;
    }

    /**
     * @param FileInterface $file
     *
     * @return bool|OnlineMediaHelperInterface
     */
    protected function getOnlineMediaHelper(FileInterface $file): bool|OnlineMediaHelperInterface
    {
        if (!$this->onlineMediaHelper) {
            $orgFile = $file;
            if ($orgFile instanceof FileReference) {
                $orgFile = $orgFile->getOriginalFile();
            }
            if ($orgFile instanceof File) {
                $this->onlineMediaHelper = GeneralUtility::makeInstance(OnlineMediaHelperRegistry::class)->getOnlineMediaHelper($orgFile);
            } else {
                $this->onlineMediaHelper = false;
            }
        }
        return $this->onlineMediaHelper;
    }

    /**
     * @param FileInterface $file
     * @param int|string $width
     * @param int|string $height
     * @param array $options
     * @param bool $usedPathsRelativeToCurrentScript
     *
     * @return string
     */
    public function render(
        FileInterface $file,
        $width,
        $height,
        array $options = [],
        bool $usedPathsRelativeToCurrentScript = false
    ): string {
        $options = $this->collectOptions($options, $file);
        $src = $this->createSpotifyUrl($options, $file);
        $attributes = $this->collectIframeAttributes($width, $height, $options);

        return sprintf(
            '<iframe src="%s" %s></iframe>',
            htmlspecialchars($src, ENT_QUOTES | ENT_HTML5),
            empty($attributes) ? '' : ' ' . $this->implodeAttributes($attributes)
        );
    }

    /**
     * @param array $options
     * @param FileInterface $file
     *
     * @return array
     */
    protected function collectOptions(array $options, FileInterface $file): array
    {
        return $options;
    }

    /**
     * @param array $options
     * @param FileInterface $file
     * @return string
     */
    protected function createSpotifyUrl(array $options, FileInterface $file): string
    {
        $mediaId = $this->getMediaIdFromFile($file);
        return sprintf('https://open.spotify.com/embed/show/%s?utm_source=oembed', $mediaId);
    }

    /**
     * @param FileInterface $file
     *
     * @return string
     */
    protected function getMediaIdFromFile(FileInterface $file): string
    {
        if ($file instanceof FileReference) {
            $orgFile = $file->getOriginalFile();
        } else {
            $orgFile = $file;
        }
        return $this->getOnlineMediaHelper($file)->getOnlineMediaId($orgFile);
    }

    /**
     * @param int|string $width
     * @param int|string $height
     * @param array $options
     *
     * @return array
     */
    protected function collectIframeAttributes($width, $height, array $options): array
    {
        $attributes = [];
        $attributes['allowfullscreen'] = true;

        if (isset($options['additionalAttributes']) && is_array($options['additionalAttributes'])) {
            $attributes = array_merge($attributes, $options['additionalAttributes']);
        }

        if (isset($options['data']) && is_array($options['data'])) {
            array_walk($options['data'], function (&$value, $key) use (&$attributes) {
                $attributes['data-' . $key] = $value;
            });
        }

        if ((int)$width > 0) {
            $attributes['width'] = (int)$width;
        }

        if ((int)$height > 0) {
            $attributes['height'] = (int)$height;
        }

        if (isset($GLOBALS['TSFE'], $GLOBALS['TSFE']->config['config']['doctype']) && is_object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->config['config']['doctype'] !== 'html5') {
            $attributes['frameborder'] = 0;
        }

        foreach (['class', 'dir', 'id', 'lang', 'style', 'title', 'accesskey', 'tabindex', 'onclick', 'poster', 'preload', 'allow'] as $key) {
            if (!empty($options[$key])) {
                $attributes[$key] = $options[$key];
            }
        }

        return $attributes;
    }

    /**
     * @param array $attributes
     *
     * @return string
     */
    protected function implodeAttributes(array $attributes): string
    {
        $attributeList = [];

        foreach ($attributes as $name => $value) {
            $name = preg_replace('/[^\p{L}0-9_.-]/u', '', $name);

            if ($value === true) {
                $attributeList[] = $name;
            } else {
                $attributeList[] = $name . '="' . htmlspecialchars($value, ENT_QUOTES | ENT_HTML5) . '"';
            }
        }

        return implode(' ', $attributeList);
    }
}
