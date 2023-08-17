<?php
class UserController
{
    public function __construct(private UserGateway $gateway)
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

        switch ($method) {
            case "GET":
                // echo json_encode($user);
                break;
            case "POST":
                if ($id == "login") {
                    $json = file_get_contents("php://input");
                    $data = $this->objectToArrayPHP($json);
                    $result = $this->gateway->loginUser($data["username"], $data["password"]);
                    if ($result["result"]) {
                        http_response_code(200);
                        echo json_encode(array('success' => $result["result"], 'message' => 'Login successful!', 'result' => $result["data"]));
                    } else {
                        http_response_code(500); // Internal Server Error
                        echo json_encode(array('success' => $result["result"], 'message' => 'Invalid username or password.', 'result' => $result["data"]));
                    }
                    // echo json_encode($mapping);
                } else if ($id == "createone") {
                    $json = file_get_contents("php://input");
                    $data = $this->objectToArrayPHP($json);
                    $result = $this->gateway->createOne($data);

                    if ($result) {
                        http_response_code(201); // Created
                        echo json_encode(["message" => "User created successfully", "id" => $result]);
                    } else {
                        http_response_code(500); // Internal Server Error
                        echo json_encode(array('message' => 'Error created user.'));
                    }
                } else {
                    $user = $this->gateway->get($id);
                    if (!$user) {
                        http_response_code(404);
                        echo json_encode(["message" => "User not found"]);
                        return;
                    } else {
                        $json = file_get_contents("php://input");
                        $data = $this->objectToArrayPHP($json);
                        $errors = $this->getValidationErrors($data, false);

                        if (!empty($errors)) {
                            http_response_code(422);
                            echo json_encode(["errors" => $errors]);
                            break;
                        }
                        $rows = $this->gateway->update($user, $data);
                        if ($rows) {
                            http_response_code(200);
                            echo json_encode(["message" => "User $id updated", "rows" => $rows]);
                        }
                    }
                }
                break;
            case "DELETE":
                $rows = $this->gateway->delete($id);
                if ($rows) {
                    http_response_code(200);
                    echo json_encode(["message" => "User $id deleted", "rows" => $rows]);
                }
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
                // Check if the file was uploaded successfully
                if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $filePath = $_FILES['file']['tmp_name'];

                    // Process the Excel file
                    $result = $this->gateway->create($_POST, $filePath);

                    if ($result["result"]) {
                        http_response_code(201); // Created
                        echo json_encode(array('message' => 'Data created successfully.', 'data' => $result['insertedData']));
                    } else {
                        http_response_code(500); // Internal Server Error
                        echo json_encode(array('message' => 'Error processing the Excel file.', 'error' => $result['message']));
                    }
                } else {
                    http_response_code(400); // Bad Request
                    echo json_encode(array('message' => 'File upload failed.'));
                }
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
        if ($is_new && empty($data["user_name"])) {
            $errors[] = "user_name is required";
        }

        if (array_key_exists("size", $data)) {
            if (filter_var($data["size"], FILTER_VALIDATE_INT) === false) {
                $errors[] = "user_size must be an integer";
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
