/*
|--------------------------------------------------------------------------
| Add Student
|--------------------------------------------------------------------------
*/

document
.getElementById("studentForm")
.addEventListener("submit", addStudent);

async function addStudent(e)
{
    e.preventDefault();

    const student_name = document
        .getElementById("student_name")
        .value
        .trim();

    const age = document
        .getElementById("age")
        .value
        .trim();

    const city = document
        .getElementById("city")
        .value
        .trim();

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if(student_name==="")
    {
        Swal.fire(
            "Validation",
            "Student Name is Required.",
            "warning"
        );

        return;
    }

    if(age==="")
    {
        Swal.fire(
            "Validation",
            "Age is Required.",
            "warning"
        );

        return;
    }

    if(city==="")
    {
        Swal.fire(
            "Validation",
            "City is Required.",
            "warning"
        );

        return;
    }

    try
    {

        const response = await apiRequest(

            API_URL + "students/insert.php",

            {

                method:"POST",

                body:JSON.stringify({

                    student_name:student_name,

                    age:age,

                    city:city

                })

            }

        );

        const result = await response.json();

        if(result.status==="S")
        {

            Swal.fire({

                icon:"success",

                title:"Success",

                text:result.message,

                confirmButtonColor:"#198754"

            }).then(()=>{

                window.location.href="students.html";

            });

        }
        else
        {

            Swal.fire(

                "Error",

                result.message,

                "error"

            );

        }

    }
    catch(error)
    {

        Swal.fire(

            "Error",

            error.message,

            "error"

        );

    }

}