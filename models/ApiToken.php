<?php
class ApiToken
{
    private $conn;
    private $table_name = "api_tokens";

    public $id;
    public $user_id;
    public $token;
    public $expires_at;
    public $revoked;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($user_id)
    {
        $this->token = bin2hex(random_bytes(32));
        $this->expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $this->user_id = $user_id;

        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, token=:token, expires_at=:expires_at";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":token", $this->token);
        $stmt->bindParam(":expires_at", $this->expires_at);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function findActiveByUser($user_id)
    {
        $query = "SELECT id, token, expires_at, created_at 
                  FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND revoked = FALSE AND expires_at > NOW() 
                  ORDER BY created_at DESC 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->token = $row['token'];
            $this->expires_at = $row['expires_at'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    public function findByToken($token)
    {
        $query = "SELECT t.*, u.username, u.email, u.status 
                  FROM " . $this->table_name . " t
                  JOIN api_users u ON t.user_id = u.id
                  WHERE t.token = :token AND t.revoked = FALSE AND t.expires_at > NOW() 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function revoke($token)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET revoked = TRUE 
                  WHERE token = :token";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);

        return $stmt->execute();
    }
}
?>
