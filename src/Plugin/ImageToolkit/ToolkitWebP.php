<?php
namespace Drupal\toolkit_gd_webp\Plugin\ImageToolkit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\ImageToolkit\ImageToolkitBase;
use Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\system\Plugin\ImageToolkit\GDToolkit;
use Psr\Log\LoggerInterface;

/**
 * Defines the GD2 toolkit for image manipulation within Drupal.
 *
 * @ImageToolkit(
 *   id = "gd",
 *   title = @Translation("GD2 image manipulation toolkit with webp")
 * )
 */
class ToolKitWebP extends GDToolkit
{

    /**
     *
     * {@inheritdoc}
     * @see \Drupal\system\Plugin\ImageToolkit\GDToolkit::save()
     */
    public function save($destination)
    {
        parent::save($destination);
        $scheme = \Drupal\Core\File\FileSystem::uriScheme($destination);
        // Work around lack of stream wrapper support in imagejpeg() and imagepng().
        if ($scheme && file_stream_wrapper_valid_scheme($scheme)) {
            // If destination is not local, save image to temporary local file.
            $local_wrappers = $this->streamWrapperManager->getWrappers(StreamWrapperInterface::LOCAL);
            if (! isset($local_wrappers[$scheme])) {
                $permanent_destination = $destination . '.webp';
                $destination = drupal_tempnam('temporary://', 'gd_');
            }
            // Convert stream wrapper URI to normal path.
            $destination = \Drupal::service('file_system')->realpath($destination) . '.webp';
        }
        
        if ($this->getType() == IMAGETYPE_JPEG) {
            $success = imagewebp($this->getResource(), $destination, $this->configFactory->get('system.image.gd')->get('jpeg_quality'));
        } else {
            // Always save PNG images with full transparency.
            if ($this->getType() == IMAGETYPE_PNG) {
                imagealphablending($this->getResource(), FALSE);
                imagesavealpha($this->getResource(), TRUE);
            }
            $success = imagewebp($this->getResource(), $destination);
        }
        $this->logger->debug('Webp aussi...');
        return $success;
    }
}