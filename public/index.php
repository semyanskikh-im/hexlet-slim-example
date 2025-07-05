<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
})->setName('/');


$app->get('/users', function ($request, $response) use ($users) {
    $search = $request->getQueryParams();
    $term = $search['term'] ?? "";
    $filteredUsers = array_filter($users, function($user) use ($term) {
        return str_contains($user, $term);
    });
    $params = ['term' => $term, 'users' => $filteredUsers];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
    
    
    // $response->getBody()->write($users);
    // return $response;
})->setName('/users');


$app->post('/users', function ($request, $response) {
    $body = $request->getParsedBody();
    $user = $body['user'];
    $user['id'] = uniqid();
    $path = __DIR__ . '/../bd/users.json';
    $users = json_decode(file_get_contents($path), true) ?? [];
    $users[] = $user;

    file_put_contents($path, json_encode($users, JSON_PRETTY_PRINT));

    return $response
        ->withHeader('Location', '/users')
        ->withStatus(302);
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    $response->getBody()->write("Course id: {$id}");
    return $response;
});


// $app->get('/users/{id}', function ($request, $response, $args) {
//     $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
//     // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
//     // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
//     // $this в Slim это контейнер зависимостей
//     return $this->get('renderer')->render($response, 'users/show.phtml', $params);
// });

$app->get('/users/new', function ($request, $response) {
    $params = ['user' => [
                    'nickname' => '',
                    'email' => ''
                    ]
             ];    
    
    
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('/users/new');

// Получаем роутер — объект, отвечающий за хранение и обработку маршрутов
$router = $app->getRouteCollector()->getRouteParser();

//Роутер прокинут в обработчик
$app->get('', function ($request, $response) use ($router) {
    $router->urlFor('/');
    $router->urlFor('/users');
    $router->urlFor('/users/new');
     return $response;
});


$app->run();
