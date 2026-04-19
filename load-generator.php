<?php
$targets = [
    "http://php-auto/"
];

while (true) {
    for ($i = 0; $i < 300; $i++) {
        $base = $targets[array_rand($targets)];
        usleep(rand(100, 2000) * 1000);
        $url = $base;
        if (rand(1, 10) <= 2) {
            $url .= "?force_error=1";
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
    sleep(600);
}
