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
    | Read JSON Request
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

    $newPassword = trim($data['new_password'] ?? '');

    $confirmPassword = trim($data['confirm_password'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (

        empty($email) ||

        empty($otp) ||

        empty($newPassword) ||

        empty($confirmPassword)

    ) {

        $conn->close();

        errorResponse(
            422,
            "All Fields are Required."
        );

    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $conn->close();

        errorResponse(
            422,
            "Invalid Email Address."
        );

    }

    if (strlen($newPassword) < 6) {

        $conn->close();

        errorResponse(
            422,
            "Password must be at least 6 characters."
        );

    }

    if ($newPassword != $confirmPassword) {

        $conn->close();

        errorResponse(
            422,
            "Confirm Password does not match."
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

    if ($otpData['is_used'] == 1) {

        $stmt->close();

        $conn->close();

        errorResponse(
            422,
            "OTP has already been used."
        );

    }

    if (strtotime($otpData['expires_at']) < time()) {

        $stmt->close();

        $conn->close();

        errorResponse(
            422,
            "OTP has Expired."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Encrypt Password
    |--------------------------------------------------------------------------
    */

    $password = password_hash(
        $newPassword,
        PASSWORD_DEFAULT
    );

    /*
    |--------------------------------------------------------------------------
    | Update Password
    |--------------------------------------------------------------------------
    */

    $update = $conn->prepare("
        UPDATE users
        SET
            password = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    if (!$update) {

        $stmt->close();

        $conn->close();

        errorResponse(
            500,
            "Unable to Prepare Update Statement."
        );

    }

    $update->bind_param(
        "si",
        $password,
        $otpData['user_id']
    );

    if (!$update->execute()) {

        $update->close();

        $stmt->close();

        $conn->close();

        errorResponse(
            500,
            "Unable to Reset Password."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Mark OTP Used
    |--------------------------------------------------------------------------
    */

    $otpUpdate = $conn->prepare("
        UPDATE password_resets
        SET
            is_used = 1
        WHERE id = ?
    ");

    if ($otpUpdate) {

        $otpUpdate->bind_param(
            "i",
            $otpData['id']
        );

        $otpUpdate->execute();

        $otpUpdate->close();

    }

    /*
    |--------------------------------------------------------------------------
    | Logout All Devices
    |--------------------------------------------------------------------------
    */

    $logout = $conn->prepare("
        UPDATE api_tokens
        SET
            is_active = 0
        WHERE user_id = ?
    ");

    if ($logout) {

        $logout->bind_param(
            "i",
            $otpData['user_id']
        );

        $logout->execute();

        $logout->close();

    }

    $update->close();

    $stmt->close();

    $conn->close();

    /*
    |--------------------------------------------------------------------------
    | Success
    |--------------------------------------------------------------------------
    */

    successResponse(
        "Password Reset Successfully. Please Login Again."
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