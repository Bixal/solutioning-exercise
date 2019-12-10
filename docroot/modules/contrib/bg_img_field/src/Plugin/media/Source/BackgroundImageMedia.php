<?php
namespace Drupal\bg_img_field\Plugin\media\Source;

use Drupal\media\Plugin\media\Source\Image;

/**
 * Image entity media source.
 *
 * @see \Drupal\Core\Image\ImageInterface
 *
 * @MediaSource(
 *   id = "bg_img_media_field",
 *   label = @Translation("Background Image"),
 *   description = @Translation("Use local images for reusable media."),
 *   allowed_field_types = {"bg_img_field"},
 *   default_thumbnail_filename = "no-thumbnail.png",
 *   thumbnail_alt_metadata_attribute = "thumbnail_alt_value"
 * )
 */
class BackgroundImageMedia extends Image {

}