<?php

namespace App\Response;

use JMS\Serializer\Annotation as Serializer;

class ListActiveDirectoryUserResponse {

    /**
     * List of objectGuids of all Active Directory users
     *
     * @Serializer\Type("array<string>")
     * @Serializer\SerializedName("users")
     * @var string[]
     */
    private $users = [ ];

    /**
     * @param string[] $users
     */
    public function __construct(array $users) {
        $this->users = $users;
    }
}