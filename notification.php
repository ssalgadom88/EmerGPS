<?php

/**
 * This function sends SMS messages from specified Apifonica phone number to specified mobile number
 * @param string $api_url URL to retrieve Apifonica API
 * @param string $accountSID Your Apifonica account identifier
 * @param string $password Password for your Apifonica account
 * @param string $from Apifonica number used as a message sender
 * @param string $to Recipient’s mobile phone number
 * @param string $message Message text
 * @return array 
 */
 

// This function will run within each post array including multi-dimensional arrays 
function ExtendedAddslash(&$params)
{ 
        foreach ($params as &$var) {
            // check if $var is an array. If yes, it will start another ExtendedAddslash() function to loop to each key inside.
            is_array($var) ? ExtendedAddslash($var) : $var=addslashes($var);
            unset($var);
        }
} 

// Initialize ExtendedAddslash() function for every $_POST variable
ExtendedAddslash($_POST);      

$submission_id = $_POST['submission_id']; 
$formID = $_POST['formID'];
$ip = $_POST['ip'];
$name = $_POST['name'];
$contactnumber = $_POST['contactnumber'];
$gpslocation = $_POST['gpslocation'];

 
function sendSMS($api_url, $accountSID, $password, $from, $to, $message) {

		$body = array(
			'from' => $from,
			'to' => $to,
			'text' => $message,
		);
	
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $api_url.'/v2/accounts/'.$accountSID.'/messages');
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_FOLLOWLOCATION, 1);
		// Set user and password
		curl_setopt ($curl, CURLOPT_USERPWD, $accountSID.':'.$password);
		// Do not check SSL
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		// Add header
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		// Set POST
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
		
		$result = curl_exec($curl);
		
		if ($result) {
			$result = json_decode($result, true);
		} else {
			$result = array(
				'error_text' => curl_error($curl),	
				'error_code' => curl_errno($curl),
				'status_code' => 600,
			);
		}
		
		return $result;
}

/**
 * This function checks the current status of the message
 * @param string $api_url URL to retrieve Apifonica API
 * @param string $accountSID Your Apifonica account identifier
 * @param string $password Password for your Apifonica account
 * @param string $smsuri SMS URL for check status 
 * @return array 
 */
function checkSMS($api_url, $accountSID, $password, $smsuri) {

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $api_url.$smsuri);
	curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($curl, CURLOPT_FOLLOWLOCATION, 1);
	// Set user and password
	curl_setopt ($curl, CURLOPT_USERPWD, $accountSID.':'.$password);
	// Do not check SSL
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	// Add header
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

	$result = curl_exec($curl);

	if ($result) {
		$result = json_decode($result, true);
	} else {
		$result = array(
				'error_text' => curl_error($curl),
				'error_code' => curl_errno($curl),
				'status_code' => 600,
		);
	}

	return $result;
}


/**
 * Set default variables 
 */

// Specify API URL
$api_url = 'XXXXXXXXXXX-UPDATE-HERE-WITH-YOUR-ACCOUNT-INFO-XXXXXXXXX';
// Specify your account SID
$accountSID = 'XXXXXXXXXXX-UPDATE-HERE-WITH-YOUR-ACCOUNT-INFO-XXXXXXXXX';
// Specify your account password
$password = 'XXXXXXXXXXX-UPDATE-HERE-WITH-YOUR-ACCOUNT-INFO-XXXXXXXXX';
// Specify the message sender's number (this number must be mobile number belonging to your account you have specified) 
$from = 'XXXXXXXXXXX-UPDATE-HERE-WITH-YOUR-ACCOUNT-INFO-XXXXXXXXX';

/**
 * Set variables from POST
 */
  
$action = isset($_POST['action'])?$_POST['action']:'default';
$email =  isset($_POST['email'])?trim($_POST['email']):'';
$name =  isset($_POST['name'])?trim($_POST['name']):'';
$message =  isset($_POST['message'])?trim($_POST['message']):'';
$allsms =  isset($_POST['allsms'])?trim($_POST['allsms']):'';

$result = false;
$numbers = "";
/**
 * Send Bulk SMS
 */
if ($action == 'sendsms') {

	//Remove all unnecessary things from the string
	$str = preg_replace('/[^0-9,]/', '', $_POST["numbers"]);

	$list = explode(",", $str);

	$arr_succesful = array();
	$fail = "";
	
	foreach ($list as $key => $val) { 		
		if ($val){		
			$b = false;	
			// Check if the phone number is valid
			if (preg_match("/^\+?[\d\s]+\(?[\d\s]{10,}$/", $val)) $b = true;		
			if ($b){
				
				$result = sendSMS($api_url, $accountSID, $password, $from, $val, $message);	
				if ($result['status_code'] == '201') $arr_succesful[$val] = $result['uri'];
			}
			else{
				$fail  .= "<span style='color:red'>" . $val . " Status = Phone number is invalid</span><br />";	
			}
		}
	}
	$out = "";
	foreach ($arr_succesful as $key => $val) { 	
		$out .= "<span style='color:green'>" . $key . ", Status = Message is successfully sent.</span><br />";	
	}
	
	//Serialize data for further SMS delivery checks
	$allsms = serialize($arr_succesful);
	
	$out .= $fail;
	
	if ($result['status_code'] > 299) {
		// Display an error message and the web entry form if the SMS send action is failed
		$action = 'view';
	}
	
/**
 * Check statuses of SMS notifications sent
 */
} else if ($action == 'checksms') {
	
	if ($allsms){
	
		$sms_status = unserialize($allsms);
		$check_text = '';
		
		foreach ($sms_status as $key => $val) { 

			$result = checkSMS($api_url, $accountSID, $password, $val);

			if ($result && isset($result['status'])) {
				switch ($result['status']) {
					case 'queued':
						$check_text .= $key . ', Status = Message is on its way! <br />';
						break;
					case 'sent':
						$check_text .= $key . ', Status = Message is successfully sent. <br />';
						break;
					case 'delivered':
						$check_text .= $key . ', Status = Message is delivered to recipient\'s phone. <br />';
						break;
					case 'failed':
						$check_text .= $key . ', Status = Message delivery failed. <br />';
						break;
					default: 
				}
			}
		}
	}
}
/**
 * Default view form (Mass Text Messaging)
 */

?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf8">
<title>Apifonica - send bulk SMS via Apifonica API</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>
<style type="text/css">
	body { 
		padding: 50px;
	}
	.bg-danger, .bg-success {
		padding: 5px;
	}
</style>
</head>
<body>
	<h1>Thanks For Your Subscription</h1>
	<p>
		<form method="post" action="" class="col-md-6">
		<?php if ($result && isset($result['status_code']) && $result['status_code'] > 299) { ?>
			<div class="bg-danger form-group">
				<h4>An error occured while sending the request :(</h4>
				<ul><li><?php echo $result['error_text']; ?></li></ul>
			</div>
		<?php } ?>
		<?php if ($action == 'sendsms') { ?>
			<input type="hidden" name="action" value="checksms"/>
			<input type="hidden" name="allsms" value='<?php echo $allsms; ?>'/>
			<div class="bg-success form-group">
				<h3>Simulate an SMS notification</h3>
                <?php echo $contactnumber; ?>
                <h4><?php echo $out; ?></h4>
			</div>
			<div>
				<a href="http://sevirtual.com/apialerts/register.html#1" class="btn btn-default">Register another number</a>
			</div>
		<?php } else if ($action == 'checksms') { ?>
			<input type="hidden" name="action" value="checksms"/>
			<input type="hidden" name="allsms" value='<?php echo $allsms; ?>'/>
            
			<div class="bg-success form-group">
				<h4><?php echo $check_text; ?></h4>
			</div>
			<div>
				<a href="http://sevirtual.com/apialerts/register.html#1" class="btn btn-default">Register another number</a>
			</div>
		<?php } else { ?>
		  <input type="hidden" name="action" value="sendsms"/>
		  <h3>Complete the form below to SIMULATE sending an SMS location alert to your trustee contact</h3>		
		  <div class="form-group">
		    <label for="InputNumbers">Your registered trustee contact:</label><br />
		    <textarea class="form-control" id="InputNumbers" name="numbers" required readonly><?php echo $contactnumber; ?></textarea>
		  </div>

		  <div class="form-group">
		    <label for="InputMessage">Message to be sent:</label>
		    <textarea class="form-control" id="InputMessage" name="message" required readonly>SIMULATION - <?php echo $name; ?> last known Latitude/Longitude location was the following <?php echo $gpslocation; ?> when an earthquake stroked nearby. - SIMULATION</textarea>
		  </div>
		  <button type="submit" class="btn btn-default">Send Your SIMULATION Message</button>
		<?php } ?>
		</form>
	</p>
</body>
</html>
