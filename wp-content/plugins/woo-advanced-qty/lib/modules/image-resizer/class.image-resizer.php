<?php namespace MTTWordPressTheme\Lib\Modules\ImageResizer;

use MTTWordPressTheme\Lib\Tools\Loader;

class ImageResizer {

	public function __construct() {
		$this->registerFilters();
	}

	public function registerFilters() {
		Loader::addFilter('intermediate_image_sizes_advanced', $this, 'preventThumbnailGeneration');
		Loader::addFilter('image_downsize', $this, 'getImage', 10, 3);
	}

	public function preventThumbnailGeneration() {
		return array();
	}

	public function getImage($downsize, $id, $size) {
		$size = $this->getIntermediateSize($id, $size);

		$meta = \wp_get_attachment_metadata($id);

		if(!empty($meta)) {
			// If size exists
			if($size === 'full') {
				$file = $meta['file'];
				$width = $meta['width'];
				$height = $meta['height'];
			} elseif(is_string($size) && $intermediate = \image_get_intermediate_size($id, $size)) {
				$file = $intermediate['file'];
				$width = $intermediate['width'];
				$height = $intermediate['height'];
			} else {
				if(is_string($size)) {
					$_size = $size;
					$size = $GLOBALS['_wp_additional_image_sizes'][$_size];
					$size['name'] = $_size;
				}
				// Create image
				$image_path = \get_attached_file($id);
				if($created_image = \image_make_intermediate_size($image_path, $size['width'], $size['height'], $size['crop'])) {
					$meta = \wp_get_attachment_metadata($id);

					$meta['sizes'][$size['name']] = $created_image;

					\wp_update_attachment_metadata($id, $meta);

					$file = $created_image['file'];
					$width = $created_image['width'];
					$height = $created_image['height'];
				}
			}

			if(!empty($file)) {
				if(is_array($size) && isset($size['name'])) {
					$name = $size['name'];
				} else {
					$name = $size;
				}
				list($width, $height) = image_constrain_size_for_editor($width, $height, $name);

				$full_url = \wp_get_attachment_url($id);
				$img_url_basename = \wp_basename($full_url);
				$file_basename = \wp_basename($file);

				$img_url = str_replace($img_url_basename, $file_basename, $full_url);

				return array($img_url, $width, $height, true);
			}
		}
		return $downsize;
	}

	public function getIntermediateSize($id, $size) {
		$meta = wp_get_attachment_metadata($id);

		if(!empty($meta)) {
			if(is_array($size) && ((isset($size[0]) || isset($size['width'])) || isset($size['height']))) {
				/* WIDTH */
				$width = null;
				if(isset($size['width'])) {
					$width = $size['width'];
				} elseif(isset($size[0])) {
					$width = $size[0];
				}
				$calculated_width = $width;

				/* HEIGHT */
				$height = null;
				if(isset($size['height'])) {
					$height = $size['height'];
				} elseif(isset($size[1])) {
					$height = $size[1];
				}
				$calculated_height = $height;

				/* CROP */
				$crop = true;
				if(isset($size['crop'])) {
					$crop = $size['crop'];
				} elseif(isset($size[2])) {
					$crop = $size[2];
				}

				if(!$crop) {
					list($calculated_width, $calculated_height) = \wp_constrain_dimensions($meta['width'], $meta['height'], $width, $height);
				}

				foreach($meta['sizes'] as $key => $existing_size) {
					if($existing_size['width'] == $calculated_width && $existing_size['height'] == $calculated_height) {
						return $key;
					}
				}

				$name = $width . 'x' . $height;
			} else {
				if(isset($meta['sizes'][$size])) {
					return $size;
				}
				if($new_size = $this->get_image_size($size)) {
					return $new_size;
				}
				return 'full';
			}

			return array(
				'name' => $name,
				'width' => $width,
				'height' => $height,
				'crop' => $crop
			);
		}

		return false;
	}

	public function get_image_sizes() {
		global $_wp_additional_image_sizes;

		$sizes = array();

		foreach(\get_intermediate_image_sizes() as $size) {
			if(in_array($size, array('thumbnail', 'medium', 'medium_large', 'large'))) {
				$sizes[$size] = array(
					'name' => $size,
					'width' => (int) \get_option("{$size}_size_w"),
					'height' => (int) \get_option("{$size}_size_h"),
					'crop' => (bool) \get_option("{$size}_crop")
				);
			} elseif(isset($_wp_additional_image_sizes[$size])) {
				$sizes[$size] = array(
					'name' => $size,
					'width' => (int) $_wp_additional_image_sizes[$size]['width'],
					'height' => (int) $_wp_additional_image_sizes[$size]['height'],
					'crop' => (bool) $_wp_additional_image_sizes[$size]['crop']
				);
			}
		}

		return $sizes;
	}

	public function get_image_size($name) {
		$sizes = $this->get_image_sizes();

		if(isset($sizes[$name])) {
			return $sizes[$name];
		}

		return false;
	}
}