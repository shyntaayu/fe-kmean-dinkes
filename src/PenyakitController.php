<?php
class PenyakitController
{
    public function __construct(private PenyakitGateway $gateway)
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
        $penyakit = $this->gateway->get($id);
        if (!$penyakit) {
            http_response_code(404);
            echo json_encode(["message" => "Penyakit not found"]);
            return;
        }
        switch ($method) {
            case "GET":
                echo json_encode($penyakit);
                break;
            case "POST":
                $data = $_POST;
                $errors = $this->getValidationErrors($data, false);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                $rows = $this->gateway->update($penyakit, $_POST);
                echo json_encode(["message" => "Penyakit $id updated", "rows" => $rows]);
                break;
            case "DELETE":
                $rows = $this->gateway->delete($id);
                echo json_encode(["message" => "Penyakit $id deleted", "rows" => $rows]);
                break;
            default:
                http_response_code(405);
                header("Allow: GET, POST,  DELETE");
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
                $data = $_POST;
                $errors = $this->getValidationErrors($data);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                $id = $this->gateway->create($_POST);
                http_response_code(201);
                echo json_encode(["message" => "Penyakit created successfully", "id" => $id]);
                break;

            default:
                http_response_code(405);
                header("Allow:GET, POST");
        }
    }

    private function getValidationErrors(array $data, bool $is_new = true): array
    {
        $errors = [];
        if ($is_new && empty($data["penyakit_name"])) {
            $errors[] = "penyakit_name is required";
        }

        if (array_key_exists("size", $data)) {
            if (filter_var($data["size"], FILTER_VALIDATE_INT) === false) {
                $errors[] = "penyakit_size must be an integer";
            }
        }
        return $errors;
    }
}
