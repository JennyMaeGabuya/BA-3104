<?php
// Set your desired password here
$password = 'admin123'; 
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo $hashed_password;
?>

To add a user in the database table use this temporary file password hasher,
you need to put the password that you're gonna use in the $password = ' '; 
just put in there and run 'localhost/visitorlogsia/passHash.php' your browser. 

It will look like a group of random numbers and symbols 
(eg.$2y$10$rXQopgF2tZpn2wToRX0xAezfSsS6I6/PxlfeTuLHXG6Uf6DtgJewm), that long stuff indicates 
that your password is now hashed and safe.

to use it change the username filed to your desired username
and for the password field just paste you hashed password.

INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$rXQopgF2tZpn2wToRX0xAezfSsS6I6/PxlfeTuLHXG6Uf6DtgJewm');