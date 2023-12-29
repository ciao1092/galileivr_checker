<?php

require __DIR__ . "/vendor/autoload.php";

function exception_handler($ex)
{
	$errno = $ex->getCode();
	$errfile = $ex->getFile();
	$errline = $ex->getLine();
	$errstr = $ex->getMessage();
	echo "*** An error occourred ***\n";
	echo "[ERROR_$errno] $errstr (at $errfile:$errline)";
	echo "\n";
	echo "\n";
	exit($errno);
}

set_exception_handler("exception_handler");


function Main()
{
	// make it accessible outsite Main function
	global $api;

	echo "##########################################\n";
	echo "# This is galileivr_checker version 1.0. #\n";
	echo "##########################################\n\n";

	echo "Checking for users whose email does not belong to the \"galileivr.org\" domain...\n\n";

	$dotenv = Dotenv\Dotenv::createImmutable(dirname(Phar::running(false)), "galileivr_checker.conf");
	$dotenv->load();

	$DRY_RUN = $_ENV['DRY_RUN'];
	$OAUTH_BEARER = $_ENV['OAUTH_BEARER'];
	$INSTANCE = $_ENV['INSTANCE'];

	$api = new RestClient(['base_url' => "https://" . $INSTANCE, 'headers' => ['Authorization' => 'Bearer ' . $OAUTH_BEARER]]);
	$userlist = "";
	$myself = get_myself();
	$result = $api->get("api/v2/admin/accounts", ['origin' => 'local']);
	if ($result->info->http_code == 200) {
		foreach ($result->decode_response() as $user) {
			if (!str_ends_with($user->email, "@galileivr.org")) {
				if ($user->username != $myself->username && $user->email != $myself->email) {
					echo ("Found: $user->username <$user->email> (UID=$user->id)\n");
					$userlist .= "$user->username <$user->email> (UID=$user->id)\n";
				}
			}
		}
	} else
		throw new Exception('HTTP response does not indicate a successful status code.', $result->info->http_code);

	echo "\nDone.\n";

	echo "\nI will now report them to my followers.\n";

	$status = "\n" . "** 🤖 galileivr_checker REPORT **\n" . "\nThese are the users whose email does not belong to \"galileivr.org\":\n\n```\n";
	$status .= $userlist;
	$status .= "```\n";
	$status .= "\n" . "* To stop receiving these messages, unfollow this account. *\n";
	if ($DRY_RUN == "false") {
		$result = $api->post("api/v1/statuses", ['status' => $status, 'visibility' => "direct"]);
		if ($result->info->http_code != 200)
			throw new Exception('HTTP response does not indicate a successful status code.', $result->info->http_code);
	} else {
		echo "Running with DRY_RUN = $DRY_RUN.\n";
		echo "This message would have been sent:\n";
		echo "\n------------------------------------------------------------------------------\n";
		echo $status;
		echo "\n------------------------------------------------------------------------------\n";
	}
	echo ("\nDone.\n");
	echo "\n";
	exit(0);
}

function get_myself()
{
	global $api;
	$result = $api->get("api/v1/accounts/verify_credentials");
	if ($result->info->http_code == 200) {
		$my_id = $result->decode_response()->id;
		$result = $api->get("api/v1/admin/accounts/$my_id");
		if ($result->info->http_code == 200) {
			return ($result->decode_response());
		}
	}
	throw new Exception('HTTP response does not indicate a successful status code.', $result->info->http_code);
} ?>

<?php Main(); ?>