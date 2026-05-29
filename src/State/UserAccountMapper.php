<?php

declare(strict_types=1);

namespace App\State;

use App\ApiResource\LibrarianOutput;
use App\ApiResource\MemberOutput;
use App\Entity\User;

/**
 * Convertit une entité User en DTO de sortie selon le type de compte.
 */
final class UserAccountMapper
{
    public function toMember(User $user): MemberOutput
    {
        $output = new MemberOutput();
        $this->fill($user, $output);

        return $output;
    }

    public function toLibrarian(User $user): LibrarianOutput
    {
        $output = new LibrarianOutput();
        $this->fill($user, $output);

        return $output;
    }

    private function fill(User $user, MemberOutput|LibrarianOutput $output): void
    {
        $output->id = (int) $user->getId();
        $output->email = $user->getEmail();
        $output->firstName = $user->getFirstName();
        $output->lastName = $user->getLastName();
        $output->createdAt = $user->getCreatedAt();
    }
}
