<?php

	include('../db.php');

	include ('../../../vendor/autoload.php');
	use \Firebase\JWT\JWT;
	use Endroid\QrCode\QrCode;

	include('../config.php');

	$name = $_POST["name"];
	$gender = $_POST["gender"];
	$phone = $_POST["phone"];
	$college = $_POST["college"];
	$address = $_POST["address"];
	$email = $_POST["email"];
	$password = $_POST["password"];
	$accomodation = $_POST["accomodation"];

	function validateName($name) {

		if ($name == '') {

			echo(json_encode(array('status' => 'failure', 'result' => 'name is required')));
		} else {

			if (!preg_match("/^[a-zA-Z ]*$/", $name)) {

				echo(json_encode(array('status' => 'failure', 'result' => 'name should only contain letters and spaces')));
			}
		}
	}

	function validateGender($gender) {

		if ($gender != 'male' && $gender != 'female' && $gender != 'others') {

			echo(json_encode(array('status' => 'failure', 'result' => 'invalid gender')));
		}
	}

	function validatePhone($phone) {

		if (!preg_match("/^[789]\d{9}$/", $phone)) {

			echo(json_encode(array('status' => 'failure', 'result' => 'phone number should have 10 digits and should start with 7,8, or 9')));
		}
	}

	function checkExistingCollege($college) {

		global $conn;

		// $query = mysqli_query($conn, 'SELECT * FROM colleges WHERE name="'.$college.'"');
		// return (mysqli_num_rows($query) != 0);

		return true;
	}

	function validateCollege($college) {

		if ($college == '') {

			echo(json_encode(array('status' => 'failure', 'result' => 'college is required')));
		} else {

			if (!checkExistingCollege($college)) {

				echo(json_encode(array('status' => 'failure', 'result' => 'college not found in our records')));
			}
		}
	}

	function validateAddress($address) {

		if ($address == '') {

			echo(json_encode(array('status' => 'failure', 'result' => 'address is required')));
		}
	}

	function checkExistingEmail($email) {

		global $conn;

		$query = mysqli_query($conn, 'SELECT * FROM users WHERE email="'.$email.'"');
		return (mysqli_num_rows($query) != 0);
	}

	function validateEmail($email) {

		if ($email == '') {

			echo(json_encode(array('status' => 'failure', 'result' => 'email is required')));
		} else {

			if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {

				echo(json_encode(array('status' => 'failure', 'result' => 'valid email is required')));
			} else {

				if (checkExistingEmail($email)) {

					echo(json_encode(array('status' => 'failure', 'result' => 'email is already registered')));
				}
			}
		}
	}

	function validatePassword($password) {

		if (strlen($password) < 7) {

			echo (json_encode(array('status' => 'failure', 'result' => 'password needs to have 8-32 characters')));
		}
	}
	
	function validateAccomodation($accomodation) {

		if ($accomodation != 'yes' && $accomodation != 'no') {

			echo(json_encode(array('status' => 'failure', 'result' => 'valid accomodation details required')));
		}
	}

	if ( isset($name) && isset($gender) && isset($phone) && isset($college) && isset($address) && isset($email) && isset($password) && isset($accomodation)) {

		validateName($name);
		validateGender($gender);
		validatePhone($phone);
		validateCollege($college);
		validateAddress($address);
		validateEmail($email);
		validatePassword($password);
		validateAccomodation($accomodation);

		$query = mysqli_query($conn, "INSERT INTO users ( name, gender, phone, college, address, email, password, accomodation) VALUES ('".mysqli_real_escape_string($conn, $name)."', '".mysqli_real_escape_string($conn, $gender)."', '".mysqli_real_escape_string($conn, $phone)."', '".mysqli_real_escape_string($conn, $college)."', '".mysqli_real_escape_string($conn, $address)."', '".mysqli_real_escape_string($conn, $email)."', '".mysqli_real_escape_string($conn, md5($password))."', '".mysqli_real_escape_string($conn, $accomodation)."')");

		if ($query) {

			$query = mysqli_query($conn, 'SELECT inno_id FROM users WHERE email="'.$email.'"');
			$inno_id = mysqli_fetch_array($query)["inno_id"];

			$data = [

                'iat'  => $issuedAt,
                'jti'  => $tokenId,
                'iss'  => $serverName,
                'nbf'  => $notBefore,
                'exp'  => $expire,
                'data' => [

                    'inno_id'  => $inno_id,
                    'email'    => $email,

                ]
            ];

            $secretKey = base64_decode(SECRET_KEY);             /// Here we will transform this array into JWT:
            $jwt = JWT::encode(

                $data, //Data to be encoded in the JWT
                $secretKey, // The signing key
                ALGORITHM
            );

			$query = mysqli_query($conn, "UPDATE users SET token = '".$jwt."' WHERE inno_id = ".$inno_id);
			if ($query) {

				error_reporting(0);

				$qrcode = new QrCode(array('inno_id' => $inno_id, 'email' => $email));
				$filepath = '../../images/qrcodes/'.$inno_id.'.png';				
				$qrcode->writeFile($filepath);
				$query = mysqli_query($conn, "UPDATE users SET qr_code_path = '".$filepath."' WHERE inno_id = ".$inno_id);
				echo(json_encode(array('status' => 'success', 'message' => 'successfully registered')));
			} else{

				echo(json_encode(array('status' => 'failure', 'message' => 'token not set')));
			}
		} else {

			echo(json_encode(array('status' => 'failure', 'message' => 'DB operation failed')));
		}
	}

?>