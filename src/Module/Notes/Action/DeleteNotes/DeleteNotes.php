<?php

declare(strict_types=1);

namespace Stu\Module\Notes\Action\DeleteNotes;

use AccessViolation;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Orm\Repository\NoteRepositoryInterface;

final class DeleteNotes implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_DELETE_NOTES';

    private $deleteNotesRequest;

    private $noteRepository;

    public function __construct(
        DeleteNotesRequestInterface $deleteNotesRequest,
        NoteRepositoryInterface $noteRepository
    ) {
        $this->deleteNotesRequest = $deleteNotesRequest;
        $this->noteRepository = $noteRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        foreach ($this->deleteNotesRequest->getNoteIds() as $noteId) {
            $obj = $this->noteRepository->find($noteId);
            if ($obj === null) {
                continue;
            }
            if ($obj->getUserId() !== $game->getUser()->getId()) {
                throw new AccessViolation();
            }
            $this->noteRepository->delete($obj);
        }

        $game->addInformation(_('Die ausgewählten Notizen wurden gelöscht'));
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
