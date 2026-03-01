<?php
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// load environment variables from .env if not already available
if (!isset($_SERVER['DATABASE_URL'])) {
    (new Dotenv())->bootEnv(__DIR__ . '/../.env');
}

$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
$sm = $em->getConnection()->createSchemaManager();

function dumpTable($name) {
    global $sm;
    echo "Columns for $name:\n";
    $cols = $sm->listTableColumns($name);
    foreach ($cols as $n => $col) {
        $arr = $col->toArray();
        if (isset($arr['type']) && is_string($arr['type']) && $arr['type'] === '1') {
            echo "  *** FOUND type '1' on column $n\n";
        }
        echo "  $n => ";
        var_dump($arr);
    }
}

// iterate all tables
$tables = $sm->listTableNames();
foreach ($tables as $tbl) {
    dumpTable($tbl);
}

// quick sanity check of random selection
$repo = $em->getRepository(\App\Entity\Cours::class);
if (method_exists($repo, 'findRandom')) {
    echo "\n-- random courses sample --\n";
    try {
        $random = $repo->findRandom(3);
        foreach ($random as $c) {
            echo "cours id " . $c->getId() . " title: " . $c->getTitre() . "\n";
        }
    } catch (\Exception $e) {
        echo "error selecting random courses: " . $e->getMessage() . "\n";
    }
}


