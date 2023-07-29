<?php

use Slim\App;

require __DIR__ . '/Handlers/handlers.php';

return function (App $app) {
    $app->post('/signup', '\App\Handlers\signUp');
    $app->post('/signin', '\App\Handlers\signIn');
    $app->post('/signout', '\App\Handlers\signOut');
    // $app->get('/groups', '\App\Handlers\getAllGroups');
    $app->group('/groups', function ($app) {
        $app->get('', '\App\Handlers\getAllGroups');
        $app->get('/{id}', '\App\Handlers\getGroup');
        $app->post('/add', '\App\Handlers\addNewGroup');
        $app->post('/join', '\App\Handlers\joinGroup');
    });
    $app->group('/message', function ($app) {
        // Send a message
        $app->post('/send', '\App\Handlers\sendMessage');
        // View messages in a group
        $app->get('/view', '\App\Handlers\viewMessages');
    });
    
};
