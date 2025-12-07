<?php
// Minimal landing page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BatStateU Complaint System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css">
    <link rel="stylesheet" href="css/landing.css">
</head>
<body>
    <header class="site-header">
        <div class="logo-wrap">
            <img src="media/bsulogo.png" alt="BatStateU" class="logo">
            <span class="brand-title">Student Complaint System</span>
        </div>
        <nav class="nav">
            <a class="nav-link" href="login.php">Login</a>
        </nav>
    </header>
    
    <main class="hero">
        <div class="hero-inner">
            <div class="hero-grid">
                <div class="hero-left">
                    <h3 class="subtitle" style="color: black;">Report issues, monitor progress, and help keep our campus safe and orderly.</h3>

                    <div class="info-card">
                        <!-- Centered cards carousel (Swiper.js - cards effect) -->
                        <div class="cards-wrapper">
                            <div class="swiper my-swiper cards-effect">
                                <div class="swiper-wrapper">
                                    <div class="swiper-slide">
                                        <div class="slide-card">
                                            <h4 class="info-title">University Vision</h4>
                                            <p class="info-text">A premier national university that develops leaders in the global knowledge economy.</p>
                                        </div>
                                    </div>

                                    <div class="swiper-slide">
                                        <div class="slide-card">
                                            <h4 class="info-title">University Mission</h4>
                                            <p class="info-text">A university committed to producing leaders by providing a 21st century learning environment through innovations in education, multidisciplinary research, and community and industry partnerships in order to nurture the spirit of nationhood, propel the national economy, and engage the world for sustainable development.</p>
                                        </div>
                                    </div>

                                    <div class="swiper-slide">
                                        <div class="slide-card">
                                            <h4 class="info-title">Core Values</h4>
                                            <ul class="core-values">
                                                <li>Patriotism</li>
                                                <li>Service</li>
                                                <li>Integrity</li>
                                                <li>Resilience</li>
                                                <li>Excellence</li>
                                                <li>Faith</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- pagination + navigation -->
                                <div class="swiper-pagination"></div>
                                <div class="swiper-button-prev" aria-label="Previous slide"></div>
                                <div class="swiper-button-next" aria-label="Next slide"></div>
                            </div>
                        </div>
                    </div>

                    <p class="note">Batangas State University — JPLPC Malvar Campus</p>
                </div>

                <div class="hero-right">
                    <div class="image-bubble" role="img" aria-label="campus image"></div>
                </div>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <small>&copy; <?php echo date('Y'); ?> BatStateU — Student Complaint System</small>
    </footer>

    <script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const swiper = new Swiper('.my-swiper', {
                effect: 'cards',
                grabCursor: true,
                centeredSlides: true,
                slidesPerView: 1,
                spaceBetween: 0,
                pagination: { el: '.swiper-pagination', clickable: true },
                navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
                cardsEffect: { slideShadows: false },
                // enable touch + mouse drag by default
            });
        });
    </script>
</body>
</html>
