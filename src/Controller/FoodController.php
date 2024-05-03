<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\Food;
use App\Repository\FoodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use OpenApi\Attributes as OA;

#[Route('api/food', name: 'app_api_food_')]
class FoodController extends AbstractController
{
  public function __construct(private EntityManagerInterface $manager, private FoodRepository $repository, private SerializerInterface $serializer, private UrlGeneratorInterface $urlGenerator)
  {
  }

  #[Route(name: 'new', methods: 'POST')]
  #[
    OA\Post(
      path: "/api/food",
      summary: "Créer un plat",
      requestBody: new OA\RequestBody(
        required: true,
        description: "Données du plat à créer",
        content: new OA\JsonContent(
          type: "object",
          properties: [
            new OA\Property(property: "title", type: "string", example: "Titre du plat"),
            new OA\Property(property: "description", type: "string", example: "Description du Plat"),
            new OA\Property(property: "price", type: "integer", example: "50"),
          ]
        )
      ),
      responses: [new OA\Response(
        response: 201,
        description: "Plat créé avec succès",
        content: new OA\JsonContent(
          type: "object",
          properties: [
            new OA\Property(property: "id", type: "integer", example: "1"),
            new OA\Property(property: "title", type: "string", example: "Titre du plat"),
            new OA\Property(property: "description", type: "string", example: "Description du Plat"),
            new OA\Property(property: "price", type: "integer", example: "50"),
            new OA\Property(property: "createdAt", type: "string", format: "date-time"),
            new OA\Property(property: "updatedAt", type: "string", format: "date-time"),

          ]
        )
      )]
    )
  ]
  public function new(Request $request): JsonResponse
  {
    $food = $this->serializer->deserialize($request->getContent(), Food::class, 'json');
    $food->setCreatedAt(new DateTimeImmutable());
    $this->manager->persist($food);
    $this->manager->flush();
    $responseData = $this->serializer->serialize($food, 'json');
    $location = $this->urlGenerator->generate(
      'app_api_Plat_show',
      ['id' => $food->getId()],
      UrlGeneratorInterface::ABSOLUTE_URL,
    );
    return new JsonResponse($responseData, Response::HTTP_CREATED, ["Location" => $location], true);
  }

  #[Route('/{id}', name: 'show', methods: 'GET')]
  #[
    OA\Get(
      path: "/api/food/{id}",
      summary: "Afficher un plat par son ID",
      parameters: [new OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "ID du plat à afficher",
        schema: new OA\Schema(type: "integer")
      )],
      responses: [
        new OA\Response(
          response: 200,
          description: "Plat trouvé avec succès",
          content: new OA\JsonContent(
            type: "object",
            properties: [
              new OA\Property(property: "id", type: "integer", example: "1"),
              new OA\Property(property: "title", type: "string", example: "Titre du plat"),
              new OA\Property(property: "description", type: "string", example: "Description du Plat"),
              new OA\Property(property: "price", type: "integer", example: "50"),
              new OA\Property(property: "createdAt", type: "string", format: "date-time"),
              new OA\Property(property: "updateddAt", type: "string", format: "date-time"),
            ]
          )
        ),
        new OA\Response(
          response: 404,
          description: "Plat non trouvé"
        )
      ]
    )
  ]
  public function show(int $id): JsonResponse
  {
    $food = $this->repository->findOneBy(['id' => $id]);

    if ($food) {
      $responseData = $this->serializer->serialize($food, 'json');

      return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }

    return new JsonResponse(null, Response::HTTP_NOT_FOUND);
  }


  #[Route('/{id}', name: 'edit', methods: 'PUT')]
  #[
    OA\Put(
      path: "/api/food/{id}",
      summary: "Modifier un plat par son ID",
      parameters: [new OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "ID du plat à modifier",
        schema: new OA\Schema(type: "integer")
      )],
      requestBody: new OA\RequestBody(
        required: true,
        description: "Données du plat à modifier",
        content: new OA\JsonContent(
          type: "object",
          properties: [
            new OA\Property(property: "title", type: "string", example: "Titre du plat modifié"),
            new OA\Property(property: "description", type: "string", example: "Description du Plat modifié"),
            new OA\Property(property: "price", type: "integer", example: "100"),
          ]
        )
      ),
      responses: [
        new OA\Response(
          response: 204,
          description: "Plat modifié avec succès",
        ),
        new OA\Response(
          response: 404,
          description: "Plat non trouvé"
        )
      ]
    )
  ]
  public function edit(int $id, Request $request): JsonResponse
  {
    $food = $this->repository->findOneBy(['id' => $id]);

    if ($food) {
      $restaurant = $this->serializer->deserialize(
        $request->getContent(),
        Food::class,
        'json',
        [AbstractNormalizer::OBJECT_TO_POPULATE => $food]
      );
      $food->setUpdatedAt(new DateTimeImmutable());
      $this->manager->flush();

      return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    return new JsonResponse(null, Response::HTTP_NOT_FOUND);
  }

  #[Route('/{id}', name: 'delete', methods: 'DELETE')]
  #[
    OA\Delete(
      path: "/api/food/{id}",
      summary: "Supprimer un plat par son ID",
      parameters: [new OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "ID du plat à supprimer",
        schema: new OA\Schema(type: "integer")
      )],
      responses: [
        new OA\Response(
          response: 204,
          description: "Plat supprimé avec succès",
        ),
        new OA\Response(
          response: 404,
          description: "Plat non trouvé"
        )
      ]
    )
  ]
  public function delete(int $id): JsonResponse
  {
    $food = $this->repository->findOneBy(['id' => $id]);

    if ($food) {
      $this->manager->remove($food);
      $this->manager->flush();

      return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    return new JsonResponse(null, Response::HTTP_NOT_FOUND);
  }
}
