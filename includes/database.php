<?php

<<<<<<< HEAD

=======
>>>>>>> origin/develop
try {
    $pdo = new PDO("mysql:host=localhost;dbname=e_commerce_sami", "root");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>