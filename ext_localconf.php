<?php

defined('TYPO3') or die();

// Add spotify to allowed mediafile extensions
$GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] .= ',spotify';

// Add spotify helper
$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers']['spotify'] = \CarstenWalther\Spotify\Resource\OnlineMedia\Helpers\SpotifyHelper::class;

// Add spotify as own mimetype
$GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['spotify'] = 'audio/spotify';

// register file extension
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerFileExtension('spotify', 'mimetypes-media-audio-spotify');
unset($iconRegistry);

// ImageRenderer/Helper
$rendererRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::class);
$rendererRegistry->registerRendererClass(\CarstenWalther\Spotify\Resource\Rendering\SpotifyRenderer::class);
unset($rendererRegistry);
