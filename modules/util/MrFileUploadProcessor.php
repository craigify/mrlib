<?php
// Mister Lib Foundation Library
// Copyright(C) 2010, 2011, 2015 McDaniel Consulting, LLC
//
// Utility class for managing uploaded files.  Provides a OOP interface to PHP's functions in the hope that
// it is slightly easier to manage uploaded files.
// @author Craig
//
// Usage:
// -------------------------------------------------------------------------------------------------------
// Lets say we upload 3 files with the following HTML form:
//  <input name="file1" type="file">
//  <input name="file2" type="file">
//  <input name="file3" type="file">
//
// $fileupload->destinationDir("/my/destination/dir");  # Set destination directory to put uploaded file
// $fileupload->addFile("file1", "foo.txt");            # Add a file to be processed and rename it
// $fileupload->addFile("file2", "bar.txt");            # Add a file to be processed and rename it
// $fileupload->addFile("file3");                       # Add a file to be processed, not renaming it
// $fileupload->process();                              # copy files to new location
//
// getProcessedFiles()    Return an array of files successfully uploaded and moved.
// numProcessedFiles()    Return the number of files successfully processed as an integer.
//

class MrFileUploadProcessor
{
   protected $files;
   protected $processedFiles;
   protected $dest_dir;
   protected $tmp_dir;


  /* Constructor
   */

   function __constructor()
   {
      $this->dest_dir = FALSE;
      $this->tmp_dir = "/tmp";
      $this->files = array();
      $this->processedFiles = array();
   }



  /* Retrieve the filename from the HTTP POST request. 
   * @input  string  The name of the form variable containing the file information.
   * @return mixed   Filename as a string, or FALSE if error.
   */

   public function getFilenameFromPost($form_variable)
   {
      if (isset($_FILES[$form_variable]) && !empty($_FILES[$form_variable]['name']))
      {
         return $_FILES[$form_variable]['name'];
      }
      else
      {
         return FALSE;
      }
   }   



  /* Add a file to be processed from the request. If destinationFilename is not set, the original name will
   * be used when saving the file to the destination directory.
   *
   * @param (string) key in the _FILES array where filename is passed in.
   * @param (string) optional name of file when saved in destination location.
   */

   public function addFile($form_variable, $destinationFilename = NULL)
   {
      if (!isset($_FILES[$form_variable]) || empty($_FILES[$form_variable]['name']))
        return FALSE;

      /* Detect if the form variable is an array of files or contains a single file */
      if (is_array($_FILES[$form_variable]['error']))
      {
         foreach ($_FILES[$form_variable]['error'] as $key => $error)
         {
            /* Take out any escaped characters at this point */
            $filearray['name'] = stripslashes($_FILES[$form_variable]['name'][$key]);
            $filearray['type'] = $_FILES[$form_variable]['type'][$key];
            $filearray['error'] = $_FILES[$form_variable]['error'][$key];
            $filearray['size'] = $_FILES[$form_variable]['size'][$key];
            $filearray['tmp_name'] = stripslashes($_FILES[$form_variable]['tmp_name'][$key]);
            $this->addFileEntry($filearray, $destinationFilename);
         }
      }
      else
      {
         $this->addFileEntry($_FILES[$form_variable], $destinationFilename);
      }
   }


  /* Set destination directory where files will go when processed.
   *
   * @param (string) directory path.
   * @return (bool) FALSE if destination directory not present or inaccessible. Otherwise TRUE.
   */

   public function destinationDir($dir)
   {
      // add a trailing slash to the end if it was not supplied
      if (substr($dir, -1) != "/")
        $dir = $dir . "/";

      if (!file_exists($dir))
      {
         trigger_error("MrFileUploadProcessor::destinationDir() - Destination directory '{$dir}' does not exist.", E_USER_WARNING);
         return FALSE;
      }

      if (!is_dir($dir))
      {
         return FALSE;
      }

      if (!is_writable($dir))
      {
         return FALSE;
      }

      $this->dest_dir = $dir;
      return TRUE;
   }



  /* Process upload files.  Put them in their new dir and apply any rules to them. If no files were added via
   * addFile(), just silently return TRUE.
   *
   * A return of false could mean total failure, or partial failure.  Some files might have been copied and
   * some not.  Sorry, it's not very useful but it gives SOME kind of indication of success.
   *
   * @return (bool)
   */

   function process()
   {
      if (empty($this->dest_dir) || $this->dest_dir == FALSE)
      {
         return FALSE;
      }

      foreach ($this->files as $file)
      {
         $dest = $this->dest_dir . $file['destination_filename'];
         $pfile['fullfile'] = $dest;
         $pfile['filename'] = $file['destination_filename'];
         $pfile['directory'] = $this->dest_dir;
         $pfile['size'] = $file['size'];

            if (copy($file['tmp_name'], $dest))
            {
               $this->processedFiles[] = $pfile;
            }
            else
            {
               trigger_error("MrFileUploadProcessor could not copy '{$file['tmp_name']}' to '{$dest}'", E_USER_WARNING);
               return FALSE;
            }
      }
      
   return TRUE;
   }



  /* Return an array of the processed files.  This returns the filenames after they have been moved to their
   * destination directory, and if applicable, renamed.
   *
   * Return multi-dimensional array:
   *  data[i]['filename']    name of file
   *  data[i]['directory']   directory that contains filename with trailing slash
   *  data[i]['fullfile']    file with full directory included
   *  data[i]['size']        size in bytes of filename
   *
   *  @return (array)
   */

   function getProcessedFiles()
   {
      return $this->processedFiles;
   }



  /* Return the number of files successfuly process and moved to their dest dir.
   *
   * @return (integer) zero and up.
   */

   function numProcessedFiles()
   {
      return count($this->processedFiles);
   }



   private function addFileEntry($filearray, $destinationFilename)
   {
      if (!is_uploaded_file($filearray['tmp_name']))
      {
         trigger_error("MrFileUploadProcessor->addFileEntry(): Invalid upload file '{$filearray['tmp_name']}'.  This file was not uploded via POST or PUT.  Check PHP max upload settings.", E_USER_WARNING);
         return FALSE;
      }

      if ($filearray['error'] > 0)
      {
         return FALSE;
      }

      /* Use original filename unless otherwise specified when saving */
      if ($destinationFilename == NULL)
      {
         $destinationFilename = $filearray['name'];
      }

      /* Save file data */
      $filearray['destination_filename'] = $destinationFilename;
      $this->files[] = $filearray;

   return TRUE;
   }


/* end class */
}



?>
