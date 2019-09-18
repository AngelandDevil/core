<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\ActivateTractorBeam;

use request;
use ShipSingleAttackCycle;
use Stu\Module\Communication\Lib\PrivateMessageSenderInterface;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;
use SystemActivationWrapper;

final class ActivateTractorBeam implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_ACTIVATE_TRAKTOR';

    private $shipLoader;

    private $privateMessageSender;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        PrivateMessageSenderInterface $privateMessageSender
    ) {
        $this->shipLoader = $shipLoader;
        $this->privateMessageSender = $privateMessageSender;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowShip::VIEW_IDENTIFIER);

        $userId = $game->getUser()->getId();

        $ship = $this->shipLoader->getByIdAndUser(
            request::indInt('id'),
            $userId
        );

        if ($ship->isTraktorBeamActive()) {
            return;
        }
        if ($ship->shieldIsActive()) {
            $game->addInformation("Die Schilde sind aktiviert");
            return;
        }
        if ($ship->isDocked()) {
            $game->addInformation("Das Schiff ist angedockt");
            return;
        }
        $wrapper = new SystemActivationWrapper($ship);
        $wrapper->setVar('eps', 2);
        if ($wrapper->getError()) {
            $game->addInformation($wrapper->getError());
            return;
        }
        $target = $this->shipLoader->getById(request::getIntFatal('target'));
        if ($target->getRump()->isTrumfield()) {
            $game->addInformation("Das Trümmerfeld kann nicht erfasst werden");
            return;
        }
        if (!checkPosition($ship, $target)) {
            return;
        }
        if ($target->isBase()) {
            $game->addInformation("Die " . $target->getName() . " kann nicht erfasst werden");
            return;
        }
        if ($target->traktorbeamToShip()) {
            $game->addInformation("Das Schiff wird bereits vom Traktorstrahl der " . $target->getTraktorShip()->getName() . " gehalten");
            return;
        }
        if ($target->isInFleet() && $target->getFleetId() == $ship->getFleetId()) {
            $game->addInformation("Die " . $target->getName() . " befindet sich in der selben Flotte wie die " . $ship->getName());
            return;
        }
        if (($target->getAlertState() == ALERT_YELLOW || $target->getAlertState() == ALERT_RED) && !$target->getUser()->isFriend($userId)) {
            if ($target->isInFleet()) {
                $attacker = $target->getFleet()->getShips();
            } else {
                $attacker = $target;
            }
            $obj = new ShipSingleAttackCycle($attacker, $ship, $target->getFleetId(),$ship->getFleetId());
            $game->addInformationMergeDown($obj->getMessages());

            $this->privateMessageSender->send(
                $userId,
                (int) $target->getUserId(),
                sprintf(
                    "Die %s versucht die %s in Sektor %s mit dem Traktorstrahl zu erfassen. Folgende Aktionen wurden ausgeführt:\n%s",
                    $ship->getName(),
                    $target->getName(),
                    $ship->getSectorString(),
                    implode(PHP_EOL, $obj->getMessages())
                ),
                PM_SPECIAL_SHIP);
        }
        if ($target->shieldIsActive()) {
            $game->addInformation("Die " . $target->getName() . " kann aufgrund der aktiven Schilde nicht erfasst werden");
            return;
        }
        $target->deactivateTraktorBeam();
        $ship->setTraktorMode(1);
        $ship->setTraktorShipId($target->getId());
        $target->setTraktorMode(2);
        $target->setTraktorShipId($ship->getId());
        $target->save();
        $ship->save();
        if ($userId != $target->getUserId()) {
            $this->privateMessageSender->send(
                $userId,
                (int)$target->getUserId(),
                "Die " . $target->getName() . " wurde in SeKtor " . $ship->getSectorString() . " vom Traktorstrahl der " . $ship->getName() . " erfasst",
                PM_SPECIAL_SHIP
            );
        }
        $game->addInformation("Der Traktorstrahl wurde auf die " . $target->getName() . " gerichtet");
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
