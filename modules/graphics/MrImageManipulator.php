<?php
// MrLib Foundation Library
// Copyright(C) 2006-2009 Datafathom, LLC.  All rights reserved.
// Copyright(C) 2011 McDaniel Consulting, LLC.  All rights reserved.
//
// Methods to perform image manipulations on existing images. We inherit all the basic functionality
// of the MrGD class.
//
//
// Methods available in ImageManipulator
// -------------------------------------------------------------------------------------------------
// resizeImage()                Resize image by specifying width and height.
// resizeImageByPercent()       Resize image by percentage value.
// resizeImageByWidth()         Resize an image proportionally by speficying new width
// resizeImageByHeight()        Resize an image proportionally by speficying new height
// grayscale()                  Convert image to grayscale
// sepia()                      Add a sepia layer to image
// rotate()                     Rotate image clockwise 90, 180 or 270 degrees
// mirror()                     Create a mirror image horizontally
// flip()                       Flip the image vertically
//

mrlib::load("gd", "MrGD.php");

class MrImageManipulator extends MrGD 
{

   function __construct()
   {
      parent::__construct();
   }


  /* Resize image by width and height specification.
   * Return TRUE on success, FALSE on error.
   */

   public function resizeImage($width, $height)
   {
      if (!function_exists("imagecopyresampled"))
      {
         //error(E_WARNING, "This version of GD does not support image resampling.");
         return FALSE;
      }

      if (!$newhandle = $this->allocHandle($width, $height))
      {
         //error(E_WARNING, "Could not allocate memory when resizing image");
         return FALSE;
      }

      $size = $this->imageSize();

      if (!imagecopyresampled($newhandle, $this->image, 0, 0, 0, 0, $width, $height, $size[0], $size[1]))
      {
         //error(E_WARNING, "Could not resample image");
         return FALSE;
      }

      $this->setHandle($newhandle);

   return TRUE;
   }



  /* Resize image by percentage.  A value of 1 (100%) would create an image of the same size, where
   * a value of .5 (50%) would create an image half the size (50 percent of the size) of the original.
   *
   * Return TRUE on success, FALSE on error.
   */

   public function resizeImageByPercent($percent)
   {
      if (!is_numeric($percent))
      {
         //error(E_WARNING, "resizeImageByPercent requires a decimal value between 0 and 1");
         return FALSE;
      }

      if ($percent > 0 && $percent < 1)
      {
         //error(E_WARNING, "resizeImageByPercent requires a decimal value between 0 and 1");
         return FALSE;
      }

      $size = $this->imageSize();
      $new_size[0] = $size[0] * $percent;
      $new_size[1] = $size[1] * $percent;

      $res = $this->resizeImage($new_size[0], $new_size[1]);

   return $res;
   }



  /* Resize an image proportionally by speficying a width.  The hight will be determined in proportion with the
   * specified width.
   */

   public function resizeImageByWidth($width)
   {
      $size = $this->imageSize();
      $percent = $width / $size[0];
      $height = $size[1] * $percent;

      $res = $this->resizeImage($width, $height);

   return $res;
   }



  /* Resize an image proportionally by speficying a height.  The width will be determined in proportion with the
   * specified height.
   */

   public function resizeImageByHeight($height)
   {
      $size = $this->imageSize();
      $percent = $height / $size[1];
      $width = $size[0] * $percent;

      $res = $this->resizeImage($width, $height);

   return $res;
   }



  /* Convert image to grayscale
   */

   public function grayscale()
   {
      global $__CONFIG;

      $size = $this->imageSize();

      for ($y = 0; $y < $size[1]; $y++)
      {
         for ($x = 0; $x < $size[0]; $x++)
         {
            $gray = (ImageColorAt($this->image, $x, $y) >> 8) & 0xFF;
            imagesetpixel($this->image, $x, $y, ImageColorAllocate($this->image, $gray, $gray, $gray));
         }
      }

   return TRUE;
   }



  /* Adjust sepia on an image.  Higher amounts will create higher sepia levels.  Generally amount
   * should range from 1 to 100.
   */

   public function sepia($amount = 30)
   {
      $size = $this->imageSize();

      /* create sepia layer */
      $image_sepia = $this->allocHandle($size[0], $size[1]);
      imagefill($image_sepia, 0, 0, ImageColorAllocate($image_sepia, 216, 170, 30));

      /* apply sepia layer on top of image (divide amount by 2 for better effects) */
      imagecopymerge($this->image, $image_sepia, 0, 0, 0, 0, $size[0], $size[1], $amount / 2);

      /* free memory used by sepia layer */
      $this->destroyHandle($image_sepia);

   return TRUE;
   }



  /* Rotate image 90, 180 or 270 degrees to the right.  If a number other than the fixed amounts are
   * specified, round that number up to meet the required value.  Image will automatically resize itself
   * to prevent clipping.
   */

   public function rotate($angle=90)
   {
      if (($angle % 360) == 0)
      {
         //error(E_WARNING, "You can only rotate an image on a 90, 180 or 270 degree angle");
         return FALSE;
      }

      // Multiply by -1 to rotate clockwise
      $newhandle = ImageRotate($this->image, $angle * -1, 0);
      $this->setHandle($newhandle);

   return TRUE;
   }



   public function mirror()
   {
      $size = $this->imageSize();
      $newhandle = $this->allocHandle($size[0], $size[1]);

      for ($x = 0; $x < $size[0]; ++$x)
      {
         imagecopy($newhandle, $this->image, $x, 0, $size[0] - $x - 1, 0, 1, $size[1]);
      }

      $this->setHandle($newhandle);

   return TRUE;
   }



   public function flip()
   {
      $size = $this->imageSize();
      $newhandle = $this->allocHandle($size[0], $size[1]);

      for ($y = 0; $y < $size[1]; ++$y)
      {
         imagecopy($newhandle, $this->image, 0, $y, 0, $size[1] - $y - 1, $size[0], 1);
      }

      $this->setHandle($newhandle);

    return TRUE;
    }



    public function AdjRGBBrightContrast($rgb,$bright,$contr)
    {
       // Decrease contrast
       if( $contr <= 0 )
       {
          $adj = abs($rgb-128) * (-$contr);
          if( $rgb < 128 ) $rgb += $adj;
          else $rgb -= $adj;
       }
       else
       {
          // Increase contrast
          if( $rgb < 128 ) $rgb = $rgb - ($rgb * $contr);
          else $rgb = $rgb + ((255-$rgb) * $contr);
       }

       // Add (or remove) various amount of white
       $rgb += $bright*255;
       $rgb=min($rgb,255);
       $rgb=max($rgb,0);

    return $rgb;
    }



  /* Fill an image starting at the x, y coordinate points with the specified color.
   * 
   * @param int        x coordinate.
   * @param int        y coordinate.
   * @param resource   GD color resource returned from MrGD::colorAllocate() or imagecolorallocate()
   */

   public function fill($x, $y, $color)
   {
      imagefill($this->image, $x, $y, $color);
   }


/* end class */
}




?>
