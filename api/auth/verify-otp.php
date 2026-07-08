<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../config/database.php";
require_once "../../config/response.php";
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

        errorResponse(
            500,
            "Database Connection Failed."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Read JSON
    |--------------------------------------------------------------------------
    */

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (json_last_error() !== JSON_ERROR_NONE) {

        $conn->close();

        errorResponse(
            400,
            "Invalid JSON Request."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Read Fields
    |--------------------------------------------------------------------------
    */

    $email = strtolower(
        sanitize(
            trim($data['email'] ?? '')
        )
    );

    $otp = trim($data['otp'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (empty($email) || empty($otp)) {

        $conn->close();

        errorResponse(
            422,
            "Email and OTP are Required."
        );

    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $conn->close();

        errorResponse(
            422,
            "Invalid Email Address."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Verify OTP
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            id,
            user_id,
            expires_at,
            is_used
        FROM password_resets
        WHERE email = ?
        AND otp = ?
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
        "ss",
        $email,
        $otp
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {

        $stmt->close();

        $conn->close();

        errorResponse(
            404,
            "Invalid OTP."
        );

    }

    $otpData = $result->fetch_assoc();

    /*
    |--------------------------------------------------------------------------
    | Check Used
    |--------------------------------------------------------------------------
    */

    if ($otpData['is_used'] == 1) {

        $stmt->close();

        $conn->close();

        errorResponse(
            422,
            "OTP has already been used."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Check Expiry
    |--------------------------------------------------------------------------
    */

    if (strtotime($otpData['expires_at']) < time()) {

        $stmt->close();

        $conn->close();

        errorResponse(
            422,
            "OTP has Expired."
        );

    }

    $stmt->close();

    $conn->close();

    /*
    |--------------------------------------------------------------------------
    | Success
    |--------------------------------------------------------------------------
    */

    successResponse(
        "OTP Verified Successfully.",
        [
            "user_id" => $otpData['user_id']
        ]
    );

}
catch(Exception $e){

    error_log($e->getMessage());

    if(isset($conn) && $conn instanceof mysqli){
        $conn->close();
    }

    errorResponse(
        500,
        "Something went wrong."
    );

}

?>