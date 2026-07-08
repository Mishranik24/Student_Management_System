/*
|--------------------------------------------------------------------------
| API Base URL
|--------------------------------------------------------------------------
*/

const API_URL = "http://localhost/Student_Management_API/api/";

/*
|--------------------------------------------------------------------------
| Access Token
|--------------------------------------------------------------------------
*/

function getAccessToken() {

    return localStorage.getItem("access_token");

}

/*
|--------------------------------------------------------------------------
| Refresh Token
|--------------------------------------------------------------------------
*/

function getRefreshToken() {

    return localStorage.getItem("refresh_token");

}

/*
|--------------------------------------------------------------------------
| Logged User
|--------------------------------------------------------------------------
*/

function getUser() {

    return JSON.parse(localStorage.getItem("user"));

}

/*
|--------------------------------------------------------------------------
| Authorization Header
|--------------------------------------------------------------------------
*/

function authHeader() {

    return {

        "Content-Type": "application/json",

        "Authorization": "Bearer " + getAccessToken()

    };

}

/*
|--------------------------------------------------------------------------
| Check Login
|--------------------------------------------------------------------------
*/

function isLoggedIn() {

    return getAccessToken() != null;

}

/*
|--------------------------------------------------------------------------
| Logout Local
|--------------------------------------------------------------------------
*/

function clearSession() {

    localStorage.removeItem("access_token");

    localStorage.removeItem("refresh_token");

    localStorage.removeItem("user");

}

/*
|--------------------------------------------------------------------------
| Refresh Access Token
|--------------------------------------------------------------------------
*/

async function refreshAccessToken() {

    const refreshToken = getRefreshToken();

    if (!refreshToken) {

        clearSession();

        window.location.href = "login.html";

        return false;

    }

    try {

        const response = await fetch(
            API_URL + "auth/refresh-token.php",
            {
                method: "POST",
                headers: {
                    "Authorization": "Bearer " + refreshToken
                }
            }
        );

        const result = await response.json();

        if (result.status === "S") {

            localStorage.setItem(
                "access_token",
                result.data.access_token
            );

            localStorage.setItem(
                "refresh_token",
                result.data.refresh_token
            );

            return true;

        }

        clearSession();

        window.location.href = "login.html";

        return false;

    } catch (error) {

        console.error(error);

        clearSession();

        window.location.href = "login.html";

        return false;

    }

}

/*
|--------------------------------------------------------------------------
| Common API Request
|--------------------------------------------------------------------------
*/

async function apiRequest(url, options = {}) {

    if (!options.headers) {
        options.headers = {};
    }

    options.headers = {
        ...options.headers,
        "Authorization": "Bearer " + getAccessToken(),
        "Content-Type": "application/json"
    };

    let response = await fetch(url, options);

    // Success
    if (response.status !== 401) {
        return response;
    }

    // Try refreshing token
    const refreshed = await refreshAccessToken();

    if (!refreshed) {
        return response;
    }

    // Retry original request
    options.headers.Authorization =
        "Bearer " + getAccessToken();

    return await fetch(url, options);

}