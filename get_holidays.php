<?php
header('Content-Type: application/json');

// A static list of 2025 Philippine holidays.
// In a real application, you might fetch this from a database or a third-party API.
$holidays_2025 = [
    ["date" => "2025-01-01", "name" => "New Year's Day"],
    ["date" => "2025-01-29", "name" => "Chinese New Year"],
    ["date" => "2025-02-25", "name" => "EDSA People Power Revolution Anniversary"],
    ["date" => "2025-03-31", "name" => "Eid'l Fitr (End of Ramadan)"],
    ["date" => "2025-04-09", "name" => "Araw ng Kagitingan"],
    ["date" => "2025-04-17", "name" => "Maundy Thursday"],
    ["date" => "2025-04-18", "name" => "Good Friday"],
    ["date" => "2025-04-19", "name" => "Black Saturday"],
    ["date" => "2025-05-01", "name" => "Labor Day"],
    ["date" => "2025-06-07", "name" => "Eid'l Adha (Feast of Sacrifice)"],
    ["date" => "2025-06-12", "name" => "Independence Day"],
    ["date" => "2025-08-21", "name" => "Ninoy Aquino Day"],
    ["date" => "2025-08-25", "name" => "National Heroes Day"],
    ["date" => "2025-11-01", "name" => "All Saints' Day"],
    ["date" => "2025-11-02", "name" => "All Souls' Day"],
    ["date" => "2025-11-30", "name" => "Bonifacio Day"],
    ["date" => "2025-12-08", "name" => "Feast of the Immaculate Conception"],
    ["date" => "2025-12-24", "name" => "Christmas Eve"],
    ["date" => "2025-12-25", "name" => "Christmas Day"],
    ["date" => "2025-12-30", "name" => "Rizal Day"],
    ["date" => "2025-12-31", "name" => "New Year's Eve"]
];

echo json_encode(['success' => true, 'data' => $holidays_2025]);
?>