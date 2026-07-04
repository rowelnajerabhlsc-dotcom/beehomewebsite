<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bee Home Labor Multipurpose Cooperative</title>
    <link rel="stylesheet" href="../CSS/contact.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="icon" type="image/png" href="../IMAGES/logo.png">
</head>

<body>

 <!-- NAVIGATION CONTENTS HERE / Dont change the contents under this section -->

<?php include "navbar.php"; ?>

 <!-- CONTACT US PAGE CONTENTS START HERE -->
   <section class="contact-hero">
        <h1>CONTACT US</h1>
    </section>


    <section class="contact">
        <div class="contact-container">

            <!-- LEFT: MAP -->
            <div class="contact-map">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2104.536616129194!2d120.96974517321758!3d14.670846936716497!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b43c1d92cd03%3A0x767f315e3a810013!2sBee%20Home%20Labor%20Service%20Cooperative!5e0!3m2!1sen!2sph!4v1770444765601!5m2!1sen!2sph" 
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div> 

        

            <!-- RIGHT: INFO -->
            <div class="contact-info">
                <h2>CONTACT US</h2>
                <h3>BEE HOME LABOR MULTIPURPOSE COOPERATIVE</h3>

                <p><strong>Location:</strong><br>
                Unit 203, 2ND Floor, MGC Veranda Building, 31, Gov. Pascual Avenue, Tinajeros, Malabon, Metro Manila</p>

                <p><strong>Message / Call Us</strong></p>
                <p>Facebook: Bee Home Labor Multipurpose Cooperative</p>
                <p>Gmail: info.bhscoop@gmail.com</p>
                <p>Contact No: 0917 588 1203</p>

                <img src="../IMAGES/logo.png" alt="Bee Home Logo" class="contact-logo">
            </div>

        </div>
    </section>


    <section class="faq">
        <h2>FREQUENTLY ASKED QUESTIONS:</h2>

        <div class="faq-item">
            <button class="faq-question">
                What is Bee Home Labor Multipurpose Cooperative?
                <span class="arrow">&#9654;</span>
            </button>
            <div class="faq-answer">
                <p>Bee Home Labor Multipurpose Cooperative is a manpower service provider that supplies trained and reliable workers to different industries..</p>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                What services does Bee Home offer?
                <span class="arrow">&#9654;</span>
            </button>
            <div class="faq-answer">
                <p>We offer Multipurpose services, transport, bills payment, remittance, and other cooperative services.</p>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                Where is your office located?
                <span class="arrow">&#9654;</span>
            </button>
            <div class="faq-answer">
                <p>We are located at Unit 203, 2nd Floor, MGC Veranda Building, 31, Gov. Pascual Avenue, Tinajeros, Malabon City.</p>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                How can I contact Bee Home?
                <span class="arrow">&#9654;</span>
            </button>
            <div class="faq-answer">
                <p>You may contact us through Facebook, Gmail, or call us at 0917 588 1203.</p>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question">
                Do you offer loan services?
                <span class="arrow">&#9654;</span>
            </button>
            <div class="faq-answer">
                <p>Yes, we offer various loan programs exclusively for members only.</p>
            </div>
        </div>

    </section>




     <?php include "footer.php"; ?>



<script>
    function toggleMenu() {
        document.getElementById("navLinks").classList.toggle("active");
    }


   const faqItems = document.querySelectorAll(".faq-item");

    faqItems.forEach(item => {
        const button = item.querySelector(".faq-question");

        button.addEventListener("click", () => {
            item.classList.toggle("active");
        });
    });

</script>

</body>
</html>
