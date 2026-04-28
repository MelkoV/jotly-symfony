<?php

declare(strict_types=1);

namespace App\Repository\Doctrine;

use App\Dto\List\CreateListData;
use App\Dto\List\CreateListItemData;
use App\Dto\List\DeleteTypesData;
use App\Dto\List\DeleteListItemData;
use App\Dto\List\DuplicateListData;
use App\Dto\List\ListPublicInfoData;
use App\Dto\List\ListData;
use App\Dto\List\ListFilterData;
use App\Dto\List\ListItemData;
use App\Dto\List\ListItemMutationData;
use App\Dto\List\ListViewData;
use App\Dto\List\PaginatedListsData;
use App\Dto\List\ShareData;
use App\Dto\List\UpdateListData;
use App\Dto\List\UpdateShareData;
use App\Entity\JotList;
use App\Entity\ListItem;
use App\Entity\ListUser;
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

    public function getDeleteTypes(string $userId, string $listId): ?DeleteTypesData
    {
        $list = $this->findAccessibleList($userId, $listId);

        if (!$list instanceof JotList) {
            return null;
        }

        return new DeleteTypesData(
            true,
            $list->getOwner()->getId() === $userId,
        );
    }

    public function leftUser(string $userId, string $listId): bool
    {
        $list = $this->findAccessibleList($userId, $listId);

        if (!$list instanceof JotList) {
            return false;
        }

        $membership = $this->findMembership($listId, $userId);
        if (!$membership instanceof ListUser) {
            return false;
        }

        $entityManager = $this->getEntityManager();
        $entityManager->remove($membership);
        $entityManager->flush();

        return true;
    }

    public function getShareData(string $userId, string $listId): ?ShareData
    {
        $list = $this->findOwnedList($userId, $listId);

        return $list instanceof JotList ? $this->mapShareData($list) : null;
    }

    public function updateShareData(string $userId, string $listId, UpdateShareData $data): ?ShareData
    {
        $list = $this->findOwnedList($userId, $listId);

        if (!$list instanceof JotList) {
            return null;
        }

        $access = ListAccess::Private->value;
        if ($data->isShareLink) {
            $access |= ListAccess::Link->value;
        }
        if ($data->canEdit) {
            $access |= ListAccess::CanEdit->value;
        }

        $list
            ->setAccess($access)
            ->setUpdatedAt(new \DateTimeImmutable());

        $entityManager = $this->getEntityManager();
        $entityManager->persist($list);
        $entityManager->flush();

        return $this->mapShareData($list);
    }

    public function joinByLink(string $userId, string $listId): ?ListData
    {
        $list = $this->find($listId);

        if (!$list instanceof JotList || null !== $list->getDeletedAt()) {
            return null;
        }

        if (($list->getAccess() & ListAccess::Link->value) !== ListAccess::Link->value) {
            return null;
        }

        $membership = $this->findMembership($listId, $userId);
        if (!$membership instanceof ListUser) {
            $now = new \DateTimeImmutable();
            $membership = new ListUser();
            $membership
                ->setList($list)
                ->setUser($this->getEntityManager()->getReference(User::class, $userId))
                ->setCreatedAt($now)
                ->setUpdatedAt($now);

            $entityManager = $this->getEntityManager();
            $entityManager->persist($membership);
            $entityManager->flush();
        }

        $accessible = $this->findAccessibleList($userId, $listId);

        return $accessible instanceof JotList ? $this->mapListData($accessible, $userId) : null;
    }

    public function findPublicInfoByShortUrl(string $shortUrl): ?ListPublicInfoData
    {
        /** @var JotList|null $list */
        $list = $this->createQueryBuilder('list')
            ->select('list, owner')
            ->join('list.owner', 'owner')
            ->andWhere('list.shortUrl = :shortUrl')
            ->andWhere('list.deletedAt IS NULL')
            ->setParameter('shortUrl', $shortUrl)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$list instanceof JotList) {
            return null;
        }

        return new ListPublicInfoData(
            $list->getId(),
            $list->getName(),
            $list->getDescription(),
            $list->getOwner()->getName(),
            $list->getOwner()->getAvatar(),
        );
    }

    public function copy(string $userId, string $listId, DuplicateListData $data): ?ListPublicInfoData
    {
        return $this->duplicateList($userId, $listId, $data->name, false);
    }

    public function createFromTemplate(string $userId, string $listId, DuplicateListData $data): ?ListPublicInfoData
    {
        return $this->duplicateList($userId, $listId, $data->name, true);
    }

    public function createListItem(string $userId, CreateListItemData $data): ?ListItemData
    {
        $list = $this->findAccessibleList($userId, $data->listId);

        if (!$list instanceof JotList || !$this->canEdit($list, $userId)) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $item = new ListItem();
        $item
            ->setId(Uuid::v7()->toRfc4122())
            ->setList($list)
            ->setUser($this->getEntityManager()->getReference(User::class, $userId))
            ->setName($data->name)
            ->setDescription($data->description)
            ->setVersion(1)
            ->setIsCompleted($data->isCompleted)
            ->setCompletedAt($data->isCompleted ? $now : null)
            ->setCompletedUser($data->isCompleted ? $this->getEntityManager()->getReference(User::class, $userId) : null)
            ->setData($this->normalizeAttributes($data->attributes()))
            ->setCreatedAt($now)
            ->setUpdatedAt($now);

        $list
            ->setTouchedAt($now)
            ->setUpdatedAt($now);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($item);
        $entityManager->persist($list);
        $entityManager->flush();
        $entityManager->refresh($item);

        return $this->mapListItemData($item);
    }

    public function updateListItem(string $userId, string $itemId, ListItemMutationData $data): ?ListItemData
    {
        $item = $this->findAccessibleItem($userId, $itemId);

        if (!$item instanceof ListItem || !$this->canEdit($item->getList(), $userId)) {
            return null;
        }

        $this->assertVersion($item, $data->version);
        $this->assertNotCompleted($item);

        $now = new \DateTimeImmutable();
        $item
            ->setName($data->name)
            ->setDescription($data->description)
            ->setData($this->normalizeAttributes($data->attributes()))
            ->setVersion($data->version + 1)
            ->setUpdatedAt($now);

        $item->getList()
            ->setTouchedAt($now)
            ->setUpdatedAt($now);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($item);
        $entityManager->persist($item->getList());
        $entityManager->flush();
        $entityManager->refresh($item);

        return $this->mapListItemData($item);
    }

    public function completeListItem(string $userId, string $itemId, ListItemMutationData $data): ?ListItemData
    {
        $item = $this->findAccessibleItem($userId, $itemId);

        if (!$item instanceof ListItem || !$this->canEdit($item->getList(), $userId)) {
            return null;
        }

        $this->assertNotTemplate($item);
        $updated = $this->updateListItem($userId, $itemId, $data);

        if (!$updated instanceof ListItemData) {
            return null;
        }

        $item = $this->findAccessibleItem($userId, $itemId);
        if (!$item instanceof ListItem) {
            return null;
        }

        $this->assertNotCompleted($item);

        $now = new \DateTimeImmutable();
        $item
            ->setIsCompleted(true)
            ->setCompletedAt($now)
            ->setCompletedUser($this->getEntityManager()->getReference(User::class, $userId))
            ->setUpdatedAt($now);

        $item->getList()
            ->setTouchedAt($now)
            ->setUpdatedAt($now);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($item);
        $entityManager->persist($item->getList());
        $entityManager->flush();
        $entityManager->refresh($item);

        return $this->mapListItemData($item);
    }

    public function uncompleteListItem(string $userId, string $itemId, ListItemMutationData $data): ?ListItemData
    {
        $item = $this->findAccessibleItem($userId, $itemId);

        if (!$item instanceof ListItem || !$this->canEdit($item->getList(), $userId)) {
            return null;
        }

        $this->assertNotTemplate($item);
        $this->assertVersion($item, $data->version);
        $this->assertCompleted($item);

        $now = new \DateTimeImmutable();
        $item
            ->setName($data->name)
            ->setDescription($data->description)
            ->setData($this->normalizeAttributes($data->attributes()))
            ->setVersion($data->version + 1)
            ->setIsCompleted(false)
            ->setCompletedAt(null)
            ->setCompletedUser(null)
            ->setUpdatedAt($now);

        $item->getList()
            ->setTouchedAt($now)
            ->setUpdatedAt($now);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($item);
        $entityManager->persist($item->getList());
        $entityManager->flush();
        $entityManager->refresh($item);

        return $this->mapListItemData($item);
    }

    public function deleteListItem(string $userId, string $itemId, DeleteListItemData $data): ?bool
    {
        $item = $this->findAccessibleItem($userId, $itemId);

        if (!$item instanceof ListItem || !$this->canEdit($item->getList(), $userId)) {
            return null;
        }

        $this->assertVersion($item, $data->version);
        $this->assertNotCompleted($item);

        $list = $item->getList();
        $now = new \DateTimeImmutable();
        $list
            ->setTouchedAt($now)
            ->setUpdatedAt($now);

        $entityManager = $this->getEntityManager();
        $entityManager->remove($item);
        $entityManager->persist($list);
        $entityManager->flush();

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

    private function findAccessibleItem(string $userId, string $itemId): ?ListItem
    {
        /** @var ListItem|null $item */
        $item = $this->getEntityManager()->createQueryBuilder()
            ->select('item, list, owner, membership, user, completedUser')
            ->from(ListItem::class, 'item')
            ->join('item.list', 'list')
            ->join('list.owner', 'owner')
            ->leftJoin('list.memberships', 'membership')
            ->join('item.user', 'user')
            ->leftJoin('item.completedUser', 'completedUser')
            ->andWhere('item.id = :id')
            ->andWhere('list.deletedAt IS NULL')
            ->andWhere('owner.id = :userId OR IDENTITY(membership.user) = :userId')
            ->setParameter('id', $itemId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();

        return $item;
    }

    private function findOwnedList(string $userId, string $listId): ?JotList
    {
        /** @var JotList|null $list */
        $list = $this->createQueryBuilder('list')
            ->select('list, owner')
            ->join('list.owner', 'owner')
            ->andWhere('list.id = :id')
            ->andWhere('list.deletedAt IS NULL')
            ->andWhere('owner.id = :userId')
            ->setParameter('id', $listId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();

        return $list;
    }

    private function findMembership(string $listId, string $userId): ?ListUser
    {
        /** @var ListUser|null $membership */
        $membership = $this->getEntityManager()->getRepository(ListUser::class)->findOneBy([
            'list' => $listId,
            'user' => $userId,
        ]);

        return $membership;
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
        $attributes = $item->getData();
        $hideCompletedUser = $item->getList()->getType() === \App\Enum\ListType::Wishlist;

        return new ListItemData(
            $item->getId(),
            $item->getUser()->getName(),
            $item->getList()->getId(),
            $item->getVersion(),
            $item->isCompleted(),
            $item->getName(),
            [
                'priority' => $attributes['priority'] ?? null,
                'unit' => $attributes['unit'] ?? null,
                'deadline' => $attributes['deadline'] ?? null,
                'price' => $attributes['price'] ?? null,
                'cost' => $attributes['cost'] ?? null,
                'count' => $attributes['count'] ?? null,
            ],
            $item->getDescription(),
            $item->getUser()->getAvatar(),
            $hideCompletedUser ? null : $item->getCompletedUser()?->getName(),
            $hideCompletedUser ? null : $item->getCompletedUser()?->getAvatar(),
        );
    }

    private function mapShareData(JotList $list): ShareData
    {
        $access = $list->getAccess();

        return new ShareData(
            $list->getShortUrl(),
            ($access & ListAccess::Link->value) === ListAccess::Link->value,
            ($access & ListAccess::CanEdit->value) === ListAccess::CanEdit->value,
        );
    }

    private function duplicateList(string $userId, string $listId, string $name, bool $forceNonTemplate): ?ListPublicInfoData
    {
        $source = $this->findAccessibleList($userId, $listId);

        if (!$source instanceof JotList) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $newList = new JotList();
        $newList
            ->setId(Uuid::v7()->toRfc4122())
            ->setName($name)
            ->setDescription($source->getDescription())
            ->setIsTemplate($forceNonTemplate ? false : $source->isTemplate())
            ->setType($source->getType())
            ->setTouchedAt($now)
            ->setShortUrl($this->generateUniqueShortUrl())
            ->setAccess(ListAccess::Private->value)
            ->setOwner($this->getEntityManager()->getReference(User::class, $userId))
            ->setCreatedAt($now)
            ->setUpdatedAt($now);

        $membership = new ListUser();
        $membership
            ->setList($newList)
            ->setUser($this->getEntityManager()->getReference(User::class, $userId))
            ->setCreatedAt($now)
            ->setUpdatedAt($now);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($newList);
        $entityManager->persist($membership);

        foreach ($source->getItems() as $sourceItem) {
            $item = new ListItem();
            $item
                ->setId(Uuid::v7()->toRfc4122())
                ->setName($sourceItem->getName())
                ->setDescription($sourceItem->getDescription())
                ->setVersion(1)
                ->setIsCompleted(false)
                ->setCompletedAt(null)
                ->setCompletedUser(null)
                ->setData($this->normalizeAttributes($sourceItem->getData()))
                ->setList($newList)
                ->setUser($this->getEntityManager()->getReference(User::class, $userId))
                ->setProduct($sourceItem->getProduct())
                ->setCreatedAt($now)
                ->setUpdatedAt($now);

            $entityManager->persist($item);
        }

        $entityManager->flush();

        return new ListPublicInfoData(
            $newList->getId(),
            $newList->getName(),
            $newList->getDescription(),
            $newList->getOwner()->getName(),
            $newList->getOwner()->getAvatar(),
        );
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    private function normalizeAttributes(array $attributes): array
    {
        return [
            'priority' => $attributes['priority'] ?? null,
            'unit' => $attributes['unit'] ?? null,
            'deadline' => $attributes['deadline'] ?? null,
            'price' => $attributes['price'] ?? null,
            'cost' => $attributes['cost'] ?? null,
            'count' => $attributes['count'] ?? null,
        ];
    }

    private function assertVersion(ListItem $item, int $version): void
    {
        if ($item->getVersion() !== $version) {
            throw new \App\Exception\ListException('Похоже, кто-то другой уже изменил этот элемент. Обновите страницу.');
        }
    }

    private function assertNotCompleted(ListItem $item): void
    {
        if ($item->isCompleted()) {
            throw new \App\Exception\ListException('Completed list items cannot be modified.');
        }
    }

    private function assertCompleted(ListItem $item): void
    {
        if (!$item->isCompleted()) {
            throw new \App\Exception\ListException('Only completed list items can be restored.');
        }
    }

    private function assertNotTemplate(ListItem $item): void
    {
        if ($item->getList()->isTemplate()) {
            throw new \App\Exception\ListException('Template list items cannot be completed.');
        }
    }

    private function generateUniqueShortUrl(): string
    {
        do {
            $shortUrl = bin2hex(random_bytes(8));
        } while (null !== $this->findOneBy(['shortUrl' => $shortUrl]));

        return $shortUrl;
    }
}
