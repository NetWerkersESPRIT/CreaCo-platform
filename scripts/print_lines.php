<?php
$lines = file(__DIR__.'/../templates/auth/visitor.html.twig');
for ($i = 190; $i <= 230; $i++) {
    echo ($i+1) . ': ' . $lines[$i];
}
