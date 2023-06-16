<?php
class MainController
{
    public function __construct(private MainGateway $gateway)
    {
    }
    public function processRequest(string $method, ?string $id, ?array $param): void
    {
        if ($id) {
            $this->processResourceRequest($method, $id);
        } else {
            $this->processCollectionRequest($method, $param);
        }
    }

    private function processResourceRequest(string $method, string $id): void
    {
        $main = $this->gateway->get($id);
        // if (!$main) {
        //     http_response_code(404);
        //     echo json_encode(["message" => "Main not found"]);
        //     return;
        // }
        switch ($method) {
            case "GET":
                // echo json_encode($main);
                $this->trial_Main();
                break;
            case "POST":
                $data = $_POST;
                $errors = $this->getValidationErrors($data, false);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                $rows = $this->gateway->update($main, $_POST);
                echo json_encode(["message" => "Main $id updated", "rows" => $rows]);
                break;
            case "DELETE":
                $rows = $this->gateway->delete($id);
                echo json_encode(["message" => "Main $id deleted", "rows" => $rows]);
                break;
            default:
                http_response_code(405);
                header("Allow: GET, POST,  DELETE");
        }
    }
    private function processCollectionRequest(string $method, array $param): void
    {
        $daerahName = $_GET['daerah_name'] ?? null;
        $penyakitName = $_GET['penyakit_name'] ?? null;
        $tahun = $_GET['tahun'] ?? null;
        switch ($method) {
            case "GET":
                $data = $this->gateway->getDataByParams($daerahName, $penyakitName, $tahun);
                echo json_encode($data);
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

            default:
                http_response_code(405);
                header("Allow:GET, POST");
        }
    }

    private function getValidationErrors(array $data, bool $is_new = true): array
    {
        $errors = [];
        if ($is_new && empty($data["main_name"])) {
            $errors[] = "main_name is required";
        }

        if (array_key_exists("size", $data)) {
            if (filter_var($data["size"], FILTER_VALIDATE_INT) === false) {
                $errors[] = "main_size must be an integer";
            }
        }
        return $errors;
    }

    private function trial_Main()
    {
        $data = [
            [2, 10],
            [2, 5],
            [8, 4],
            [5, 8],
            [7, 5],
            [6, 4],
            [1, 2],
            [4, 9]
        ];

        $k = 2; // Number of clusters
        $maxIterations = 100;

        $kmeans = new KMeans($k, $maxIterations);
        $clusters = $kmeans->fit($data);

        // Output the resulting clusters
        foreach ($clusters as $clusterIndex => $cluster) {
            echo "Cluster " . ($clusterIndex + 1) . ": ";
            foreach ($cluster as $point) {
                echo "[" . implode(", ", $point) . "] ";
            }
            echo PHP_EOL;
        }
    }
}
