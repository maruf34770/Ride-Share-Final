<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include 'config.php';

if (!isset($_GET['driver_id'])) {
    echo json_encode(["error" => "driver_id required"]);
    exit();
}

$driver_id = (int)$_GET['driver_id'];

$sql = "SELECT 
            driver_id,
            COUNT(rating_id)  AS total_ratings,
            ROUND(AVG(rating), 2) AS avg_rating,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS five_star,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS four_star,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS three_star,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) AS two_star,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) AS one_star
        FROM ratings
        WHERE driver_id = $driver_id
        GROUP BY driver_id";

$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo json_encode([
        "driver_id"     => $driver_id,
        "total_ratings" => 0,
        "avg_rating"    => 0,
        "five_star"     => 0,
        "four_star"     => 0,
        "three_star"    => 0,
        "two_star"      => 0,
        "one_star"      => 0
    ]);
} else {
    $row = $result->fetch_assoc();
    echo json_encode($row);
}
?>