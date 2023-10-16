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
        //     echo json_encode(["message" => "Main tidak ditemukan"]);
        //     return;
        // }
        switch ($method) {
            case "GET":
                // var_dump($inputData);
                // $json = file_get_contents("php://input");
                // $data = $this->objectToArrayPHP($json);
                // $restructuredData = $this->prosesPrediksi($data);
                // echo json_encode($restructuredData);
                // break;
            case "POST":
                if ($id == "clustering") {
                    $data = $_POST;
                    $json = file_get_contents("php://input");
                    $restructuredData = $this->getKMeans($json);
                    $mapping = $this->mappingColor($restructuredData["data"]);
                    $res = [
                        'iterations' => $restructuredData['iterations'],
                        'data' => $mapping
                    ];
                    echo json_encode($res);
                } else if ($id == "prediksi") {
                    $json = file_get_contents("php://input");
                    $data = $this->objectToArrayPHP($json);
                    $restructuredData = $this->prosesPrediksi($data["data"], $data["tahunAwal"], $data["tahunAkhir"]);
                    echo json_encode($restructuredData);
                } else if ($id == "createsingle") {
                    $json = file_get_contents("php://input");
                    $data = $this->objectToArrayPHP($json);
                    $result = $this->gateway->createSingle($data);
                    if ($result["result"]) {
                        http_response_code(201); // Created
                        echo json_encode(array('message' => 'Data berhasil dibuat.', 'data' => $result['insertedData']));
                    } else {
                        http_response_code(500); // Internal Server Error
                        echo json_encode(array('message' => 'Kesalahan membuat data.', 'error' => $result['message']));
                    }
                    break;
                } else if ($id == "createmulti") {
                    $json = file_get_contents("php://input");
                    $data = $this->objectToArrayPHP($json);
                    $result = $this->gateway->createMulti($data);
                    if ($result["result"]) {
                        http_response_code(201); // Created
                        echo json_encode(array('message' => 'Data berhasil dibuat.', 'data' => $result['insertedData']));
                    } else {
                        http_response_code(500); // Internal Server Error
                        echo json_encode(array('message' => 'Kesalahan membuat data.', 'error' => $result['message']));
                    }
                    break;
                }
                // $errors = $this->getValidationErrors($data, false);

                // if (!empty($errors)) {
                //     http_response_code(422);
                //     echo json_encode(["errors" => $errors]);
                //     break;
                // }

                // $rows = $this->gateway->update($main, $_POST);
                // echo json_encode(["message" => "Main $id diperbarui", "rows" => $rows]);
                break;
            case "DELETE":
                $rows = $this->gateway->delete($id);
                echo json_encode(["message" => "Main $id terhapus", "rows" => $rows]);
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
                        echo json_encode(array('message' => 'Data berhasil dibuat.', 'data' => $result['insertedData']));
                    } else {
                        http_response_code(500); // Internal Server Error
                        echo json_encode(array('message' => 'Kesalahan saat memproses file Excel.', 'error' => $result['message']));
                    }
                } else {
                    http_response_code(400); // Bad Request
                    echo json_encode(array('message' => 'Pengunggahan file gagal.'));
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
            $errors[] = "pilihan diperlukan";
        }

        if (empty($data["baris"])) {
            $errors[] = "baris diperlukan";
        }
        if (empty($data["kolom"])) {
            $errors[] = "kolom diperlukan";
        }
        return $errors;
    }

    private function getKMeans($param)
    {
        $data = $this->objectToArrayPHP($param);
        // var_dump($data);
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
            foreach ($result["clusters"] as $cluster => $clusterData) {
                foreach ($clusterData as $index => $row) {
                    $restructuredRow = $row;
                    $restructuredRow["cluster"] = (string) $cluster;
                    $restructuredData[] = $restructuredRow;
                }
            }
            return [
                'iterations' => $result['iterations'],
                'data' => $restructuredData
            ];
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
            // var_dump($point1);
            // var_dump($point2);
            // var_dump($key);
            if ($key != 'daerah_name' && $key != 'penyakit_name') {
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
            $assignedCluster = 0;
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
                    if ($key !== 'daerah_name' && $key !== 'penyakit_name') {
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
        // Langkah 1: Inisialisasi centroid secara acak
        // $centroids = array();
        // shuffle($data); // Acak urutan data
        // for ($i = 0; $i < $k; $i++) {
        //     $centroids[] = $data[$i];
        // }
        // $centroids = array_slice($data, 0, $k);
        // // $out = array_values($centroids);

        // // var_dump(json_encode($out));
        // // $arJson = json_encode($centroids, true);
        // // var_dump($arJson[0]);

        // for ($i = 0; $i < $maxIterations; $i++) {
        //     $clusters = $this->assignToClusters($data, $centroids);
        //     $newCentroids = $this->calculateCentroids($clusters);

        //     // Check for convergence
        //     if ($centroids == $newCentroids) {
        //         break;
        //     }

        //     $centroids = $newCentroids;
        // }

        // Jumlah cluster (k)
        $k = 3;

        // Langkah 1: Inisialisasi centroid secara acak
        $centroids = array();
        shuffle($data); // Acak urutan data
        for ($i = 0; $i < $k; $i++) {
            $centroids[] = $data[$i];
        }
        $convergenceThreshold = 0.0001;
        // Langkah iterasi
        $iterations = 0;
        do {
            // Langkah 2: Mengelompokkan titik data ke centroid terdekat
            $clusters = array_fill(0, $k, array());

            foreach ($data as $point) {
                $minDistance = PHP_INT_MAX;
                $closestCentroid = 0;

                foreach ($centroids as $key => $centroid) {
                    $distance = $this->euclideanDistance($point, $centroid);

                    if ($distance < $minDistance) {
                        $minDistance = $distance;
                        $closestCentroid = $key;
                    }
                }

                $clusters[$closestCentroid][] = $point;
            }

            // Langkah 3: Menghitung ulang centroid untuk setiap cluster
            $maxShift = 0;
            foreach ($clusters as $key => $cluster) {
                $clusterSize = count($cluster);
                if ($clusterSize > 0)
                    $newCentroid = $cluster[0];

                if ($clusterSize > 1) {
                    for ($i = 1; $i < $clusterSize; $i++) {
                        foreach ($newCentroid as $attribute => $value) {
                            if ($attribute !== 'daerah_name' && $attribute !== "penyakit_name") {
                                $newCentroid[$attribute] += $cluster[$i][$attribute];
                            }
                        }
                    }

                    foreach ($newCentroid as $attribute => $value) {
                        if ($attribute !== 'daerah_name' && $attribute !== "penyakit_name") {
                            $newCentroid[$attribute] /= $clusterSize;
                        }
                    }
                }

                // Hitung perubahan posisi centroid
                $shift = $this->euclideanDistance($centroids[$key], $newCentroid);
                $maxShift = max($maxShift, $shift);
                // var_dump($maxShift);
                // var_dump($convergenceThreshold);
                $centroids[$key] = $newCentroid;
            }

            $iterations++;

            // Ulangi iterasi sampai perubahan maksimum posisi centroid kurang dari ambang batas
        } while ($maxShift > $convergenceThreshold);

        // Lanjutkan langkah-langkah di atas untuk beberapa iterasi sampai konvergensi

        // Hasil akhir
        return [
            'iterations' => $iterations,
            'clusters' => $clusters
        ];
    }

    function objectToArrayPHP($param)
    {
        $data = json_decode($param, true);
        return $data;
    }

    function linearRegression($x, $y)
    {
        $n = count($x);
        $xSum = array_sum($x);
        $ySum = array_sum($y);
        $xySum = 0;
        $xSquaredSum = 0;
        for ($i = 0; $i < $n; $i++) {
            if ($x[$i] != 'daerah_name') {
                $xySum += $x[$i] * $y[$i];
                $xSquaredSum += $x[$i] * $x[$i];
            }
        }

        $slope = ($n * $xySum - $xSum * $ySum) / ($n * $xSquaredSum - $xSum * $xSum);
        $intercept = ($ySum - $slope * $xSum) / $n;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
        ];
    }

    function prosesPrediksi($data, $tahunAwal, $tahunAkhir)
    {
        // Predict values for 2022, 2023,dan 2024 menggunakan simple moving average
        $yearsToPredict = range($tahunAwal, $tahunAkhir);
        $period = 5;

        foreach ($data as &$item) {
            $values = array_slice($item, 0, -1);
            $values = array_values($values);
            $predictedValues = [];

            foreach ($yearsToPredict as $year) {
                $start = max(0, count($values) - $period);
                $end = count($values);
                $sum = array_sum(array_slice($values, $start, $end));
                $average = $sum / $period;

                $predictedValues[$year] = $average;
                $item[$year] = (int)$average;
                $values[] = $average;
            }

            $item = $item + $predictedValues;
        }
        return $data;
    }

    function mappingColor($data)
    {
        $clusterLevels = [];
        // var_dump($data);
        // Iterate through each data point
        $val1 = $this->findElementsByCluster($data, '0');
        $val2 = $this->findElementsByCluster($data, '1');
        $val3 = $this->findElementsByCluster($data, '2');
        // var_dump($val1);
        // var_dump($val2);
        // var_dump($val3);
        $all = [$val1, $val2, $val3];
        // var_dump($all);
        $conclusion = $this->calculateStats($all);
        // echo json_encode($all);
        foreach ($data as $point) {
            $cluster = $point['cluster'];
            $values = array_slice($point, 0, 5); // Get the values without "daerah_name" and "cluster"

            // Calculate the average value
            $averageValue = array_sum($values) / count($values);
            // var_dump($averageValue);
            // var_dump($cluster);

            // Assign cluster level based on average value and threshold
            if ($cluster == $conclusion["clusterMin"]) {
                $clusterLevel = 'rendah';
            } elseif ($cluster == $conclusion["clusterMax"]) {
                $clusterLevel = 'tinggi';
            } else {
                $clusterLevel = 'sedang';
            }


            // Add the cluster level to the result
            $point['clusterLevel'] = $clusterLevel;
            // $clusterLevels[$cluster][] = $point;
            $clusterLevels[] = $point;
        }

        return $clusterLevels;
    }

    function findElementsByCluster($data, $cluster)
    {
        $result = [];
        $averageValue = 0;
        foreach ($data as $item) {
            if ($item['cluster'] == $cluster) {
                $result[] = $item;
                $values = array_slice($item, 0, 5); // Get the values without "daerah_name" and "cluster"

                // Calculate the average value
                $averageValue = array_sum($values) / count($values);
            }
        }

        return [
            'cluster' => $cluster,
            'avg' => $averageValue,
        ];
    }

    function calculateStats($data)
    {
        $result = [];

        // Menginisialisasi nilai awal untuk min dan max
        $min = PHP_INT_MAX;
        $max = PHP_INT_MIN;
        $clusterMin = null;
        $clusterMax = null;

        foreach ($data as $item) {
            // Menghitung nilai min
            if ($item['avg'] < $min) {
                $min = $item['avg'];
                $clusterMin = $item['cluster'];
            }

            // Menghitung nilai max
            if ($item['avg'] > $max) {
                $max = $item['avg'];
                $clusterMax = $item['cluster'];
            }
        }

        // Menghitung nilai rata-rata
        $avg = array_sum(array_column($data, 'avg')) / count($data);

        $result['avg'] = $avg;
        $result['min'] = $min;
        $result['max'] = $max;
        $result["clusterMin"] = $clusterMin;
        $result["clusterMax"] = $clusterMax;

        return $result;
    }
}
