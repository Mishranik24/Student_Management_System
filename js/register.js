async function registerUser()
{

    let name=document.getElementById("name").value.trim();

    let email=document.getElementById("email").value.trim();

    let mobile=document.getElementById("mobile").value.trim();

    let password=document.getElementById("password").value;

    let confirm=document.getElementById("confirm_password").value;

    if(name==""){

        Swal.fire("Error","Enter Name","error");

        return;

    }

    if(email==""){

        Swal.fire("Error","Enter Email","error");

        return;

    }

    if(mobile==""){

        Swal.fire("Error","Enter Mobile","error");

        return;

    }

    if(password==""){

        Swal.fire("Error","Enter Password","error");

        return;

    }

    if(password!=confirm){

        Swal.fire("Error","Password Doesn't Match","error");

        return;

    }

    try{

        const response=await fetch(

            API_URL+"auth/register.php",

            {

                method:"POST",

                headers:{

                    "Content-Type":"application/json"

                },

                body:JSON.stringify({

                    name:name,

                    email:email,

                    mobile:mobile,

                    password:password

                })

            }

        );

        const result=await response.json();

        if(result.status=="S"){

            Swal.fire({

                icon:"success",

                title:"Success",

                text:result.message

            }).then(()=>{

                location.href="login.html";

            });

        }
        else{

            Swal.fire({

                icon:"error",

                title:"Error",

                text:result.message

            });

        }

    }
    catch(error){

        Swal.fire({

            icon:"error",

            title:"Server Error",

            text:"Unable to Register."

        });

    }

}