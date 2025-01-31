<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Crew\Lib\CrewCreatorInterface;
use Stu\Module\Ship\Lib\ShipCreatorInterface;
use Stu\Orm\Repository\MapRepositoryInterface;
use Stu\Orm\Repository\ShipBuildplanRepositoryInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;
use Stu\Orm\Repository\TorpedoTypeRepositoryInterface;
use Stu\Orm\Repository\UserRepositoryInterface;

@session_start();

require_once __DIR__ . '/../../../Config/Bootstrap.php';

$db = $container->get(EntityManagerInterface::class);

$db->beginTransaction();

$container->get(GameControllerInterface::class)->sessionAndAdminCheck();

$buildplanRepo = $container->get(ShipBuildplanRepositoryInterface::class);
$torpedoTypeRepo = $container->get(TorpedoTypeRepositoryInterface::class);
$userRepo = $container->get(UserRepositoryInterface::class);
$shipCreator = $container->get(ShipCreatorInterface::class);
$shipRepo = $container->get(ShipRepositoryInterface::class);
$crewCreator = $container->get(CrewCreatorInterface::class);
$mapRepo = $container->get(MapRepositoryInterface::class);

$userId = request::indInt('userId');
$buildplanId = request::indInt('buildplanId');
$torptypeId = request::indInt('torptypeId');
$noTorps = request::indInt('noTorps');

if ($torptypeId > 0 || $noTorps) {

    $plan = $buildplanRepo->find($buildplanId);
    $cx = request::postIntFatal('cx');
    $cy = request::postIntFatal('cy');
    $shipcount = request::postIntFatal('shipcount');

    for ($i = 0; $i < $shipcount; $i++) {
        $ship = $shipCreator->createBy($userId, $plan->getRump()->getId(), $plan->getId());
        $outerMap = $mapRepo->getByCoordinates($cx, $cy);
        $ship->setMap($outerMap);
        $ship->setEps($ship->getMaxEps());
        $ship->setWarpcoreLoad($ship->getWarpcoreCapacity());
        $ship->setShield($ship->getMaxShield());
        $ship->setEBatt($ship->getMaxEBatt());

        if ($torptypeId > 0) {
            $torp_obj = $torpedoTypeRepo->find($torptypeId);
            $ship->setTorpedo($torp_obj);
            $ship->setTorpedoCount($ship->getMaxTorpedos());
        }

        $shipRepo->save($ship);
        $db->flush();

        for ($j = 1; $j <= $plan->getCrew(); $j++) {
            $crewCreator->create($userId);
        }
        $db->flush();

        $crewCreator->createShipCrew($ship);
        $db->flush();
    }

    echo $shipcount . ' Schiff(e) erstellt, mit Wurstblinkern!';
} else {
    if ($buildplanId > 0) {

        printf(
            '<form action="" method="post">
            <input type="hidden" name="userId" value="%d" />
            <input type="hidden" name="buildplanId" value="%d" />
            <input type="hidden" name="cx" value="%d" />
            <input type="hidden" name="cy" value="%d" />
            <input type="hidden" name="shipcount" value="%d" />
            ',
            $userId,
            $buildplanId,
            request::postIntFatal('cx'),
            request::postIntFatal('cy'),
            request::postIntFatal('shipcount')
        );

        $plan = $buildplanRepo->find($buildplanId);

        $possibleTorpedoTypes = $torpedoTypeRepo->getByLevel((int) $plan->getRump()->getTorpedoLevel());

        if (empty($possibleTorpedoTypes)) {
            printf(
                '<input type="hidden" name="noTorps" value="1" />
                Schiff kann keine Torpedos tragen'
            );
        } else {
            foreach ($possibleTorpedoTypes as $torpType) {
                printf(
                    '<input type="radio" name="torptypeId" value="%d" />%s<br />',
                    $torpType->getId(),
                    $torpType->getName()
                );
            }
        }

        printf(
            '<br /><br />
            <input type="submit" value="Schiff erstellen" /></form>'
        );
    } else {
        if ($userId > 0) {
            $buildplans = $buildplanRepo->getByUser($userId);

            printf(
                '<form action="" method="post">
                <input type="hidden" name="userId" value="%d" />',
                $userId
            );

            foreach ($buildplans as $plan) {
                printf(
                    '<input type="radio" name="buildplanId" value="%d" />%s<br />',
                    $plan->getId(),
                    $plan->getName()
                );
            }

            printf(
                '<br /><br />
                Koordinaten<br /><input type="text" size="3" name="cx" /> | <input type="text" size="3" name="cy" /><br />
                Anzahl<br /><input type="text" size="3" name="shipcount" value="1"/><br /><br />
                <input type="submit" value="weiter zu Torpedo-Auswahl" /></form>'
            );
        } else {
            foreach ($userRepo->getNpcList() as $user) {
                printf(
                    '<a href="?userId=%d">%s</a><br />',
                    $user->getId(),
                    $user->getUserName()
                );
            }
            foreach ($userRepo->getNonNpcList() as $user) {
                printf(
                    '<a href="?userId=%d">%s</a><br />',
                    $user->getId(),
                    $user->getUserName()
                );
            }
        }
    }
}

$db->commit();
