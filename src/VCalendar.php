<?php 

namespace Hansvn\Vcalendar;

class Vcalendar {

	const VCAL_DTFORMAT = "Ymd\THi00";
	private static $REQ_PARAMS		= array('prodid', 'uid', 'categories', 'organizer', 'location', 'subject', 'description', 'start_date', 'end_date', 'rsvp', 'attendees');
	private static $PRODID_PARAMS 	= array('company', 'product', 'language');
	private static $PERSON_PARAMS	= array('name', 'email');

	protected $use_utc = true;

	protected $vcal_data = array(
		'prodid' => array('company' => 'My Company', 'product' => 'VCalendar attachment', 'language' => 'EN'),
		'uid' => 'hello@example.com',
		'categories' => '',
		'organizer' => array('name' => 'My Company', 'email' => 'hello@example.com'),
		'location' => '',
		'subject' => '',
		'description' => '',
		'start_date' => '',
		'end_date' => '',
		'rsvp' => false,
		'attendees' => array(
			array('name' => '', 'email' => '')
		)
	);

	/**
	 *
	 * the .vcal can also be passed to the constructor
	 *
	 **/
	public function __construct($data = null) {
		//check if debugging mode is on
		define("DEBUG", \Config::get('app.debug') );

		if(is_array($data))
			array_merge($this->vcal_data, $data);
		else {
			try {
				$data = (array) $data;
				array_merge($this->vcal_data, $data);
			}
			catch (\Exception $e) {
				throw new \Exception($e->getMessage(), 500);
			}
		}
	}

	/**
	* Generate the vcalendar file
	*
	* @access public
	* @param array 	$data 	the data to build the .vcal file check the docs for the required keys
	* @return string 		the file location
	*/
	public function generate($data = array()) {
		$data = array_merge((array)$this->vcal_data, (array)$data);
		$start_date = \DateTime::createFromFormat('Y-m-d H:i:s', $data['start_date']);
		$end_date = \DateTime::createFromFormat('Y-m-d H:i:s', $data['end_date']);
		$now = new \DateTime;

		//validate before proceeding
		if( ! $start_date )
			throw new \Exception("The start date must be in format 'Y-m-d H:i:s'!", 500);
		if( ! $end_date )
			throw new \Exception("The end date must be in format 'Y-m-d H:i:s'!", 500);
		if( ! $this->all_array_keys_exist($data, self::$REQ_PARAMS))
			throw new \Exception("Invalid parameters!", 500);
		if( ! $this->all_array_keys_exist($data['prodid'], self::$PRODID_PARAMS))
			throw new \Exception("Invalid prodid parameters! - must be an array with keys 'company', 'product' and 'language'.", 500);
		if( ! $this->all_array_keys_exist($data['organizer'], self::$PERSON_PARAMS))
			throw new \Exception("Invalid organizer parameters! - must be an array with keys 'name' and 'email'.", 500);

		foreach ($data['attendees'] as $key => $attendee) {
			if( ! $this->all_array_keys_exist($attendee, self::$PERSON_PARAMS))
				throw new \Exception("Invalid attendee parameters fo attendee #$key! - must be an array with keys 'name' and 'email'.", 500);
		}
			
		//generate all ics file lines
		$vcal[]	= "BEGIN:VCALENDAR";
		$vcal[]	= "VERSION:2.0";
		$vcal[]	= "CALSCALE:GREGORIAN";
		$vcal[]	= "METHOD:REQUEST";
		$vcal[]	= "PRODID:-//"	. $data['prodid']['company'] . "//" . $data['prodid']['product'] . "//" . strtoupper($data['prodid']['language']);

		$vcal[]	= "BEGIN:VEVENT";
		$vcal[]	= "CATEGORIES:"	. $data['categories'];
		$vcal[]	= "ORGANIZER;CN=". $data['organizer']['name'] . ":MAILTO:" . $data['organizer']['email'];

		foreach ($data['attendees'] as $attendee) {
			$vcal[]	= "ATTENDEE;ROLE=REQ-PARTICIPANT;RSVP=".$data['rsvp'].";CN=".$attendee['name'].":MAILTO:".$attendee['email'];
		}

		$vcal[]	= "DTSTART" 	. $this->getTime($start_date);
		$vcal[]	= "DTEND"   	. $this->getTime($end_date);
		$vcal[]	= "DTSTAMP" 	. $this->getTime($now); //when vcal was created
		$vcal[]	= "CREATED" 	. $this->getTime($now);
		$vcal[]	= "LAST-MODIFIED".$this->getTime($now);
		$vcal[]	= "UID:"	 	. $data['uid']; //unique identifier
		$vcal[]	= "LOCATION:"	. $data['location'];
		$vcal[]	= "SEQUENCE:0";  //0 if this vcal is not an update to a previous one
		$vcal[]	= "STATUS:CONFIRMED";
		$vcal[]	= "SUMMARY:"	. $data['subject'];//subject
		$vcal[]	= "DESCRIPTION:". $data['description'];
		$vcal[]	= "TRANSP:OPAQUE"; //if set to opaque, the time on the calendar is actually taken, when set to transparent, the receiver is available on free-busy time searches
		$vcal[]	= "END:VEVENT";

		$vcal[]	= "END:VCALENDAR";

		$vcal = implode("\r\n", $vcal);

		$fname = tempnam(sys_get_temp_dir(), 'vcal-');
		$filename = $fname.'.ics';
		unlink($fname);
		$file = fopen($filename, 'w');
		fwrite($file, "\xEF\xBB\xBF".  $vcal);
		fclose($file);

		return $filename;
	}

	/**
	* Send The mail
	*
	* @access public
	* @param string || array $from 	email
	* @param string $to 			email
	* @param string $subject 		the subject of the email
	* @param array 	$data 			the data to build the emails.vcalendar.blade.php file
	* @param string $file 			the path to the .vcal file to attach
	* @return bool
	*/
	public function sendMail($from, $to, $subject, $data, $file, $cc = null) {
		try{
			if(strpos($to, '@') === false) {
				throw new \Exception("Receiver email is not an email", 500);	
			}

			//send the mail with attachment
			\Mail::send('emails.vcalendar', $data, function($message) use ($to, $subject, $from, $file, $cc) {
				if(is_array($from) && array_key_exists('email', $from) && array_key_exists('name', $from))
					$message->from( array($from['email'] => $from['name']) );
				else
					$message->from($from);
				
				$message->to($to)->subject($subject);

				if($cc != null) {
					if(is_array($cc) && array_key_exists('email', $cc) && array_key_exists('name', $cc))
						$message->cc( array($cc['email'] => $cc['name']) );
					else
						$message->cc($cc);
				}

				$message->attach($file, array('mime' => 'text/x-vCalendar'));
			});
			//delete the file
			unlink($file);
			return true;
		}
		catch(\Exception $e) {
			\Log::error("*** VCalendar Error ***\n".$e->getMessage()."\n******");

			if(DEBUG)
				return $e->getMessage();
			else
				return false;
		}
	}

	/**
	* Convert given Date to UTC time
	*
	* @access protected
	* @param DateTime 	$date 	date to convert
	* @return DateTime
	*/
	protected function getTime($date) {
		if($this->use_utc) {
			$date->setTimezone( new \DateTimeZone("UTC") );
			return ':' . $date->format(self::VCAL_DTFORMAT).'Z';
		}
		else {
			return ';TZID=' . $date->getTimezone()->getName() . ':' . $date->format(self::VCAL_DTFORMAT);
		}
	}

	/**
	* Validate the given arrays
	*
	* @access protected
	* @param array 	$data 		array to check
	* @param array 	$required 	array with required keys
	* @return bool
	*/
	protected static function all_array_keys_exist($data, $required = array()) {
		if(!is_array($data) || !is_array($required)) {
			if(DEBUG)
				throw new Exception("The parameters must be of the type array", 500);
			else
				return false;
		}
			
		if(count(array_intersect_key(array_flip($required), $data)) === count($required))
			return true;
		else
			return false;
	}

	/**
	* GETTERS AND SETTERS
	**/
	public function setProdid($prodid) {
		if( ! $this->all_array_keys_exist($prodid, self::$PRODID_PARAMS))
			throw new \Exception("Invalid prodid parameters! - must be an array with keys 'company', 'product' and 'language'.", 500);

		$this->vcal_data['prodid'] = $prodid;
	}
	public function getProdid() {
		return $this->vcal_data['prodid'];
	}
	public function setUID($uid) {
		$this->vcal_data['uid'] = $uid;
	}
	public function getUID() {
		return $this->vcal_data['uid'];
	}
	public function setCategories($categories) {
		$this->vcal_data['categories'] = $categories;
	}
	public function getCategories() {
		return $this->vcal_data['categories'];
	}
	public function setOrganiser($organizer) {
		if( ! $this->all_array_keys_exist($organizer, self::$PERSON_PARAMS))
			throw new \Exception("Invalid organizer parameters! - must be an array with keys 'name' and 'email'.", 500);

		$this->vcal_data['organizer'] = $organizer;
	}
	public function getOrganizer() {
		return $this->vcal_data['organizer'];
	}
	public function setLocation($location) {
		$this->vcal_data['location'] = $location;
	}
	public function getLocation() {
		return $this->vcal_data['location'];
	}
	public function setSubject($subject) {
		$this->vcal_data['subject'] = $subject;
	}
	public function getSubject() {
		return $this->vcal_data['subject'];
	}
	public function setDescription($description) {
		$this->vcal_data['description'] = $description;
	}
	public function getDescription() {
		return $this->vcal_data['description'];
	}
	public function setStartDate($start_date) {
		$this->vcal_data['start_date'] = $start_date;
	}
	public function getStartDate() {
		return $this->vcal_data['start_date'];
	}
	public function setEndDate($end_date) {
		$this->vcal_data['end_date'] = $end_date;
	}
	public function getEndDate() {
		return $this->vcal_data['end_date'];
	}
	public function setRSVP($rsvp) {
		$this->vcal_data['rsvp'] = $rsvp;
	}
	public function getRSVP() {
		return $this->vcal_data['rsvp'];
	}
	public function setAttendee($attendee) {
		if( ! $this->all_array_keys_exist($attendee, self::$PERSON_PARAMS))
			throw new \Exception("Invalid attendee parameters! - must be an array with keys 'name' and 'email'.", 500);

		$this->vcal_data['attendees'][] = $attendee;
	}
	public function getAttendee($key) {
		return $this->vcal_data['attendees'][$key];
	}
	public function getAttendees() {
		return $this->vcal_data['attendees'];
	}

}
