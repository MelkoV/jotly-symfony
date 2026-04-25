<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'list_invites')]
class ListInvite
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: JotList::class, inversedBy: 'invites')]
    #[ORM\JoinColumn(name: 'list_id', referencedColumnName: 'id', nullable: false)]
    private JotList $list;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private string $email;

    public function getList(): JotList
    {
        return $this->list;
    }

    public function setList(JotList $list): static
    {
        $this->list = $list;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }
}
