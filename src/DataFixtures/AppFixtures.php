<?php

namespace App\DataFixtures;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public const ADMIN_EMAIL    = 'ina@zaoui.com';
    public const ADMIN_PASSWORD = 'Admin1234!@#';
    public const GUEST_EMAIL    = 'invite@example.com';
    public const GUEST_PASSWORD = 'Guest1234!@#';
    public const BLOCKED_EMAIL  = 'blocked@example.com';

    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // --- Admin ---
        $admin = new User();
        $admin->setName('Ina Zaoui');
        $admin->setEmail(self::ADMIN_EMAIL);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setBlocked(false);
        $admin->setPassword($this->hasher->hashPassword($admin, self::ADMIN_PASSWORD));
        $manager->persist($admin);

        // --- Albums ---
        $albums = [];
        foreach (['Album 1', 'Album 2', 'Album 3', 'Album 4', 'Album 5'] as $name) {
            $album = new Album();
            $album->setName($name);
            $manager->persist($album);
            $albums[] = $album;
        }

        // --- Médias de l'admin (portfolio) ---
        for ($i = 1; $i <= 5; $i++) {
            $media = new Media();
            $media->setTitle('Photo admin ' . $i);
            $media->setPath('uploads/admin_' . $i . '.jpg');
            $media->setUser($admin);
            $media->setAlbum($albums[($i - 1) % count($albums)]);
            $manager->persist($media);
        }

        // --- Invité actif ---
        $guest = new User();
        $guest->setName('Invité Actif');
        $guest->setEmail(self::GUEST_EMAIL);
        $guest->setRoles(['ROLE_USER']);
        $guest->setBlocked(false);
        $guest->setDescription('Photographe invité actif sur la plateforme.');
        $guest->setPassword($this->hasher->hashPassword($guest, self::GUEST_PASSWORD));
        $manager->persist($guest);

        for ($i = 1; $i <= 3; $i++) {
            $media = new Media();
            $media->setTitle('Photo invité ' . $i);
            $media->setPath('uploads/guest_' . $i . '.jpg');
            $media->setUser($guest);
            $manager->persist($media);
        }

        // --- Invité bloqué ---
        $blocked = new User();
        $blocked->setName('Invité Bloqué');
        $blocked->setEmail(self::BLOCKED_EMAIL);
        $blocked->setRoles(['ROLE_USER']);
        $blocked->setBlocked(true);
        $blocked->setDescription('Cet invité a été bloqué.');
        $blocked->setPassword($this->hasher->hashPassword($blocked, 'Blocked1234!@#'));
        $manager->persist($blocked);

        for ($i = 1; $i <= 2; $i++) {
            $media = new Media();
            $media->setTitle('Photo bloqué ' . $i);
            $media->setPath('uploads/blocked_' . $i . '.jpg');
            $media->setUser($blocked);
            $manager->persist($media);
        }

        $manager->flush();
    }
}
