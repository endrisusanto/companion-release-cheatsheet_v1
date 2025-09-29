<?php

// Membuat objek DateTime untuk hari ini
$today = new DateTime();


// Menampilkan tanggal kemarin dalam format yyyy-mm-dd
echo $today->format('Y-m-d');

?>