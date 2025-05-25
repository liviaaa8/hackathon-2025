<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\ExpenseService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;
    private const DEFAULT_CATEGORIES=[
        'groceries','utilities',  'transport','entertainment', 'housing', 'health', 'other'
    ];

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the expenses page
        // Hints:
        // - use the session to get the current user ID
        // - use the request query parameters to determine the page number and page size
        // - use the expense service to fetch expenses for the current user
        // parse request parameters
        // TODO: obtain logged-in user ID from session
        $userId= (int)$_SESSION['user_id'];
        $queryParams= $request->getQueryParams();

        $page=max(1,(int)($request->getQueryParams()['page'] ?? 1));
        $pageSize=max(1,(int)($request->getQueryParams()['pageSize'] ?? self::PAGE_SIZE));
        $year=(int)($queryParams['year'] ?? date('Y'));
        $month=(int)($queryParams['month'] ?? date('m'));

        $expenses=$this->expenseService->list($userId,$year,$month, $page, $pageSize);
        $totalExpenses=$this->expenseService->countExpenses($userId,$year,$month);

        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $expenses,
            'page'     => $page,
            'pageSize' => $pageSize,
            'total'    => $totalExpenses,
            'year'     => $year,
            'month'    => $month,
            'availableYears'=>$this->expenseService->getAvailableYears($userId),
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the create expense page
        // Hints:
        // - obtain the list of available categories from configuration and pass to the view

        return $this->render($response, 'expenses/create.twig',
            ['categories' => self::DEFAULT_CATEGORIES , 'defaultDate' => date('Y-m-d')]);
    }

    public function store(Request $request, Response $response): Response
    {
        // TODO: implement this action method to create a new expense
        // Hints:
        // - use the session to get the current user ID
        $userId=$_SESSION['user_id'];
        // - use the expense service to create and persist the expense entity
        $formData=$request->getParsedBody();

        try{
            $this->expenseService->create(
                $userId,
                (float)$formData['amount'],
                $formData['description'],
                new \DateTimeImmutable($formData['date']),
                $formData['category']
            );

            return $response
                ->withHeader('Location', '/expenses')
                ->withStatus(302);
        }catch(\InvalidArgumentException $e){
            return $this->render($response, 'expenses/create.twig',
                ['categories'=>self::DEFAULT_CATEGORIES, 'errors'=>[$e->getMessage()], 'formData'=>$formData
            ]);

        }
        // - rerender the "expenses.create" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to display the edit expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        $userId=(int)$_SESSION['user_id'];
        $expenseId=(int)$routeParams['id'];

        $expense=$this->expenseService->findById($expenseId);

        if(!$expense || $expense->userId !== $userId){
            return $response->withStatus(403);
        }
        // - load the expense to be edited by its ID (use route params to get it)

        return $this->render($response,'expenses/edit.twig', ['expense'=>$expense, 'categories'=>self::DEFAULT_CATEGORIES]);
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not

    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to update an existing expense

        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        $userId=(int)$_SESSION['user_id'];
        $expenseId=(int)$routeParams['id'];
        $formData=$request->getParsedBody();
        try {
            $expense = $this->expenseService->findById($expenseId);

            if (!$expense || $expense->userId !== $userId) {
                return $response->withStatus(403);
            }

            $this->expenseService->update(
                $expense,
                (float)$formData['amount'],
                $formData['description'],
                new \DateTimeImmutable($formData['date']),
                $formData['category']
            );

            return $response
                ->withHeader('Location', '/expenses')
                ->withStatus(302);
        } catch (\InvalidArgumentException $e) {
            return $this->render($response, 'expenses/edit.twig', [
                'expense' => $expense,
                'categories' => self::DEFAULT_CATEGORIES,
                'errors' => [$e->getMessage()],
            ]);
        }
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to delete an existing expense
        $userId=(int)$_SESSION['user_id'];
        $expenseId=(int)$routeParams['id'];
        $expense=$this->expenseService->findById($expenseId);

        if(!$expenseId || $expense->userId !== $userId){
            return $response->withStatus(403);
        }

        // - load the expense to be edited by its ID (use route params to get it)
        $this->expenseService->delete($expenseId);
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - call the repository method to delete the expense
        // - redirect to the "expenses.index" page
        return $response
        ->withHeader('Location', '/expenses')
        ->withStatus(302);
    }

}
