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
        // $main = $this->gateway->get($id);
        // if (!$main) {
        //     http_response_code(404);
        //     echo json_encode(["message" => "Main not found"]);
        //     return;
        // }
        switch ($method) {
            case "GET":
                // var_dump($inputData);
                // $restructuredData = $this->getKMeans();
                // echo json_encode($restructuredData);
                break;
            case "POST":
                $data = $_POST;
                $json = file_get_contents("php://input");
                $restructuredData = $this->getKMeans($json);
                echo json_encode($restructuredData);
                // $errors = $this->getValidationErrors($data, false);

                // if (!empty($errors)) {
                //     http_response_code(422);
                //     echo json_encode(["errors" => $errors]);
                //     break;
                // }

                // $rows = $this->gateway->update($main, $_POST);
                // echo json_encode(["message" => "Main $id updated", "rows" => $rows]);
                break;
            case "DELETE":
                $rows = $this->gateway->delete($id);
                echo json_encode(["message" => "Main $id deleted", "rows" => $rows]);
                break;
            case "OPTIONS":
                break;
            default:
                http_response_code(405);
                header("Allow: GET, POST,DELETE, OPTIONS");
        }
    }
    private function processCollectionRequest(string $method, array $param): void
    {
        $daerahName = $_GET['daerah_name'] ?? null;
        $penyakitName = $_GET['penyakit_name'] ?? null;
        $tahun = $_GET['tahun'] ?? null;
        $pilihan = $_GET['pilihan'] ?? null;
        $baris = $_GET['baris'] ?? null;
        $kolom = $_GET['kolom'] ?? null;
        switch ($method) {
            case "GET":
                $data = $_GET;
                $errors = $this->getValidationErrors($data);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }
                $data = $this->gateway->getDataByParams($daerahName, $penyakitName, $tahun, $pilihan, $baris, $kolom);
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
            case "OPTIONS":
                break;
            default:
                http_response_code(405);
                header("Allow:GET, POST, DELETE, OPTIONS");
        }
    }

    private function getValidationErrors(array $data): array
    {
        $errors = [];
        if (empty($data["pilihan"])) {
            $errors[] = "pilihan is required";
        }

        if (empty($data["baris"])) {
            $errors[] = "baris is required";
        }
        if (empty($data["kolom"])) {
            $errors[] = "kolom is required";
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

    private function getKMeans($param)
    {
        $data = $this->objectToArrayPHP($param);

        if (is_array($data)) {
            // Data is an array, proceed with K-means calculation
            // Your K-means logic goes here
            $k = 3; // Number of clusters
            $result = $this->kmeans($data, $k);
            // Example: 
            // foreach ($data as $item) {
            //     echo "Daerah Name: " . $item['daerah_name'] . "\n";
            //     echo "2017: " . $item['2017'] . "\n";
            //     // Access other elements as needed
            // }
            $restructuredData = [];

            foreach ($result as $cluster => $clusterData) {
                foreach ($clusterData as $index => $row) {
                    $restructuredRow = $row;
                    $restructuredRow["cluster"] = (string) $cluster;
                    $restructuredData[] = $restructuredRow;
                }
            }

            return $restructuredData;
        } else {
            // Data is not an array, handle the error
            echo "Invalid JSON data";
        }
        // $k = 3; // Number of clusters
        // $result = $this->kmeans($data, $k);

        // Output the result


    }

    // Function to calculate the Euclidean distance between two points
    function euclideanDistance($point1, $point2)
    {
        $sum = 0;
        foreach ($point1 as $key => $value) {
            if ($key != 'daerah_name') {
                $sum += pow($point1[$key] - $point2[$key], 2);
            }
        }
        return sqrt($sum);
    }

    // Function to assign data points to clusters
    function assignToClusters($data, $centroids)
    {
        $clusters = [];
        foreach ($data as $point) {
            $minDistance = PHP_INT_MAX;
            $assignedCluster = null;
            foreach ($centroids as $cluster => $centroid) {
                $distance = $this->euclideanDistance($point, $centroid);
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $assignedCluster = $cluster;
                }
            }
            $clusters[$assignedCluster][] = $point;
        }
        return $clusters;
    }

    // Function to calculate new centroids
    function calculateCentroids($clusters)
    {
        $centroids = [];
        foreach ($clusters as $cluster => $points) {
            $centroid = array_fill_keys(array_keys($points[0]), 0);
            foreach ($points as $point) {
                foreach ($point as $key => $value) {
                    if ($key !== 'daerah_name') {
                        $centroid[$key] += $value;
                    }
                }
            }
            $count = count($points);
            $centroid = array_map(function ($value) use ($count) {
                return $value / $count;
            }, $centroid);
            $centroids[$cluster] = $centroid;
        }
        return $centroids;
    }

    // Function to perform K-means clustering
    function kmeans($data, $k, $maxIterations = 100)
    {
        // Initialize centroids randomly
        $centroids = array_slice($data, 0, $k);

        for ($i = 0; $i < $maxIterations; $i++) {
            $clusters = $this->assignToClusters($data, $centroids);
            $newCentroids = $this->calculateCentroids($clusters);

            // Check for convergence
            if ($centroids == $newCentroids) {
                break;
            }

            $centroids = $newCentroids;
        }

        return $clusters;
    }

    function objectToArrayPHP($param)
    {
        $data = json_decode($param, true);
        return $data;
    }
}
