<?php

namespace App\Repository;

use App\Entity\ActiveDirectoryUser;
use App\Entity\User;
use App\Entity\UserRole;
use App\Entity\UserType;
use Doctrine\ORM\Tools\Pagination\Paginator;

interface UserRepositoryInterface {
    /**
     * @param string[] $usernames
     * @return User[]
     */
    public function findUsersByUsernames(array $usernames);

    /**
     * @param \DateTime $dateTime
     * @param string[] $usernames
     * @return User[]
     */
    public function findUsersUpdatedAfter(\DateTime $dateTime, array $usernames = [ ]);

    public function findOneByUsername(string $username): ?User;

    public function findOneByExternalId(string $externalId): ?User;

    public function findOneByUuid(string $uuid): ?User;

    /**
     * @param string $guid
     * @return ActiveDirectoryUser|null
     */
    public function findActiveDirectoryUserByObjectGuid(string $guid): ?ActiveDirectoryUser;

    /**
     * @return string[]
     */
    public function findAllActiveDirectoryUsersObjectGuid(): array;

    /**
     * @param string $email
     * @return User|null
     */
    public function findOneByEmail(string $email): ?User;

    /**
     * @param int $offset
     * @param int|null $limit
     * @param bool $deleted Whether or not to return deleted users
     * @return User[]
     */
    public function findAll($offset = 0, $limit = null, bool $deleted = false);

    /**
     * @param int $offset
     * @param int|null $limit
     * @return string[]
     */
    public function findAllUuids($offset = 0, $limit = null);

    /**
     * @param int $limit
     * @return User[]
     */
    public function findNextNonProvisionedUsers(int $limit): array;

    /**
     * @param UserType|null $userType
     * @return int
     */
    public function countUsers(?UserType $userType = null): int;

    public function persist(User $user);

    public function remove(User $user);

    /**
     * @param int $itemsPerPage
     * @param int $page
     * @param UserType|null $type
     * @param UserRole|null $role
     * @param string|null $query
     * @param bool $deleted
     * @return Paginator
     */
    public function getPaginatedUsers($itemsPerPage, &$page, $type = null, $role = null, $query = null, bool $deleted = false): Paginator;
}