<?php
session_start();
include "config.php";

/* ACCESS CONTROL */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 3 && $_SESSION['role'] != 4)) {
    header("Location: home.php");
    exit();
}

/* FETCH DATA */
$result = $conn->query("
    SELECT 
        u.id,
        u.username,
        u.email,
        p.batching_id,
        p.fname, p.mname, p.lname,
        p.department, p.position,
        p.contact_number
    FROM users u
    LEFT JOIN user_profiles p ON u.id = p.user_id
    ORDER BY u.id ASC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Records</title>
    <link rel="stylesheet" href="../CSS/auth.css">
    <link rel="stylesheet" href="../CSS/navbar.css">

    <style>
        .table-container {
            padding: 20px;
        }

        .search-box {
            margin-bottom: 15px;
        }

        .search-box input {
            width: 300px;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }

        th {
            background: #f4f4f4;
        }

        .action-btn {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            cursor: pointer;
        }

        .edit {
            background: #4CAF50;
            color: white;
        }

        .delete {
            background: #f44336;
            color: white;
        }
    </style>
</head>
<body>

<?php include "navbar.php"; ?>

<div class="table-container">

    <h2>Employee Records</h2>

    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search employees...">
    </div>

    <table>
        <tr>
            <th>Batching ID</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Department</th>
            <th>Position</th>
            <th>Contact</th>
            <th>Actions</th>
        </tr>

        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['batching_id']; ?></td>
            <td><?= htmlspecialchars($row['username']); ?></td>
            <td><?= htmlspecialchars(trim($row['fname']." ".$row['mname']." ".$row['lname'])); ?></td>
            <td><?= htmlspecialchars($row['email']); ?></td>
            <td><?= htmlspecialchars($row['department']); ?></td>
            <td><?= htmlspecialchars($row['position']); ?></td>
            <td><?= htmlspecialchars($row['contact_number']); ?></td>

            <td>
                <a href="edit_user.php?id=<?= $row['id']; ?>">
                    <button class="action-btn edit">Edit</button>
                </a>

                <a href="delete_user.php?id=<?= $row['id']; ?>" onclick="return confirm('Delete this user?')">
                    <button class="action-btn delete">Delete</button>
                </a>
            </td>
        </tr>
        <?php endwhile; ?>

    </table>

</div>

<script>
document.getElementById("searchInput").addEventListener("keyup", function () {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("table tr");

    rows.forEach((row, index) => {
        if (index === 0) return; // Skip header row

        let text = row.textContent.toLowerCase();

        if (text.indexOf(filter) > -1) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});
</script>

</body>
</html>