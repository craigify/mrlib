<?php
// Mister Lib Foundation Library
// Copyright(C) 2015 McDaniel Consulting, LLC
//
// Data Type Validator - Provide a (not so) extensive set of methods to validate various data types.
//
// * This class is designed to hook into the MrInputData singleton and provide it with methods to validate
//   input data, although it certainly could be used in a standalone fashion.
//
// * Extend this class and define your own validation types if needed.  You can tell MrInputData to use
//   your extended validation class.
//
// * Each method accepts a single input variable, and returns either TRUE or FALSE. 
//
//


class Validator
{


  /* Useless constructor for now.
   */

   function __construct()
   {

   }



  /* Return TRUE if input data is all whitespace.
   */

   public function isAllWhitespace($in)
   {
      if (preg_match("/^\s+$/", $in))
      {
         return TRUE;
      }
   }



  /* Return TRUE if input data has any whitespace.
   */

   public function hasWhitespace($in)
   {
      if (preg_match("/\s/", $in))
      {
         return TRUE;
      }
   }



  /* Return TRUE if input data contains all alphanumeric data.
   */

   public function isAlphaNumeric($in)
   {
      return (!preg_match("/^([-a-z0-9])+$/i", $in)) ? FALSE : TRUE;
   }



  /* Returns TRUE if string contains only letters.
   */

   public function isAlpha($in)
   {
      return (!preg_match("/^([A-Za-z])+$/", $in)) ? FALSE : TRUE;
   }



  /* Return TRUE if input data contains all numeric data.  This is NOT THE SAME AS is_numeric(). This
   * makes sure all data are numbers.
   */

   public function isNumeric($in)
   {
      return (!preg_match("/^([0-9])+$/", $in)) ? FALSE : TRUE;
   }



  /* Check if data is a float.  This checks the formatting, not the internal type.
   *
   * Input data must be in the format of digits.digits where digits are any [0-9] characters
   * in succession of an unlimited length.
   *
   * Also accepts the comma as a whole, fraction separator.
   */

   public function isFloat($in)
   {
      return (!preg_match("/^(?:\d+?[\.\,]\d+?)$/", $in)) ? FALSE : TRUE;
   }



  /* Return TRUE if input data is a number.  This accepts whole numbers and decimal numbers that
   * use either the period or comma to seperate whole and fraction parts.
   *
   * Does not accept any other formatting, like grouping digits with commas, periods or spaces.
   *
   * 100      --> pass
   * 100.10   --> pass
   * 100,10   --> pass
   * 1,000    --> fail
   * 1,000.10 --> fail
   * 1.000,10 --> fail
   * 1 000.10 --> fail
   */

   public function isNumber($in)
   {
      return (!preg_match("/^(?:\d+?|\d+?[\.\,]\d+?)$/", $in)) ? FALSE : TRUE;
   }



  /* Return TRUE if a number is between $min and $max values.
   */

   public function isNumberRange($in, $min, $max)
   {
      if ($this->isNumber($in) && $this->isNumber($max))
      {
         if ($in >= $min && $in <= $max)
         {
            return TRUE;
         }
         else
         {
            return FALSE;
         }
      }
      else
      {
         return FALSE;
      }
   }



  /* Return TRUE if input data is a valid email address.  Make sure there are no preceeding or trailing spaces.
   */

   public function isEmail($in)
   {
      if (empty($in))
      {
         return FALSE;
      }

      if (preg_match("/^([a-z0-9_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,4}\$/i", $in))
      {
         return TRUE;
      }
      else
      {
         return FALSE;
      }
   }



  /* Check if input data is a valid date string.  Accept a number of valid input date formats,
   * specifically those which are acceptable to the PHP function strtoime().
   *
   * Return TRUE if date string is valid, FALSE otherwise.
   */

   public function isDate($in)
   {
      $ret = @strtotime($in);

      if ($ret == -1 || $ret === FALSE)
      {
         return FALSE;
      }
      else
      {
         return TRUE;
      }
   }



  /* Generic username validator.
   *
   * Accepts usernames that are a combination of any alphanumeric character, a period '.', an underscore '_',
   * or a hyphen '-'.  Must start with an alphanumeric character or an underscore. Length must be between 3
   * to 16 characters.
   */

   public function isUsername($in)
   {
      if (preg_match("/^[a-zA-Z_][a-zA-Z0-9._-]{2,15}$/", $in))
      {
         return TRUE;
      }
      else
      {
         return FALSE;
      }
   }



//	************************************************************
//	Valid phone number? true or false
//	Tries to validate a phone number

//	Strips (,),-,+ from number prior to checking
//	Less than 7 digits = fail
//	More than 13 digits = fail
//	Anything other than numbers after the stripping = fail

	public function is_phone ($Phone = "")
	{
		if($this->CLEAR) { $this->clear_error(); }

		if(empty($Phone))
		{
			$this->ERROR = "is_phone: No Phone number submitted";
			return false;
		}

		$Num = $Phone;
		$Num = $this->strip_space($Num);
		$Num = eregi_replace("(\(|\)|\-|\+)","",$Num);
		if(!$this->is_allnumbers($Num))
		{
			$this->ERROR = "is_phone: bad data in phone number";
			return false;
		}

		if ( (strlen($Num)) < 7)
		{
			$this->ERROR = "is_phone: number is too short [$Num][$Phone]";
			return false;
		}

		// 000 000 000 0000
 		// CC  AC  PRE SUFX = max 13 digits

		if( (strlen($Num)) > 13)
		{
			$this->ERROR = "is_phone: number is too long [$Num][$Phone]";
			return false;
		}

		return true;
	}

//	************************************************************
//	Valid, fully qualified hostname? true or false
//	Checks the -syntax- of the hostname, not it's actual
//	validity as a reachable internet host

	public function is_hostname ($hostname = "")
	{
		if($this->CLEAR) { $this->clear_error(); }

		$web = false;

		if(empty($hostname))
		{
			$this->ERROR = "is_hostname: No hostname submitted";
			return false;
		}

		// Only a-z, 0-9, and "-" or "." are permitted in a hostname

		// Patch for POSIX regex lib by Sascha Schumann sas@schell.de
		$Bad = eregi_replace("[-A-Z0-9\.]","",$hostname);

		if(!empty($Bad))
		{
			$this->ERROR = "is_hostname: invalid chars [$Bad]";
			return false;
		}

		// See if we're doing www.hostname.tld or hostname.tld
		if(eregi("^www\.",$hostname))
		{
			$web = true;
		}

		// double "." is a not permitted
		if(ereg("\.\.",$hostname))
		{
			$this->ERROR = "is_hostname: Double dot in [$hostname]";
			return false;
		}
		if(ereg("^\.",$hostname))
		{
			$this->ERROR = "is_hostname: leading dot in [$hostname]";
			return false;
		}

		$chunks = explode(".",$hostname);

		if( (gettype($chunks)) != "array")
		{
			$this->ERROR = "is_hostname: Invalid hostname, no dot seperator [$hostname]";
			return false;
		}

		$count = ( (count($chunks)) - 1);

		if($count < 1)
		{
			$this->ERROR = "is_hostname: Invalid hostname [$count] [$hostname]\n";
			return false;
		}

		// Bug that can't be killed without doing an is_host,
		// something.something will return TRUE, even if it's something
		// stupid like NS.SOMETHING (with no tld), because SOMETHING is
		// construed to BE the tld.  The is_bigfour and is_country
		// checks should help eliminate this inconsistancy. To really
		// be sure you've got a valid hostname, do an is_host() on it.

		if( ($web) and ($count < 2) )
		{
			$this->ERROR = "is_hostname: Invalid hostname [$count] [$hostname]\n";
			return false;
		}

		$tld = $chunks[$count];

		if(empty($tld))
		{
			$this->ERROR = "is_hostname: No TLD found in [$hostname]";
			return false;
		}

		if(!$this->is_bigfour($tld))
		{
			if(!$this->is_country($tld))
			{
				$this->ERROR = "is_hostname: Unrecognized TLD [$tld]";
				return false;
			}
		}


		return true;
	}

//	************************************************************

	public function is_bigfour ($tld)
	{
		if(empty($tld))
		{
			return false;
		}
		if(eregi("^\.",$tld))
		{
			$tld = eregi_replace("^\.","",$tld);
		}
		$BigFour = array (com=>com,edu=>edu,net=>net,org=>org);
		$tld = strtolower($tld);

		if(isset($BigFour[$tld]))
		{
			return true;
		}

		return false;
	}

//	************************************************************
//	Hostname is a reachable internet host? true or false

	public function is_host ($hostname = "", $type = "ANY")
	{
		if($this->CLEAR) { $this->clear_error(); }

		if(empty($hostname))
		{
			$this->ERROR = "is_host: No hostname submitted";
			return false;
		}

		if(!$this->is_hostname($hostname))	{ return false; }

		if(!checkdnsrr($hostname,$type))
		{
			$this->ERROR = "is_host: no DNS records for [$hostname].";
			return false;
		}

		return true;
	}

//	************************************************************
//	Dotted quad IPAddress within valid range? true or false
//	Checks format, leading zeros, and values > 255
//	Does not check for reserved or unroutable IPs.

	public function is_ipaddress ($IP = "")
	{
		if($this->CLEAR) { $this->clear_error(); }

		if(empty($IP))
		{
			$this->ERROR = "is_ipaddress: No IP address submitted";
			return false;
		}
		//	123456789012345
		//	xxx.xxx.xxx.xxx

		$len = strlen($IP);
		if( $len > 15 )
		{
			$this->ERROR = "is_ipaddress: too long [$IP][$len]";
			return false;
		}

		$Bad = eregi_replace("([0-9\.]+)","",$IP);

		if(!empty($Bad))
		{
			$this->ERROR = "is_ipaddress: Bad data in IP address [$Bad]";
			return false;
		}
		$chunks = explode(".",$IP);
		$count = count($chunks);

		if ($count != 4)
		{
			$this->ERROR = "is_ipaddress: not a dotted quad [$IP]";
			return false;
		}

		while ( list ($key,$val) = each ($chunks) )
		{
			if(ereg("^0",$val))
			{
				$this->ERROR = "is_ipaddress: Invalid IP segment [$val]";
				return false;
			}
			$Num = $val;
			settype($Num,"integer");
			if($Num > 255)
			{
				$this->ERROR = "is_ipaddress: Segment out of range [$Num]";
				return false;
			}

		}

		return true;

	}	// end is_ipaddress

//	************************************************************
//	IP address is valid, and resolves to a hostname? true or false

	public function ip_resolves ($IP = "")
	{
		if($this->CLEAR) { $this->clear_error(); }

		if(empty($IP))
		{
			$this->ERROR = "ip_resolves: No IP address submitted";
			return false;
		}

		if(!$this->is_ipaddress($IP))
		{
			return false;
		}

		$Hostname = gethostbyaddr($IP);

		if($Hostname == $IP)
		{
			$this->ERROR = "ip_resolves: IP does not resolve.";
			return false;
		}

		if($Hostname)
		{
			if(!checkdnsrr($Hostname))
			{
				$this->ERROR = "is_ipaddress: no DNS records for resolved hostname [$Hostname]";
				return false;
			}
			if( (gethostbyname($Hostname)) != $IP )
			{
				$this->ERROR = "is_ipaddress: forward:reverse mismatch, possible forgery";
				//	Non-fatal, but it should be noted.
			}
		}
		else
		{
			$this->ERROR = "ip_resolves: IP address does not resolve";
			return false;
		}

		return true;
	}



//	************************************************************
//	United States valid state code? true or false

	public function is_state ($State = "")
	{
		if($this->CLEAR) { $this->clear_error(); }

		if(empty($State))
		{
			$this->ERROR = "is_state: No state submitted";
			return false;
		}
		if( (strlen($State)) != 2)
		{
			$this->ERROR = "is_state: Too many digits in state code";
			return false;
		}

		$State = strtoupper($State);

		// 50 states, Washington DC, Puerto Rico and the US Virgin Islands

		$SCodes = array (

				"AK"	=>	1,
				"AL"	=>	1,
				"AR"	=>	1,
				"AZ"	=>	1,
				"CA"	=>	1,
				"CO"	=>	1,
				"CT"	=>	1,
				"DC"	=>	1,
				"DE"	=>	1,
				"FL"	=>	1,
				"GA"	=>	1,
				"HI"	=>	1,
				"IA"	=>	1,
				"ID"	=>	1,
				"IL"	=>	1,
				"IN"	=>	1,
				"KS"	=>	1,
				"KY"	=>	1,
				"LA"	=>	1,
				"MA"	=>	1,
				"MD"	=>	1,
				"ME"	=>	1,
				"MI"	=>	1,
				"MN"	=>	1,
				"MO"	=>	1,
				"MS"	=>	1,
				"MT"	=>	1,
				"NC"	=>	1,
				"ND"	=>	1,
				"NE"	=>	1,
				"NH"	=>	1,
				"NJ"	=>	1,
				"NM"	=>	1,
				"NV"	=>	1,
				"NY"	=>	1,
				"OH"	=>	1,
				"OK"	=>	1,
				"OR"	=>	1,
				"PA"	=>	1,
				"PR"	=>	1,
				"RI"	=>	1,
				"SC"	=>	1,
				"SD"	=>	1,
				"TN"	=>	1,
				"TX"	=>	1,
				"UT"	=>	1,
				"VA"	=>	1,
				"VI"	=>	1,
				"VT"	=>	1,
				"WA"	=>	1,
				"WI"	=>	1,
				"WV"	=>	1,
				"WY"	=>	1
			);

		if(!isset($SCodes[$State]))
		{
			$this->ERROR = "is_state: Unrecognized state code [$State]";
			return false;
		}

		// Lets not have this big monster camping in memory eh?
		unset($SCodes);

		return true;
	}

//	************************************************************
//	Valid postal zip code? true or false

	public function is_zip ($zipcode = "")
	{
		if($this->CLEAR) { $this->clear_error(); }

		if(empty($zipcode))
		{
			$this->ERROR = "is_zip: No zipcode submitted";
			return false;
		}

		$Bad = eregi_replace("([-0-9]+)","",$zipcode);

		if(!empty($Bad))
		{
			$this->ERROR = "is_zip: Bad data in zipcode [$Bad]";
			return false;
		}
		$Num = eregi_replace("\-","",$zipcode);
		$len = strlen($Num);
		if ( ($len > 10) or ($len < 5) )
		{
			$this->ERROR = "is_zipcode: Invalid length [$len] for zipcode";
			return false;
		}

		return true;

	}

//	************************************************************
//	Valid postal country code?
//	Returns the name of the country, or null on failure
//	Current array recognizes ~232 country codes.

//	I don't know if all of these are 100% accurate.
//	You don't wanna know how difficult it was just getting
//	this listing in here. :)

	public function is_country ($countrycode = "")
	{
		if($this->CLEAR) { $this->clear_error(); }

		$Return = "";

		if(empty($countrycode))
		{
			$this->ERROR = "is_country: No country code submitted";
			return $Return;
		}

		$countrycode = strtolower($countrycode);

		if( (strlen($countrycode)) != 2 )
		{
			$this->ERROR = "is_country: 2 digit codes only [$countrycode]";
			return $Return;
		}

		//	Now for a really big array

		//	Dominican Republic, cc = "do" because it's a reserved
		//	word in PHP. That parse error took 10 minutes of
		//	head-scratching to figure out :)

		//	A (roughly) 3.1 Kbyte array

		/*
		$CCodes =	array (

			"do"	=>	"Dominican Republic",
				ad	=>	"Andorra",
				ae	=>	"United Arab Emirates",
				af	=>	"Afghanistan",
				ag	=>	"Antigua and Barbuda",
				ai	=>	"Anguilla",
				al	=>	"Albania",
				am	=>	"Armenia",
				an	=>	"Netherlands Antilles",
				ao	=>	"Angola",
				aq	=>	"Antarctica",
				ar	=>	"Argentina",
				as	=>	"American Samoa",
				at	=>	"Austria",
				au	=>	"Australia",
				aw	=>	"Aruba",
				az	=>	"Azerbaijan",
				ba	=>	"Bosnia Hercegovina",
				bb	=>	"Barbados",
				bd	=>	"Bangladesh",
				be	=>	"Belgium",
				bf	=>	"Burkina Faso",
				bg	=>	"Bulgaria",
				bh	=>	"Bahrain",
				bi	=>	"Burundi",
				bj	=>	"Benin",
				bm	=>	"Bermuda",
				bn	=>	"Brunei Darussalam",
				bo	=>	"Bolivia",
				br	=>	"Brazil",
				bs	=>	"Bahamas",
				bt	=>	"Bhutan",
				bv	=>	"Bouvet Island",
				bw	=>	"Botswana",
				by	=>	"Belarus (Byelorussia)",
				bz	=>	"Belize",
				ca	=>	"Canada",
				cc	=>	"Cocos Islands",
				cd	=>	'Congo, The Democratic Republic of the',
				cf	=>	"Central African Republic",
				cg	=>	"Congo",
				ch	=>	"Switzerland",
				ci	=>	"Ivory Coast",
				ck	=>	"Cook Islands",
				cl	=>	"Chile",
				cm	=>	"Cameroon",
				cn	=>	"China",
				co	=>	"Colombia",
				cr	=>	"Costa Rica",
				cs	=>	"Czechoslovakia",
				cu	=>	"Cuba",
				cv	=>	"Cape Verde",
				cx	=>	"Christmas Island",
				cy	=>	"Cyprus",
				cz	=>	'Czech Republic',
				de	=>	"Germany",
				dj	=>	"Djibouti",
				dk	=>	'Denmark',
				dm	=>	"Dominica",
				dz	=>	"Algeria",
				ec	=>	"Ecuador",
				ee	=>	"Estonia",
				eg	=>	"Egypt",
				eh	=>	"Western Sahara",
				er	=>	'Eritrea',
				es	=>	"Spain",
				et	=>	"Ethiopia",
				fi	=>	"Finland",
				fj	=>	"Fiji",
				fk	=>	"Falkland Islands",
				fm	=>	"Micronesia",
				fo	=>	"Faroe Islands",
				fr	=>	"France",
				fx	=>	'France, Metropolitan FX',
				ga	=>	"Gabon",
				gb	=>	'United Kingdom (Great Britain)',
				gd	=>	"Grenada",
				ge	=>	"Georgia",
				gf	=>	"French Guiana",
				gh	=>	"Ghana",
				gi	=>	"Gibraltar",
				gl	=>	"Greenland",
				gm	=>	"Gambia",
				gn	=>	"Guinea",
				gp	=>	"Guadeloupe",
				gq	=>	"Equatorial Guinea",
				gr	=>	"Greece",
				gs	=>	'South Georgia and the South Sandwich Islands',
				gt	=>	"Guatemala",
				gu	=>	"Guam",
				gw	=>	"Guinea-bissau",
				gy	=>	"Guyana",
				hk	=>	"Hong Kong",
				hm	=>	"Heard and McDonald Islands",
				hn	=>	"Honduras",
				hr	=>	"Croatia",
				ht	=>	"Haiti",
				hu	=>	"Hungary",
				id	=>	"Indonesia",
				ie	=>	"Ireland",
				il	=>	"Israel",
				in	=>	"India",
				io	=>	"British Indian Ocean Territory",
				iq	=>	"Iraq",
				ir	=>	"Iran",
				is	=>	"Iceland",
				it	=>	"Italy",
				jm	=>	"Jamaica",
				jo	=>	"Jordan",
				jp	=>	"Japan",
				ke	=>	"Kenya",
				kg	=>	"Kyrgyzstan",
				kh	=>	"Cambodia",
				ki	=>	"Kiribati",
				km	=>	"Comoros",
				kn	=>	"Saint Kitts and Nevis",
				kp	=>	"North Korea",
				kr	=>	"South Korea",
				kw	=>	"Kuwait",
				ky	=>	"Cayman Islands",
				kz	=>	"Kazakhstan",
				la	=>	"Laos",
				lb	=>	"Lebanon",
				lc	=>	"Saint Lucia",
				li	=>	"Lichtenstein",
				lk	=>	"Sri Lanka",
				lr	=>	"Liberia",
				ls	=>	"Lesotho",
				lt	=>	"Lithuania",
				lu	=>	"Luxembourg",
				lv	=>	"Latvia",
				ly	=>	"Libya",
				ma	=>	"Morocco",
				mc	=>	"Monaco",
				md	=>	"Moldova Republic",
				mg	=>	"Madagascar",
				mh	=>	"Marshall Islands",
				mk	=>	'Macedonia, The Former Yugoslav Republic of',
				ml	=>	"Mali",
				mm	=>	"Myanmar",
				mn	=>	"Mongolia",
				mo	=>	"Macau",
				mp	=>	"Northern Mariana Islands",
				mq	=>	"Martinique",
				mr	=>	"Mauritania",
				ms	=>	"Montserrat",
				mt	=>	"Malta",
				mu	=>	"Mauritius",
				mv	=>	"Maldives",
				mw	=>	"Malawi",
				mx	=>	"Mexico",
				my	=>	"Malaysia",
				mz	=>	"Mozambique",
				na	=>	"Namibia",
				nc	=>	"New Caledonia",
				ne	=>	"Niger",
				nf	=>	"Norfolk Island",
				ng	=>	"Nigeria",
				ni	=>	"Nicaragua",
				nl	=>	"Netherlands",
				no	=>	"Norway",
				np	=>	"Nepal",
				nr	=>	"Nauru",
				nt	=>	"Neutral Zone",
				nu	=>	"Niue",
				nz	=>	"New Zealand",
				om	=>	"Oman",
				pa	=>	"Panama",
				pe	=>	"Peru",
				pf	=>	"French Polynesia",
				pg	=>	"Papua New Guinea",
				ph	=>	"Philippines",
				pk	=>	"Pakistan",
				pl	=>	"Poland",
				pm	=>	"St. Pierre and Miquelon",
				pn	=>	"Pitcairn",
				pr	=>	"Puerto Rico",
				pt	=>	"Portugal",
				pw	=>	"Palau",
				py	=>	"Paraguay",
				qa	=>	'Qatar',
				re	=>	"Reunion",
				ro	=>	"Romania",
				ru	=>	"Russia",
				rw	=>	"Rwanda",
				sa	=>	"Saudi Arabia",
				sb	=>	"Solomon Islands",
				sc	=>	"Seychelles",
				sd	=>	"Sudan",
				se	=>	"Sweden",
				sg	=>	"Singapore",
				sh	=>	"St. Helena",
				si	=>	"Slovenia",
				sj	=>	"Svalbard and Jan Mayen Islands",
				sk	=>	'Slovakia (Slovak Republic)',
				sl	=>	"Sierra Leone",
				sm	=>	"San Marino",
				sn	=>	"Senegal",
				so	=>	"Somalia",
				sr	=>	"Suriname",
				st	=>	"Sao Tome and Principe",
				sv	=>	"El Salvador",
				sy	=>	"Syria",
				sz	=>	"Swaziland",
				tc	=>	"Turks and Caicos Islands",
				td	=>	"Chad",
				tf	=>	"French Southern Territories",
				tg	=>	"Togo",
				th	=>	"Thailand",
				tj	=>	"Tajikistan",
				tk	=>	"Tokelau",
				tm	=>	"Turkmenistan",
				tn	=>	"Tunisia",
				to	=>	"Tonga",
				tp	=>	"East Timor",
				tr	=>	"Turkey",
				tt	=>	"Trinidad, Tobago",
				tv	=>	"Tuvalu",
				tw	=>	"Taiwan",
				tz	=>	"Tanzania",
				ua	=>	"Ukraine",
				ug	=>	"Uganda",
				uk	=>	"United Kingdom",
				um	=>	"United States Minor Islands",
				us	=>	"United States of America",
				uy	=>	"Uruguay",
				uz	=>	"Uzbekistan",
				va	=>	"Vatican City",
				vc	=>	"Saint Vincent, Grenadines",
				ve	=>	"Venezuela",
				vg	=>	"Virgin Islands (British)",
				vi	=>	"Virgin Islands (USA)",
				vn	=>	"Viet Nam",
				vu	=>	"Vanuatu",
				wf	=>	'Wallis and Futuna Islands',
				ws	=>	"Samoa",
				ye	=>	'Yemen',
				yt	=>	'Mayotte',
				yu	=>	"Yugoslavia",
				za	=>	"South Africa",
				zm	=>	"Zambia",
				zr	=>	"Zaire",
				zw	=>	"Zimbabwe"
		);
*/

		if(isset($CCodes[$countrycode]))
		{
			$Return = $CCodes[$countrycode];
		}
		else
		{
			$this->ERROR = "is_country: Unrecognized country code [$countrycode]";
			$Return = "";
		}

		// make sure this monster is removed from memory

		unset($CCodes);

		return ($Return);

	}	// end is_country

}	// End class



?>
