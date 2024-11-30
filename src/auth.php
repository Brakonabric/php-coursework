<?php
session_start();
session_unset(); // Удаляет все переменные сессии
session_destroy(); // Уничтожает сессию

header('Location: /index.php'); // Перенаправляет на главную страницу
exit();
