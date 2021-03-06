<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use R\U2FTwoFactorBundle\Model\U2F\TwoFactorKeyInterface;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity()
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="user_unique", columns={"user_id", "key_handle"})})
 */
class U2fKey implements TwoFactorKeyInterface {

    use IdTrait;
    use UuidTrait;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\Column(type="string")
     */
    public $keyHandle;

    /**
     * @ORM\Column(type="string")
     */
    public $publicKey;

    /**
     * @ORM\Column(type="text")
     */
    public $certificate;

    /**
     * @ORM\Column(type="integer")
     */
    public $counter;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="u2fKeys")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $user;

    public function __construct() {
        $this->uuid = Uuid::uuid4();
    }

    /**
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param User $user
     * @return U2fKey
     */
    public function setUser(User $user) {
        $this->user = $user;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getKeyHandle() {
        return $this->keyHandle;
    }

    /**
     * @inheritDoc
     */
    public function setKeyHandle($keyHandle) {
        $this->keyHandle = $keyHandle;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPublicKey() {
        return $this->publicKey;
    }

    /**
     * @inheritDoc
     */
    public function setPublicKey($publicKey) {
        $this->publicKey = $publicKey;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCertificate() {
        return $this->certificate;
    }

    /**
     * @inheritDoc
     */
    public function setCertificate($certificate) {
        $this->certificate = $certificate;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCounter() {
        return $this->counter;
    }

    /**
     * @inheritDoc
     */
    public function setCounter($counter) {
        $this->counter = $counter;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }
}