<?php

date_default_timezone_set('Asia/Kolkata');

/*
|--------------------------------------------------------------------------
| Application
|--------------------------------------------------------------------------
*/

define("APP_NAME", "Student Management API");

/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

define("DB_HOST", "localhost");
define("DB_NAME", "student_api");
define("DB_USER", "root");
define("DB_PASS", "");

/*
|--------------------------------------------------------------------------
| JWT
|--------------------------------------------------------------------------
*/

define('JWT_SECRET_KEY', 'StudentManagementAPI@2026#JWTSecretKey!1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');

define("JWT_ISSUER", "http://localhost");

define("JWT_AUDIENCE", "http://localhost");

define("ACCESS_TOKEN_EXPIRY", 900);

define("REFRESH_TOKEN_EXPIRY", 2592000);