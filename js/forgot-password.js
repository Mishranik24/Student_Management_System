document
.getElementById("forgotPasswordForm")
.addEventListener("submit", forgotPassword);

async function forgotPassword(e){

    e.preventDefault();

    const email=document
    .getElementById("email")
    .value.trim();

    try{

        const response=await apiRequest(

            API_URL+"auth/forgot-password.php",

            {

                method:"POST",

                body:JSON.stringify({

                    email:email

                })

            }

        );

        const result=await response.json();

        if(result.status=="S"){

            sessionStorage.setItem("reset_email",email);

            Swal.fire({

                icon:"success",

                title:"OTP Sent",

                text:result.message

            }).then(()=>{

                location.href="reset-password.html";

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

            "Unable to send OTP.",

            "error"

        );

    }

}