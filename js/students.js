let currentPage = 1;

let currentLimit = 10;

let currentSearch = "";

async function loadStudents(page = 1) {

    currentPage = page;

    currentSearch = document.getElementById("search").value.trim();

    try {

        const response = await apiRequest(

            API_URL + "students/search.php",

            {

                method: "POST",

                // headers: authHeader(),

                body: JSON.stringify({

                    search: currentSearch,

                    page: currentPage,

                    limit: currentLimit

                })

            }

        );

        const result = await response.json();

        if(result.status=="S"){

            displayStudents(result.data);

        }else{

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

            text:"Unable to fetch students."

        });

    }

}

function displayStudents(data){
    let srNo = (currentPage - 1) * currentLimit + 1;

    let html="";

    data.students.forEach(student=>{

        html+=`

        <tr>
            <td>${srNo++}</td>

            <td>${student.student_name}</td>

            <td>${student.age}</td>

            <td>${student.city}</td>

            <td>

                <button
                    class="btn btn-warning btn-sm me-2"
                    onclick="editStudent(${student.id})">

                    <i class="bi bi-pencil-square"></i>

                    Edit

                </button>

                <button
                    class="btn btn-danger btn-sm"
                    onclick="deleteStudent(${student.id})">

                    <i class="bi bi-trash"></i>

                    Delete

                </button>

            </td>

        </tr>

        `;

    });

    if(data.students.length===0){

        html=`

        <tr>

            <td colspan="5" class="text-center">

                No Students Found

            </td>

        </tr>

        `;

    }
    console.log(html);
    document.getElementById("studentTable").innerHTML=html;

    createPagination(data);

}

function createPagination(data){

    let html="";

    for(let i=1;i<=data.total_pages;i++){

        html+=`

        <button
            class="btn ${i===data.page ? 'btn-primary' : 'btn-outline-primary'} btn-sm me-1"
            onclick="loadStudents(${i})">

            ${i}

        </button>

        `;

    }

    document.getElementById("pagination").innerHTML=html;

}

let searchTimer;

document
.getElementById("search")
.addEventListener("input", function () {

    clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {

        currentPage = 1;

        loadStudents();

    }, 300);

});

/*
|--------------------------------------------------------------------------
| Edit Student
|--------------------------------------------------------------------------
*/

function editStudent(id)
{
    window.location.href = "edit-student.html?id=" + id;
}

/*
|--------------------------------------------------------------------------
| Delete Student
|--------------------------------------------------------------------------
*/

async function deleteStudent(id)
{

    const confirmDelete = await Swal.fire({

        title: "Delete Student?",

        text: "You won't be able to recover this student.",

        icon: "warning",

        showCancelButton: true,

        confirmButtonColor: "#dc3545",

        cancelButtonColor: "#6c757d",

        confirmButtonText: "Yes, Delete"

    });

    if(!confirmDelete.isConfirmed){

        return;

    }

    try{

        const response = await apiRequest(

            API_URL + "students/delete.php",

            {

                method: "DELETE",

                body: JSON.stringify({

                    id: id

                })

            }

        );

        const result = await response.json();

        if(result.status=="S"){

            Swal.fire({

                icon: "success",

                title: "Success",

                text: result.message,

                timer: 1500,

                showConfirmButton: false

            });

            loadStudents(currentPage);

        }
        else{

            Swal.fire({

                icon: "error",

                title: "Error",

                text: result.message

            });

        }

    }
    catch(error){

        Swal.fire({

            icon: "error",

            title: "Server Error",

            text: "Unable to delete student."

        });

    }

}