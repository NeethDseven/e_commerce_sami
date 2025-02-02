<?php

function connect(PDO $pdo, string $username, string $pass)
{
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $query = "SELECT * FROM utilisateur WHERE (nom = :username OR email = :username)";
    $prep = $pdo->prepare($query);
    $prep->bindValue(':username', $username, PDO::PARAM_STR);
    try
    {
        $prep->execute();
        $res = $prep->fetch();
        $prep->closeCursor();
        return $res;
    }
    catch (PDOException $e)
    {
        // Log the error message or handle it as needed
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}