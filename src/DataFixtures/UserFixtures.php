<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $user = new User();

        $user->setRoles([
            'ROLE_ADMIN'
        ]);
        $user->setSteamid('76561198076130599');

        $manager->persist($user);
        $manager->flush();
    }
}
