<?php

declare(strict_types=1);

namespace App\Repository\Doctrine;

use App\Dto\List\CreateListData;
use App\Dto\List\ListData;
use App\Dto\List\ListFilterData;
use App\Dto\List\ListItemData;
use App\Dto\List\ListViewData;
use App\Dto\List\PaginatedListsData;
use App\Dto\List\UpdateListData;
use App\Entity\JotList;
use App\Entity\ListItem;
use App\Entity\User;
use App\Enum\ListAccess;
use App\Enum\ListFilterTemplate;
use App\Repository\ListRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<JotList>
 */
final class DoctrineListRepository extends ServiceEntityRepository implements ListRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JotList::class);
    }

    public function getFiltered(string $userId, ListFilterData $filter): PaginatedListsData
    {
        $queryBuilder = $this->createAccessibleListsQueryBuilder($userId, $filter);

        /** @var list<JotList> $items */
        $items = (clone $queryBuilder)
            ->orderBy('list.touchedAt', 'DESC')
            ->setFirstResult(($filter->page - 1) * $filter->perPage)
            ->setMaxResults($filter->perPage)
            ->getQuery()
            ->getResult();

        $countQueryBuilder = $this->createAccessibleListsQueryBuilder($userId, $filter)
            ->select('COUNT(DISTINCT list.id)');

        $total = (int) $countQueryBuilder->getQuery()->getSingleScalarResult();
        $lastPage = max(1, (int) ceil($total / $filter->perPage));

        $data = array_map(
            fn (JotList $list): ListData => $this->mapListData($list, $userId),
            $items,
        );

        return new PaginatedListsData(
            $data,
            $filter->page,
            $filter->perPage,
            $total,
            $lastPage,
            $total > 0 ? (($filter->page - 1) * $filter->perPage) + 1 : null,
            $total > 0 ? (($filter->page - 1) * $filter->perPage) + count($data) : null,
        );
    }

    public function create(string $userId, CreateListData $data): ListData
    {
        $now = new \DateTimeImmutable();

        $list = new JotList();
        $list
            ->setId(Uuid::v7()->toRfc4122())
            ->setOwner($this->getEntityManager()->getReference(User::class, $userId))
            ->setName($data->name)
            ->setDescription($data->description)
            ->setIsTemplate($data->isTemplate)
            ->setType($data->type)
            ->setTouchedAt($now)
            ->setShortUrl($this->generateUniqueShortUrl())
            ->setAccess(ListAccess::Private->value)
            ->setCreatedAt($now)
            ->setUpdatedAt($now);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($list);
        $entityManager->flush();

        return $this->mapListData($list, $userId);
    }

    public function findById(string $userId, string $listId): ?ListViewData
    {
        $list = $this->findAccessibleList($userId, $listId);

        if (!$list instanceof JotList) {
            return null;
        }

        $items = array_map(
            fn (ListItem $item): ListItemData => $this->mapListItemData($item),
            array_values($list->getItems()->toArray()),
        );

        return new ListViewData($this->mapListData($list, $userId), $items);
    }

    public function update(string $userId, string $listId, UpdateListData $data): ?ListData
    {
        $list = $this->findAccessibleList($userId, $listId);

        if (!$list instanceof JotList || !$this->canEdit($list, $userId)) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $list
            ->setName($data->name)
            ->setDescription($data->description)
            ->setTouchedAt($now)
            ->setUpdatedAt($now);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($list);
        $entityManager->flush();

        return $this->mapListData($list, $userId);
    }

    public function delete(string $userId, string $listId): bool
    {
        $list = $this->findAccessibleList($userId, $listId);

        if (!$list instanceof JotList || $list->getOwner()->getId() !== $userId) {
            return false;
        }

        $list
            ->setDeletedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->getEntityManager()->persist($list);
        $this->getEntityManager()->flush();

        return true;
    }

    private function createAccessibleListsQueryBuilder(string $userId, ListFilterData $filter): \Doctrine\ORM\QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('list')
            ->select('DISTINCT list, owner, membership')
            ->join('list.owner', 'owner')
            ->leftJoin('list.memberships', 'membership')
            ->andWhere('list.deletedAt IS NULL');

        if ($filter->isOwner) {
            $queryBuilder->andWhere('owner.id = :userId');
        } else {
            $queryBuilder->andWhere('owner.id = :userId OR IDENTITY(membership.user) = :userId');
        }

        if (null !== $filter->type) {
            $queryBuilder
                ->andWhere('list.type = :type')
                ->setParameter('type', $filter->type);
        }

        if (null !== $filter->template) {
            $queryBuilder
                ->andWhere('list.isTemplate = :isTemplate')
                ->setParameter('isTemplate', ListFilterTemplate::Template === $filter->template);
        }

        if (null !== $filter->text && '' !== $filter->text) {
            $queryBuilder
                ->andWhere('LOWER(list.name) LIKE :text OR LOWER(COALESCE(list.description, \'\')) LIKE :text')
                ->setParameter('text', '%'.mb_strtolower($filter->text).'%');
        }

        return $queryBuilder->setParameter('userId', $userId);
    }

    private function findAccessibleList(string $userId, string $listId): ?JotList
    {
        /** @var JotList|null $list */
        $list = $this->createQueryBuilder('list')
            ->select('DISTINCT list, owner, membership, item, itemUser, completedUser')
            ->join('list.owner', 'owner')
            ->leftJoin('list.memberships', 'membership')
            ->leftJoin('list.items', 'item')
            ->leftJoin('item.user', 'itemUser')
            ->leftJoin('item.completedUser', 'completedUser')
            ->andWhere('list.id = :id')
            ->andWhere('list.deletedAt IS NULL')
            ->andWhere('owner.id = :userId OR IDENTITY(membership.user) = :userId')
            ->setParameter('id', $listId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();

        return $list;
    }

    private function canEdit(JotList $list, string $userId): bool
    {
        if ($list->getOwner()->getId() === $userId) {
            return true;
        }

        if (($list->getAccess() & ListAccess::CanEdit->value) !== ListAccess::CanEdit->value) {
            return false;
        }

        foreach ($list->getMemberships() as $membership) {
            if ($membership->getUser()->getId() === $userId) {
                return true;
            }
        }

        return false;
    }

    private function mapListData(JotList $list, string $userId): ListData
    {
        return new ListData(
            $list->getId(),
            $list->getOwner()->getId(),
            $list->getName(),
            $list->isTemplate(),
            $list->getType(),
            $list->getOwner()->getName(),
            $list->getTouchedAt(),
            $this->canEdit($list, $userId),
            $list->getOwner()->getAvatar(),
            $list->getDescription(),
        );
    }

    private function mapListItemData(ListItem $item): ListItemData
    {
        return new ListItemData(
            $item->getId(),
            $item->getUser()->getName(),
            $item->getList()->getId(),
            $item->getVersion(),
            $item->isCompleted(),
            $item->getName(),
            $item->getData(),
            $item->getDescription(),
            $item->getUser()->getAvatar(),
            $item->getCompletedUser()?->getName(),
            $item->getCompletedUser()?->getAvatar(),
        );
    }

    private function generateUniqueShortUrl(): string
    {
        do {
            $shortUrl = bin2hex(random_bytes(8));
        } while (null !== $this->findOneBy(['shortUrl' => $shortUrl]));

        return $shortUrl;
    }
}
