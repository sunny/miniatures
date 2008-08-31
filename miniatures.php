<?php
/*
 * Miniatures
 *
 * Creates on-the-fly thumbnails and spits them back
 * Supports jpg, gif and png files
 *
 * Usage:
 *    <img src="miniatures.php?file=photos/myphoto.jpg" alt="My photo" />
 *
 * Creates miniature files (default: gif) in the thumbnails folder (default: _miniatures).
 *
 */

/*
 * Options
 */

if (!defined('MINIATURES_THUMBS_FOLDER')) define('MINIATURES_THUMBS_FOLDER', '_miniatures');
if (!defined('MINIATURES_WIDTH'))         define('MINIATURES_WIDTH', 100);
if (!defined('MINIATURES_HEIGHT'))        define('MINIATURES_HEIGHT', 200);

/*
 * Controller
 */

if (isset($_GET['file'])) {
  if (get_magic_quotes_gpc())
    $_GET['file'] = stripslashes($_GET['file']);

  $image = new Image($_GET['file']);
  if (!$image->exists())
    die('No such image.');

  // on peut artificiellement créer la miniature gif à la main

  if (!$image->thumb_exists()) {
    if (!$image->is_supported())
      die('Supports jpg, gif and png files only.');
    else
      $image->create_thumb(MINIATURES_WIDTH, MINIATURES_HEIGHT);
  }

  header('Content-type: image/' . $image->headtype());
  $image->read_thumb();
}


/*
 * Class
 */

class Image {
  function Image($filename) {
    $this->filename = $filename;
  }

  function is_supported() {
    return in_array($this->type(), array('jpg', 'jpeg', 'png', 'gif'));
  }

  function thumb_name() {
    if (!$this->is_supported()) { // pas un jpg ou gif ou png
      $path = explode('/', $this->filename);
      $path = implode('--', $path);
      $sub = substr($path , 0, strpos($path, '.') );
      return MINIATURES_THUMBS_FOLDER . '/' . $sub . '.gif';
    } else {
      $path = explode('/', $this->filename);
      return MINIATURES_THUMBS_FOLDER . '/' . implode('--', $path);
    }
  }

  function headtype() {
    $name_parts = explode('.', $this->filename);
  	$extension = array_pop($name_parts);
  	if ($this->is_supported())
  	  return $extension == 'jpeg' ? 'jpg' : $extension;
  	else
  	  return 'gif';
  }

  function type() {
    $name_parts = explode('.', $this->filename);
   	$extension = array_pop($name_parts);
   	return $extension == 'jpeg' ? 'jpg' : $extension;
   }

  function read_thumb() {
    if ($this->type() == 'gif' and !function_exists('imagecreatefromgif'))
      return readfile($this->filename); // return original if no support for gif in php-gd
    return readfile($this->thumb_name());
  }

  function exists() {
    return file_exists($this->filename);
  }

  function thumb_exists() {
    return file_exists($this->thumb_name());
  }

  function create_thumb($new_w, $new_h) {
    if ($this->type() == 'jpg')
  		$src_img = imagecreatefromjpeg($this->filename);
  	elseif ($this->type() == 'png')
  		$src_img = imagecreatefrompng($this->filename);
    elseif ($this->type() == 'gif' and function_exists('imagecreatefromgif'))
    	$src_img = imagecreatefromgif($this->filename);
  	else
  	  return false;

  	$old_w = imageSX($src_img);
    $old_h = imageSY($src_img);
    if ($old_h < $old_w) {
    	$thumb_w = $old_w * ($new_h / $old_h);
    	$thumb_h = $new_h;
    } else {
    	$thumb_w = $new_w;
    	$thumb_h = $old_h * ($new_w / $old_w);
    }

    $dest_img = ImageCreateTrueColor($thumb_w, $thumb_h);
    imagecopyresampled($dest_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_w, $old_h);

    if ($this->type() == 'jpg')
  	  imagejpeg($dest_img, $this->thumb_name());
    elseif ($this->type() == 'png')
    	imagepng($dest_img, $this->thumb_name());
    elseif ($this->type() == 'gif')
    	imagegif($dest_img, $this->thumb_name());

    imagedestroy($dest_img);
    imagedestroy($src_img);
  }
}
