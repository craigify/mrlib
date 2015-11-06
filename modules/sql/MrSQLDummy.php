<?php
// Mister Lib Foundation Library
// Copyright(C) 2006, 2007 Datafathom, LLC.  All rights reserved.
// Copyright(C) 2011 McDaniel Consulting, LLC.  All rights resvered.
//
// MrLib Database Library
//
// - Dummy SQL class.  Provide methods, but do nothing.
// There is alot of code that will attempt to query from the database, and instead of writing in code to
// check if there even is an active database, we just use the DummySQL class instead.
//
// TO DO: This needs to actually implement the abstract methods in MrSQL.  Right now it won't work...
//
//

mrlib::load("sql/MrSQL");


class MrSQLDummy extends MrSQL
{

   function __construct()
   {
      parent::__construct();
   }


}



?>
