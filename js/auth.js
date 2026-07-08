document
    .getElementById("loginForm")
    .addEventListener("submit", login);

async function login(e) {

    e.preventDefault();

    const loginValue = document.getElementById("login").value.trim();
    const password = document.getElementById("password").value.trim();

    if (loginValue == "") {

        Swal.fire({
            icon: "warning",
            title: "Validation",
            text: "Email or Mobile is required."
        });

        return;
    }

    if (password == "") {

        Swal.fire({
            icon: "warning",
            title: "Validation",
            text: "Password is required."
        });

        return;
    }

    const btn = document.querySelector("button[type='submit']");

    btn.disabled = true;
    btn.innerHTML = "Please Wait...";

    try {

        const response = await fetch(API_URL + "auth/login.php", {

            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify({

                login: loginValue,

                password: password

            })

        });

        const result = await response.json();

        if (result.status == "S") {

            localStorage.setItem(
                "access_token",
                result.data.access_token
            );

            localStorage.setItem(
                "refresh_token",
                result.data.refresh_token
            );

            localStorage.setItem(
                "user",
                JSON.stringify(result.data.user)
            );

            Swal.fire({

                icon: "success",

                title: "Success",

                text: result.message,

                timer: 1200,

                showConfirmButton: false

            }).then(() => {

                window.location.href = "dashboard.html";

            });

        } else {

            Swal.fire({

                icon: "error",

                title: "Login Failed",

                text: result.message

            });

        }

    } catch (error) {

        Swal.fire({

            icon: "error",

            title: "Error",

            text: "Unable to connect to server."

        });

    }

    btn.disabled = false;

    btn.innerHTML = "Login";

}

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

async function logout() {

    try {

        const response = await fetch(

            API_URL + "auth/logout.php",

            {

                method: "POST",

                headers: authHeader()

            }

        );

        const result = await response.json();

    } catch (e) {

    }

    clearSession();

    window.location.href = "login.html";

}