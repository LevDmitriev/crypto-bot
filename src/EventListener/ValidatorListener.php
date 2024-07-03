<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Position;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsDoctrineListener(event: Events::prePersist, priority: 100)]
class ValidatorListener
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $violations = $this->validator->validate($args->getObject());
        if ($violations->count()) {
            throw new ValidationFailedException("Ошибка валидации", $violations);
        }
    }
}
