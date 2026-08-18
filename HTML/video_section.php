<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// fallback if not set
$videoPath = $videoPath ?? "../VIDEO/default.mp4";
$videoFile = basename($videoPath);

// Optional per-page overrides (set these before including this file)
$videoHeading         = $videoHeading ?? null;   // e.g. "Ma-Bee-lis at Ligtas na Bee-yahe" (legacy bee-word heading)
$videoOverlayTitle    = $videoOverlayTitle ?? "Watch Our Story";
$videoOverlaySubtitle = $videoOverlaySubtitle ?? "Service Overview Video";
$videoTags            = $videoTags ?? ["\u{1F41D} Bee Home Cooperative"];
$videoPoster          = $videoPoster ?? null;    // optional photo path for the card background (e.g. "../IMAGES/canteen-team.jpg")
?>

<section class="property-video-section snap-section">

    <?php if ($videoHeading): ?>
        <div class="transport-container">
            <h2 class="transport-title"><?php echo $videoHeading; ?></h2>
        </div>
    <?php endif; ?>

    <div class="video-container">
        <div class="video-card <?php echo $videoPoster ? 'has-poster' : ''; ?>"
             data-video-card
             <?php if ($videoPoster): ?>style="background-image: linear-gradient(rgba(9,109,42,0.55), rgba(9,109,42,0.55)), url('<?php echo htmlspecialchars($videoPoster); ?>');"<?php endif; ?>>

            <?php if (!$videoPoster): ?>
                <div class="video-card-grid"></div>
            <?php endif; ?>

            <video data-video-el playsinline>
                <source src="<?php echo $videoPath; ?>" type="video/mp4">
            </video>

            <div class="video-card-overlay" data-video-overlay>
                <button class="play-btn" type="button" data-video-play aria-label="Play video">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#096D2B">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </button>
                <h3><?php echo htmlspecialchars($videoOverlayTitle); ?></h3>
                <p><?php echo htmlspecialchars($videoOverlaySubtitle); ?></p>
            </div>

            <div class="video-card-tags">
                <?php foreach ($videoTags as $tag): ?>
                    <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
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

<script>
(function () {
    document.querySelectorAll('[data-video-card]').forEach(function (card) {
        var video = card.querySelector('[data-video-el]');
        var playBtn = card.querySelector('[data-video-play]');
        if (!video || !playBtn) return;

        playBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            video.setAttribute('controls', '');
            video.play();
            card.classList.add('playing');
        });

        video.addEventListener('pause', function () {
            card.classList.remove('playing');
            video.removeAttribute('controls');
        });

        video.addEventListener('ended', function () {
            card.classList.remove('playing');
            video.removeAttribute('controls');
        });
    });
})();
</script>

<style>
/* VIDEO CARD (shared partial styling -- self-contained so any page can include video_section.php) */
.property-video-section {
    width: 100%;
}

.video-container {
    display: flex;
    justify-content: center;
    width: 100%;
}

.video-card {
    position: relative;
    width: 100%;
    max-width: 550px;
    aspect-ratio: 16 / 11.3;
    border-radius: 20px;
    overflow: hidden;
    background: linear-gradient(135deg, #0c8a36, #075021);
    background-size: cover;
    background-position: center;
    box-shadow: 0 20px 40px rgba(9, 109, 42, 0.25);
}

.video-card-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.08) 1px, transparent 1px);
    background-size: 48px 48px;
    pointer-events: none;
}

.video-card video {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    background: transparent;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
}

.video-card.playing video {
    opacity: 1;
    pointer-events: auto;
}

.video-card-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 20px;
    transition: opacity 0.25s ease;
}

.video-card.playing .video-card-overlay,
.video-card.playing .video-card-tags {
    opacity: 0;
    pointer-events: none;
}

.play-btn {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background-color: #FFD400;
    border: 4px solid rgba(255,255,255,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    margin-bottom: 18px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    transition: transform 0.2s ease;
}

.play-btn:hover {
    transform: scale(1.08);
}

.play-btn svg {
    margin-left: 3px;
}

.video-card-overlay h3 {
    color: #ffffff;
    font-size: 22px;
    font-weight: 800;
    margin-bottom: 4px;
}

.video-card-overlay p {
    color: #e3f5e8;
    font-size: 15px;
}

.video-card-tags {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-start;
    position: absolute;
    bottom: 20px;
    left: 20px;
    right: 20px;
    transition: opacity 0.25s ease;
}

.tag {
    background-color: rgba(0,0,0,0.35);
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    padding: 8px 16px;
    border-radius: 8px;
}

@media (max-width: 480px) {
    .video-card-tags {
        justify-content: center;
    }
}
</style>
