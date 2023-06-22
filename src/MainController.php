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
                // echo json_encode($main);
                $inputData = $this->getKMeans();
                // var_dump($inputData);
                $restructuredData = [];

                foreach ($inputData as $cluster => $clusterData) {
                    foreach ($clusterData as $index => $row) {
                        $restructuredRow = $row;
                        $restructuredRow["cluster"] = (string) $cluster;
                        $restructuredData[] = $restructuredRow;
                    }
                }
                echo json_encode($restructuredData);
                break;
            case "POST":
                $data = $_POST;
                $errors = $this->getValidationErrors($data, false);

                if (!empty($errors)) {
                    http_response_code(422);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                // $rows = $this->gateway->update($main, $_POST);
                // echo json_encode(["message" => "Main $id updated", "rows" => $rows]);
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

            default:
                http_response_code(405);
                header("Allow:GET, POST");
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

    private function getKMeans()
    {
        $data = $this->objectToArrayPHP([]);

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
        } else {
            // Data is not an array, handle the error
            echo "Invalid JSON data";
        }
        // $k = 3; // Number of clusters
        // $result = $this->kmeans($data, $k);

        // Output the result
        return $result;
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

        $jsonData = '[
        {
            "2017": 214,
            "2018": 611,
            "2019": 362,
            "2020": 288,
            "2021": 212,
            "daerah_name": "KAB. PACITAN"
        },
        {
            "2017": 977,
            "2018": 1065,
            "2019": 1158,
            "2020": 904,
            "2021": 687,
            "daerah_name": "KAB. PONOROGO"
        },
        {
            "2017": 401,
            "2018": 465,
            "2019": 552,
            "2020": 402,
            "2021": 239,
            "daerah_name": "KAB. TRENGGALEK"
        },
        {
            "2017": 1043,
            "2018": 1188,
            "2019": 1236,
            "2020": 821,
            "2021": 716,
            "daerah_name": "KAB. TULUNGAGUNG"
        },
        {
            "2017": 462,
            "2018": 695,
            "2019": 853,
            "2020": 641,
            "2021": 485,
            "daerah_name": "KAB. BLITAR"
        },
        {
            "2017": 1735,
            "2018": 1709,
            "2019": 1874,
            "2020": 1542,
            "2021": 1262,
            "daerah_name": "KAB. KEDIRI"
        },
        {
            "2017": 2160,
            "2018": 2362,
            "2019": 2783,
            "2020": 1828,
            "2021": 1762,
            "daerah_name": "KAB. MALANG"
        },
        {
            "2017": 1270,
            "2018": 1258,
            "2019": 1926,
            "2020": 1129,
            "2021": 1222,
            "daerah_name": "KAB. LUMAJANG"
        },
        {
            "2017": 1516,
            "2018": 3397,
            "2019": 4208,
            "2020": 3047,
            "2021": 3028,
            "daerah_name": "KAB. JEMBER"
        },
        {
            "2017": 2016,
            "2018": 2216,
            "2019": 2635,
            "2020": 2005,
            "2021": 1892,
            "daerah_name": "KAB. BANYUWANGI"
        },
        {
            "2017": 1089,
            "2018": 1144,
            "2019": 1483,
            "2020": 892,
            "2021": 934,
            "daerah_name": "KAB. BONDOWOSO"
        },
        {
            "2017": 1210,
            "2018": 1270,
            "2019": 1249,
            "2020": 977,
            "2021": 914,
            "daerah_name": "KAB. SITUBONDO"
        },
        {
            "2017": 1371,
            "2018": 999,
            "2019": 1682,
            "2020": 1165,
            "2021": 1193,
            "daerah_name": "KAB. PROBOLINGGO"
        },
        {
            "2017": 2393,
            "2018": 2735,
            "2019": 3181,
            "2020": 1770,
            "2021": 1708,
            "daerah_name": "KAB. PASURUAN"
        },
        {
            "2017": 2092,
            "2018": 3127,
            "2019": 3540,
            "2020": 2520,
            "2021": 2713,
            "daerah_name": "KAB. SIDOARJO"
        },
        {
            "2017": 1164,
            "2018": 1464,
            "2019": 1558,
            "2020": 1080,
            "2021": 1046,
            "daerah_name": "KAB. MOJOKERTO"
        },
        {
            "2017": 1089,
            "2018": 1522,
            "2019": 1677,
            "2020": 1288,
            "2021": 1244,
            "daerah_name": "KAB. JOMBANG"
        },
        {
            "2017": 431,
            "2018": 564,
            "2019": 1068,
            "2020": 719,
            "2021": 682,
            "daerah_name": "KAB. NGANJUK"
        },
        {
            "2017": 1084,
            "2018": 1103,
            "2019": 1242,
            "2020": 590,
            "2021": 567,
            "daerah_name": "KAB. MADIUN"
        },
        {
            "2017": 535,
            "2018": 555,
            "2019": 766,
            "2020": 472,
            "2021": 447,
            "daerah_name": "KAB. MAGETAN"
        },
        {
            "2017": 835,
            "2018": 811,
            "2019": 1046,
            "2020": 755,
            "2021": 660,
            "daerah_name": "KAB. NGAWI"
        },
        {
            "2017": 1785,
            "2018": 1785,
            "2019": 1852,
            "2020": 1431,
            "2021": 1225,
            "daerah_name": "KAB. BOJONEGORO"
        },
        {
            "2017": 1219,
            "2018": 1227,
            "2019": 2000,
            "2020": 1286,
            "2021": 1260,
            "daerah_name": "KAB. TUBAN"
        },
        {
            "2017": 2377,
            "2018": 2072,
            "2019": 2270,
            "2020": 1495,
            "2021": 1632,
            "daerah_name": "KAB. LAMONGAN"
        },
        {
            "2017": 2115,
            "2018": 2305,
            "2019": 2505,
            "2020": 1463,
            "2021": 1771,
            "daerah_name": "KAB. GRESIK"
        },
        {
            "2017": 1556,
            "2018": 1250,
            "2019": 1445,
            "2020": 999,
            "2021": 998,
            "daerah_name": "KAB. BANGKALAN"
        },
        {
            "2017": 1153,
            "2018": 1028,
            "2019": 1111,
            "2020": 818,
            "2021": 917,
            "daerah_name": "KAB. SAMPANG"
        },
        {
            "2017": 1043,
            "2018": 827,
            "2019": 1101,
            "2020": 729,
            "2021": 801,
            "daerah_name": "KAB. PAMEKASAN"
        },
        {
            "2017": 1057,
            "2018": 1657,
            "2019": 1865,
            "2020": 1612,
            "2021": 1536,
            "daerah_name": "KAB. SUMENEP"
        },
        {
            "2017": 301,
            "2018": 820,
            "2019": 867,
            "2020": 578,
            "2021": 661,
            "daerah_name": "KOTA KEDIRI"
        },
        {
            "2017": 271,
            "2018": 263,
            "2019": 276,
            "2020": 238,
            "2021": 161,
            "daerah_name": "KOTA BLITAR"
        },
        {
            "2017": 1783,
            "2018": 1818,
            "2019": 2218,
            "2020": 1377,
            "2021": 1342,
            "daerah_name": "KOTA MALANG"
        },
        {
            "2017": 436,
            "2018": 692,
            "2019": 786,
            "2020": 348,
            "2021": 359,
            "daerah_name": "KOTA PROBOLINGGO"
        },
        {
            "2017": 499,
            "2018": 569,
            "2019": 581,
            "2020": 521,
            "2021": 584,
            "daerah_name": "KOTA PASURUAN"
        },
        {
            "2017": 497,
            "2018": 391,
            "2019": 455,
            "2020": 330,
            "2021": 482,
            "daerah_name": "KOTA MOJOKERTO"
        },
        {
            "2017": 634,
            "2018": 692,
            "2019": 709,
            "2020": 526,
            "2021": 447,
            "daerah_name": "KOTA MADIUN"
        },
        {
            "2017": 6338,
            "2018": 7007,
            "2019": 7950,
            "2020": 4151,
            "2021": 4631,
            "daerah_name": "KOTA SURABAYA"
        },
        {
            "2017": 32,
            "2018": 200,
            "2019": 241,
            "2020": 185,
            "2021": 140,
            "daerah_name": "KOTA BATU"
        }
    ]';

        // Convert JSON to array of objects
        $data = json_decode($jsonData, true);

        return $data;
        // // Convert array of objects to array
        // $result = [];

        // foreach ($data as $item) {
        //     $result[] = (array) $item;
        // }

        // // Print the result
        // return $result;
        // Output the new JSON data
        // return $newJsonData;
    }
}
