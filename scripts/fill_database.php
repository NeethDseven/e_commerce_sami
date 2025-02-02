<?php
require_once 'vendor/autoload.php';

$faker = Faker\Factory::create();
$mysqli = new mysqli("localhost", "root", "", "e_commerce_sami");

// Insert categories
for ($i = 0; $i < 10; $i++) {
    $nom = $faker->word;
    $ordre = $i + 1;
    $mysqli->query("INSERT INTO categorie (nom, ordre) VALUES ('$nom', '$ordre')");
}

// Insert articles
for ($i = 0; $i < 100; $i++) {
    $nom = $faker->word;
    $description = $faker->sentence;
    $image = $faker->imageUrl;
    $prix = $faker->randomFloat(2, 1, 1000);
    $stock = $faker->numberBetween(0, 100);
    $id_categorie = $faker->numberBetween(1, 10);
    $mysqli->query("INSERT INTO article (nom, description, image, prix, stock, id_categorie) VALUES ('$nom', '$description', '$image', '$prix', '$stock', '$id_categorie')");
}

// Insert users
for ($i = 0; $i < 50; $i++) {
    $nom = $faker->lastName;
    $prenom = $faker->firstName;
    $email = $faker->email;
    $mot_de_passe = password_hash($faker->password, PASSWORD_BCRYPT);
    $adresse = $faker->address;
    $carte = $faker->creditCardNumber;
    $role = $faker->randomElement(['utilisateur', 'admin']);
    $mysqli->query("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, adresse, carte, role) VALUES ('$nom', '$prenom', '$email', '$mot_de_passe', '$adresse', '$carte', '$role')");
}

// Insert orders
for ($i = 0; $i < 200; $i++) {
    $id_utilisateur = $faker->numberBetween(1, 50);
    $date_commande = $faker->dateTimeThisYear->format('Y-m-d');
    $statut = $faker->randomElement(['en cours', 'expédié', 'livré']);
    $est_invite = $faker->boolean;
    $nom_livraison = $faker->lastName;
    $prenom_livraison = $faker->firstName;
    $email_livraison = $faker->email;
    $adresse_livraison = $faker->address;
    $mysqli->query("INSERT INTO commande (id_utilisateur, date_commande, statut, est_invite, nom_livraison, prenom_livraison, email_livraison, adresse_livraison) VALUES ('$id_utilisateur', '$date_commande', '$statut', '$est_invite', '$nom_livraison', '$prenom_livraison', '$email_livraison', '$adresse_livraison')");
}

// Insert order details
for ($i = 0; $i < 500; $i++) {
    $id_commande = $faker->numberBetween(1, 200);
    $id_article = $faker->numberBetween(1, 100);
    $quantite = $faker->numberBetween(1, 10);
    $prix_unitaire = $faker->randomFloat(2, 1, 1000);
    $mysqli->query("INSERT INTO detail_commande (id_commande, id_article, quantite, prix_unitaire) VALUES ('$id_commande', '$id_article', '$quantite', '$prix_unitaire')");
}

// Insert promotions for articles
for ($i = 0; $i < 200; $i++) {
    $id_article = $i + 1;
    $prix_promotionnel = $faker->randomFloat(2, 5, 500);
    $date_debut = $faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d');
    $date_fin = $faker->dateTimeBetween('now', '+1 month')->format('Y-m-d');
    $reduction_percent = $faker->randomFloat(2, 5, 50);
    $mysqli->query("INSERT INTO promotion (id_article, prix_promotionnel, date_debut, date_fin, reduction_percent) VALUES ('$id_article', '$prix_promotionnel', '$date_debut', '$date_fin', '$reduction_percent')");
}

$mysqli->close();
?>