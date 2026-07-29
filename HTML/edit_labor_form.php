<?php
session_start();
include "auth_check.php";

/* ✅ ONLY RECRUITMENT */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 2 && $_SESSION['role'] != 4) {
    die("Access Denied");
}

/* GET CURRENT LINK */
$stmt = $conn->prepare("SELECT * FROM labor_form LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$form = $result->fetch_assoc();
$stmt->close();

/* UPDATE */
if (isset($_POST['update'])) {

    $link = $_POST['link'];

    $stmt = $conn->prepare("UPDATE labor_form SET link=? WHERE id=?");
    $stmt->bind_param("si", $link, $form['id']);
    $stmt->execute();
    $stmt->close();

    header("Location: labor.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Link</title>

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

<h2>Edit Apply Link</h2>

<form method="POST">
    <input type="text" name="link" value="<?php echo htmlspecialchars($form['link']); ?>" style="width:300px;">
    <br><br>
    <button type="submit" name="update" class="save-btn">Save</button>
</form>
</div>


</body>
</html>