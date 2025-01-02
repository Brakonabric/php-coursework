<?php
ob_start();
$page_title = 'Kontakti';
include '../includes/header.php';
?>

<main>
    <div class="contacts-container">
        <h1>FK "Nonames" Kontakti</h1>
        
        <div class="contact-sections">
            <section class="contact-info">
                <h2>Kā ar mums sazināties</h2>
                <div class="contact-details">
                    <div class="contact-item">
                        <span class="material-icons">phone</span>
                        <div>
                            <h3>Tālrunis</h3>
                            <p>+371 2X XXX XXX (Vispārīga informācija)</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <span class="material-icons">email</span>
                        <div>
                            <h3>E-pasts</h3>
                            <p>info@nonames.lv</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <span class="material-icons">location_on</span>
                        <div>
                            <h3>Adrese</h3>
                            <p>Augšiela 1, Rīga, LV-1009</p>
                            <p>Daugavas stadions</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="social-media">
                <h2>Sociālie tīkli</h2>
                <div class="social-links">
                    <a href="#" class="social-link">
                        <span class="material-icons">facebook</span>
                        <span>Facebook</span>
                    </a>
                    <a href="#" class="social-link">
                        <span class="material-icons">photo_camera</span>
                        <span>Instagram</span>
                    </a>
                    <a href="#" class="social-link">
                        <span class="material-icons">play_circle</span>
                        <span>YouTube</span>
                    </a>
                </div>
            </section>

            <section class="team-activities">
                <h2>Komandas piedāvājumi</h2>
                <div class="activities-content">
                    <div class="activity-item">
                        <span class="material-icons">sports_soccer</span>
                        <h3>Meistarklases</h3>
                        <p>Individuālas nodarbības ar profesionāliem treneriem</p>
                    </div>
                    
                    <div class="activity-item">
                        <span class="material-icons">groups</span>
                        <h3>Atvērtie treniņi</h3>
                        <p>Īpaši treniņi faniem kopā ar komandu</p>
                    </div>
                    
                    <div class="activity-item">
                        <span class="material-icons">wb_sunny</span>
                        <h3>Vasaras nometne</h3>
                        <p>Intensīvas treniņnometnes jauniešiem vasaras sezonā</p>
                    </div>
                </div>
            </section>

            <section class="map-section">
                <h2>Kā mūs atrast</h2>
                <div class="map-container">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1087.4776025454283!2d24.158077100000003!3d56.9555442!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x46eece38dc89230d%3A0xbf238846772eb979!2sDaugava%20Stadium!5e0!3m2!1sen!2slv!4v1708804716044!5m2!1sen!2slv"
                        width="100%" 
                        height="450" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </section>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?> 