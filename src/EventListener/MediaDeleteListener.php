<?php

namespace App\EventListener;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsEntityListener(event: Events::preRemove, entity: Media::class)]
class MediaDeleteListener
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {}

    public function preRemove(Media $media): void
    {
        $absolutePath = $this->projectDir . '/public/' . $media->getPath();

        if (file_exists($absolutePath)) {
            unlink($absolutePath);
        }
    }
}
