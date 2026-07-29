<section class="cah-section">
    <div class="cah-hex-field" aria-hidden="true">
        <span class="cah-hex h1"></span>
        <span class="cah-hex h2"></span>
        <span class="cah-hex h3"></span>
        <span class="cah-hex h4"></span>
    </div>

    <div class="cah-inner">
        <div class="cah-eyebrow">Consumer Assistance</div>
        <h2 class="cah-headline">May concern ka ba?<br>Kausapin ang Bee Home.</h2>
        <p class="cah-sub">
            Whether it's a complaint, a request, or just a question — our Consumer
            Assistance Team (CAT) is ready to listen. Tell us what's going on and
            we'll open a case, assign it a reference number, and get back to you.
        </p>

        <div class="cah-steps">
            <div class="cah-step">
                <div class="cah-hex-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="cah-step-label">Step 1</div>
                <h3>Sabihin sa amin</h3>
                <p>Fill out one short form — complaint, request, or inquiry, whichever fits.</p>
            </div>

            <div class="cah-step-connector" aria-hidden="true"></div>

            <div class="cah-step">
                <div class="cah-hex-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12l2 2 4-4M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="cah-step-label">Step 2</div>
                <h3>Nabubuksan ang case mo</h3>
                <p>"We'll assign a reference number to your concern so our team can monitor its progress."</p>
            </div>

            <div class="cah-step-connector" aria-hidden="true"></div>

            <div class="cah-step">
                <div class="cah-hex-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="cah-step-label">Step 3</div>
                <h3>Sasagutin ka namin</h3>
                <p>Our team reviews, investigates if needed, and sends you a formal response.</p>
            </div>
        </div>

        <div class="cah-cta-row">
            <a href="https://forms.gle/ZCajUttLfEE4V1Qd8" target="_blank" rel="noopener" class="cah-cta">
                File a Complaint, Request, or Inquiry
            </a>
            <span class="cah-cta-note">Opens the Consumer Assistance form in a new tab</span>
        </div>
    </div>
</section>

<style>
.cah-section {
    position: relative;
    overflow: hidden;
    background: linear-gradient(180deg, #ffffff 0%, #f9f9f9 100%);
    padding: 72px 20px;
    font-family: Arial, sans-serif;
    color: #1c2b21;
}

.cah-inner {
    position: relative;
    z-index: 2;
    max-width: 980px;
    margin: 0 auto;
    text-align: center;
}

/* ---------- Decorative honeycomb field ---------- */
.cah-hex-field {
    position: absolute;
    inset: 0;
    z-index: 1;
    pointer-events: none;
}

.cah-hex {
    position: absolute;
    width: 140px;
    height: 121px;
    background: rgba(9, 109, 43, 0.05);
    clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
}
.cah-hex.h1 { top: -30px; left: -40px; }
.cah-hex.h2 { top: 60px; right: -50px; width: 180px; height: 156px; background: rgba(44, 171, 74, 0.06); }
.cah-hex.h3 { bottom: -50px; left: 8%; width: 110px; height: 95px; }
.cah-hex.h4 { bottom: -20px; right: 12%; width: 90px; height: 78px; background: rgba(9, 109, 43, 0.04); }

/* ---------- Header ---------- */
.cah-eyebrow {
    font-family: "Fredoka", "Trebuchet MS", sans-serif;
    font-weight: 600;
    font-size: 0.8em;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #26a753;
    margin-bottom: 14px;
}

.cah-headline {
    font-family: "Fredoka", "Trebuchet MS", sans-serif;
    font-weight: 700;
    font-size: clamp(1.8em, 4vw, 2.6em);
    line-height: 1.2;
    color: #096D2B;
    margin: 0 0 18px;
}

.cah-sub {
    max-width: 620px;
    margin: 0 auto 48px;
    font-size: 1.05em;
    font-weight: 500;
    line-height: 1.6;
    color: #4a5c50;
}

/* ---------- Steps ---------- */
.cah-steps {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    gap: 4px;
    margin-bottom: 48px;
    flex-wrap: wrap;
}

.cah-step {
    flex: 1 1 220px;
    max-width: 260px;
    padding: 8px 14px;
}

.cah-step-connector {
    flex: 0 0 40px;
    height: 2px;
    background: repeating-linear-gradient(90deg, rgba(9,109,43,0.25) 0 6px, transparent 6px 12px);
    margin-top: 44px;
}

.cah-hex-icon {
    width: 68px;
    height: 59px;
    margin: 0 auto 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #096D2B;
    color: #fff;
    clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
    box-shadow: 0 4px 15px rgba(9, 109, 43, 0.25);
    transition: transform 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.cah-step:hover .cah-hex-icon {
    transform: translateY(-4px) scale(1.06);
    background: #26a753;
}
.cah-hex-icon svg { width: 26px; height: 26px; }

.cah-step-label {
    font-family: "Fredoka", "Trebuchet MS", sans-serif;
    font-size: 0.72em;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #2cab4a;
    margin-bottom: 6px;
}

.cah-step h3 {
    font-family: "Fredoka", "Trebuchet MS", sans-serif;
    font-size: 1.1em;
    font-weight: 700;
    color: #096D2B;
    margin: 0 0 8px;
}

.cah-step p {
    font-size: 0.9em;
    line-height: 1.5;
    color: #5a6b60;
    margin: 0;
}

/* ---------- CTA ---------- */
.cah-cta-row {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.cah-cta {
    display: inline-block;
    background: #096D2B;
    color: #fff;
    text-decoration: none;
    font-family: "Fredoka", "Trebuchet MS", sans-serif;
    font-weight: 600;
    font-size: 1.02em;
    padding: 16px 38px;
    border-radius: 50px;
    box-shadow: 0 4px 15px rgba(9, 109, 43, 0.3);
    transition: background 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275),
                transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.cah-cta:hover {
    background: #26a753;
    transform: translateY(-2px);
}
.cah-cta:focus-visible {
    outline: 3px solid #2cab4a;
    outline-offset: 3px;
}

.cah-cta-note {
    font-size: 0.8em;
    color: #8a9a90;
}

/* ---------- Responsive ---------- */
@media (max-width: 720px) {
    .cah-steps {
        flex-direction: column;
        align-items: center;
        gap: 28px;
    }
    .cah-step-connector { display: none; }
    .cah-step { max-width: 320px; }
}

@media (prefers-reduced-motion: reduce) {
    .cah-hex-icon,
    .cah-cta {
        transition: none;
    }
}
</style>
