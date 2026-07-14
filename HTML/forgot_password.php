<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Forgot Password</title>

<link rel="stylesheet" href="../CSS/auth.css">
<link rel="icon" type="image/png" href="../IMAGES/logo.png">

<style>

/* ===== SUCCESS MODAL ===== */

.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    justify-content:center;
    align-items:center;
    z-index:9999;
}

.modal-content{
    background:#fff;
    width:420px;
    padding:35px;
    border-radius:12px;
    text-align:center;
    box-shadow:0 15px 40px rgba(0,0,0,.2);
    animation:popup .25s ease;
}

.modal-content h2{
    color:#0c8a36;
    margin-bottom:15px;
}

.modal-content p{
    color:#555;
    margin-bottom:25px;
    line-height:1.6;
}

@keyframes popup{

    from{
        transform:scale(.8);
        opacity:0;
    }

    to{
        transform:scale(1);
        opacity:1;
    }

}

</style>

</head>
<body>

<div class="auth-container">

    <div class="auth-card">

        <h1>Forgot Password</h1>

        <p style="text-align:center;color:#666;margin-bottom:20px;">
            Enter your registered email address.
            We'll send you a password reset link.
        </p>

        <?php
        if(isset($_SESSION['message']) && $_SESSION['msg_type']=="error"){
            echo '<div class="error-message">';
            echo $_SESSION['message'];
            echo '</div>';

            unset($_SESSION['message']);
            unset($_SESSION['msg_type']);
        }
        ?>

        <form action="send-reset.php" method="POST">

            <label>Email Address</label>

            <input
                type="email"
                name="email"
                placeholder="Enter your email"
                required
            >

            <button type="submit" class="primary-btn">
                Send Reset Link
            </button>

        </form>

        <div class="auth-link">
            <a href="login.php">← Back to Login</a>
        </div>

    </div>

</div>

<?php if(isset($_SESSION['message']) && $_SESSION['msg_type']=="success"): ?>

<div id="successModal" class="modal">

    <div class="modal-content">

        <h2>Success!</h2>

        <p>
            <?php echo $_SESSION['message']; ?>
        </p>

        <button class="primary-btn" onclick="goToLogin()">
            OK
        </button>

    </div>

</div>

<script>

window.onload=function(){

    document.getElementById("successModal").style.display="flex";

}

function goToLogin() {
    window.location.href = "login.php";
}

}

</script>

<?php
unset($_SESSION['message']);
unset($_SESSION['msg_type']);
endif;
?>

</body>
</html>