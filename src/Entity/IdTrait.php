<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

trait IdTrait {

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned": true})
     * @Serializer\Exclude()
     * @var int|null
     */
    private $id;

    public function getId(): ?int {
        return $this->id;
    }
}