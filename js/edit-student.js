/*
|--------------------------------------------------------------------------
| Student ID
|--------------------------------------------------------------------------
*/

const studentId = new URLSearchParams(window.location.search).get("id");

if (!studentId) {

    Swal.fire({

        icon: "error",

        title: "Invalid Request",

        text: "Student ID not found."

    }).then(() => {

        window.location.href = "students.html";

    });

}

/*
|--------------------------------------------------------------------------
| Load Student
|--------------------------------------------------------------------------
*/

async function loadStudent()
{

    try{

        const response = await apiRequest(

            API_URL + "students/fetch-single.php?id=" + studentId,

            {

                method: "GET"

            }

        );

        const result = await response.json();

        if(result.status=="S"){

            const student = result.data.student;

            document.getElementById("student_id").value = student.id;

            document.getElementById("student_name").value = student.student_name;

            document.getElementById("age").value = student.age;

            document.getElementById("city").value = student.city;

        }
        else{

            Swal.fire({

                icon:"error",

                title:"Error",

                text:result.message

            }).then(()=>{

                window.location.href="students.html";

            });

        }

    }
    catch(error){

        Swal.fire({

            icon:"error",

            title:"Server Error",

            text:"Unable to load student."

        });

    }

}

/*
|--------------------------------------------------------------------------
| Update Student
|--------------------------------------------------------------------------
*/

document
.getElementById("studentForm")
.addEventListener("submit", updateStudent);

async function updateStudent(e)
{

    e.preventDefault();

    const id = document.getElementById("student_id").value;

    const student_name = document.getElementById("student_name").value.trim();

    const age = document.getElementById("age").value.trim();

    const city = document.getElementById("city").value.trim();

    if(student_name==""){

        Swal.fire("Validation","Student Name is Required.","warning");

        return;

    }

    if(age==""){

        Swal.fire("Validation","Age is Required.","warning");

        return;

    }

    if(city==""){

        Swal.fire("Validation","City is Required.","warning");

        return;

    }

    try{

        const response = await apiRequest(

            API_URL + "students/update.php",

            {

                method:"PUT",

                body:JSON.stringify({

                    id:id,

                    student_name:student_name,

                    age:age,

                    city:city

                })

            }

        );

        const result = await response.json();

        if(result.status=="S"){

            Swal.fire({

                icon:"success",

                title:"Success",

                text:result.message

            }).then(()=>{

                window.location.href="students.html";

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

            text:"Unable to update student."

        });

    }

}

/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

loadStudent();