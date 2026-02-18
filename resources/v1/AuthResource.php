<?php
require_once '../config/database.php';
require_once '../models/ApiUser.php';
require_once '../models/ApiToken.php';

class AuthResource
{
    private $db;
    private $user;
    private $token;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new ApiUser($this->db);
        $this->token = new ApiToken($this->db);
    }

    // POST /api/v1/login
    public function login()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->email) && !empty($data->password)) {
            if ($this->user->findByEmail($data->email)) {
                if ($this->user->verifyPassword($data->password)) {
                    if ($this->token->create($this->user->id)) {
                        http_response_code(200);
                        echo json_encode(array(
                            "message" => "Login exitoso",
                            "token" => $this->token->token,
                            "expires_at" => $this->token->expires_at,
                            "user" => array(
                                "id" => $this->user->id,
                                "username" => $this->user->username,
                                "email" => $this->user->email
                            )
                        ));
                    } else {
                        http_response_code(503);
                        echo json_encode(array("message" => "Error al generar token"));
                    }
                } else {
                    http_response_code(401);
                    echo json_encode(array("message" => "Password incorrecto"));
                }
            } else {
                http_response_code(401);
                echo json_encode(array("message" => "Usuario no encontrado o inactivo"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Email y password requeridos"));
        }
    }

    // POST /api/v1/logout
    public function logout()
    {
        header("Content-Type: application/json");

        $headers = getallheaders();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

        if (!empty($token)) {
            if ($this->token->revoke($token)) {
                http_response_code(200);
                echo json_encode(array("message" => "Logout exitoso"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Error al cerrar sesión"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Token requerido"));
        }
    }

    // POST /api/v1/register
    public function register()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->username) && !empty($data->email) && !empty($data->password)) {
            $this->user->username = $data->username;
            $this->user->email = $data->email;
            $this->user->password_hash = $data->password;

            if ($this->user->create()) {
                http_response_code(201);
                echo json_encode(array(
                    "message" => "Usuario registrado exitosamente",
                    "user" => array(
                        "id" => $this->user->id,
                        "username" => $this->user->username,
                        "email" => $this->user->email
                    )
                ));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo registrar el usuario"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Username, email y password requeridos"));
        }
    }
}
?>