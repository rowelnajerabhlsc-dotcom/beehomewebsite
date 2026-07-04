<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// fallback if not set
$videoPath = $videoPath ?? "../VIDEO/default.mp4";
$videoFile = basename($videoPath);
?>

<section class="property-video-section">
    <div class="video-container">
        <video controls>
            <source src="<?php echo $videoPath; ?>" type="video/mp4">
        </video>
    </div>
</section>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] == 4): ?>
<section class="upload-section">
    <div class="upload-container">
        <h3>Change Video</h3>

        <form action="upload_video.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="video" accept="video/mp4" required>
            <input type="hidden" name="target" value="<?php echo $videoFile; ?>">
            <button type="submit">Upload New Video</button>
        </form>
    </div>
</section>
<?php endif; ?>