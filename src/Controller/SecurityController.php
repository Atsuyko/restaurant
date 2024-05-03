<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use OpenApi\Attributes as OA;



#[Route('/api', name: 'app_api_')]
class SecurityController extends AbstractController
{
    public function __construct(private EntityManagerInterface $manager, private SerializerInterface $serializer, private UserRepository $repository)
    {
    }

    #[Route('/registration', name: 'registration', methods: 'POST')]
    #[
        OA\Post(
            path: "/api/registration",
            summary: "Inscription d'un nouvel utilisateur",
            requestBody: new OA\RequestBody(
                required: true,
                description: "Données de l'utilisateur à inscrire",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "email", type: "string", example: "adresse@email.com"),
                        new OA\Property(property: "firstName", type: "string", example: "Firstname"),
                        new OA\Property(property: "lastName", type: "string", example: "Lastname"),
                        new OA\Property(property: "allergy", type: "string", example: "Allergy"),
                        new OA\Property(property: "password", type: "string", example: "Mot de passe")
                    ]
                )
            ),
            responses: [new OA\Response(
                response: 201,
                description: "Utilisateur inscrit avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "user", type: "string", example: "Nom d'utilisateur"),
                        new OA\Property(property: "apiToken", type: "string", example: "31a023e212f116124a36af14ea0c1c3806eb9378"),
                        new OA\Property(
                            property: "roles",
                            type: "array",
                            items: new OA\Items(type: "string", example: "ROLE_USER")
                        )
                    ]
                )
            )]
        )
    ]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        $user->setCreatedAt(new DateTimeImmutable());
        $this->manager->persist($user);
        $this->manager->flush();
        return new JsonResponse(
            ['user'  => $user->getUserIdentifier(), 'apiToken' => $user->getApiToken(), 'roles' => $user->getRoles()],
            Response::HTTP_CREATED
        );
    }

    #[Route('/login', name: 'login', methods: 'POST')]
    #[
        OA\Post(
            path: "/api/login",
            summary: "Connecter utilisateur",
            requestBody: new OA\RequestBody(
                required: true,
                description: "Données de l'utilisateur pour se connecter",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "username", type: "string", example: "adresse@email.com"),
                        new OA\Property(property: "password", type: "string", example: "Mot de passe")
                    ]
                )
            ),
            responses: [new OA\Response(
                response: 200,
                description: "Connexion réussie",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "user", type: "string", example: "Nom d'utilisateur"),
                        new OA\Property(property: "apiToken", type: "string", example: "31a023e212f116124a36af14ea0c1c3806eb9378"),
                        new OA\Property(
                            property: "roles",
                            type: "array",
                            items: new OA\Items(type: "string", example: "ROLE_USER")
                        )
                    ]
                )
            )]
        )
    ]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['message' => 'Missing credentials'], Response::HTTP_UNAUTHORIZED);
        }
        return new JsonResponse([
            'user'  => $user->getUserIdentifier(),
            'apiToken' => $user->getApiToken(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/account/me', name: 'account_me', methods: 'GET')]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if ($user) {
            $responseData = $this->serializer->serialize($user, 'json');

            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }


    #[Route('/account/edit', name: 'account_edit', methods: 'PUT')]
    public function edit(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if ($user) {
            $user = $this->serializer->deserialize(
                $request->getContent(),
                User::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $user]
            );
            $user->setUpdatedAt(new DateTimeImmutable());
            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
}
