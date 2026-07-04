<?php
session_start();

/* DATABASE CONNECTION */
$host = "localhost:3306";
$user = "kwchy8j4554l";
$password = "Be3home@2026";
$database = "beehome";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* FETCH REQUESTS */
$result = $conn->query("
    SELECT *
    FROM manpower_requests
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manpower Request Logs</title>

<link rel="stylesheet" href="../CSS/navbar.css">
<link rel="stylesheet" href="../CSS/home.css">
<link rel="stylesheet" href="../CSS/manpower-request-logs.css">

</head>
<body>

<div id="page-content">

    <?php include "navbar.php"; ?>

    <div class="logs-container">

        <h2>Manpower Request Logs</h2>

        <table>

            <tr>
                <th>ID</th>
                <th>Business Name</th>
                <th>Contact Person</th>
                <th>Email</th>
                <th>Requested Position</th>
                <th>No. Required</th>
                <th>Assignment Place</th>
                <th>Actions</th>
            </tr>

            <?php if($result->num_rows > 0): ?>

                <?php while($row = $result->fetch_assoc()): ?>

                <tr>

                    <td><?= $row['id']; ?></td>

                    <td><?= htmlspecialchars($row['business_name']); ?></td>

                    <td><?= htmlspecialchars($row['contact_person']); ?></td>

                    <td><?= htmlspecialchars($row['email']); ?></td>

                    <td><?= htmlspecialchars($row['req_position']); ?></td>

                    <td><?= htmlspecialchars($row['number_required']); ?></td>

                    <td><?= htmlspecialchars($row['assignment_place']); ?></td>

                    <td>

                        <button
                            class="btn-view"
                            onclick="openModal(
                                '<?= htmlspecialchars($row['business_name'], ENT_QUOTES); ?>',
                                '<?= htmlspecialchars($row['contact_person'], ENT_QUOTES); ?>',
                                '<?= htmlspecialchars($row['req_position'], ENT_QUOTES); ?>',
                                '<?= htmlspecialchars($row['email'], ENT_QUOTES); ?>',
                                '<?= htmlspecialchars($row['telephone'] ?? '', ENT_QUOTES); ?>',
                                '<?= htmlspecialchars($row['fax'] ?? '', ENT_QUOTES); ?>',
                                '<?= htmlspecialchars($row['website'] ?? '', ENT_QUOTES); ?>',
                                '<?= htmlspecialchars($row['req_position'], ENT_QUOTES); ?>',
                                '<?= htmlspecialchars($row['number_required'], ENT_QUOTES); ?>',
                                '<?= htmlspecialchars($row['job_description'] ?? '', ENT_QUOTES); ?>',
                                '<?= htmlspecialchars($row['assignment_place'], ENT_QUOTES); ?>'
                            )">
                            View
                        </button>

                        <button
    class="btn-delete"
    onclick="openDeleteModal(<?= $row['id']; ?>)">
    Delete
</button>

                    </td>

                </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="8" style="text-align:center;">
                        No manpower requests found.
                    </td>
                </tr>

            <?php endif; ?>

        </table>

    </div>

</div>

<!-- VIEW MODAL -->
<div id="viewModal" class="modal-overlay">

    <div class="modal-box">

        <h2>Manpower Request Details</h2>

        <p><strong>Business Name:</strong> <span id="m_business"></span></p>

        <p><strong>Contact Person:</strong> <span id="m_contact"></span></p>

        <p><strong>Position:</strong> <span id="m_position"></span></p>

        <p><strong>Email:</strong> <span id="m_email"></span></p>

        <p><strong>Telephone:</strong> <span id="m_telephone"></span></p>

        <p><strong>Fax:</strong> <span id="m_fax"></span></p>

        <p><strong>Website:</strong> <span id="m_website"></span></p>

        <p><strong>Requested Position:</strong> <span id="m_req_position"></span></p>

        <p><strong>Number Required:</strong> <span id="m_number"></span></p>

        <p><strong>Job Description:</strong> <span id="m_description"></span></p>

        <p><strong>Assignment Place:</strong> <span id="m_assignment"></span></p>

        <button class="close-btn" onclick="closeModal()">
            Close
        </button>

    </div>

</div>
<!-- DELETE MODAL -->
<div id="deleteModal" class="modal-overlay">

    <div class="confirm-modal">

        <h3>Delete Request</h3>

        <p>Are you sure you want to delete this manpower request?</p>

        <div class="confirm-buttons">

            <button class="btn-yes" onclick="confirmDelete()">
                Yes, Delete
            </button>

            <button class="btn-no" onclick="closeDeleteModal()">
                Cancel
            </button>

        </div>

    </div>

</div>
<script>

let deleteID = null;

function openModal(
    business,
    contact,
    position,
    email,
    telephone,
    fax,
    website,
    req_position,
    number_required,
    job_description,
    assignment_place
){

    document.getElementById("m_business").innerText = business;
    document.getElementById("m_contact").innerText = contact;
    document.getElementById("m_position").innerText = position;
    document.getElementById("m_email").innerText = email;
    document.getElementById("m_telephone").innerText = telephone;
    document.getElementById("m_fax").innerText = fax;
    document.getElementById("m_website").innerText = website;
    document.getElementById("m_req_position").innerText = req_position;
    document.getElementById("m_number").innerText = number_required;
    document.getElementById("m_description").innerText = job_description;
    document.getElementById("m_assignment").innerText = assignment_place;

    document.getElementById("viewModal").style.display = "flex";
    document.getElementById("page-content").classList.add("blur-background");
}

function closeModal(){

    document.getElementById("viewModal").style.display = "none";
    document.getElementById("page-content").classList.remove("blur-background");
}

function openDeleteModal(id){

    deleteID = id;

    document.getElementById("deleteModal").style.display = "flex";
    document.getElementById("page-content").classList.add("blur-background");
}

function closeDeleteModal(){

    document.getElementById("deleteModal").style.display = "none";
    document.getElementById("page-content").classList.remove("blur-background");
}

function confirmDelete(){

    if(deleteID){
        window.location.href = "delete-manpower.php?id=" + deleteID;
    }
}

window.onclick = function(event){

    let viewModal = document.getElementById("viewModal");
    let deleteModal = document.getElementById("deleteModal");

    if(event.target === viewModal){
        closeModal();
    }

    if(event.target === deleteModal){
        closeDeleteModal();
    }
    
}



</script>

</body>
</html>

<?php
$conn->close();
?>