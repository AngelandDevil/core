<?php

declare(strict_types=1);

namespace Stu\Component\Ship\System\Type;

use Stu\Component\Ship\System\ShipSystemModeEnum;
use Stu\Component\Ship\System\ShipSystemTypeEnum;
use Stu\Component\Ship\System\ShipSystemTypeInterface;
use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;

final class WarpdriveShipSystem extends AbstractShipSystemType implements ShipSystemTypeInterface
{
    private ShipRepositoryInterface $shipRepository;

    public function __construct(
        ShipRepositoryInterface $shipRepository
    ) {
        $this->shipRepository = $shipRepository;
    }

    public function checkActivationConditions(ShipInterface $ship, &$reason): bool
    {
        if ($ship->traktorBeamToShip()) {
            $reason = _('es von einem Traktorstrahl gehalten wird');
            return false;
        }

        if (!$ship->isSystemHealthy(ShipSystemTypeEnum::SYSTEM_WARPCORE)) {
            $reason = _('der Warpkern zerstört ist');
            return false;
        }

        return true;
    }

    public function activate(ShipInterface $ship): void
    {
        $ship->cancelRepair();
        $ship->setDockedTo(null);
        $ship->getDockedShips()->clear();
        $ship->getShipSystem(ShipSystemTypeEnum::SYSTEM_WARPDRIVE)->setMode(ShipSystemModeEnum::MODE_ON);

        if ($ship->traktorBeamFromShip()) {
            if ($ship->getEps() > $this->getEnergyUsageForActivation()) {
                $traktorShip = $ship->getTraktorShip();

                $traktorShip->cancelRepair();

                $ship->setEps($ship->getEps() - $this->getEnergyUsageForActivation());

                $this->shipRepository->save($traktorShip);
            } else {
                $ship->deactivateTraktorBeam();
            }
        }
    }

    public function deactivate(ShipInterface $ship): void
    {
        $ship->getShipSystem(ShipSystemTypeEnum::SYSTEM_WARPDRIVE)->setMode(ShipSystemModeEnum::MODE_OFF);
    }
}
