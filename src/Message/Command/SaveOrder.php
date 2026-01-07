<?php

namespace App\Message\Command;

use Symfony\Component\Validator\Constraints as Assert;

readonly class SaveOrder
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $userId,

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        public string $stockSymbol,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $quantity,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public float $price,
    ) {
    }
}
