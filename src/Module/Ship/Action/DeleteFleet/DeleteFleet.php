<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\DeleteFleet;

use AccessViolation;
use Ship;
use Stu\Control\ActionControllerInterface;
use Stu\Control\GameControllerInterface;

final class DeleteFleet implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_DELETE_FLEET';

    private $deleteFleetRequest;

    public function __construct(
        DeleteFleetRequestInterface $deleteFleetRequest
    ) {
        $this->deleteFleetRequest = $deleteFleetRequest;
    }

    public function handle(GameControllerInterface $game): void
    {
        /**
         * @var Ship $ship
         */
        $ship = Ship::getById($this->deleteFleetRequest->getShipId());
        if (!$ship->ownedByCurrentUser()) {
            throw new AccessViolation();
        }
        if (!$ship->isInFleet()) {
            return;
        }
        if (!$ship->isFleetLeader()) {
            return;
        }
        $ship->getFleet()->deleteFromDb();
        $ship->unsetFleet();
        $ship->setFleetId(0);
        $ship->save();

        $game->addInformation(_('Die Flotte wurde aufgelöst'));
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
