<?php
require_once __DIR__ . '/DBConnection.php';
class Auth {
    public static function create_user(string $username, string $email, string $password) {
        $connection = DBConnection::get_connection();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
        $statement = $connection->prepare($sql);
        return $statement->execute([$username, $email, $hash]);
    }

    public static function login(string $username, string $password) : bool {
        $connection = DBConnection::get_connection();
        $sql = "SELECT * FROM users WHERE username = ?";
        $statement = $connection->prepare($sql);
        $statement->execute([$username]);
        $user = $statement->fetch();
        if (!$user) {
            return false;
        }
        if(password_verify($password, $user['password_hash'])) {
           $_SESSION['user'] = $user;
           return true;
        }
        return false;
    }
}
?>