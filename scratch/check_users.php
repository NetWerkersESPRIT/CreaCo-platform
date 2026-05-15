<?php

use App\Kernel;
use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

require __DIR__.'/../vendor/autoload.php';

$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

$userRepo = $entityManager->getRepository(Users::class);
$users = $userRepo->findAll();

$roles = [];
foreach ($users as $user) {
    $role = $user->getRole();
    if (!isset($roles[$role])) {
        $roles[$role] = 0;
    }
    $roles[$role]++;
}

echo "Current User Roles:\n";
print_r($roles);
