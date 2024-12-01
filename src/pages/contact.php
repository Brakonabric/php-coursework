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
                        <i class="fas fa-phone"></i>
                        <div>
                            <h3>Tālrunis</h3>
                            <p>+371 2X XXX XXX (Vispārīga informācija)</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h3>E-pasts</h3>
                            <p>info@nonames.lv</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
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
                        <i class="fab fa-facebook"></i>
                        <span>Facebook</span>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-instagram"></i>
                        <span>Instagram</span>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-youtube"></i>
                        <span>YouTube</span>
                    </a>
                </div>
            </section>

            <section class="team-activities">
                <h2>Komandas piedāvājumi</h2>
                <div class="activities-content">
                    <div class="activity-item">
                        <i class="fas fa-futbol"></i>
                        <h3>Atvērtie treniņi faniem</h3>
                        <p>Pievienojieties mums īpašos treniņos kopā ar komandu</p>
                    </div>
                    
                    <div class="activity-item">
                        <i class="fas fa-star"></i>
                        <h3>Meistarklases</h3>
                        <p>Mācieties no mūsu labākajiem spēlētājiem</p>
                    </div>
                    
                    <div class="activity-item">
                        <i class="fas fa-campground"></i>
                        <h3>Sporta nometne</h3>
                        <p>Vasaras futbola nometne jauniešiem</p>
                    </div>
                    
                    <div class="activity-item">
                        <i class="fas fa-tshirt"></i>
                        <h3>Komandas merčs</h3>
                        <p>Oficiālā komandas atribūtika un suvenīri</p>
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

<style>
.contacts-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.contact-sections {
    display: grid;
    gap: 30px;
    margin-top: 20px;
}

.contact-info, .social-media, .working-hours, .map-section, .additional-info {
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

h1 {
    text-align: center;
    color: #333;
    margin-bottom: 30px;
    font-weight: bold;
}

h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.5em;
}

.contact-details {
    display: grid;
    gap: 20px;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.contact-item i {
    font-size: 24px;
    color: #1e88e5; /* Синий цвет для футбольной тематики */
    margin-top: 5px;
}

.contact-item h3 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.contact-item p {
    margin: 0;
    color: #666;
}

.social-links {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.social-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    background: #f8f9fa;
    border-radius: 5px;
    text-decoration: none;
    color: #333;
    transition: background-color 0.3s;
}

.social-link:hover {
    background: #e9ecef;
}

.social-link i {
    font-size: 24px;
}

.schedule {
    display: grid;
    gap: 10px;
}

.schedule-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.schedule-item:last-child {
    border-bottom: none;
}

.days {
    font-weight: bold;
    color: #2c3e50;
}

.hours {
    color: #666;
}

.info-content ul {
    list-style-type: none;
    padding: 0;
}

.info-content li {
    padding: 12px 0;
    border-bottom: 1px solid #eee;
    color: #666;
    position: relative;
    padding-left: 25px;
}

.info-content li:before {
    content: "⚽";
    position: absolute;
    left: 0;
    color: #1e88e5;
}

.info-content li:last-child {
    border-bottom: none;
}

.map-container {
    border-radius: 8px;
    overflow: hidden;
    margin-top: 15px;
}

@media (max-width: 768px) {
    .contact-sections {
        grid-template-columns: 1fr;
    }
    
    .social-links {
        justify-content: center;
    }
    
    .contact-item {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
}

@media (min-width: 769px) {
    .contact-sections {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .map-section, .additional-info {
        grid-column: 1 / -1;
    }
}

.team-activities {
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    grid-column: 1 / -1;
}

.activities-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.activity-item {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: transform 0.3s ease;
}

.activity-item:hover {
    transform: translateY(-5px);
}

.activity-item i {
    font-size: 2em;
    color: #1e88e5;
    margin-bottom: 15px;
}

.activity-item h3 {
    color: #2c3e50;
    margin-bottom: 10px;
    font-size: 1.2em;
}

.activity-item p {
    color: #666;
    margin: 0;
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .activities-content {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Подключаем Font Awesome для иконок -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<?php include '../includes/footer.php'; ?> 