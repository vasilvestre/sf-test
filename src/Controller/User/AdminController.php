<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\User\Application\Query\GetUsersQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for user administration.
 */
#[Route('/admin/users', name: 'admin_users_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $queryBus
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(50, (int) $request->query->get('limit', 20)));
        $role = $request->query->get('role');

        $query = new GetUsersQuery($page, $limit, $role);
        $envelope = $this->queryBus->dispatch($query);
        $result = $envelope->last(HandledStamp::class)->getResult();

        return $this->render('user/admin/index.html.twig', [
            'users' => $result['users'],
            'total' => $result['total'],
            'page' => $result['page'],
            'limit' => $result['limit'],
            'role_filter' => $role,
            'total_pages' => ceil($result['total'] / $result['limit']),
        ]);
    }
}