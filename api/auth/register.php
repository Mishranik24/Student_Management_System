<?php

header("Content-Type: application/json");

require_once "../../config/database.php";
require_once "../../config/response.php";
require_once "../../helper/common_helper.php";
require_once "../../helper/validation_helper.php";

$db = new Database();
$conn = $db->connect();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    errorResponse(400, "Invalid JSON");
}

$name = sanitize($data['name'] ?? '');
$email = sanitize($data['email'] ?? '');
$mobile = sanitize($data['mobile'] ?? '');
$password = $data['password'] ?? '';

if ($name == "")
    errorResponse(422, "Name Required");

if (!validateEmail($email))
    errorResponse(422, "Invalid Email");

if (!validateMobile($mobile))
    errorResponse(422, "Invalid Mobile");

if (!validatePassword($password))
    errorResponse(422, "Password Minimum 6 Characters");

$stmt = $conn->prepare("SELECT id FROM users WHERE email=? OR mobile=?");

$stmt->bind_param("ss", $email, $mobile);

$stmt->execute();

$stmt->store_result();

if ($stmt->num_rows > 0) {

    errorResponse(409, "Email or Mobile Already Exists");

}

$stmt->close();

$hashPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$stmt = $conn->prepare("
INSERT INTO users
(
name,
email,
mobile,
password
)
VALUES
(
?,
?,
?,
?
)
");

$stmt->bind_param(
    "ssss",
    $name,
    $email,
    $mobile,
    $hashPassword
);

if ($stmt->execute()) {

    successResponse(
        "Registration Successful"
    );

}

errorResponse(
    500,
    "Registration Failed"
);