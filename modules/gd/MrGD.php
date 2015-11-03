<?php
// Mister Lib Foundation Library
// Copyright(C) 2006-2009 Datafathom, LLC.  All rights reserved.
// Copyright(C) 2011 McDaniel Consulting, LLC.  All rights reserved.
//
// This class attempts to provide a object oriented approach to the GD graphics library.  Its primary function
// includes some basic image tools, such as:
//
//    * take an existing image, or create a new image in memory
//    * perform some functionality on it, like resizing.
//    * output the manipulated image, usually to disk.
//    * provide the handling of memory semi-automatically.
//
// Higher level image operations are in the MrImageManipulator or similar classes.  This class is designed to 
// encapsulate the low level operations of the GD library only.
//
//
// Methods available in all graphics code using this library
// ---------------------------------------------------------------------------------------------------------------
//   loadFromFile()        Load an image in memory from a file and attempt to detect its type automatically.
//   createNew()           Allocate new memory for the internal image handlers, erasing any previous memory.
//   restoreOriginal()     Revert to the original image in memory before any changes were made.
//   saveToFile()          Save the image in memory to disk.
//   imageSize()           Return the pixel dimensions of image in memory.
//   imageType()           Return the numerical value of an image currently in memory, or specified by filename.
//   cleanUp()             Clean up all internal memory.
//
// Lower level methods (remember to free any memory created with these methods)
// ---------------------------------------------------------------------------------------------------------------
//   allocHandle()         Allocate memory for a new image handle, and return the new image handle.
//   setHandle()           Set the internal image handle.
//   getHandle()           Return the current image handle.
//   destroyHandle()       Free memory associated with an image handle.
//
// Other methods are defined, but are not designed to be called directly.
//


define("IMAGE_TRUECOLOR", "1");



class MrGD
{
   protected $original_image;
   protected $image;
   protected $filename;
   protected $image_type;


  /* Constructor.
   */

   function __construct()
   {
      if (!function_exists("imagecreate"))
      {
         //error(E_WARNING, "GD support is not enabled in this version of PHP");
      }
   }



  /* Load an image from a file into memory.  Attempt to auto-detect the image type.
   * This will erase any other images currently in memory.
   *
   * @param  string   filename on disk of the image
   * @return boolean  TRUE for successful load, FALSE if error.
   */

   public function loadFromFile($filename)
   {
      $type = $this->ImageType($filename);

      switch ($type)
      {
         case 1:
           $type = "GIF";
           $this->cleanUp();
           $ret = $this->loadFromGIF($filename);
         break;

         case 2:
           $type = "JPEG";
           $this->cleanUp();
           $ret = $this->loadFromJPEG($filename);
         break;

         case 3:
           $type = "PNG";
           $this->cleanUp();
           $ret = $this->loadFromPNG($filename);
         break;

         case 6:
         case 15:
           $type = "BMP/WBMP";
           $this->cleanUp();
           $ret = $this->loadFromWBMP($filename);
         break;

         case 16:
           $type = "XBM";
           $this->cleanUp();
           $ret = $this->loadFromXBM($filename);
         break;

         default:
           //error(E_WARNING, "The file '$filename' is not a supported image type.");
           return FALSE;
         break;
      }

      if ($ret == FALSE)
      {
         //error(E_WARNING, "The file '$filename' (detected to be of type '$type') could not be loaded.");
         return FALSE;
      }

      $this->image_type = $type;
      $this->filename = $filename;

      //debug("GD Successfully loaded '$filename' as type '$type' into memory");

   return TRUE;
   }



  /* Save the current image in memory to a file.
   *
   * If the image type is not specified, the file is saved in the same format as it was when it was
   * read into memory.
   *
   * @param  string   location of the filename on disk that you want to save to.
   * @param  string   the output format.
   * @return boolean  TRUE on success, FALSE on fail.
   */

   public function saveToFile($filename, $image_type = NULL)
   {
      if ($image_type == NULL)
        $image_type = $this->image_type;

      switch ($image_type)
      {
         case "GIF":
           $ret = $this->outputGIF($filename);
         break;

         case "JPEG":
           $ret = $this->outputJPEG($filename);
         break;

         case "PNG":
           $ret = $this->outputPNG($filename);
         break;

         case "BMP":
         case "WBMP":
           $ret = $this->outputWBMP($filename);
         break;

         case "XBM":
           $ret = $this->outputXBM($filename);
         break;

         default:
           //error(E_WARNING, "Cannot save image '$filename' as '$image_type': Invalid image format");
           return FALSE;
         break;
      }

      if ($ret == FALSE)
      {
         //error(E_WARNING, "Could not save image '$filename' to disk");
         return FALSE;
      }

      //debug("Successfully saved '$filename' as type '$image_type'");

   return TRUE;
   }



  /* Create new image in memory.  By default we create a true color image, unless otherwise specified.
   * This will erase any previous image in memory!
   *
   * @param  int      width of image
   * @param  int      height of image
   * @param  boolean  [optional] TRUE for full color image, FALSE for palette based image.
   * @return boolean  TRUE on success, FALSE on failure.
   */

   public function createNew($width, $height, $truecolor = TRUE)
   {
      $this->destroyHandle($this->original_image);
      $this->destroyHandle($this->image);

      if (!$image = $this->allocHandle($width, $height, $truecolor))
        return FALSE;

      if (!$original_image = $this->allocHandle($width, $height, $truecolor))
        return FALSE;

      $this->image = $image;
      $this->original_image = $original_image;

      //error(E_NOTICE, "GD: New image created in memory and ready for use");

   return TRUE;
   }



  /* Set the internal image handler to the original image as it was first loaded in memory.
   */

   public function restoreOriginal()
   {
      $this->destroyHandle($this->image);
      $this->image = $this->copyHandle($this->original_image);

   return TRUE;
   }



  /* Return the image type after examining it.  Automatically detect if EXIF support is compiled into
   * PHP and use it, otherwise use a slower method.
   *
   * @param  string  [Optional] filname on disk.  Otherwise use image loaded in memory.
   * @return mixed   A numerical value representing the type (from GD library) or FALSE on error.
   */

   public function imageType($filename = NULL)
   {
      if ($filename == NULL)
        $filename = $this->filename;

      if (function_exists("exif_imagetype"))
      {
         if (!$type = exif_imagetype($filename))
         {
            //error(E_NOTICE, "Invalid image or unknown image format");
            return FALSE;
         }
      }
      else
      {
         if (!$data = getimagesize($filename))
         {
            //error(E_NOTICE, "Invalid image or unknown image format");
            return FALSE;
         }

         $type = $data[2];
      }

   return $type;
   }



  /* Return the dimensions of the current image in memory.
   *
   * @return mixed   Array with the following information or FALSE if the image is invalid or not supported.
   *                 data[0] width in pixels
   *                 data[1] height in pixels
   *                 data[2] image type, numerical value
   */

   public function imageSize()
   {
      if (!$data = getimagesize($this->filename))
      {
         //error(E_NOTICE, "Invalid image or unknown image format");
         return FALSE;
      }

   return $data;
   }


  /* Allocate a color to the current image in memory.  Specify RGB values as integers or hexidecimals.
   *
   * @param   integer or hexidecimal   red value
   * @param   integer or hexidecimal   green value
   * @param   integer or hexidecimal   blue value
   * @return  resource                 GD color resource.
   */

   public function colorAllocate($r, $g, $b)
   {
      $color = imagecolorallocate($this->image, $r, $g, $b);
      
   return $color;
   }



  /* Free all internal memory used.
   */

   public function cleanUp()
   {
      if (!empty($this->image))
        $this->destroyHandle($this->image);

      if (!empty($this->original_image))
        $this->destroyHandle($this->original_image);
   }



  /* Copy the memory from the internal image handle into a new image handle, and return that handle.
   *
   * @return mixed   Resource representing the new GD image, or FALSE if error.
   */

   protected function copyInternalHandle()
   {
      $size = $this->imageSize();
      $newhandle = $this->allocHandle($size[0], $size[1]);

      if (!imagecopy($newhandle, $this->image, 0, 0, 0, 0, $size[0], $size[1]))
      {
         //error(E_WARNING, "copyInternalHandle() Could not copy image");
         return FALSE;
      }

   return $newhandle;
   }



  /* Allocate memory for a new image resource, copy the memory from the specified handle into the
   * new image handle, and return that handle.
   *
   * @param   resource  Resource variable representing GD image.
   * @return  mixed     New image resource, or FALSE if there was an error. 
   */

   protected function copyHandle($handle)
   {
      $size[0] = imagesx($handle);
      $size[1] = imagesy($handle);
      $newhandle = $this->allocHandle($size[0], $size[1]);

      if (!imagecopy($newhandle, $handle, 0, 0, 0, 0, $size[0], $size[1]))
      {
         //error(E_WARNING, "copyHandle() Could not copy image");
         return FALSE;
      }

   return $newhandle;
   }



  /* Allocate memory for a new image handler, and return that handler.
   *
   * @param   int      Width of new image.
   * @param   int      Height of new image.
   * @param   boolean  [Optional] TRUE for truecolor.  TRUE by default.
   * @return  mixed    Resource for new GD image, or FALSE if error.
   */

   protected function allocHandle($width, $height, $truecolor = TRUE)
   {
      if ($truecolor == FALSE)
        $image = imagecreate($width, $height);
      else
        $image = imagecreatetruecolor($width, $height);

      if ($image == FALSE)
      {
         //error(E_WARNING, "Could not allocate memory for new image");
         return FALSE;
      }

   return $image;
   }



  /* Erase memory of an image handle.
   *
   * @param resource   GD image handle.
   */

   protected function destroyHandle($handle)
   {
      @imagedestroy($handle);
   }



  /* Retrieve the current image handle.
   * 
   * @return  resource   GD resource.
   */

   protected function getHandle()
   {
      return $this->image;
   }



   // This will erase the current image memory and replace it with the handle passed to this function.
   // 
   // @param resouce    GD image resource.

   protected function setHandle($image)
   {
      $this->destroyHandle($this->image);
      $this->image = $image;
   }



   ////////////////////////////////////////////////////////////////////////////////////////////
   // Internal stuff
   ////////////////////////////////////////////////////////////////////////////////////////////



   private function loadFromJPEG($filename)
   {
      if (!function_exists("imagecreatefromjpeg"))
      {
         //error(E_WARNING, "JPEG support is not enabled in this build of the GD library");
         return FALSE;
      }

      if (!$image = imagecreatefromjpeg($filename))
        return FALSE;

      if (!$original_image = imagecreatefromjpeg($filename))
        return FALSE;

      $this->original_image = $original_image;
      $this->image = $image;

   return TRUE;
   }



   private function loadFromGIF($filename)
   {
      if (!function_exists("imagecreatefromgif"))
      {
         //error(E_WARNING, "GIF support is not enabled in this build of the GD library");
         return FALSE;
      }

      if (!$image = imagecreatefromgif($filename))
        return FALSE;

      if (!$original_image = imagecreatefromgif($filename))
        return FALSE;

      $this->original_image = $original_image;
      $this->image = $image;

   return TRUE;
   }



   private function loadFromPNG($filename)
   {
      if (!function_exists("imagecreatefrompng"))
      {
         //error(E_WARNING, "PNG support is not enabled in this build of the GD library");
         return FALSE;
      }

      if (!$image = imagecreatefrompng($filename))
        return FALSE;

      if (!$original_image = imagecreatefrompng($filename))
        return FALSE;

      $this->original_image = $original_image;
      $this->image = $image;

   return TRUE;
   }



   private function loadFromGD($filename)
   {
      if (!function_exists("imagecreatefromgd"))
      {
         //error(E_WARNING, "GD image support is not enabled in this build of the GD library");
         return FALSE;
      }

      if (!$image = imagecreatefromgd($filename))
        return FALSE;

      if (!$original_image = imagecreatefromgd($filename))
        return FALSE;

      $this->original_image = $original_image;
      $this->image = $image;

   return TRUE;
   }



   private function loadFromGD2($filename)
   {
      if (!function_exists("imagecreatefromgd2"))
      {
         //error(E_WARNING, "GD2 image support is not enabled in this build of the GD library");
         return FALSE;
      }

      if (!$image = imagecreatefromgd2($filename))
        return FALSE;

      if (!$original_image = imagecreatefromgd2($filename))
        return FALSE;

      $this->original_image = $original_image;
      $this->image = $image;

   return TRUE;
   }



   private function loadFromXPM($filename)
   {
      if (!function_exists("imagecreatefromxpm"))
      {
         //error(E_WARNING, "GD2 image support is not enabled in this build of the GD library");
         return FALSE;
      }

      if (!$image = imagecreatefromxpm($filename))
        return FALSE;

      if (!$original_image = imagecreatefromxpm($filename))
        return FALSE;

      $this->original_image = $original_image;
      $this->image = $image;

   return TRUE;
   }



   private function loadFromXBM($filename)
   {
      if (!function_exists("imagecreatefromxbm"))
      {
         //error(E_WARNING, "XBM image support is not enabled in this build of the GD library");
         return FALSE;
      }

      if (!$image = imagecreatefromxbm($filename))
        return FALSE;

      if (!$original_image = imagecreatefromxbm($filename))
        return FALSE;

      $this->original_image = $original_image;
      $this->image = $image;

   return TRUE;
   }



   private function loadFromWBMP($filename)
   {
      if (!function_exists("imagecreatefromwbmp"))
      {
         //error(E_WARNING, "WBMP image support is not enabled in this build of the GD library");
         return FALSE;
      }

      if (!$image = imagecreatefromwbmp($filename))
        return FALSE;

      if (!$original_image = imagecreatefromwbmp($filename))
        return FALSE;

      $this->original_image = $original_image;
      $this->image = $image;

   return TRUE;
   }



   // Output functions.  These will write the image to a file if specified, otherwise return
   // the raw data in binary form.
   //
   // Return TRUE if filename specified and secessfully wrote.  Return raw data if no filename
   // was specified.  Return FALSE on any error.
   //


   private function outputGD($filename = NULL)
   {
      if (!function_exists("imagegd"))
      {
         //error(E_WARNING, "GD2 image support is not enabled in this build of the GD library");
         return FALSE;
      }

      if ($filename == NULL)
      {
         ob_start();
         imagegd($this->image);
         $res = ob_get_contents();
         ob_end_clean();
      }
      else
      {
         $res = imagegd($this->image, $filename);
      }

   return $res;
   }



   private function outputGD2($filename = NULL)
   {
      if (!function_exists("imagegd2"))
      {
         //error(E_WARNING, "GD2 image support is not enabled in this build of the GD library");
         return FALSE;
      }

      if ($filename == NULL)
      {
         ob_start();
         imagegd2($this->image);
         $res = ob_get_contents();
         ob_end_clean();
      }
      else
      {
         $res = imagegd2($this->image, $filename);
      }

   return $res;
   }



   private function outputJPEG($filename = NULL, $quality=100)
   {
      if (!function_exists("imagejpeg"))
      {
         //error(E_WARNING, "JPEG image support is not enabled in this build of the GD library");
         return FALSE;
      }

      if ($filename == NULL)
      {
         ob_start();
         imagejpeg($this->image, "", $quality);
         $res = ob_get_contents();
         ob_end_clean();
      }
      else
      {
         $res = imagejpeg($this->image, $filename);
      }

   return $res;
   }



   private function outputGIF($filename = NULL)
   {
      if (!function_exists("imagegif"))
      {
         //error(E_WARNING, "GIF image support is not enabled in this build of the GD library");
         return FALSE;
      }

      if ($filename == NULL)
      {
         ob_start();
         imagegif($this->image);
         $res = ob_get_contents();
         ob_end_clean();
      }
      else
      {
         $res = imagegif($this->image, $filename);
      }

   return $res;
   }



   private function outputPNG($filename = NULL)
   {
      if (!function_exists("imagepng"))
      {
         //error(E_WARNING, "PNG image support is not enabled in this build of the GD library");
         return FALSE;
      }

      if ($filename == NULL)
      {
         ob_start();
         imagepng($this->image);
         $res = ob_get_contents();
         ob_end_clean();
      }
      else
      {
         $res = imagepng($this->image, $filename);
      }

   return $res;
   }



   private function outputWBMP($filename = NULL)
   {
      if (!function_exists("imagewbmp"))
      {
         //error(E_WARNING, "WBMP image support is not enabled in this build of the GD library");
         return FALSE;
      }

      if ($filename == NULL)
      {
         ob_start();
         imagewbmp($this->image);
         $res = ob_get_contents();
         ob_end_clean();
      }
      else
      {
         $res = imagewbmp($this->image, $filename);
      }

   return $res;
   }


   private function outputXBM($filename = NULL)
   {
      if (!function_exists("imagexbm"))
      {
         //error(E_WARNING, "XBM image support is not enabled in this build of the GD library");
         return FALSE;
      }

      if ($filename == NULL)
      {
         ob_start();
         imagexbm($this->image);
         $res = ob_get_contents();
         ob_end_clean();
      }
      else
      {
         $res = imagexbm($this->image, $filename);
      }

   return $res;
   }



/* end class */
}


?>
