<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../config/database.php";
require_once "../../config/response.php";
require_once "../../config/jwt.php";
require_once "../../helper/auth_helper.php";

try {

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
    | Read Access Token
    |--------------------------------------------------------------------------
    */

    $token = getBearerToken();

    if (empty($token)) {

        $conn->close();

        errorResponse(
            401,
            "Authorization Token Required."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Verify JWT Token
    |--------------------------------------------------------------------------
    */

    $payload = JWTHandler::verifyToken($token);

    if (!$payload) {

        $conn->close();

        errorResponse(
            401,
            "Invalid Access Token."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Find Active Session
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            id,
            user_id,
            is_active
        FROM api_tokens
        WHERE access_token=?
        LIMIT 1
    ");

    if (!$stmt) {

        $conn->close();

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
            "Session Not Found."
        );

    }

    $session = $result->fetch_assoc();

    /*
    |--------------------------------------------------------------------------
    | Check Active Session
    |--------------------------------------------------------------------------
    */

    if ($session['is_active'] != 1) {

        $stmt->close();

        $conn->close();

        errorResponse(
            401,
            "Session Already Logged Out."
        );

    }

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | Logout User
    |--------------------------------------------------------------------------
    */

    $update = $conn->prepare("
        UPDATE api_tokens
        SET
            is_active=0,
            last_used=NOW()
        WHERE id=?
    ");

    if (!$update) {

        $conn->close();

        errorResponse(
            500,
            "Unable to Prepare Logout Statement."
        );

    }

    $update->bind_param(
        "i",
        $session['id']
    );

    if (!$update->execute()) {

        $update->close();

        $conn->close();

        errorResponse(
            500,
            "Unable to Logout User."
        );

    }

    $update->close();

    $conn->close();

    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    successResponse(
        "Logout Successful.",
        []
    );

}
catch (Exception $e) {

    error_log($e->getMessage());

    if (isset($conn) && $conn instanceof mysqli) {

        $conn->close();

    }

    errorResponse(
        500,
        "Something went wrong."
    );

}

?>