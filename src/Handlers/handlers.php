<?php

namespace App\Handlers;

use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;


function signUp(Request $request, Response $response)
{
    $responseFactory = new ResponseFactory();
    $streamFactory = new StreamFactory();
    $data = $request->getParsedBody();
    $username = $data['username'];
    $password = $data['password'];
    if (empty($username) || empty($password)) {
        $stream = $streamFactory->createStream(json_encode(['error' => 'Username and password are required']));

        return $responseFactory->createResponse(400)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }

    if (isUsernameTaken($username)) {
    
        $stream = $streamFactory->createStream(json_encode(['error' => 'Username already exists']));

        return $responseFactory->createResponse(400)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }

    $createdAt = date('Y-m-d H:i:s');
    $token = generateRandomToken();

    createUser($username, $password, $createdAt,$token);

    // return $response->json_encode(['message' => 'User created successfully']);
    $stream = $streamFactory->createStream(json_encode(['message' => 'User created successfully']));
    return $responseFactory->createResponse(201)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
}

function signIn(Request $request, Response $response)
{
    $responseFactory = new ResponseFactory();
    $streamFactory = new StreamFactory();

    $data = $request->getParsedBody();
    $username = $data['username'];
    $password = $data['password'];

    $user = getUserByUsername($username);

    if (!$user || !password_verify($password, $user['password'])) {
        $stream = $streamFactory->createStream(json_encode(['error' => 'Invalid credentials']));
        // return $response->withStatus(401)->withJson(['error' => 'Invalid credentials']);
        return $responseFactory->createResponse(401)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }

    // Generate and store a random token
    $token = generateRandomToken();
    updateUserToken($user['id'], $token);

    session_start();
    
    // $_SESSION['id'] = $user['id']; // Store username in the session
    setcookie('username', $username, time() + (86400 * 30), '/', '', false, true); // Cookie expires in 30 days
    setcookie('token', $token, time() + (86400 * 30), '/', '', false, true);
    setcookie('userid', $user['id'], time() + (86400 * 30), '/', '', false, true);

    $stream = $streamFactory->createStream(json_encode(['token' => $token]));
    // return $response->withJson(['token' => $token]);
    return $responseFactory->createResponse(201)
    ->withHeader('Content-Type', 'application/json')
    ->withBody($stream);
}

function isUserAuthenticated(Request $request)
{
    $cookies = $request->getCookieParams();
    $username = $cookies['username'] ?? '';
    $token = $cookies['token'] ?? '';
    $userid=$cookies['userid'] ?? '';

    // Verify the token based on the username
    return isValidToken($userid, $token);
}

function signOut(Request $request, Response $response)
{
    $responseFactory = new ResponseFactory();
    $streamFactory = new StreamFactory();
    // Start or resume the session
    session_start();

    // Destroy the session (log out the user)
    session_destroy();

    $stream = $streamFactory->createStream(json_encode(['message' => 'Logged out successfully']));
    return $responseFactory->createResponse(201)
    ->withHeader('Content-Type', 'application/json')
    ->withBody($stream);
    // return $response->withJson(['message' => 'Logged out successfully']);
}

function getAllGroups(Request $request, Response $response)
{

    $responseFactory = new ResponseFactory();
    $streamFactory = new StreamFactory();

    if (isUserAuthenticated($request)){
        $groups = getAllGroupsFromDatabase();
        $stream = $streamFactory->createStream(json_encode($groups));
        return $responseFactory->createResponse(201)
        ->withHeader('Content-Type', 'application/json')
        ->withBody($stream);
    }
    else{
        $stream = $streamFactory->createStream(json_encode(['error' => 'Authentication error']));
        return $responseFactory->createResponse(401)
        ->withHeader('Content-Type', 'application/json')
        ->withBody($stream);
    }
    // return $response->withJson($groups);
    
}

function getGroup(Request $request, Response $response, $args)
{
    $responseFactory = new ResponseFactory();
    $streamFactory = new StreamFactory();
    $groupid = $args['id'];

    // Verify the token based on the username
    if (isUserAuthenticated($request)) {
        // The token is valid, proceed to fetch the group data
        $group = getGroupByID($groupid);
        if ($group) {
            $stream = $streamFactory->createStream(json_encode($group));
            return $responseFactory->createResponse(201)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($stream);
        } else {
            // Group not found, return an error response
            // $errorResponse = $response->withStatus(404);
            // $errorResponse->getBody()->write(json_encode(['error' => 'Group not found']));
            // return $errorResponse->withHeader('Content-Type', 'application/json');
            $stream = $streamFactory->createStream(json_encode(['error' => 'Group not found']));
                return $responseFactory->createResponse(404)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($stream);
        }
    } else {
        // Invalid token, return an error response
        $stream = $streamFactory->createStream(json_encode(['error' => 'Authentication error']));
        return $responseFactory->createResponse(401)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }
}

function addNewGroup(Request $request, Response $response)
{
    $responseFactory = new ResponseFactory();
    $streamFactory = new StreamFactory();
    // Check if the user is authenticated based on the cookie
    if (!isUserAuthenticated($request)) {
        // User is not authenticated, return an authentication error response
        $stream = $streamFactory->createStream(json_encode(['error' => 'Authentication error']));
        return $responseFactory->createResponse(401)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }

    $data = $request->getParsedBody();

    // Check if the required fields are provided in the request body
    if (!isset($data['groupname']) || !isset($data['groupabout'])) {
        $stream = $streamFactory->createStream(json_encode(['error' => 'Missing groupname or groupabout']));
        return $responseFactory->createResponse(400)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }

    $groupname = $data['groupname'];
    $groupabout = $data['groupabout'];
    $createdAt = date('Y-m-d H:i:s');
    // Create the new group in the database
    $groupId = createGroup($groupname, $groupabout,$createdAt);

    // Return the newly created group in the response
    $newGroup = ['groupid' => $groupId, 'groupname' => $groupname, 'groupabout' => $groupabout];
    $stream = $streamFactory->createStream(json_encode($newGroup));
        return $responseFactory->createResponse(201)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
}

function joinGroup(Request $request, Response $response)
{
    $responseFactory = new ResponseFactory();
    $streamFactory = new StreamFactory();
    // Check if the user is authenticated based on the cookie
    if (!isUserAuthenticated($request)) {
        // User is not authenticated, return an authentication error response
        $stream = $streamFactory->createStream(json_encode(['error' => 'Authentication error']));
        return $responseFactory->createResponse(401)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }

    $data = $request->getParsedBody();

    // Check if the required fields are provided in the request body
    if (!isset($data['groupid'])) {
        $stream = $streamFactory->createStream(json_encode(['error' => 'Missing group_id']));
        return $responseFactory->createResponse(400)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }

    // Get the user ID from the authenticated session
    $userId = getUserIdFromSession($request);

    // Get the group ID from the request body
    $groupId = $data['groupid'];

    // $stream = $streamFactory->createStream(json_encode($groupId));
    // return $responseFactory->createResponse(501)
    //         ->withHeader('Content-Type', 'application/json')
    //         ->withBody($stream);

    // Join the group by adding the user to the group_members table
    if(joinGroupById($userId, $groupId)){
        $stream = $streamFactory->createStream(json_encode(['message' => 'Successfully joined the group']));
        return $responseFactory->createResponse(201)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }

    // Return a success response

    $stream = $streamFactory->createStream(json_encode(['error' => 'Error adding user to group']));
    return $responseFactory->createResponse(501)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    
}

function sendMessage(Request $request, Response $response)
{
    $responseFactory = new ResponseFactory();
    $streamFactory = new StreamFactory();
    // Check if the user is authenticated based on the user_id and token cookies
    if (!isUserAuthenticated($request)) {
        $stream = $streamFactory->createStream(json_encode(['error' => 'Authentication error']));
        return $responseFactory->createResponse(401)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);    }

    $data = $request->getParsedBody();
    $userId = getUserIdFromSession($request);
    $groupId = $data['groupid'] ?? null;
    $messageContent = $data['message_content'] ?? '';

    // Check if the required fields are provided in the request body
    if (empty($groupId) || empty($messageContent)) {
        $stream = $streamFactory->createStream(json_encode(['error' => 'Missing group_id or message']));
        return $responseFactory->createResponse(400)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);    
    }

    // Check if the user is a member of the specified group
    if (!isUserInGroup($userId, $groupId)) {
        $stream = $streamFactory->createStream(json_encode(['error' => 'You are not a member of the specified group']));
        return $responseFactory->createResponse(403)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream); 
    }

    // Get the current timestamp for the 'sent_at' field
    $sentAt = date('Y-m-d H:i:s');

    try {
        addMessageToGroup($groupId, $userId, $messageContent, $sentAt);
        $stream = $streamFactory->createStream(json_encode(['message' => 'Message sent successfully']));
        return $responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream); 
    } catch (\Exception $e) {
        // Handle any exceptions that occur during the database operation
        $stream = $streamFactory->createStream(json_encode(['error' => 'Error sending message: ' . $e->getMessage()]));
        return $responseFactory->createResponse(500)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream); 
    }
}

function viewMessages(Request $request, Response $response)
{
    $responseFactory = new ResponseFactory();
    $streamFactory = new StreamFactory();
    // Check if the user is authenticated based on the user_id and token cookies
    if (!isUserAuthenticated($request)) {
        $stream = $streamFactory->createStream(json_encode(['error' => 'Authentication error']));
        return $responseFactory->createResponse(401)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);    
    }

    $groupId = $request->getQueryParams()['groupid'] ?? null;
    $userId = getUserIdFromSession($request);

    // Check if the required 'group_id' is provided in the query parameters
    if (empty($groupId)) {
        return $response->withStatus(400)->withJson(['error' => 'Missing group_id']);
    }

    // Check if the user is a member of the specified group
    if (!isUserInGroup($userId, $groupId)) {
        $stream = $streamFactory->createStream(json_encode(['error' => 'You are not a member of the specified group']));
        return $responseFactory->createResponse(403)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream); 
    }

    // Set a long polling timeout 
    $longPollingTimeout = 30;
    // Perform long polling
    $startPollingTime = time();


    try {
        while (true) {
            // Retrieve messages from the group_messages table for the specified group
            $messages = getMessagesByGroup($groupId);

            // Check if there are any new messages
            if (!empty($messages)) {
                // Return the messages as a JSON response
                $stream = $streamFactory->createStream(json_encode(['messages' => $messages]));
                return $responseFactory->createResponse(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($stream);
            }

            // Check if the long polling timeout has been reached
            if (time() - $startPollingTime >= $longPollingTimeout) {
                // Return an empty response indicating no new messages within the timeout
                $stream = $streamFactory->createStream(json_encode(['messages' => []]));
                return $responseFactory->createResponse(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($stream);
            }

            // Sleep for a short interval before checking for new messages again
            usleep(1000000); // 1 second (adjust as needed)
        }
    } catch (\Exception $e) {
        // Handle any exceptions that occur during the long polling process
        $stream = $streamFactory->createStream(json_encode(['error' => 'Error Fetching messages: ' . $e->getMessage()]));
        return $responseFactory->createResponse(500)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }

    // try {
    //     $messages = getMessagesByGroup($groupId);
    //     $stream = $streamFactory->createStream(json_encode(['messages' => $messages]));
    //     return $responseFactory->createResponse(200)
    //         ->withHeader('Content-Type', 'application/json')
    //         ->withBody($stream); 
    // } catch (\Exception $e) {
    //     // Handle any exceptions that occur during the database operation
    //     $stream = $streamFactory->createStream(json_encode(['error' => 'Error Fetching message: ' . $e->getMessage()]));
    //     return $responseFactory->createResponse(500)
    //         ->withHeader('Content-Type', 'application/json')
    //         ->withBody($stream); 
    // }


}


// Function to get the user ID from the authenticated session
function getUserIdFromSession(Request $request)
{
    $cookies = $request->getCookieParams();
    $userid=$cookies['userid'] ?? '';
    return $userid;
}

// Function to join the group by adding the user to the group_members table
function joinGroupById($userId, $groupId)
{
    // Call the database function to insert the user into the group_members table
    return addGroupMember($groupId, $userId);
}





// function getAllGroups(Request $request, Response $response)
// {
//     $responseFactory = new ResponseFactory();
//     $streamFactory = new StreamFactory();

//     if (isUserAuthenticated()) {
//         // $groups = getGroupsFromDatabase();
//         // Retrieve all groups from the database
//         $groups = getAllGroupsFromDatabase();
//         $stream = $streamFactory->createStream(json_encode($groups));
//         // Return the groups as a JSON response
//         return $responseFactory->createResponse(201)
//         ->withHeader('Content-Type', 'application/json')
//         ->withBody($stream);
//     }else {
//         // User is not authenticated, return an error response
//         $stream = $streamFactory->createStream(json_encode(['error' => 'Unauthorized']));
//         return $responseFactory->createResponse(401)
//         ->withHeader('Content-Type', 'application/json')
//         ->withBody($stream);
    
//     }

// }

// handlers.php

// ... Other functions ...


