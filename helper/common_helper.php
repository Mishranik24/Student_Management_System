<?php

function sanitize($data)
{
    return htmlspecialchars(
        trim($data),
        ENT_QUOTES,
        'UTF-8'
    );
}

function getIPAddress()
{

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {

        return $_SERVER['HTTP_CLIENT_IP'];

    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

        return $_SERVER['HTTP_X_FORWARDED_FOR'];

    }

    return $_SERVER['REMOTE_ADDR'];

}

function getUserAgent()
{
    return $_SERVER['HTTP_USER_AGENT'];
}