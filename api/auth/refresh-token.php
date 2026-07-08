<?php

header("Content-Type: application/json");

require_once "../../config/database.php";
require_once "../../config/constants.php";
require_once "../../config/jwt.php";
require_once "../../config/response.php";
require_once "../../helper/auth_helper.php";

try {

    /*
    |--------------------------------------------------------------------------
    | Authenticate Refresh Token
    |--------------------------------------------------------------------------
    */

    $tokenData = authenticateRefreshToken();

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    */

    $db = new Database();

    $conn = $db->connect();

    /*
    |--------------------------------------------------------------------------
    | Fetch User
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
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

    if (!$stmt) {
        errorResponse(500, "Unable to Prepare Statement.");
    }

    $stmt->bind_param(
        "i",
        $tokenData['user_id']
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {

        $stmt->close();
        $conn->close();

        errorResponse(
            404,
            "User Not Found."
        );

    }

    $user = $result->fetch_assoc();

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | Check User Status
    |--------------------------------------------------------------------------
    */

    if ($user['status'] != 1) {

        $conn->close();

        errorResponse(
            403,
            "Your Account is Inactive."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Generate New Access Token
    |--------------------------------------------------------------------------
    */

    $issuedAt = time();

    $accessExpire = $issuedAt + ACCESS_TOKEN_EXPIRY;

    $newAccessJti = JWTHandler::generateJTI();

    $payload = [

        "iss" => JWT_ISSUER,

        "aud" => JWT_AUDIENCE,

        "iat" => $issuedAt,

        "exp" => $accessExpire,

        "user_id" => $user['id'],

        "name" => $user['name'],

        "email" => $user['email'],

        "mobile" => $user['mobile'],

        "role" => $user['role'],

        "access_jti" => $newAccessJti,

        "acc_session_id" => $tokenData['session_id']

    ];

    $newAccessToken = JWTHandler::generateToken($payload);

    $accessExpireDate = date(
        "Y-m-d H:i:s",
        $accessExpire
    );

    /*
    |--------------------------------------------------------------------------
    | Update Access Token
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        UPDATE api_tokens
        SET
            access_token = ?,
            access_jti = ?,
            access_expires_at = ?,
            last_used = NOW()
        WHERE session_id = ?
    ");

    if (!$stmt) {

        $conn->close();

        errorResponse(
            500,
            "Unable to Update Token."
        );

    }

    $stmt->bind_param(

        "ssss",

        $newAccessToken,

        $newAccessJti,

        $accessExpireDate,

        $tokenData['session_id']

    );

    if (!$stmt->execute()) {

        $stmt->close();

        $conn->close();

        errorResponse(
            500,
            "Unable to Refresh Token."
        );

    }

    $stmt->close();

    $conn->close();

    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    successResponse(

        "Access Token Refreshed Successfully.",

        [

            "access_token" => $newAccessToken,

            "token_type" => "Bearer",

            "expires_in" => ACCESS_TOKEN_EXPIRY

        ]

    );

} catch (Exception $e) {

    errorResponse(

        500,

        $e->getMessage()

    );

}

?>