<?php

$hookSecret = null;  # set NULL to disable check
set_error_handler(function($severity, $message, $file, $line) {
	throw new \ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function($e) {
	header('HTTP/1.1 500 Internal Server Error');
	echo "Error on line {$e->getLine()}: " . htmlSpecialChars($e->getMessage());
	die();
});
$rawPost = NULL;
if ($hookSecret !== NULL) {
	if (!isset($_SERVER['HTTP_X_HUB_SIGNATURE'])) {
		throw new \Exception("HTTP header 'X-Hub-Signature' is missing.");
	} elseif (!extension_loaded('hash')) {
		throw new \Exception("Missing 'hash' extension to check the secret code validity.");
	}
	list($algo, $hash) = explode('=', $_SERVER['HTTP_X_HUB_SIGNATURE'], 2) + array('', '');
	if (!in_array($algo, hash_algos(), TRUE)) {
		throw new \Exception("Hash algorithm '$algo' is not supported.");
	}
	$rawPost = file_get_contents('php://input');
	/*
	if ($hash !== hash_hmac($algo, $rawPost, $hookSecret)) {
		throw new \Exception('Hook secret does not match.');
	}
	*/
};
if (!isset($_SERVER['HTTP_CONTENT_TYPE'])) {
	//throw new \Exception("Missing HTTP 'Content-Type' header.");
} elseif (!isset($_SERVER['HTTP_X_GITHUB_EVENT'])) {
	throw new \Exception("Missing HTTP 'X-Github-Event' header.");
}
//switch ($_SERVER['HTTP_CONTENT_TYPE']) {
//	case 'application/json':
		$json = $rawPost ?: file_get_contents('php://input');
//		break;
//	case 'application/x-www-form-urlencoded':
//		$json = $_POST['payload'];
//		break;
//	default:
//		throw new \Exception("Unsupported content type: $_SERVER[HTTP_CONTENT_TYPE]");
//}
# Payload structure depends on triggered event
# https://developer.github.com/v3/activity/events/types/
$payload = json_decode($json, true);
		
$repo_url = $payload['repository']['git_url'];
$dir = 'projects'.DIRECTORY_SEPARATOR.$payload['repository']['full_name'];
$source = $dir.'/source';
$built = $dir.'/built';

if (!file_exists($built)) {
	mkdir($built, 0777, true);
}

shell_exec("git clone {$repo_url} {$source}");
shell_exec("php composer.phar install --working-dir={$source} --no-dev --optimize-autoloader --no-interaction");
shell_exec("rm -Rf {$built}/*");
shell_exec("php -d phar.readonly=off compiler.phar compile {$source} --dest={$built}");

$conf = json_decode(file_get_contents($dir.'/app.json'));



if ('release'===$_SERVER['HTTP_X_GITHUB_EVENT']) {

	$token = $conf->token;


	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, str_replace('{+path}', 'compiler.json', $payload['repository']['contents_url']));
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Accept: application/json',
		'User-Agent: Millesime-Appellation-App',
		'Authorization: token '.$token,
	]);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$file = json_decode(curl_exec($ch));
	$content = base64_decode($file->content);

	foreach ($content->distrib as $distrib) {
		$url = $payload['release']['upload_url'];
		$url = str_replace('{?name,label}', '?name='.$distrib->name.'.phar&label='.$distrib->name.'.phar', $url);

		$headers = array(
			'Content-Type: application/phar',
			'User-Agent: Millesime-Appellation-App',
			'Authorization: token '.$token,
		);

		$options = array(
			CURLOPT_URL => $url,
		    CURLOPT_POST => 1,
		    CURLOPT_POSTFIELDS => file_get_contents($built.'/'.$distrib->name.'.phar'),
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_BINARYTRANSFER => 1,
			CURLOPT_RETURNTRANSFER => 1,
		);

		$ch = curl_init();
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
	}


}

//		header('HTTP/1.0 404 Not Found');
//		echo "Event:$_SERVER[HTTP_X_GITHUB_EVENT] Payload:\n";
//		print_r($payload); # For debug only. Can be found in GitHub hook log.
//		die();
