<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AlertGenerator;
use App\Domain\Service\MonthlySummaryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class DashboardController extends BaseController
{
    public function __construct(
        Twig $view,
        private readonly MonthlySummaryService $monthlySummaryService,
        private readonly AlertGenerator $alertGenerator
        // TODO: add necessary services here and have them injected by the DI container
    )
    {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: parse the request parameters
        $userId= (int)$_SESSION['user_id'];
        $queryParams= $request->getQueryParams();
        // TODO: load the currently logged-in user
        // TODO: get the list of available years for the year-month selector
        // TODO: call service to generate the overspending alerts for current month
        // TODO: call service to compute total expenditure per selected year/month
        // TODO: call service to compute category totals per selected year/month
        // TODO: call service to compute category averages per selected year/month

        $year=(int)($queryParams['year'] ?? date('Y'));
        $month=(int)($queryParams['month'] ?? date('m'));


        $alerts=$this->alertGenerator->generate($userId, $year, $month);
        $availableYears=$this->monthlySummaryService->getAvailableYears($userId);

        return $this->render($response, 'dashboard.twig', [
            'year'                  => $year,
            'month'                 => $month,
            'alerts'                => $alerts,
            'totalForMonth'         => $this->monthlySummaryService->computeTotalExpenditure($userId, $year, $month),
            'totalsForCategories'   => $this->monthlySummaryService->computePerCategoryTotals($userId, $year, $month),
            'averagesForCategories' => $this->monthlySummaryService->computePerCategoryAverages($userId, $year, $month),
            'availableYears'        => $availableYears,
        ]);
    }
}
