<?php
session_start();
include "auth_check.php"; 

/* ACCESS CONTROL */
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 1 && $_SESSION['role'] != 4)) {
    die("Access Denied");
}

/* VALIDATE ID */
if (!isset($_GET['id'])) {
    die("Invalid request");
}

$id = (int) $_GET['id'];

/* FETCH DATA (PREPARED) */
$stmt = $conn->prepare("SELECT * FROM credit_forms WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$form = $result->fetch_assoc();

if (!$form) {
    die("Form not found");
}

/* UPDATE LOGIC */
if (isset($_POST['update'])) {

    $title = $_POST['title'];
    $desc  = $_POST['description'];
    $link  = $_POST['link'];

    $stmt = $conn->prepare("UPDATE credit_forms SET title=?, description=?, link=? WHERE id=?");
    $stmt->bind_param("sssi", $title, $desc, $link, $id);
    $stmt->execute();

    header("Location: credit.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Credit Form</title>
    <link rel="stylesheet" href="../CSS/credit.css">
    <style>
        .edit-container {
            width: 400px;
            margin: 60px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .edit-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .edit-container input,
        .edit-container textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .save-btn {
            width: 100%;
            padding: 10px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .save-btn:hover {
            background: #1b5e20;
        }
    </style>
</head>
<body>

<div class="edit-container">
    <h2>Edit Form</h2>

    <form method="POST">
        <input type="text" name="title" value="<?php echo htmlspecialchars($form['title']); ?>" required>

        <textarea name="description" required><?php echo htmlspecialchars($form['description']); ?></textarea>

        <input type="text" name="link" value="<?php echo htmlspecialchars($form['link']); ?>" required>

        <button type="submit" name="update" class="save-btn">Save Changes</button>
    </form>
</div>

</body>
</html>