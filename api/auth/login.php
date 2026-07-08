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
require_once "../../config/constants.php";
require_once "../../config/response.php";
require_once "../../config/jwt.php";
require_once "../../helper/common_helper.php";

try {

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    */

    $db = new Database();
    $conn = $db->connect();
    if (!$conn) {
        errorResponse(500, "Database Connection Failed.");
    }

    /*
    |--------------------------------------------------------------------------
    | Read JSON Request
    |--------------------------------------------------------------------------
    */

    $data = json_decode(file_get_contents("php://input"), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        errorResponse(400, "Invalid JSON Request.");
    }

    if (empty($data)) {
        errorResponse(400, "Request Body Required.");
    }

    /*
    |--------------------------------------------------------------------------
    | Read Login Credentials
    |--------------------------------------------------------------------------
    */

    $login = sanitize(trim($data['login'] ?? ''));
    $password = trim($data['password'] ?? '');

    if (empty($login)) {
        errorResponse(422, "Email or Mobile is Required.");
    }

    if (empty($password)) {
        errorResponse(422, "Password is Required.");
    }

    /*
    |--------------------------------------------------------------------------
    | Find User
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            email,
            mobile,
            password,
            role,
            status
        FROM users
        WHERE email = ?
        OR mobile = ?
        LIMIT 1
    ");

    if (!$stmt) {
        errorResponse(500, "Unable to prepare statement.");
    }

    $stmt->bind_param(
        "ss",
        $login,
        $login
    );

    if (!$stmt->execute()) {
        errorResponse(500, "Unable to execute login query.");
    }

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {

        $stmt->close();
        $conn->close();


        errorResponse(
            401,
            "Invalid Email or Mobile."
        );
    }

    $user = $result->fetch_assoc();

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | Account Status
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
    | Verify Password
    |--------------------------------------------------------------------------
    */

    if (!password_verify($password, $user['password'])) {

        $conn->close();
        errorResponse(
            401,
            "Invalid Password."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Generate Session & JWT IDs
    |--------------------------------------------------------------------------
    */

    $session_id = JWTHandler::generateSessionId();

    $access_jti = JWTHandler::generateJTI();

    $refresh_jti = JWTHandler::generateJTI();

    /*
    |--------------------------------------------------------------------------
    | Generate Access Token
    |--------------------------------------------------------------------------
    */

    $issuedAt = time();

    $accessExpire = $issuedAt + ACCESS_TOKEN_EXPIRY;

    $accessPayload = [

        "iss" => JWT_ISSUER,

        "aud" => JWT_AUDIENCE,

        "iat" => $issuedAt,

        "exp" => $accessExpire,

        "user_id" => $user['id'],

        "name" => $user['name'],

        "email" => $user['email'],

        "mobile" => $user['mobile'],

        "role" => $user['role'],

        "access_jti" => $access_jti,

        "acc_session_id" => $session_id

    ];

    $accessToken = JWTHandler::generateToken(
        $accessPayload
    );

    /*
    |--------------------------------------------------------------------------
    | Generate Refresh Token
    |--------------------------------------------------------------------------
    */

    $refreshExpire = $issuedAt + REFRESH_TOKEN_EXPIRY;

    $refreshPayload = [

        "iss" => JWT_ISSUER,

        "aud" => JWT_AUDIENCE,

        "iat" => $issuedAt,

        "exp" => $refreshExpire,

        "user_id" => $user['id'],

        "mobile" => $user['mobile'],

        "refresh_jti" => $refresh_jti,

        "ref_session_id" => $session_id

    ];

    $refreshToken = JWTHandler::generateToken(
        $refreshPayload
    );

    /*
    |--------------------------------------------------------------------------
    | Device Information
    |--------------------------------------------------------------------------
    */

    $deviceName = getUserAgent();

    $deviceIP = getIPAddress();

    $accessExpireDate = date(
        "Y-m-d H:i:s",
        $accessExpire
    );

    $refreshExpireDate = date(
        "Y-m-d H:i:s",
        $refreshExpire
    );

    $lastUsed = date(
        "Y-m-d H:i:s"
    );

        /*
    |--------------------------------------------------------------------------
    | Save Access & Refresh Tokens
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        INSERT INTO api_tokens
        (
            user_id,
            mobile,
            access_token,
            refresh_token,
            access_jti,
            refresh_jti,
            session_id,
            access_expires_at,
            refresh_expires_at,
            last_used,
            device_name,
            device_ip,
            is_active
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?,?,?,?,?,?
        )
    ");

    if (!$stmt) {

        $conn->close();

        errorResponse(
            500,
            "Unable to prepare token statement."
        );

    }

    $isActive = 1;

    $stmt->bind_param(

        "isssssssssssi",

        $user['id'],

        $user['mobile'],

        $accessToken,

        $refreshToken,

        $access_jti,

        $refresh_jti,

        $session_id,

        $accessExpireDate,

        $refreshExpireDate,

        $lastUsed,

        $deviceName,

        $deviceIP,

        $isActive

    );

    if (!$stmt->execute()) {

        $stmt->close();

        $conn->close();

        errorResponse(
            500,
            "Unable to Generate Login Session."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Login Success Response
    |--------------------------------------------------------------------------
    */

    $response = [

        "access_token" => $accessToken,

        "refresh_token" => $refreshToken,

        "token_type" => "Bearer",

        "expires_in" => ACCESS_TOKEN_EXPIRY,

        "refresh_expires_in" => REFRESH_TOKEN_EXPIRY,
        
        "login_time" => $lastUsed,

        "user" => [

            "id" => $user['id'],

            "name" => $user['name'],

            "email" => $user['email'],

            "mobile" => $user['mobile'],

            "role" => $user['role']

        ]

    ];

    $stmt->close();

    $conn->close();

    successResponse(
        "Login Successful.",
        $response
    );

}
catch (Exception $e) {

    error_log($e->getMessage());

    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }

    errorResponse(
        500,
        "Something went wrong. Please try again later."
    );
}

?>