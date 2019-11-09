<?php

declare(strict_types=1);

namespace Stu\Module\Communication\View\ShowPlotKn;

use Stu\Component\Communication\Kn\KnFactoryInterface;
use Stu\Component\Communication\Kn\KnItemInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Communication\View\ShowKnPlot\ShowKnPlot;
use Stu\Orm\Entity\KnPostInterface;
use Stu\Orm\Repository\KnPostRepositoryInterface;
use Stu\Orm\Repository\RpgPlotRepositoryInterface;

final class ShowPlotKn implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_PLOTKN';

    private const KNLIMITER = 6;

    private $showPlotKnRequest;

    private $knPostRepository;

    private $rpgPlotRepository;

    private $knFactory;

    public function __construct(
        ShowPlotKnRequestInterface $showPlotKnRequest,
        KnPostRepositoryInterface $knPostRepository,
        RpgPlotRepositoryInterface $rpgPlotRepository,
        KnFactoryInterface $knFactory
    ) {
        $this->showPlotKnRequest = $showPlotKnRequest;
        $this->knPostRepository = $knPostRepository;
        $this->rpgPlotRepository = $rpgPlotRepository;
        $this->knFactory = $knFactory;
    }

    public function handle(GameControllerInterface $game): void
    {
        $user = $game->getUser();

        $plot = $this->rpgPlotRepository->find($this->showPlotKnRequest->getPlotId());

        if ($plot === null) {
            return;
        }
        $mark = $this->showPlotKnRequest->getKnOffset();

        if ($mark % static::KNLIMITER != 0 || $mark < 0) {
            $mark = 0;
        }
        $maxcount = $this->knPostRepository->getAmountByPlot((int) $plot->getId());
        $maxpage = ceil($maxcount / static::KNLIMITER);
        $curpage = floor($mark / static::KNLIMITER);
        $knNavigation = [];
        if ($curpage != 0) {
            $knNavigation[] = ["page" => "<<", "mark" => 0, "cssclass" => "pages"];
            $knNavigation[] = ["page" => "<", "mark" => ($mark - static::KNLIMITER), "cssclass" => "pages"];
        }
        for ($i = $curpage - 1; $i <= $curpage + 3; $i++) {
            if ($i > $maxpage || $i < 1) {
                continue;
            }
            $knNavigation[] = [
                "page" => $i,
                "mark" => ($i * static::KNLIMITER - static::KNLIMITER),
                "cssclass" => ($curpage + 1 == $i ? "pages selected" : "pages")
            ];
        }
        if ($curpage + 1 != $maxpage) {
            $knNavigation[] = ["page" => ">", "mark" => ($mark + static::KNLIMITER), "cssclass" => "pages"];
            $knNavigation[] = ["page" => ">>", "mark" => $maxpage * static::KNLIMITER - static::KNLIMITER, "cssclass" => "pages"];
        }

        $game->setTemplateFile('html/plotkn.xhtml');
        $game->appendNavigationPart('comm.php', _('KommNet'));
        $game->appendNavigationPart('comm.php?SHOW_PLOTLIST=1', _('Plots'));
        $game->appendNavigationPart(
            sprintf('comm.php?%s=1&plotid=%s', ShowKnPlot::VIEW_IDENTIFIER, $plot->getId()),
            $plot->getTitle()
        );
        $game->appendNavigationPart(
            sprintf('comm.php?%s=1&plotid=%s', static::VIEW_IDENTIFIER, $plot->getId()),
            _('Beiträge')
        );
        $game->setPageTitle("Plot: " . $plot->getTitle());

        $game->setTemplateVar(
            'KN_POSTINGS',
            array_map(
                function (KnPostInterface $knPost) use ($user): KnItemInterface {
                    return $this->knFactory->createKnItem(
                        $knPost,
                        $user
                    );
                },
                $this->knPostRepository->getByPlot($plot, $mark, static::KNLIMITER)
            )
        );
        $game->setTemplateVar('PLOT', $plot);
        $game->setTemplateVar('KN_OFFSET', $mark);
        $game->setTemplateVar('KN_NAVIGATION', $knNavigation);
    }
}
