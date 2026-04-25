<?php

namespace App\Controller\v1;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class TestController extends AbstractController
{
    #[Route('/v1/test', name: 'api_v1_test', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'Hello, world',
        ]);
    }
}
