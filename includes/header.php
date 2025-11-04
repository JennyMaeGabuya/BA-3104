<?php
    // random number to alter version number of css files every refresh
    $randNum = rand(1, 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/mainStyles.css?v=<?= $randNum ?>">

    <!-- load css file dynamically -->
    <?= "<link rel='stylesheet' href='css/{$page}Styles.css?v={$randNum}'>" ?>
</head>
<body>
