<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class ServiceAttributeUserTypeValue implements ServiceAttributeValueInterface {
    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="ServiceAttribute")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $attribute;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="UserType", inversedBy="attributes")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $userType;

    /**
     * @ORM\Column(type="object", nullable=true)
     */
    private $value;

    /**
     * @return ServiceAttribute
     */
    public function getAttribute() {
        return $this->attribute;
    }

    /**
     * @param ServiceAttribute $attribute
     * @return ServiceAttributeUserTypeValue
     */
    public function setAttribute(ServiceAttribute $attribute) {
        $this->attribute = $attribute;
        return $this;
    }

    /**
     * @return UserType
     */
    public function getUserType() {
        return $this->userType;
    }

    /**
     * @param UserType $userType
     * @return ServiceAttributeUserTypeValue
     */
    public function setUserType(UserType $userType) {
        $this->userType = $userType;
        return $this;
    }

    /**
     * @return string|string[]|int
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param string|string[]|int $value
     */
    public function setValue($value) {
        $this->value = $value;
    }

}