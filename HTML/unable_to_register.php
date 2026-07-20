<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unable to Register</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }

        .container {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            max-width: 400px;
            width: 90%;
        }

        h1 {
            color: #b02a2a;
            margin-bottom: 15px;
        }

        p {
            color: #555;
            margin-bottom: 25px;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            transition: 0.3s;
            font-weight: bold;
        }

        .btn-login {
            background: #0c8a36;
        }

        .btn-login:hover {
            background: #086b29;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Registration Not Available</h1>
    <p>This registration link is invalid, expired, or already used. Please contact the administrator for a new invitation link.</p>

    <div class="button-group">
        <a href="/login" class="btn btn-login">Go to Login</a>
    </div>
</div>

</body>
</html>
