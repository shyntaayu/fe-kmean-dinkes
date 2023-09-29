<?php
class RoleController
{
    public function __construct(private RoleGateway $gateway)
    {
    }
    public function processRequest(string $method, ?string $id): void
    {
        if ($id) {
            $this->processResourceRequest($method, $id);
        } else {
            $this->processCollectionRequest($method);
        }
    }

    private function processResourceRequest(string $method, string $id): void
    {
        $role = $this->gateway->get($id);
        if (!$role) {
            http_response_code(404);
            echo json_encode(["message" => "Role tidak ditemukan"]);
            return;
        }
        switch ($method) {
            case "GET":
                echo json_encode($role);
                break;
            case "POST":
                $json = file_get_contents("php://input");
                $data = $this->objectToArrayPHP($json);
                $errors = $this->getValidationErrors($data, false);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                $rows = $this->gateway->update($role, $data);
                echo json_encode(["message" => "Role $id diperbarui", "rows" => $rows]);
                break;
            case "DELETE":
                $rows = $this->gateway->delete($id);
                echo json_encode(["message" => "Role $id terhapus", "rows" => $rows]);
                break;
            case "OPTIONS":
                break;
            default:
                http_response_code(405);
                header("Allow: GET, POST,  DELETE, OPTIONS");
        }
    }
    private function processCollectionRequest(string $method): void
    {
        switch ($method) {
            case "GET":
                echo json_encode($this->gateway->getAll());
                break;
            case "POST":
                // $data = (array) json_decode($_POST, true);
                $json = file_get_contents("php://input");
                $data = $this->objectToArrayPHP($json);
                $errors = $this->getValidationErrors($data);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                $id = $this->gateway->create($data);
                http_response_code(201); // Created
                echo json_encode(["message" => "Role berhasil dibuat", "id" => $id]);
                break;
            case "OPTIONS":
                break;
            default:
                http_response_code(405);
                header("Allow:GET, POST, OPTIONS");
        }
    }

    private function getValidationErrors(array $data, bool $is_new = true): array
    {
        $errors = [];
        if ($is_new && empty($data["role_name"])) {
            $errors[] = "role_name diperlukan";
        }

        if (array_key_exists("size", $data)) {
            if (filter_var($data["size"], FILTER_VALIDATE_INT) === false) {
                $errors[] = "role_size harus berupa integer";
            }
        }
        return $errors;
    }

    function objectToArrayPHP($param)
    {
        $data = json_decode($param, true);
        return $data;
    }
}
