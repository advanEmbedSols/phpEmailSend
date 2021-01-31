<?php
//Copyright 2021 - Kyle Johnson
/* This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

error_reporting(E_ALL);
ini_set('log_errors', 1);

//Set the webadmin
$WEB_ADMIN_EMAIL = '';

//Function to alert system admin of errors via email
function alertOnError($errno, $errstr) 
{
	global $WEB_ADMIN_EMAIL;
	error_log("Error: [$errno] $errstr",1,
	$WEB_ADMIN_EMAIL);
}
  
//set error handler
set_error_handler("alertOnError");


//Function to create the JSON return messages
//Param:
// $succ = boolean if successful or not
// $strMsg = Extra information
function getJsonMessage($succ, $strMsg)
{
	$msg = array('success'=>$succ, 'message'=> $strMsg);
	return (json_encode($msg));
}

//Only support POST
try
{
	if( ($_SERVER["REQUEST_METHOD"] == "POST") && isset($_POST['name']) && isset($_POST['email']) && isset($_POST['message']) )
	{
		if(is_string($_POST["name"]))
		{
			//Use a regular expression to verify the name.
			preg_match("/^[A-Za-z\s\.]+$/", $_POST["name"], $name);
			if (count($name) == 0)
			{
				http_response_code(200);
				echo getJsonMessage(false, "Name was incorrect. Must only be letters.");
			}
			else
			{
				//The return is an array
				$name = $name[0];
				if (strlen($name) > 100) 
				{
					http_response_code(200);
					echo getJsonMessage(false, "Name was incorrect. Must be less than 100 characters.");
				}
				else
				{
					//Sanitize Email to remove any illegal characters.
					$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
					//Validate email. Size no more than 100
					if(filter_var($email, FILTER_VALIDATE_EMAIL))
					{
						if (strlen($email) > 100)
						{
							//Email was not send
							http_response_code(200);
							echo getJsonMessage(false, "Email address is too long!");
						}
						else
						{
							//Email is correct so proceed with sending email
							//Sanitize the message.
							$message = str_replace("\n.", "\n..", htmlspecialchars(strip_tags(filter_var($_POST['message'], FILTER_SANITIZE_STRING))));
							//Verify message is not greater than 500 words
							if (strlen($message) > 500)
							{
								http_response_code(200);
								echo getJsonMessage(false,"Email message cannot be over 500 words!");
							}
							else
							{

								//Add email to message
								$headers = "From: ".$email."\r\n";
								if(mail($WEB_ADMIN_EMAIL, 'Email Form', $message, $headers))
								{
									//Successful send
									http_response_code(200);
									echo getJsonMessage(true, "Email was sent.");
								}
								else
								{
									//Email was not send
									http_response_code(200);
									echo getJsonMessage(false, "Email could not be sent. Verify and try again!");

								}

							}
						}

					}
					else
					{
						http_response_code(200);
						echo getJsonMessage(false, "Email was not valid.");
					}
				}
			}
		}		
	}
	else
	{
		http_response_code(200);
		echo getJsonMessage(false, "Interface only accepts POST");
	}
}
catch(Exception $e)
{
	http_response_code(500);
	echo getJsonMessage(false, "");
}
?>
