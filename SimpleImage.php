<?php

/*
* File: SimpleImage.php
* Author: Simon Jarvis
* Copyright: 2006 Simon Jarvis
* Date: 08/11/06
* Link: http://www.white-hat-web-design.co.uk/blog/resizing-images-with-php/
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details:
* http://www.gnu.org/licenses/gpl.html
*
*/

class SimpleImage {

   var $image;
   var $image_type;
   public      $quality = 80;
   protected $filename, $original_info, $width, $height;

   function load($filename) {

      $image_info = getimagesize($filename);
      $this->image_type = $image_info[2];
      if( $this->image_type == IMAGETYPE_JPEG ) {
         $this->image = imagecreatefromjpeg($filename);
      } elseif( $this->image_type == IMAGETYPE_GIF ) {
         $this->image = imagecreatefromgif($filename);
      } elseif( $this->image_type == IMAGETYPE_PNG ) {
         $this->image = imagecreatefrompng($filename);
      }

      $this->original_info = array(
         'width'       => $image_info[0],
         'height'      => $image_info[1],
         'orientation' => $this->get_orientation(),
         'exif'        => function_exists('exif_read_data') && $image_info['mime'] === 'image/jpeg' ? $this->exif = @exif_read_data($this->filename) : null,
         'format'      => preg_replace('/^image\//', '', $image_info['mime']),
         'mime'        => $image_info['mime']
      );
      $this->width   = $image_info[0];
      $this->height  = $image_info[1];
      imagesavealpha($this->image, true);
      imagealphablending($this->image, true);
      return $this;
   }

   function save($filename, $waterMark=null, $compression=75, $permissions=null) {
      $image_type=$this->image_type;
      if ($waterMark!=null) {
         $im = imagecreate(100, 100);
         $text_color = imagecolorallocate($im, 255, 0, 0);
         imagestring($this->image, 1, 10, 105,  $waterMark, $text_color);
      }
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image,$filename,$compression);
      } elseif( $image_type == IMAGETYPE_GIF ) {

         imagegif($this->image,$filename);
      } elseif( $image_type == IMAGETYPE_PNG ) {

         imagepng($this->image,$filename);
      }
      if( $permissions != null) {

         chmod($filename,$permissions);
      }
   }
   function output($image_type=IMAGETYPE_JPEG) {

      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image);
      } elseif( $image_type == IMAGETYPE_GIF ) {

         imagegif($this->image);
      } elseif( $image_type == IMAGETYPE_PNG ) {

         imagepng($this->image);
      }
   }
   function getWidth() {

      return imagesx($this->image);
   }
   function getHeight() {

      return imagesy($this->image);
   }
   function resizeToHeight($height) {

      $ratio = $height / $this->getHeight();
      $width = $this->getWidth() * $ratio;
      $this->resize($width,$height);
   }

   function resizeToWidth($width) {
      $ratio = $width / $this->getWidth();
      $height = $this->getheight() * $ratio;
      $this->resize($width,$height);
   }

   function scale($scale) {
      $width = $this->getWidth() * $scale/100;
      $height = $this->getheight() * $scale/100;
      $this->resize($width,$height);
   }

   function resize($width,$height,$forcesize='n') {
      if ($forcesize == 'n') {
         if ($width > $this->getWidth() && $height > $this->getHeight()){
             $width = $this->getWidth();
             $height = $this->getHeight();
         }
      }
      $new_image = imagecreatetruecolor($width, $height);
      if(($this->image_type == IMAGETYPE_GIF) || ($this->image_type==IMAGETYPE_PNG)){
         imagealphablending($new_image, false);
         imagesavealpha($new_image,true);
         $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
         imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
      }
      imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
      $this->image = $new_image; 
   }

   function overlay ($overlay_file, $position = 'center', $opacity = 1, $x_offset = 0, $y_offset = 0) {
      // Load overlay image
      $overlay = new SimpleImage($overlay_file);
      // Convert opacity
      $opacity = $opacity * 100;
      // Determine position
      switch (strtolower($position)) {
         case 'top left':
            $x = 0 + $x_offset;
            $y = 0 + $y_offset;
            break;
         case 'top right':
            $x = $this->width - $overlay->width + $x_offset;
            $y = 0 + $y_offset;
            break;
         case 'top':
            $x = ($this->width / 2) - ($overlay->width / 2) + $x_offset;
            $y = 0 + $y_offset;
            break;
         case 'bottom left':
            $x = 0 + $x_offset;
            $y = $this->height - $overlay->height + $y_offset;
            break;
         case 'bottom right':
            $x = $this->width - $overlay->width + $x_offset;
            $y = $this->height - $overlay->height + $y_offset;
            break;
         case 'bottom':
            $x = ($this->width / 2) - ($overlay->width / 2) + $x_offset;
            $y = $this->height - $overlay->height + $y_offset;
            break;
         case 'left':
            $x = 0 + $x_offset;
            $y = ($this->height / 2) - ($overlay->height / 2) + $y_offset;
            break;
         case 'right':
            $x = $this->width - $overlay->width + $x_offset;
            $y = ($this->height / 2) - ($overlay->height / 2) + $y_offset;
            break;
         case 'center':
         default:
            $x = ($this->width / 2) - ($overlay->width / 2) + $x_offset;
            $y = ($this->height / 2) - ($overlay->height / 2) + $y_offset;
            break;
      }
      $this->imagecopymerge_alpha($this->image, $overlay->image, $x, $y, 0, 0, $overlay->width, $overlay->height, $opacity);
      return $this;
   }

   function imagecopymerge_alpha ($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {
      $pct     /= 100;
      // Get image width and height
      $w       = imagesx($src_im);
      $h       = imagesy($src_im);
      // Turn alpha blending off
      imagealphablending($src_im, false);
      // Find the most opaque pixel in the image (the one with the smallest alpha value)
      $minalpha   = 127;
      for ($x = 0; $x < $w; $x++) {
         for ($y = 0; $y < $h; $y++) {
            $alpha   = (imagecolorat($src_im, $x, $y) >> 24) & 0xFF;
            if ($alpha < $minalpha) {
               $minalpha   = $alpha;
            }
         }
      }
      // Loop through image pixels and modify alpha for each
      for ($x = 0; $x < $w; $x++) {
         for ($y = 0; $y < $h; $y++) {
            // Get current alpha value (represents the TANSPARENCY!)
            $colorxy    = imagecolorat($src_im, $x, $y);
            $alpha         = ($colorxy >> 24) & 0xFF;
            // Calculate new alpha
            if ($minalpha !== 127) {
               $alpha   = 127 + 127 * $pct * ($alpha - 127) / (127 - $minalpha);
            } else {
               $alpha   += 127 * $pct;
            }
            // Get the color index with new alpha
            $alphacolorxy  = imagecolorallocatealpha($src_im, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);
            // Set pixel with the new color + opacity
            if (!imagesetpixel($src_im, $x, $y, $alphacolorxy)) {
               return;
            }
         }
      }
      imagesavealpha($dst_im, true);
      imagealphablending($dst_im, true);
      imagesavealpha($src_im, true);
      imagealphablending($src_im, true);
      imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
   }    

   function text ($text, $font_file, $font_size = 12, $color = '#000000', $position = 'center', $x_offset = 0, $y_offset = 0) {
      // todo - this method could be improved to support the text angle
      $angle      = 0;
      $rgba    = $this->normalize_color($color);
      $color      = imagecolorallocatealpha($this->image, $rgba['r'], $rgba['g'], $rgba['b'], $rgba['a']);
      // Determine textbox size
      $box     = imagettfbbox($font_size, $angle, $font_file, $text);
      if (!$box) {
      }
      $box_width  = abs($box[6] - $box[2]);
      $box_height = abs($box[7] - $box[1]);
      // Determine position
      switch (strtolower($position)) {
         case 'top left':
            $x = 0 + $x_offset;
            $y = 0 + $y_offset + $box_height;
            break;
         case 'top right':
            $x = $this->width - $box_width + $x_offset;
            $y = 0 + $y_offset + $box_height;
            break;
         case 'top':
            $x = ($this->width / 2) - ($box_width / 2) + $x_offset;
            $y = 0 + $y_offset + $box_height;
            break;
         case 'bottom left':
            $x = 0 + $x_offset;
            $y = $this->height - $box_height + $y_offset + $box_height;
            break;
         case 'bottom right':
            $x = $this->width - $box_width + $x_offset;
            $y = $this->height - $box_height + $y_offset + $box_height;
            break;
         case 'bottom':
            $x = ($this->width / 2) - ($box_width / 2) + $x_offset;
            $y = $this->height - $box_height + $y_offset + $box_height;
            break;
         case 'left':
            $x = 0 + $x_offset;
            $y = ($this->height / 2) - (($box_height / 2) - $box_height) + $y_offset;
            break;
         case 'right';
            $x = $this->width - $box_width + $x_offset;
            $y = ($this->height / 2) - (($box_height / 2) - $box_height) + $y_offset;
            break;
         case 'center':
         default:
            $x = ($this->width / 2) - ($box_width / 2) + $x_offset;
            $y = ($this->height / 2) - (($box_height / 2) - $box_height) + $y_offset;
            break;
      }
      imagettftext($this->image, $font_size, $angle, $x, $y, $color, $font_file, $text);
      return $this;
   }

   function get_orientation () {
      if (imagesx($this->image) > imagesy($this->image)) {
         return 'landscape';
      }
      if (imagesx($this->image) < imagesy($this->image)) {
         return 'portrait';
      }
      return 'square';
   }

   protected function normalize_color ($color) {
      if (is_string($color)) {
         $color   = trim($color, '#');
         if (strlen($color) == 6) {
            list($r, $g, $b) = array(
               $color[0].$color[1],
               $color[2].$color[3],
               $color[4].$color[5]
            );
         } elseif (strlen($color) == 3) {
            list($r, $g, $b) = array(
               $color[0].$color[0],
               $color[1].$color[1],
               $color[2].$color[2]
            );
         } else {
            return false;
         }
         return array(
            'r'   => hexdec($r),
            'g'   => hexdec($g),
            'b'   => hexdec($b),
            'a'   => 0
         );
      } elseif (is_array($color) && (count($color) == 3 || count($color) == 4)) {
         if (isset($color['r'], $color['g'], $color['b'])) {
            return array(
               'r'   => $this->keep_within($color['r'], 0, 255),
               'g'   => $this->keep_within($color['g'], 0, 255),
               'b'   => $this->keep_within($color['b'], 0, 255),
               'a'   => $this->keep_within(isset($color['a']) ? $color['a'] : 0, 0, 127)
            );
         } elseif (isset($color[0], $color[1], $color[2])) {
            return array(
               'r'   => $this->keep_within($color[0], 0, 255),
               'g'   => $this->keep_within($color[1], 0, 255),
               'b'   => $this->keep_within($color[2], 0, 255),
               'a'   => $this->keep_within(isset($color[3]) ? $color[3] : 0, 0, 127)
            );
         }
      }
      return false;
   }

}
?>