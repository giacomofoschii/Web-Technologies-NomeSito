<?php
class DatabaseHelper {
    public $db;

    public function __construct($servername, $username, $password, $dbname, $port) {
        $this->db = new mysqli($servername, $username, $password, $dbname, $port);
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
    }  //connect to the database

    /**
     * User CRUD
     */

    public function getUsersByUsername($username) {
        $query = "
            SELECT Username, Immagineprofilo, Nome, Cognome, Mail, DataDiNascita, codCensismento, 
                gruppoAppartenenza, password, scout, bio, fazzolettone, specialita, totem 
            FROM utente 
            WHERE username = ?
        "; 
        //get all the user's data by username
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function searchUser($input) {
        $query = "
            SELECT username, immagineProfilo, nome, cognome,
            FROM utente 
            WHERE username LIKE CONCAT(?, '%') 
            OR nome LIKE CONCAT(?, '%') 
            OR cognome LIKE CONCAT(?, '%')
        "; 
        //get username, immagineProfilo, nome, cognome of all the users that match the input

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("sss", $input, $input, $input);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getUsersByusername($username) {
        $query = "
            SELECT username, immagineProfilo
            FROM utente 
            WHERE username LIKE ?
        ";
       //get username and immagineProfilo of all the users that match the input

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /** Funzione che non so se andremo ad usare, MOMENTANEA*/
    public function getUsersFriendsByusername ($username) {
        $query = "
            SELECT u.username, u.immagineProfilo
            FROM seguire s INNER JOIN utente u ON s.usernameSeguito = u.username
            WHERE s.usernameSeguace = ?
        ";
        //search for the friends of a user by username

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getNotificationsByUsername($username) {
        $query = "
            SELECT *
            FROM Notifica
            WHERE username = ? AND letta = false
        ";
        //search for the notifications of a user by username

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getSeguitiByUsername($username) {
        $query = "
            SELECT u.usernameSeguito, u.immagineProfilo
            FROM seguire s INNER JOIN utente u ON s.usernameSeguito = u.username
            WHERE s.usernameSeguace = ?
        ";
        //search for the followed users of a user by username

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getSeguaciById($username) {
        $query = "
            SELECT u.usernameSeguace, u.immagineProfilo
            FROM seguire s INNER JOIN utente u ON s.usernameSeguace = u.username
            WHERE s.usernameSeguito = ?
        ";
        //search for the followers of a user by username

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function checkFollow($username, $usernameFollowed) {
        $query = "
            SELECT *
            FROM seguire
            WHERE username = ? AND username_seguito = ?
        ";
        //search for the followers of a user by username

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $username, $username_seguito);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function follow($username, $username_seguito) {
        $query = "
            INSERT INTO seguire (username, username_seguito)
            VALUES (?, ?)
        ";
        //follow an user by username

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $username, $username_seguito);
        $stmt->execute();
    }

    public function unfollow($username, $username_seguito) {
        $query = "
            DELETE FROM seguire
            WHERE username = ? AND username_seguito = ?
        ";

        //unfollow an user by username

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $username, $username_seguito);
        $stmt->execute();
    }

    public function updateUserWithoutImg($username, $email, $name, $surname) {
        $query = "
            UPDATE utente
            SET mail = ?, nome = ?, cognome = ?
            WHERE username = ?
        ";
        //update the user's data by username

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssss", $email, $name, $surname, $username);
        $stmt->execute();
    }

    /**
     * post CRUD
    */

    public function getPostById($idPost) {
        $query = "
        SELECT 
        u.username, u.immagineProfilo, u.nome, u.cognome, 
        p.idPost, p.data, p.testo, p.immagine,
        GROUP_CONCAT(DISTINCT h.nome ORDER BY h.nome ASC) AS hashtag_list,
        COUNT(DISTINCT l.username) AS num_like,
        COUNT(DISTINCT c.idCommento) AS num_commenti,
        GROUP_CONCAT(DISTINCT c.testo ORDER BY c.data ASC) AS commenti_list
        FROM 
            post p 
        INNER JOIN 
            utente u ON p.username = u.username 
        LEFT JOIN 
            like_table l ON p.idPost = l.idPost
        LEFT JOIN 
            commenti_table c ON p.idPost = c.idPost
        LEFT JOIN 
            post_hashtag ph ON p.idPost = ph.idPost
        LEFT JOIN 
            hashtag h ON ph.nome = h.nome
        WHERE 
            p.idPost = ?
        GROUP BY 
            u.username, u.immagineProfilo, u.nome, u.cognome, 
            p.idPost, p.data, p.testo, p.immagine;
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $idPost);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getPostByhashtag ($hashtagName) {
        $query = "
            SELECT u.username, u.imgProfilo, u.nome, u.cognome, p.idPost, p.data, p.testo, p.imgPost, p.like, p.commenti
            FROM post p INNER JOIN utente u ON p.username = u.username INNER JOIN hashtag h ON p.hashtag = h.idHashtag
            WHERE h.nome = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $hashtagName);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function insertPost($idPost, $image, $hashtag, $username, $date) {
        $query = "
            INSERT INTO post (idPost, imgPost, hastag, username, date)
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("issss", $idPost, $image, $hashtag, $username, $date);
        $stmt->execute();

        return $stmt->insert_id;
    }

    public function updatePostWhitImage($idPost, $text, $image) {
        $query = "
            UPDATE post
            SET testo = ?, imgPost = ?
            WHERE idPost = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssi", $text, $image, $idPost);
        $stmt->execute();

        return $stmt->execute();
    }

    public function updatePostWithoutImage($idPost, $text) {
        $query = "
            UPDATE post
            SET testo = ?
            WHERE idPost = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("si", $text, $idPost);
        $stmt->execute();

        return $stmt->execute();
    }

    public function deletePostImage($idPost) {
        $query = "
            UPDATE post
            SET immagine = NULL
            WHERE idPost = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $idPost);
        $stmt->execute();

        return $stmt->execute();
    }

    public function incrementCommentsById($idPost) {
        $query = "
            UPDATE post
            SET commenti = commenti + 1
            WHERE idPost = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $idPost);
        $stmt->execute();

        return $stmt->execute();
    }

    public function decrementCommentsById($idPost) {
        $query = "
            UPDATE post
            SET commenti = commenti - 1
            WHERE idPost = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $idPost);
        $stmt->execute();

        return $stmt->execute();
    }

    public function incrementLikesById($idPost) {
        $query = "
            UPDATE post
            SET like = like + 1
            WHERE idPost = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $idPost);
        $stmt->execute();

        return $stmt->execute();
    }

    public function decrementLikesById($idPost) {
        $query = "
            UPDATE post
            SET like = like - 1
            WHERE idPost = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $idPost);
        $stmt->execute();

        return $stmt->execute();
    }

    public function deletePostById($idPost) {
        $query = "
            DELETE FROM post
            WHERE idPost = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $idPost);
        $stmt->execute();
        var_dump($stmt->error); //is used for debugging purposes. It will output any error message from the last operation on $stmt.

        return true;
    }

    /**
     * Comments CRUD
     */

    public function getCommentsById($idPost) {
        $query = "
            SELECT u.username, u.imgProfilo, u.nome, u.cognome, c.idCommento, c.dataOra, c.testo
            FROM commento c INNER JOIN utente u ON c.username = u.username
            WHERE c.idPost = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $idPost);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function insertComment($idCommento, $text, $username, $idPost, $idNotifitication) {
        $query = "
            INSERT INTO commento (idCommento, testo, username, idPost, idNotifica)
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("issii", $idCommento, $text, $username, $idPost, $idNotification);
        $stmt->execute();

        return $stmt->insert_id;
    }

    /**
     * Likes CRUD
     */

    public function getLikesByPostId($idPost) {
        $query = "
            SELECT like
            FORM post
            WHERE idPost = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $idPost);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getLikesByUserAndPostId($username, $idPost) {
        $query = "
            SELECT *
            FROM like
            WHERE username = ? AND idPost = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("si", $username, $idPost);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function insertLike($idPost, $username) {
        $query = "
            INSERT INTO like (idPost, username)
            VALUES (?, ?)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("is", $idPost, $username);
        $stmt->execute();
        $result = array("username" => $username, "idPost" => $idPost);

        return $result;
    }

    public function removelike($idPost, $username) {
        $query = "
            DELETE FROM like
            WHERE idPost = ? AND username = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("is", $idPost, $username);
        $stmt->execute();

        return $stmt->execute();
    }

    /**
     * Notifications CRUD
     */

    public function insertNotification($testo, $idPost, $usernameReciver, $usernameSender) {
        $query = "
            INSERT INTO notifica (testo, idPost, usernameReciver, usernameSender)
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("siss", $testo, $idPost, $usernameReciver, $usernameSender);
        $stmt->execute();

        return $stmt->insert_id;
    }

    public function removeNotification($idNotifica) {
        $query = "
            DELETE FROM notifica
            WHERE idNotifica = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $idNotifica);
        $stmt->execute();

        return $stmt->execute();
    }

    /**
     * Login
     */

    public function checkLogin($username, $password) {
        $query = "
            SELECT *
            FROM utente
            WHERE username = ? AND password = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    //funzione che inserisce un tentativo di login
    public function insertLoginAttempt($username, $time){
        $query = "
                INSERT INTO tentativoLogin (username, time)
                VALUES (?, ?)
                ";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $username, $time);
        $stmt->execute();

        return $stmt->insert_id;
    }

    public function getLoginAttempt($username, $timeThd){
        $query = "
                SELECT time 
                FROM tentativoLogin 
                WHERE user_id = ? AND time > ?
                ";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ii', $userId, $timeThd);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Register
     */

    public function insertUser($username, $nome, $cognome, $dataNascita, $codCensimento, $gruppo, $email, $password, $scout, $bio, $fazzolettone, $specialita, $totem){
        $query = "
            INSERT INTO utente (username, nome, cognome, dataNascita, codCensimento, gruppo, email, password, scout, bio, fazzolettone, specialita, totem)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssssisssissss", $username, $nome, $cognome, $dataNascita, $codCensimento, $gruppo, $email, $password, $scout, $bio, $fazzolettone, $specialita, $totem);
        $stmt->execute();

        return $username;
    }

}
?>