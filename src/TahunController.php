<?php
class TahunController
{
    public function __construct(private TahunGateway $gateway)
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
        $tahun = $this->gateway->get($id);
        if (!$tahun) {
            http_response_code(404);
            echo json_encode(["message" => "Tahun not found"]);
            return;
        }
        switch ($method) {
            case "GET":
                echo json_encode($tahun);
                break;
            case "POST":
                $data = $_POST;
                $errors = $this->getValidationErrors($data, false);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                $rows = $this->gateway->update($tahun, $_POST);
                echo json_encode(["message" => "Tahun $id updated", "rows" => $rows]);
                break;
            case "DELETE":
                $rows = $this->gateway->delete($id);
                echo json_encode(["message" => "Tahun $id deleted", "rows" => $rows]);
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
                echo json_encode(["message" => "Tahun created successfully", "id" => $id]);
                break;

            default:
                http_response_code(405);
                header("Allow:GET, POST");
        }
    }

    private function getValidationErrors(array $data, bool $is_new = true): array
    {
        $errors = [];
        if ($is_new && empty($data["tahun_name"])) {
            $errors[] = "tahun_name is required";
        }

        if (array_key_exists("size", $data)) {
            if (filter_var($data["size"], FILTER_VALIDATE_INT) === false) {
                $errors[] = "tahun_size must be an integer";
            }
        }
        return $errors;
    }
}
