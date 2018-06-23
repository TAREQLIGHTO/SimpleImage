The SimpleImage PHP class
=========================
This class makes image manipulation in PHP as simple as possible:

Usage
-----

```php
// Resize the image to 320x200
$image = new SimpleImage();
$image->load('image.png');
$image->resize(320, 320);
$image->save('output.png');
// Overlay watermark.png at 50% opacity at the bottom-right of the image with a 10 pixel horizontal and vertical margin
$image = new SimpleImage();
$image->load('image.png');
$image->overlay('watermark.png', 'bottom right', .5, -10, -10);
$image->save('output.png');
// Add 32-point white text top-centered (plus 20px) on the image*
$image->text('Your Text', 'font.ttf', 32, '#FFFFFF', 'top', 0, 20);
$image = new SimpleImage();
$image->load('image.png');
$image->save('output.png');
```
