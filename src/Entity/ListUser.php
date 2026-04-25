<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'list_users')]
class ListUser
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: JotList::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(name: 'list_id', referencedColumnName: 'id', nullable: false)]
    private JotList $list;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'listMemberships')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    public function getList(): JotList
    {
        return $this->list;
    }

    public function setList(JotList $list): static
    {
        $this->list = $list;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
