document
.getElementById("changePasswordForm")
.addEventListener("submit",changePassword);

async function changePassword(e){

    e.preventDefault();

    const old_password=document
    .getElementById("old_password").value;

    const new_password=document
    .getElementById("new_password").value;

    const confirm_password=document
    .getElementById("confirm_password").value;

    if(new_password!=confirm_password){

        Swal.fire(
            "Error",
            "Passwords do not match.",
            "error"
        );

        return;

    }

    const response=await apiRequest(

        API_URL+"auth/change-password.php",

        {

            method:"POST",

            body:JSON.stringify({

                old_password: document.getElementById("old_password").value,

                new_password: document.getElementById("new_password").value,

                confirm_password: document.getElementById("confirm_password").value

            })

        }

    );

    const result=await response.json();

    if(result.status == "S"){

        clearSession();

        window.location.href = "login.html";

    }else{

        Swal.fire(
            "Error",
            result.message,
            "error"
        );

    }

}