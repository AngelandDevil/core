<?php

declare(strict_types=1);

namespace Stu\Module\Starmap\View\ShowSystemEditField;

use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Orm\Entity\StarSystemMapInterface;
use Stu\Orm\Repository\MapFieldTypeRepositoryInterface;
use Stu\Orm\Repository\StarSystemMapRepositoryInterface;

final class ShowSystemEditField implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_SYSTEM_EDITFIELD';

    private $showSystemEditFieldRequest;

    private $mapFieldTypeRepository;

    private $starSystemMapRepository;

    public function __construct(
        ShowSystemEditFieldRequestInterface $showSystemEditFieldRequest,
        MapFieldTypeRepositoryInterface $mapFieldTypeRepository,
        StarSystemMapRepositoryInterface $starSystemMapRepository
    ) {
        $this->showSystemEditFieldRequest = $showSystemEditFieldRequest;
        $this->mapFieldTypeRepository = $mapFieldTypeRepository;
        $this->starSystemMapRepository = $starSystemMapRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $possibleFieldTypes = ['row_0', 'row_1', 'row_2', 'row_3', 'row_4', 'row_5'];
        foreach ($this->mapFieldTypeRepository->findAll() as $key => $value) {
            if ($value->getIsSystem()) {
                continue;
            }
            $possibleFieldTypes['row_' . ($key % 6)][] = $value;
        }

        /** @var StarSystemMapInterface $selectedField */
        $field = $this->starSystemMapRepository->find($this->showSystemEditFieldRequest->getFieldId());

        $game->setPageTitle(_('Feld wählen'));
        $game->setTemplateFile('html/ajaxwindow.xhtml');
        $game->setMacro('html/macros.xhtml/mapeditor_system_fieldselector');
        $game->setTemplateVar('POSSIBLE_FIELD_TYPES', $possibleFieldTypes);
        $game->setTemplateVar('SELECTED_MAP_FIELD', $field);
    }
}