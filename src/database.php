<?php

function getDatabaseConnection()
{
    $pdo = new PDO('sqlite:../database.sqlite');
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

function createUser($username, $password, $createdAt,$token)
{
    $db = getDatabaseConnection();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, password, createdAt,token) VALUES (:username, :password, :createdAt,:token)');
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $passwordHash);
    $stmt->bindParam(':createdAt', $createdAt);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
}

function getUserByUsername($username)
{
    $db = getDatabaseConnection();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    return $stmt->fetch();
}

function getUserById($userid)
{
    $db = getDatabaseConnection();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :userid');
    $stmt->bindParam(':userid', $userid);
    $stmt->execute();
    return $stmt->fetch();
}

function updateUserToken($userId, $token)
{
    $db = getDatabaseConnection();
    $stmt = $db->prepare('UPDATE users SET token = :token WHERE id = :id');
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
}

function isUsernameTaken($username)
{
    $db = getDatabaseConnection();
    $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    return $count > 0;
}

function getAllGroupsFromDatabase()
{
    $db = getDatabaseConnection();
    $stmt = $db->query('SELECT id, group_name, description FROM groups');
    return $stmt->fetchAll();
}

function getGroupByID($groupID)
{
    $db = getDatabaseConnection();
    $stmt = $db->prepare('SELECT id, group_name, description FROM groups WHERE id = :groupid');
    $stmt->bindParam(':groupid', $groupID);
    $stmt->execute();
    return $stmt->fetch();
}

function createGroup($groupName, $groupAbout,$createdAt)
{
    $db = getDatabaseConnection();
    $stmt = $db->prepare('INSERT INTO groups (group_name, description,created_at) VALUES (:groupname, :groupabout, :createdat)');
    $stmt->bindParam(':groupname', $groupName);
    $stmt->bindParam(':groupabout', $groupAbout);
    $stmt->bindParam(':createdat', $createdAt);
    $stmt->execute();
    return $db->lastInsertId();
}

function addGroupMember($groupId, $userId)
{
    $db = getDatabaseConnection();

    try {
        // Check if the group and user exist before adding the member
        $groupExists = isGroupExists($groupId);
        $userExists = isUserExists($userId);

        if (!$groupExists || !$userExists) {
            // Handle the case where the group or user does not exist
            // You can return false, throw an exception, or take appropriate action
            echo $groupExists;
            return false;
        }

        // Get the current timestamp for the 'joined_at' field
        $joinedAt = date('Y-m-d H:i:s');

        // Prepare the SQL statement to insert the new group member
        $stmt = $db->prepare('INSERT INTO group_members (group_id, user_id, joined_at) VALUES (:group_id, :user_id, :joined_at)');
        $stmt->bindParam(':group_id', $groupId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':joined_at', $joinedAt);

        // Execute the SQL statement to insert the new group member
        $stmt->execute();

        // Return the ID of the newly added group member (if needed)
        return True;
    } catch (PDOException $e) {
        // Handle any exceptions or errors that occur during the database operation
        // For example, log the error or return false to indicate failure
        return false;
    }
}

function addMessageToGroup($groupId, $userId, $messageContent, $sentAt)
{
    $db = getDatabaseConnection();

    try {
        $stmt = $db->prepare('INSERT INTO group_messages (group_id, user_id, message_content, sent_at) VALUES (:group_id, :user_id, :message_content, :sent_at)');
        $stmt->bindParam(':group_id', $groupId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':message_content', $messageContent);
        $stmt->bindParam(':sent_at', $sentAt);
        $stmt->execute();
    } catch (PDOException $e) {
        // Handle any exceptions or errors that occur during the database operation
        // For example, log the error or throw an exception to handle it at a higher level
        throw new Exception('Failed to add message to group: ' . $e->getMessage());
    }
}

function getMessagesByGroup($groupId)
{
    $db = getDatabaseConnection();

    try {
        $stmt = $db->prepare('SELECT * FROM group_messages WHERE group_id = :group_id ORDER BY sent_at DESC');
        $stmt->bindParam(':group_id', $groupId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle any exceptions or errors that occur during the database operation
        // For example, log the error or throw an exception to handle it at a higher level
        throw new Exception('Failed to retrieve messages: ' . $e->getMessage());
    }
}

function isValidToken($userid, $token)
{
    // Get the user from the database based on the provided username
    if (empty($userid) || empty($token)) {
        return false;
    }
    $user = getUserById($userid);

    // Check if the user exists and the token matches the one from the database
    if ($user && $user['token'] === $token) {
        return true;
    }

    return false;
}

function isGroupExists($groupId)
{
    $db = getDatabaseConnection();

    try {
        $stmt = $db->prepare('SELECT COUNT(*) FROM groups WHERE id = :groupId');
        $stmt->bindParam(':groupId', $groupId);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        return $count > 0; // Return true if the group exists, false otherwise
    } catch (PDOException $e) {
        // Handle any exceptions or errors that occur during the database operation
        // For example, log the error or return false to indicate failure
        return False;
    }
}

function isUserExists($userId)
{
    $db = getDatabaseConnection();

    try {
        $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE id = :userId');
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        return $count > 0; // Return true if the user exists, false otherwise
    } catch (PDOException $e) {
        // Handle any exceptions or errors that occur during the database operation
        // For example, log the error or return false to indicate failure
        return false;
    }
}

function isUserInGroup($userId, $groupId)
{
    $db = getDatabaseConnection();

    try {
        $stmt = $db->prepare('SELECT COUNT(*) FROM group_members WHERE user_id = :user_id AND group_id = :group_id');
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':group_id', $groupId);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        return $count > 0; // Return true if the user is a member of the group, false otherwise
    } catch (PDOException $e) {
        // Handle any exceptions or errors that occur during the database operation
        // For example, log the error or return false to indicate failure
        return false;
    }
}
