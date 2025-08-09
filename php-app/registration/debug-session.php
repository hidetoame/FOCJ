<?php
session_start();

echo "<h1>Session Debug</h1>";
echo "<h2>Session ID: " . session_id() . "</h2>";

echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Form Data in Session:</h2>";
if (isset($_SESSION['form_data'])) {
    echo "<pre>";
    print_r($_SESSION['form_data']);
    echo "</pre>";
    
    echo "<h3>Specific Fields:</h3>";
    echo "address-type: " . ($_SESSION['form_data']['address-type'] ?? 'NOT SET') . "<br>";
    echo "car-model: " . ($_SESSION['form_data']['car-model'] ?? 'NOT SET') . "<br>";
    echo "car-year: " . ($_SESSION['form_data']['car-year'] ?? 'NOT SET') . "<br>";
} else {
    echo "<p>No form data in session</p>";
}

echo "<h2>POST Data:</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h2>Cookies:</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";
?>