<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\List\CreateListData;
use App\Dto\List\CreateListItemData;
use App\Dto\List\DeleteTypesData;
use App\Dto\List\DeleteListItemData;
use App\Dto\List\ListPublicInfoData;
use App\Dto\List\ListFilterData;
use App\Dto\List\ListData;
use App\Dto\List\ListItemData;
use App\Dto\List\ListItemMutationData;
use App\Dto\List\ShareData;
use App\Dto\List\UpdateShareData;
use App\Dto\List\ListViewData;
use App\Dto\List\PaginatedListsData;
use App\Dto\List\UpdateListData;

interface ListRepository
{
    public function getFiltered(string $userId, ListFilterData $filter): PaginatedListsData;

    public function create(string $userId, CreateListData $data): ListData;

    public function findById(string $userId, string $listId): ?ListViewData;

    public function update(string $userId, string $listId, UpdateListData $data): ?ListData;

    public function delete(string $userId, string $listId): bool;

    public function getDeleteTypes(string $userId, string $listId): ?DeleteTypesData;

    public function leftUser(string $userId, string $listId): bool;

    public function getShareData(string $userId, string $listId): ?ShareData;

    public function updateShareData(string $userId, string $listId, UpdateShareData $data): ?ShareData;

    public function joinByLink(string $userId, string $listId): ?ListData;

    public function findPublicInfoByShortUrl(string $shortUrl): ?ListPublicInfoData;

    public function createListItem(string $userId, CreateListItemData $data): ?ListItemData;

    public function updateListItem(string $userId, string $itemId, ListItemMutationData $data): ?ListItemData;

    public function completeListItem(string $userId, string $itemId, ListItemMutationData $data): ?ListItemData;

    public function uncompleteListItem(string $userId, string $itemId, ListItemMutationData $data): ?ListItemData;

    public function deleteListItem(string $userId, string $itemId, DeleteListItemData $data): ?bool;
}
