<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PsrJwt\Factory\Jwt;
use Slim\Routing\RouteCollectorProxy;
$db = null;
require_once __DIR__ . '/../encryption/Bcrypt.php';

$factory = new Jwt();
$builder = $factory->builder();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();
$secret = $_ENV['JWT_SECRET'];

$app->group('/api', function (RouteCollectorProxy $group) use ($db, $secret, $builder, $app) {
	$group->group('/user', function (RouteCollectorProxy $group) use ($db, $secret, $builder, $app) {
		//Login user and return token if success
		$group->post('/login', function (Request $request, Response $response, $args) use ($builder, $secret,$db) {
			require __DIR__ . '/../db/dbconnect.php';
			$post = $request->getParsedBody();
			$payload = json_decode(base64_decode($post['payload']),true);
			$email = $payload['username'];
			$password = $payload['password'];

			$data = $db->users()->where('email',$email)->limit(1)->fetch();

			if($data){
				$hashedPassword = $data['password'];
				if(Bcrypt::checkPassword($password, $hashedPassword)){
					$token = $builder->setSecret($secret)->setPayloadClaim('uid', $data['id'])->build();
					$response->getBody()->write(json_encode(["_token" => $token->getToken(), "id" => $data['id'],"name" => $data['name']]));
					return $response;
				}
			}

			$response->getBody()->write(json_encode(["error" => "login_fail"]));
			return $response->withStatus(403,"login_fail");
		});
		//Register user
		$group->post('/register', function (Request $request, Response $response, $args) use ($builder, $secret, $db) {
			require __DIR__ . '/../db/dbconnect.php';
			$post = $request->getParsedBody();
			$email = $post['email'];
			$name = $post['name'];
			$password = $post['password'];

			$data = array (
				"email" => $email,
				"name" => $name,
				"password" => $password,
			);
			$result = $db->users()->insert($data);
			$response->getBody()->write(json_encode($result));
			return $response;
		});

	});
});