<?php

declare(strict_types=1);

namespace Stu\Module\Alliance\View\Topic;

use AccessViolation;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Orm\Entity\AllianceBoardTopicInterface;
use Stu\Orm\Repository\AllianceBoardPostRepositoryInterface;
use Stu\Orm\Repository\AllianceBoardTopicRepositoryInterface;

final class Topic implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_TOPIC';

    public const ALLIANCEBOARDLIMITER = 20;

    private $topicRequest;

    private $allianceBoardPostRepository;

    private $allianceBoardTopicRepository;

    public function __construct(
        TopicRequestInterface $topicRequest,
        AllianceBoardPostRepositoryInterface $allianceBoardPostRepository,
        AllianceBoardTopicRepositoryInterface $allianceBoardTopicRepository
    ) {
        $this->topicRequest = $topicRequest;
        $this->allianceBoardPostRepository = $allianceBoardPostRepository;
        $this->allianceBoardTopicRepository = $allianceBoardTopicRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $alliance = $game->getUser()->getAlliance();
        $boardId = $this->topicRequest->getBoardId();
        $topicId = $this->topicRequest->getTopicId();
        $allianceId = $alliance->getId();

        /** @var AllianceBoardTopicInterface $topic */
        $topic = $this->allianceBoardTopicRepository->find($topicId);
        if ($topic === null || $topic->getAllianceId() != $allianceId) {
            throw new AccessViolation();
        }

        $game->setPageTitle(_('Allianzforum'));

        $game->appendNavigationPart(
            sprintf('alliance.php?SHOW_ALLIANCE=1&id=%d', $allianceId),
            _('Allianz')
        );
        $game->appendNavigationPart(
            'alliance.php?SHOW_BOARDS=1',
            _('Forum')
        );
        $game->appendNavigationPart(
            sprintf(
                'alliance.php?SHOW_BOARD=1&bid=%d&id=%d',
                $boardId,
                $allianceId
            ),
            $topic->getBoard()->getName()
        );
        $game->appendNavigationPart(
            sprintf(
                'alliance.php?SHOW_TOPIC=1&bid=%d&tid=%d',
                $boardId,
                $topicId
            ),
            $topic->getName()
        );

        $game->setTemplateFile('html/allianceboardtopic.xhtml');
        $game->setTemplateVar('TOPIC', $topic);
        $game->setTemplateVar('TOPIC_NAVIGATION', $this->getTopicNavigation($topic));
        $game->setTemplateVar(
            'POSTINGS',
            $this->allianceBoardPostRepository->getByTopic(
                (int) $topic->getId(),
                static::ALLIANCEBOARDLIMITER,
                $this->topicRequest->getPageMark()
            )
        );
        $game->setTemplateVar('IS_MODERATOR', $alliance->currentUserIsBoardModerator());
    }

    private function getTopicNavigation(AllianceBoardTopicInterface $topic): array
    {
        $mark = $this->topicRequest->getPageMark();
        if ($mark % static::ALLIANCEBOARDLIMITER != 0 || $mark < 0) {
            $mark = 0;
        }
        $maxcount = $topic->getPostCount();
        $maxpage = ceil($maxcount / static::ALLIANCEBOARDLIMITER);
        $curpage = floor($mark / static::ALLIANCEBOARDLIMITER);
        $ret = array();
        if ($curpage != 0) {
            $ret[] = array("page" => "<<", "mark" => 0, "cssclass" => "pages");
            $ret[] = array("page" => "<", "mark" => ($mark - static::ALLIANCEBOARDLIMITER), "cssclass" => "pages");
        }
        for ($i = $curpage - 1; $i <= $curpage + 3; $i++) {
            if ($i > $maxpage || $i < 1) {
                continue;
            }
            $ret[] = array(
                "page" => $i,
                "mark" => ($i * static::ALLIANCEBOARDLIMITER - static::ALLIANCEBOARDLIMITER),
                "cssclass" => ($curpage + 1 == $i ? "pages selected" : "pages")
            );
        }
        if ($curpage + 1 != $maxpage) {
            $ret[] = array("page" => ">", "mark" => ($mark + static::ALLIANCEBOARDLIMITER), "cssclass" => "pages");
            $ret[] = array(
                "page" => ">>",
                "mark" => $maxpage * static::ALLIANCEBOARDLIMITER - static::ALLIANCEBOARDLIMITER,
                "cssclass" => "pages"
            );
        }
        return $ret;
    }
}
