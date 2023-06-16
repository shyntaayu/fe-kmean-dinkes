<?php
class KMeans
{
    private $k; // Number of clusters
    private $maxIterations; // Maximum number of iterations
    private $data; // Input data
    private $centroids; // Cluster centroids

    public function __construct($k, $maxIterations)
    {
        $this->k = $k;
        $this->maxIterations = $maxIterations;
    }

    public function fit($data)
    {
        $this->data = $data;

        // Initialize centroids randomly
        $this->initializeCentroids();

        // Run the K-means algorithm
        $iteration = 0;
        do {
            $clusters = $this->assignDataToClusters();
            $this->updateCentroids($clusters);
            $iteration++;
        } while ($iteration < $this->maxIterations && $this->hasCentroidsChanged());

        return $clusters;
    }

    private function initializeCentroids()
    {
        $dataCount = count($this->data);
        $randomIndices = array_rand($this->data, $this->k);
        $this->centroids = [];
        foreach ($randomIndices as $index) {
            $this->centroids[] = $this->data[$index];
        }
    }

    private function assignDataToClusters()
    {
        $clusters = array_fill(0, $this->k, []);
        foreach ($this->data as $point) {
            $minDistance = INF;
            $closestCluster = null;
            for ($i = 0; $i < $this->k; $i++) {
                $distance = $this->calculateDistance($point, $this->centroids[$i]);
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $closestCluster = $i;
                }
            }
            $clusters[$closestCluster][] = $point;
        }
        return $clusters;
    }
    private function updateCentroids($clusters)
    {
        for ($i = 0; $i < $this->k; $i++) {
            if (!empty($clusters[$i])) {
                $centroid = array_reduce($clusters[$i], function ($carry, $point) {
                    return array_map(function ($a, $b) {
                        return $a + $b;
                    }, $carry, $point);
                }, array_fill(0, count($clusters[$i][0]), 0));
                $centroid = array_map(function ($value) use ($clusters, $i) {
                    return $value / count($clusters[$i]);
                }, $centroid);
                $this->centroids[$i] = $centroid;
            }
        }
    }

    private function calculateDistance($point1, $point2)
    {
        $sumSquaredDistances = 0;
        $dimensions = count($point1);
        for ($i = 0; $i < $dimensions; $i++) {
            $sumSquaredDistances += pow($point1[$i] - $point2[$i], 2);
        }
        return sqrt($sumSquaredDistances);
    }
    private function hasCentroidsChanged()
    { // Clone centroids to compare with updated centroids 
        $previousCentroids = $this->centroids;

        // Update centroids
        // ...

        return $previousCentroids !== $this->centroids;
    }
}
