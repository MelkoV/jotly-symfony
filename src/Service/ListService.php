<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Common\SuccessData;
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
use App\Exception\ListException;
use App\Repository\ListRepository;
use App\Repository\UserRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ListService
{
    public function __construct(
        private UserRepository $userRepository,
        private ListRepository $listRepository,
        private TranslatorInterface $translator,
    ) {
    }

    public function getFiltered(string $email, ListFilterData $filter): PaginatedListsData
    {
        return $this->listRepository->getFiltered($this->requireUserId($email), $filter);
    }

    public function create(string $email, CreateListData $data): ListData
    {
        return $this->listRepository->create($this->requireUserId($email), $data);
    }

    public function findById(string $email, string $listId): ListViewData
    {
        $list = $this->listRepository->findById($this->requireUserId($email), $listId);

        if (null === $list) {
            throw new ListException($this->translator->trans('list.access_denied'));
        }

        return $list;
    }

    public function update(string $email, string $listId, UpdateListData $data): ListData
    {
        $list = $this->listRepository->update($this->requireUserId($email), $listId, $data);

        if (null === $list) {
            throw new ListException($this->translator->trans('list.access_denied'));
        }

        return $list;
    }

    public function delete(string $email, string $listId): SuccessData
    {
        $deleted = $this->listRepository->delete($this->requireUserId($email), $listId);

        if (!$deleted) {
            throw new ListException($this->translator->trans('list.access_denied'));
        }

        return new SuccessData();
    }

    public function getDeleteTypes(string $email, string $listId): DeleteTypesData
    {
        $data = $this->listRepository->getDeleteTypes($this->requireUserId($email), $listId);

        if (null === $data) {
            throw new ListException($this->translator->trans('list.access_denied'));
        }

        return $data;
    }

    public function leftUser(string $email, string $listId): SuccessData
    {
        $left = $this->listRepository->leftUser($this->requireUserId($email), $listId);

        if (!$left) {
            throw new ListException($this->translator->trans('list.access_denied'));
        }

        return new SuccessData();
    }

    public function getShareData(string $email, string $listId): ShareData
    {
        $data = $this->listRepository->getShareData($this->requireUserId($email), $listId);

        if (null === $data) {
            throw new ListException($this->translator->trans('list.access_denied'));
        }

        return $data;
    }

    public function updateShareData(string $email, string $listId, UpdateShareData $data): ShareData
    {
        $updated = $this->listRepository->updateShareData($this->requireUserId($email), $listId, $data);

        if (null === $updated) {
            throw new ListException($this->translator->trans('list.access_denied'));
        }

        return $updated;
    }

    public function joinByLink(string $email, string $listId): ListData
    {
        $joined = $this->listRepository->joinByLink($this->requireUserId($email), $listId);

        if (null === $joined) {
            throw new ListException($this->translator->trans('list.link_access_denied'));
        }

        return $joined;
    }

    public function findPublicInfoByShortUrl(string $shortUrl): ListPublicInfoData
    {
        $info = $this->listRepository->findPublicInfoByShortUrl($shortUrl);

        if (null === $info) {
            throw new ListException($this->translator->trans('list.not_found'), 'url');
        }

        return $info;
    }

    public function copy(string $email, string $listId, DuplicateListData $data): ListPublicInfoData
    {
        $copy = $this->listRepository->copy($this->requireUserId($email), $listId, $data);

        if (null === $copy) {
            throw new ListException($this->translator->trans('list.access_denied'));
        }

        return $copy;
    }

    public function createFromTemplate(string $email, string $listId, DuplicateListData $data): ListPublicInfoData
    {
        $copy = $this->listRepository->createFromTemplate($this->requireUserId($email), $listId, $data);

        if (null === $copy) {
            throw new ListException($this->translator->trans('list.access_denied'));
        }

        return $copy;
    }

    public function createListItem(string $email, CreateListItemData $data): ListItemData
    {
        $item = $this->listRepository->createListItem($this->requireUserId($email), $data);

        if (null === $item) {
            throw new ListException($this->translator->trans('list.access_denied'), 'list_id');
        }

        return $item;
    }

    public function updateListItem(string $email, string $itemId, ListItemMutationData $data): ListItemData
    {
        $item = $this->listRepository->updateListItem($this->requireUserId($email), $itemId, $data);

        if (null === $item) {
            throw new ListException($this->translator->trans('list.access_denied'));
        }

        return $item;
    }

    public function completeListItem(string $email, string $itemId, ListItemMutationData $data): ListItemData
    {
        $item = $this->listRepository->completeListItem($this->requireUserId($email), $itemId, $data);

        if (null === $item) {
            throw new ListException($this->translator->trans('list.access_denied'));
        }

        return $item;
    }

    public function uncompleteListItem(string $email, string $itemId, ListItemMutationData $data): ListItemData
    {
        $item = $this->listRepository->uncompleteListItem($this->requireUserId($email), $itemId, $data);

        if (null === $item) {
            throw new ListException($this->translator->trans('list.access_denied'));
        }

        return $item;
    }

    public function deleteListItem(string $email, string $itemId, DeleteListItemData $data): SuccessData
    {
        $deleted = $this->listRepository->deleteListItem($this->requireUserId($email), $itemId, $data);

        if (null === $deleted) {
            throw new ListException($this->translator->trans('list.access_denied'));
        }

        return new SuccessData($deleted);
    }

    private function requireUserId(string $email): string
    {
        $user = $this->userRepository->findProfileByEmail($email);

        if (null === $user) {
            throw new ListException($this->translator->trans('security.user_not_found', ['%email%' => $email]));
        }

        return $user->id;
    }
}
