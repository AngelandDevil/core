<?php

namespace Stu\Orm\Repository;

use Doctrine\Persistence\ObjectRepository;
use Stu\Orm\Entity\StarSystemInterface;
use Stu\Orm\Entity\StarSystemMapInterface;

/**
 * @method null|StarSystemMapInterface find(integer $id)
 */
interface StarSystemMapRepositoryInterface extends ObjectRepository
{
    /**
     * @return StarSystemMapInterface[]
     */
    public function getBySystemOrdered(int $starSystemId): array;

    public function getByCoordinates(
        int $starSystemId,
        int $sx,
        int $sy
    ): ?StarSystemMapInterface;

    /**
     * @return StarSystemMapInterface[]
     */
    public function getByCoordinateRange(
        StarSystemInterface $starSystem,
        int $startSx,
        int $endSx,
        int $startSy,
        int $endSy
    ): array;

    public function getRandomFieldsForAstroMeasurement(int $starSystemId): array;

    public function save(StarSystemMapInterface $starSystemMap): void;
}
