const user = getUser();

document.getElementById("welcomeUser").innerHTML =
"Hi, " + user.name;

loadProfile();

async function loadProfile()
{

    try{

        const response = await apiRequest(

            API_URL + "auth/profile.php",

            {

                method:"GET"

            }

        );

        const result = await response.json();

        if(result.status=="S"){

            const profile = result.data.user;

            document.getElementById("name").value = profile.name;

            document.getElementById("email").value = profile.email;

            document.getElementById("mobile").value = profile.mobile;

            document.getElementById("role").value = profile.role;

        }
        else{

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

            "Unable to load profile.",

            "error"

        );

    }

}

document
.getElementById("profileForm")
.addEventListener(

"submit",

async function(e){

    e.preventDefault();

    try{

        const response = await apiRequest(

            API_URL + "auth/profile.php",

            {

                method:"PUT",

                body:JSON.stringify({

                    name:document.getElementById("name").value,

                    email:document.getElementById("email").value

                })

            }

        );

        const result = await response.json();

        if(result.status=="S"){

            const user = getUser();

            user.name = document.getElementById("name").value;

            localStorage.setItem(

                "user",

                JSON.stringify(user)

            );

            document.getElementById("welcomeUser").innerHTML =
            "Hi, " + user.name;

            Swal.fire(

                "Success",

                result.message,

                "success"

            );

        }
        else{

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

            "Unable to update profile.",

            "error"

        );

    }

});