<?php
require_once '../config/database.php';
require_once '../models/ApiToken.php';

class TokenResource
{
    private $db;
    private $token;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->token = new ApiToken($this->db);
    }

    // GET /api/v1/tokens
    public function index()
    {
        header("Content-Type: application/json");

        $query = "SELECT t.id, t.user_id, t.token, t.expires_at, t.revoked, t.created_at,
                        u.username, u.email, u.status
                 FROM api_tokens t
                 JOIN api_users u ON t.user_id = u.id
                 ORDER BY t.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $num = $stmt->rowCount();

        if ($num > 0) {
            $tokens_arr = array();
            $tokens_arr["records"] = array();
            $tokens_arr["total"] = $num;

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $token_item = array(
                    "id" => $id,
                    "user_id" => $user_id,
                    "username" => $username,
                    "email" => $email,
                    "token" => $token,
                    "expires_at" => $expires_at,
                    "revoked" => $revoked,
                    "created_at" => $created_at,
                    "status" => $status
                );
                array_push($tokens_arr["records"], $token_item);
            }

            http_response_code(200);
            echo json_encode($tokens_arr);
        } else {
            http_response_code(200);
            echo json_encode(array(
                "records" => array(),
                "total" => 0,
                "message" => "No se encontraron tokens"
            ));
        }
    }

    // GET /api/v1/tokens/{id}
    public function show($id)
    {
        header("Content-Type: application/json");

        $query = "SELECT t.id, t.user_id, t.token, t.expires_at, t.revoked, t.created_at,
                        u.username, u.email, u.status
                 FROM api_tokens t
                 JOIN api_users u ON t.user_id = u.id
                 WHERE t.id = :id
                 LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            extract($row);
            $token_arr = array(
                "id" => $id,
                "user_id" => $user_id,
                "username" => $username,
                "email" => $email,
                "token" => $token,
                "expires_at" => $expires_at,
                "revoked" => $revoked,
                "created_at" => $created_at,
                "status" => $status
            );

            http_response_code(200);
            echo json_encode($token_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Token no encontrado"));
        }
    }

    // GET /api/v1/tokens/active
    public function active()
    {
        header("Content-Type: application/json");

        $query = "SELECT t.id, t.user_id, t.token, t.expires_at, t.created_at,
                        u.username, u.email, u.status
                 FROM api_tokens t
                 JOIN api_users u ON t.user_id = u.id
                 WHERE t.revoked = FALSE AND t.expires_at > NOW()
                 ORDER BY t.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        $num = $stmt->rowCount();

        if ($num > 0) {
            $tokens_arr = array();
            $tokens_arr["records"] = array();
            $tokens_arr["total"] = $num;

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $token_item = array(
                    "id" => $id,
                    "user_id" => $user_id,
                    "username" => $username,
                    "email" => $email,
                    "token" => $token,
                    "expires_at" => $expires_at,
                    "created_at" => $created_at,
                    "status" => $status
                );
                array_push($tokens_arr["records"], $token_item);
            }

            http_response_code(200);
            echo json_encode($tokens_arr);
        } else {
            http_response_code(200);
            echo json_encode(array(
                "records" => array(),
                "total" => 0,
                "message" => "No se encontraron tokens activos"
            ));
        }
    }

    // GET /api/v1/tokens/user/{user_id}
    public function byUser($user_id)
    {
        header("Content-Type: application/json");

        $query = "SELECT t.id, t.token, t.expires_at, t.revoked, t.created_at
                 FROM api_tokens t
                 WHERE t.user_id = :user_id
                 ORDER BY t.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $num = $stmt->rowCount();

        if ($num > 0) {
            $tokens_arr = array();
            $tokens_arr["records"] = array();
            $tokens_arr["total"] = $num;
            $tokens_arr["user_id"] = $user_id;

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $token_item = array(
                    "id" => $id,
                    "token" => $token,
                    "expires_at" => $expires_at,
                    "revoked" => $revoked,
                    "created_at" => $created_at
                );
                array_push($tokens_arr["records"], $token_item);
            }

            http_response_code(200);
            echo json_encode($tokens_arr);
        } else {
            http_response_code(200);
            echo json_encode(array(
                "records" => array(),
                "total" => 0,
                "user_id" => $user_id,
                "message" => "No se encontraron tokens para este usuario"
            ));
        }
    }
}
?>
