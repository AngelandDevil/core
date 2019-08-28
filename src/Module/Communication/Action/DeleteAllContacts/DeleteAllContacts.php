<?php

declare(strict_types=1);

namespace Stu\Module\Communication\Action\DeleteAllContacts;

use Contactlist;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;

final class DeleteAllContacts implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_DELETE_ALL_CONTACTS';

    public function handle(GameControllerInterface $game): void
    {
        Contactlist::truncate(sprintf('WHERE user_id = %d', $game->getUser()->getId()));
        $game->addInformation(_('Die Kontakte wurden gelöscht'));
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
