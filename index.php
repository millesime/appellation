<?php

include 'config.php';

session_start();

if (!$_SESSION['oauth_state']) {
	$_SESSION['oauth_state'] = uniqid();
}

$state = $_SESSION['oauth_state'];

if (!array_key_exists('token', $_SESSION)) {
	if (!array_key_exists('code', $_GET)) {

		$redirect = sprintf(
			'https://github.com/login/oauth/authorize?client_id=%s&scope=%s&redirect_uri=%s&state=%s', 
			$client_id, $scope, urlencode($redirect_uri), $state
		);

		header('Location: '.$redirect);
		exit;

	} else {

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_URL, "https://github.com/login/oauth/access_token");
	    curl_setopt($ch, CURLOPT_HTTPHEADER, [
	    	'Content-Type: application/json',
	    	'Accept: application/json',
	    ]); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode((object) [
			'client_id' => $client_id,
			'client_secret' => $client_secret,
			'code' => $_GET['code'],
			'state' => $state,
		]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$result = json_decode(curl_exec($ch));

		$_SESSION['token'] = $result->access_token;

		header('Location: '.$redirect);
	}
}

$token = $_SESSION['token'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.github.com/user");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
//	'Content-Type: application/json',
	'Accept: application/json',
	'User-Agent: Millesime-Appellation-App',
	'Authorization: token '.$token,
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$user = json_decode(curl_exec($ch));

//var_dump($user);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user->repos_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
//	'Content-Type: application/json',
	'Accept: application/json',
	'User-Agent: Millesime-Appellation-App',
	'Authorization: token '.$token,
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$repos = json_decode(curl_exec($ch));

$repositories = array_reduce($repos, function($names, $repo) { $names[] = $repo; return $names; });

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user->organizations_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
//	'Content-Type: application/json',
	'Accept: application/json',
	'User-Agent: Millesime-Appellation-App',
	'Authorization: token '.$token,
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$orgs = json_decode(curl_exec($ch));

foreach ($orgs as $org) {

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $org->repos_url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
	//	'Content-Type: application/json',
		'Accept: application/json',
		'User-Agent: Millesime-Appellation-App',
		'Authorization: token '.$token,
	]);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$org_repos = json_decode(curl_exec($ch));

	$repositories = array_merge($repositories, array_reduce($org_repos, function($names, $repo) { $names[] = $repo; return $names; }) );
}

foreach ($repositories as $repository) {

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, str_replace('{+path}', 'compiler.json', $repository->contents_url));
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
	//	'Content-Type: application/json',
		'Accept: application/json',
		'User-Agent: Millesime-Appellation-App',
		'Authorization: token '.$token,
	]);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$file = json_decode(curl_exec($ch));

	if (property_exists($file, "content") && $file->content) {

		// on sauvegarde le token
		$content = base64_decode($file->content);
		$path = 'projects/'.$repository->full_name;
		if (!file_exists($path)) {
			mkdir($path, 0777, true);
		}

		$data = new stdClass;
		$data->token = $token;
		file_put_contents($path.'/app.json', json_encode($data));


		// on ajoute le hook
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $repository->hooks_url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Accept: application/json',
			'User-Agent: Millesime-Appellation-App',
			'Authorization: token '.$token,
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode((object) [
			'name' => 'web',
			'config' => [
				'url'=> 'https://methylbro.fr/oauth/hook.php',
				'content_type'=> 'json',
			],
			'events' => ['release'],
			'active' => true,
		]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = json_decode(curl_exec($ch));


		echo $repository->full_name. ' added '.PHP_EOL;
	}

}

