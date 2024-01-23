<?php 
    require_once("../db_config.php");

    $idPost = $_POST["idPost"];

    //controllo se l'utente loggato abbia o meno messo mi piace al post
    $liked = $dbh->getLikesByUserAndPostId($_SESSION["username"], $idPost);

    if(empty($liked)){
        $result["isLiked"] = false;
    } else {
        $result["isLiked"] = true;
    }

    header('Content-Type: application/json');
    echo json_encode($result);
?>