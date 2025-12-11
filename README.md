# BA-3104 GROUP 4 - ParkEase
ParkEase – Parking Reservation System

A web-based parking reservation system with user and admin functionality. Users can reserve, preview, download receipts, and cancel reservations. Admins can view, delete, reservations.

Prerequisites

PHP & XAMPP

Node.js & npm

Modern browser (Chrome, Edge, Firefox)

Backend Setup (PHP/XAMPP)

Move backend folder to your htdocs folder in XAMPP:

C:\xampp\htdocs\ParkEase\BA-3104\backend

Set up database:

CREATE DATABASE parking_db;

CREATE TABLE reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fullName VARCHAR(255),
  studentId VARCHAR(50),
  vPlate VARCHAR(50),
  vType VARCHAR(50),
  rDate DATE,
  startTime TIME,
  endTime TIME,
  spot VARCHAR(20)
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255),
  fullname VARCHAR(255),
  studentId VARCHAR(50),
  password VARCHAR(255),
  token VARCHAR(32),
  verified TINYINT(1)
);


Edit db.php:

<?php
session_start();
  private $host = "localhost";
  private $user = "root";
  private $pass = "";
  private $db = "parking_db"; // heere
  private $port = 3307; // configure port else delete this
  
?>


Start XAMPP and start Apache & MySQL

Access backend endpoints via:

http://localhost/ParkEase/BA-3104/backend/public/<endpoint>

example -> http://localhost/ParkEase/BA-3104/backend/public/delete-reservations -> route

Frontend Setup 

Navigate to the frontend folder:

cd ParkEase/BA-3104/frontend


Install dependencies 
npm install


Start a local development server:

npm run dev


Access frontend in browser:

http://localhost:5173  (Vite default)

and also make sure xampp is runnning
