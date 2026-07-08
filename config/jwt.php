<?php

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/constants.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JWTHandler
{

    /*
    |--------------------------------------------------------------------------
    | Generate JWT Token
    |--------------------------------------------------------------------------
    */

    public static function generateToken(array $payload): string
    {
        return JWT::encode(
            $payload,
            JWT_SECRET_KEY,
            'HS256'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Verify JWT Token
    |--------------------------------------------------------------------------
    */

    public static function verifyToken(string $token)
    {
        try {

            return JWT::decode(
                $token,
                new Key(JWT_SECRET_KEY, 'HS256')
            );

        } catch (ExpiredException $e) {

            return false;

        } catch (SignatureInvalidException $e) {

            return false;

        } catch (Exception $e) {

            return false;

        }
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Secure Session ID
    |--------------------------------------------------------------------------
    */

    public static function generateSessionId(): string
    {
        return bin2hex(
            random_bytes(32)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Unique JWT ID (JTI)
    |--------------------------------------------------------------------------
    */

    public static function generateJTI(): string
    {
        return bin2hex(
            random_bytes(16)
        );
    }

}