<?php

declare(strict_types=1);

namespace Stu\Module\Colony\View\ShowBeamTo;

use AccessViolation;
use Stu\Module\Ship\Lib\PositionCheckerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Colony\Lib\ColonyLoaderInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;

final class ShowBeamTo implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_BEAMTO';

    private $colonyLoader;

    private $showBeamToRequest;

    private $shipRepository;

    private $positionChecker;

    public function __construct(
        ColonyLoaderInterface $colonyLoader,
        ShowBeamToRequestInterface $showBeamToRequest,
        ShipRepositoryInterface $shipRepository,
        PositionCheckerInterface $positionChecker
    ) {
        $this->colonyLoader = $colonyLoader;
        $this->showBeamToRequest = $showBeamToRequest;
        $this->shipRepository = $shipRepository;
        $this->positionChecker = $positionChecker;
    }

    public function handle(GameControllerInterface $game): void
    {
        $user = $game->getUser();
        $userId = $user->getId();

        $colony = $this->colonyLoader->byIdAndUser(
            $this->showBeamToRequest->getColonyId(),
            $userId
        );

        $target = $this->shipRepository->find($this->showBeamToRequest->getShipId());
        if ($target === null) {
            return;
        }

        if (!$this->positionChecker->checkColonyPosition($colony,$target) || ($target->getCloakState() && $target->getUser() !== $user)) {
            throw new AccessViolation();
        }

        $game->setPageTitle(_('Zu Schiff beamen'));
        $game->setTemplateFile('html/ajaxwindow.xhtml');
        $game->setMacro('html/colonymacros.xhtml/show_ship_beamto');
        $game->setTemplateVar('targetShip', $target);
        $game->setTemplateVar('COLONY', $colony);
    }
}
