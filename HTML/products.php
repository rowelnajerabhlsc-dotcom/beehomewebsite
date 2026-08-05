<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bee Home Labor Multipurpose Cooperative</title>
    <link rel="stylesheet" href="../CSS/products.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" type="image/png" href="../IMAGES/logo.png">
    <link rel="stylesheet" href="../CSS/skeleton.css">
</head>

<body>

    <?php include "navbar.php"; ?>

    <!-- HERO -->
    <section class="products-hero" data-animate="fade-in">
        <h1>PRODUCTS & SERVICES</h1>
    </section>

    <section class="products-section">
        <div class="products-container">

            <!-- ================= NON MEMBERS ================= -->
            <h2 class="section-title" data-animate="fade-in">Available for Non-Members</h2>

            <div class="products-row" data-animate="slide-left">
                <!-- LABOR -->
                <div class="product-box" data-animate="slide-left">
                    <img src="../IMAGES/product-logo1.png" data-src="../IMAGES/product-logo1.png" alt="Labor Operation" class="lazy-load">
                    <h3>LABOR OPERATION</h3>
                    <p>• Human Resources</p>
                    <p>• Job Contracting</p>
                    <p>• Sub Contracting</p>
                    <div class="product-buttons">
                        <a href="../HTML/labor.php" class="btn-green">View More</a>
                    </div>
                </div>

                <!-- TRANSPORT -->
                <div class="product-box" data-animate="slide-left">
                    <img src="../IMAGES/product-logo3.png" data-src="../IMAGES/product-logo3.png" alt="Transport Operation" class="lazy-load">
                    <h3>TRANSPORT OPERATION</h3>
                    <p>• Transport Fleet Management</p>
                    <p>• Other Allied Business</p>

                    <div class="product-buttons">
                        <a href="../HTML/transport.php" class="btn-green">View More</a>

                    </div>
                </div>

                <!-- PROPERTY -->
                <div class="product-box" data-animate="slide-left">
                    <img src="../IMAGES/product-logo7.png" data-src="../IMAGES/product-logo7.png" alt="Property Management" class="lazy-load">
                    <h3>PROPERTY MANAGEMENT</h3>
                    <p>• Canteen Operation</p>
                    <br>

                    <div class="product-buttons">
                        <a href="../HTML/property.php" class="btn-green">View More</a>

                    </div>
                </div>

                <!-- TRAVEL -->
                <div class="product-box" data-animate="slide-left">
                    <img src="../IMAGES/product-logo4.png" data-src="../IMAGES/product-logo4.png" alt="Travel & Tours" class="lazy-load">
                    <h3>TRAVEL & TOURS</h3>
                    <p>• Coming Soon</p>
                    <br><br>
                    <div class="product-buttons">
                        <a href="../HTML/travel.php" class="btn-green">View More</a>

                    </div>
                </div>
            </div>

            <!-- ================= MEMBERS ONLY ================= -->
            <h2 class="section-title" data-animate="fade-in">Exclusive to Members Only</h2>

            <div class="products-row" data-animate="slide-right">
                <!-- CREDIT -->
                <div class="product-box" data-animate="slide-right">
                    <img src="../IMAGES/product-logo2.png" data-src="../IMAGES/product-logo2.png" alt="Credit Operation" class="lazy-load">
                    <h3>CREDIT OPERATION</h3>
                    <p>(FOR MEMBERS ONLY)</p>
                    <p>• Low Interest Rate</p>
                    <p>• Short Term Loan 3%</p>
                    <p>• Long Term Loan 1.7%</p>
                    <p>• BHLIP 3%</p>
                    <div class="product-buttons">
                        <a href="<?php echo isset($_SESSION['user_id']) ? '../HTML/credit.php' : '../HTML/needlogin.php'; ?>"
                            class="btn-green">
                            View More
                        </a>

                    </div>
                </div>

                <!-- RETAIL -->
                <div class="product-box" data-animate="slide-right">
                    <img src="../IMAGES/product-logo6.png" data-src="../IMAGES/product-logo6.png" alt="Retail Operation" class="lazy-load">
                    <h3>RETAIL OPERATION</h3>
                    <p>(FOR MEMBERS ONLY)</p>
                    <p>• Basic Household Needs</p>
                    <br><br><br>
                    <div class="product-buttons">
                        <a href="<?php echo isset($_SESSION['user_id']) ? '../HTML/retail.php' : '../HTML/needlogin.php'; ?>"
                            class="btn-green">
                            View More
                        </a>

                    </div>
                </div>

                <!-- BILLS -->
                <div class="product-box" data-animate="slide-right">
                    <img src="../IMAGES/product-logo5.png" data-src="../IMAGES/product-logo5.png" alt="Bills Payment" class="lazy-load">
                    <h3>BILLS PAYMENT</h3>
                    <p>• Coming Soon</p>
                    <br><br><br><br>
                    <div class="product-buttons">
                        <a href="<?php echo isset($_SESSION['user_id']) ? '../HTML/bills.php' : '../HTML/needlogin.php'; ?>"
                            class="btn-green">
                            View More
                        </a>

                    </div>
                </div>
            </div>

        </div>
    </section>

    <?php include "footer.php"; ?>

</body>
<script src="../JS/lazy-load.js"></script>

</html>