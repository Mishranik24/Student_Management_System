document
.getElementById("resetPasswordForm")
.addEventListener("submit", resetPassword);

async function resetPassword(e){

    e.preventDefault();

    const email=sessionStorage.getItem("reset_email");

    const otp=document
    .getElementById("otp")
    .value.trim();

    const password=document
    .getElementById("password")
    .value;

    const confirm_password=document
    .getElementById("confirm_password")
    .value;

    if(password!=confirm_password){

        Swal.fire(

            "Error",

            "Passwords do not match.",

            "error"

        );

        return;

    }

    try{

        const response=await apiRequest(

            API_URL+"auth/reset-password.php",

            {

                method:"POST",

                body:JSON.stringify({

                    email:email,

                    otp:otp,

                    new_password:password,

                    confirm_password:confirm_password

                })

            }

        );

        const result=await response.json();

        if(result.status=="S"){

            sessionStorage.removeItem("reset_email");

            Swal.fire({

                icon:"success",

                title:"Success",

                text:result.message

            }).then(()=>{

                location.href="login.html";

            });

        }else{

            Swal.fire(

                "Error",

                result.message,

                "error"

            );

        }

    }
    catch(error){

        Swal.fire(

            "Error",

            "Unable to reset password.",

            "error"

        );

    }

}