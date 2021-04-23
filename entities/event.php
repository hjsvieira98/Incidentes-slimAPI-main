<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PsrJwt\Factory\Jwt;
use PsrJwt\Factory\JwtMiddleware;
use Slim\Routing\RouteCollectorProxy;
$db = null;
require_once __DIR__ . '/../encryption/Bcrypt.php';

$factory = new Jwt();
$builder = $factory->builder();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();
$secret = $_ENV['JWT_SECRET'];


$app->group('/api', function (RouteCollectorProxy $group) use ($db, $secret, $builder, $app) {
	$group->group('/event', function (RouteCollectorProxy $group) use ($db, $secret, $builder, $app) {
		//Get All Events by user
		$group->get('/user/{id}/get', function (Request $request, Response $response, $args) use ($builder, $secret, $db) {
			require __DIR__ . '/../db/dbconnect.php';
			$id_user = $request->getAttribute("id");
			$result = $db->events()->where("user_id",$id_user);
			$response->getBody()->write(json_encode($result));
			return $response;
		})->add(JwtMiddleware::json($secret, 'jwt', ['Authorisation Failed']));
		//Get An Event by id
		$group->get('/get/{id}', function (Request $request, Response $response, $args) use ($builder, $secret, $db) {
			require __DIR__ . '/../db/dbconnect.php';
			$id = $request->getAttribute("id");
			$result = $db->events[$id];
			$response->getBody()->write(json_encode($result));
			return $response;
		});
		//Get All Events
		$group->get('/get', function (Request $request, Response $response, $args) use ($builder, $secret, $db) {
			require __DIR__ . '/../db/dbconnect.php';
			$result = $db->events;
			$data = [];
			foreach ($result as $item){
				$data[] = $item;
				$item['status'] = $item->status;
			}
			$response->getBody()->write(json_encode($data));
			return $response;
		});
		//Create new Events
		$group->post('/insert', function (Request $request, Response $response, $args) use ($builder, $secret, $db) {
			require __DIR__ . '/../db/dbconnect.php';
			$post = $request->getParsedBody();
			$payload = json_decode(base64_decode($post['payload']),true);
			$user_id = $payload['user_id'];
			$location = $payload['location'];
			$latitude = $payload['latitude'];
			$longitude = $payload['longitude'];
			$photo = $payload['photo'];
			$description = $payload['description'];

			$data = array (
				"photo" => $photo,
				"user_id" => $user_id,
				"location" => $location,
				"latitude" => $latitude,
				"longitude" => $longitude,
				"description" => $description,
				"status_id" => 4,
				"date" => date("d/m/Y"),
				"time" => date("h:i")
			);
			$result = $db->events()->insert($data);
			$response->getBody()->write(json_encode($result));
			return $response;
		})->add(JwtMiddleware::json($secret, 'jwt', ['Authorisation Failed']));
		//Delete Event by id
		$group->delete('/delete', function (Request $request, Response $response, $args) use ($builder, $secret, $db) {
			require __DIR__ . '/../db/dbconnect.php';
			$post = $request->getParsedBody();
			$id = $post['id'];
			$user_id = $post['user_id'];
			//$result = $db->events[$id]->delete();
			$result = $db->events()->where([["user_id",$user_id],['id',$id]])->delete();
			$response->getBody()->write(json_encode($result));
			return $response;
		})->add(JwtMiddleware::json($secret, 'jwt', ['Authorisation Failed']));
		//Update Event
		$group->put('/update', function (Request $request, Response $response, $args) use ($builder, $secret, $db) {
			require __DIR__ . '/../db/dbconnect.php';

			$post = $request->getParsedBody();
			$id = $post['id'];
			$location = $post['location'];
			$latitude = $post['latitude'];
			$longitude = $post['longitude'];
			$image = $post['image'];
			$description = $post['description'];
			$data = array (
				"id" => $id,
				"location" => $location,
				"latitude" => $latitude,
				"longitude" => $longitude,
				"image" => $image,
				"description" => $description,
			);
			$result = $db->marks[$id]->update($data);
			$response->getBody()->write(json_encode($result));
			return $response;
		})->add(JwtMiddleware::json($secret, 'jwt', ['Authorisation Failed']));

	});
});