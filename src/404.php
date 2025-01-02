<?php
$page_title = 'Lapa nav atrasta';
include 'includes/header.php';
?>
<style>
    body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        margin: 0;
    }
    main {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--primary-bg-color);
    }
    footer {
        margin-top: auto;
    }
    .error-container {
        background: linear-gradient(135deg, var(--section-a-bg-color) 0%, var(--section-b-bg-color) 100%);
        padding: var(--spacing-xl);
        text-align: center;
        width: var(--container-width);
        max-width: var(--container-max-width);
        box-shadow: var(--shadow-large);
    }
    .error-title {
        font-size: var(--font-size-huge);
        font-weight: var(--font-weight-bold);
        color: var(--primary-light);
        margin-bottom: var(--spacing-lg);
        text-shadow: var(--text-shadow);
    }
    .error-subtitle {
        font-size: var(--font-size-xxl);
        color: var(--primary-light);
        margin-bottom: var(--spacing-md);
        text-shadow: var(--text-shadow);
    }
    .error-text {
        font-size: var(--font-size-lg);
        color: var(--primary-light);
        margin-bottom: var(--spacing-xl);
    }
    .error-button {
        display: inline-flex;
        align-items: center;
        gap: var(--spacing-sm);
        font-size: var(--font-size-md);
        font-weight: var(--font-weight-bold);
        padding: var(--spacing-sm) var(--spacing-xl);
        border-radius: var(--border-radius-pill);
        background-color: var(--primary-hover);
        color: var(--primary-dark);
        text-decoration: none;
        transition: var(--transition-base);
    }
    .error-button:hover {
        background-color: var(--primary-dark);
        color: var(--primary-hover);
        transform: translateY(-5px);
        box-shadow: var(--shadow-large);
    }
</style>
<main>
    <div class="error-container">
        <div class="error-title">404</div>
        <h2 class="error-subtitle">Lapa nav atrasta</h2>
        <p class="error-text">Diemžēl meklētā lapa neeksistē vai ir pārvietota.</p>
        <a href="/index.php" class="error-button">
            <span class="material-icons">home</span>
            Atpakaļ uz sākumlapu
        </a>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
