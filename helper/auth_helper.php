<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/jwt.php";
require_once __DIR__ . "/../config/response.php";

/*
|--------------------------------------------------------------------------
| Get Authorization Header
|--------------------------------------------------------------------------
*/

function getBearerToken()
{
    $headers = [];

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    }

    if (empty($headers) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
    }

    if (isset($headers['Authorization'])) {

        $authorization = trim($headers['Authorization']);

    } else {

        $authorization = "";

    }

    if (empty($authorization)) {

        return null;

    }

    if (preg_match('/Bearer\s(\S+)/', $authorization, $matches)) {

        return $matches[1];

    }

    return null;
}

/*
|--------------------------------------------------------------------------
| Authenticate Access Token
|--------------------------------------------------------------------------
*/

function authenticateAccessToken()
{
    return authenticateToken("access");
}

/*
|--------------------------------------------------------------------------
| Authenticate Refresh Token
|--------------------------------------------------------------------------
*/

function authenticateRefreshToken()
{
    return authenticateToken("refresh");
}

/*
|--------------------------------------------------------------------------
| Common Authentication Function
|--------------------------------------------------------------------------
*/

function authenticateToken($type)
{

    /*
    |--------------------------------------------------------------------------
    | Read Bearer Token
    |--------------------------------------------------------------------------
    */

    $token = getBearerToken();

    if (!$token) {

        errorResponse(
            401,
            "Authorization Token Required."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Verify JWT
    |--------------------------------------------------------------------------
    */

    $payload = JWTHandler::verifyToken($token);

    if (!$payload) {

        errorResponse(
            401,
            "Invalid or Expired Token."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    */

    $db = new Database();

    $conn = $db->connect();

    if (!$conn) {

        errorResponse(
            500,
            "Database Connection Failed."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Dynamic Columns
    |--------------------------------------------------------------------------
    */

    if ($type == "access") {

        $tokenColumn = "access_token";

        $jtiColumn = "access_jti";

        $expiryColumn = "access_expires_at";

        $payloadJti = "access_jti";

        $payloadSession = "acc_session_id";

    } else {

        $tokenColumn = "refresh_token";

        $jtiColumn = "refresh_jti";

        $expiryColumn = "refresh_expires_at";

        $payloadJti = "refresh_jti";

        $payloadSession = "ref_session_id";

    }

    /*
    |--------------------------------------------------------------------------
    | Find Token
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("

        SELECT

            id,
            user_id,
            mobile,
            session_id,

            access_jti,
            refresh_jti,

            access_token,
            refresh_token,

            access_expires_at,
            refresh_expires_at,

            is_active

        FROM api_tokens

        WHERE {$tokenColumn}=?

        LIMIT 1

    ");

    if (!$stmt) {

        errorResponse(
            500,
            "Unable to Prepare Statement."
        );

    }

    $stmt->bind_param(
        "s",
        $token
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {

        $stmt->close();

        $conn->close();

        errorResponse(
            401,
            "Token Not Found."
        );

    }

    $tokenData = $result->fetch_assoc();

    /*
    |--------------------------------------------------------------------------
    | Check Active
    |--------------------------------------------------------------------------
    */

    if ($tokenData['is_active'] != 1) {

        $stmt->close();

        $conn->close();

        errorResponse(
            401,
            "Session Expired."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Check Database Expiry
    |--------------------------------------------------------------------------
    */

    if (strtotime($tokenData[$expiryColumn]) < time()) {

        $stmt->close();

        $conn->close();

        errorResponse(
            401,
            "Token Expired."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Validate JTI
    |--------------------------------------------------------------------------
    */

    if ($payload->{$payloadJti} != $tokenData[$jtiColumn]) {

        $stmt->close();

        $conn->close();

        errorResponse(
            401,
            "Invalid Token."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Validate Session
    |--------------------------------------------------------------------------
    */

    if ($payload->{$payloadSession} != $tokenData['session_id']) {

        $stmt->close();

        $conn->close();

        errorResponse(
            401,
            "Invalid Session."
        );

    }

        /*
    |--------------------------------------------------------------------------
    | Update Last Used (Only Access Token)
    |--------------------------------------------------------------------------
    */

    if ($type == "access") {

        $update = $conn->prepare("
            UPDATE api_tokens
            SET last_used = NOW()
            WHERE id = ?
        ");

        if ($update) {

            $update->bind_param(
                "i",
                $tokenData['id']
            );

            $update->execute();

            $update->close();

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Return Token Data for Refresh Token
    |--------------------------------------------------------------------------
    */

    if ($type == "refresh") {

        $stmt->close();

        $conn->close();

        return $tokenData;

    }

    /*
    |--------------------------------------------------------------------------
    | Fetch Logged In User
    |--------------------------------------------------------------------------
    */

    $userStmt = $conn->prepare("
        SELECT

            id,
            name,
            email,
            mobile,
            role,
            status

        FROM users

        WHERE id = ?

        LIMIT 1
    ");

    if (!$userStmt) {

        $stmt->close();

        $conn->close();

        errorResponse(
            500,
            "Unable to Prepare User Statement."
        );

    }

    $userStmt->bind_param(
        "i",
        $tokenData['user_id']
    );

    $userStmt->execute();

    $userResult = $userStmt->get_result();

    if ($userResult->num_rows == 0) {

        $userStmt->close();

        $stmt->close();

        $conn->close();

        errorResponse(
            401,
            "User Not Found."
        );

    }

    $user = $userResult->fetch_assoc();

    /*
    |--------------------------------------------------------------------------
    | Check User Status
    |--------------------------------------------------------------------------
    */

    if ($user['status'] != 1) {

        $userStmt->close();

        $stmt->close();

        $conn->close();

        errorResponse(
            403,
            "Your Account is Inactive."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Close Connections
    |--------------------------------------------------------------------------
    */

    $userStmt->close();

    $stmt->close();

    $conn->close();

    /*
    |--------------------------------------------------------------------------
    | Return Logged In User
    |--------------------------------------------------------------------------
    */

    return $user;

}

?>