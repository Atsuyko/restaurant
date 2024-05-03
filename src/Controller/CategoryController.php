<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\Category;
use App\Repository\CategoryRepository;
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


#[Route('api/category', name: 'app_api_category_')]
class CategoryController extends AbstractController
{
  public function __construct(private EntityManagerInterface $manager, private CategoryRepository $repository, private SerializerInterface $serializer, private UrlGeneratorInterface $urlGenerator)
  {
  }

  #[Route(name: 'new', methods: 'POST')]
  #[
    OA\Post(
      path: "/api/category",
      summary: "Créer une catégorie",
      requestBody: new OA\RequestBody(
        required: true,
        description: "Données de la catégorie à créer",
        content: new OA\JsonContent(
          type: "object",
          properties: [
            new OA\Property(property: "title", type: "string", example: "Titre de la catégorie"),
          ]
        )
      ),
      responses: [new OA\Response(
        response: 201,
        description: "Catégorie créée avec succès",
        content: new OA\JsonContent(
          type: "object",
          properties: [
            new OA\Property(property: "id", type: "integer", example: "1"),
            new OA\Property(property: "title", type: "string", example: "Nom de la Catégorie"),
            new OA\Property(property: "createdAt", type: "string", format: "date-time"),
            new OA\Property(property: "updatedAt", type: "string", format: "date-time"),
          ]
        )
      )]
    )
  ]
  public function new(Request $request): JsonResponse
  {
    $category = $this->serializer->deserialize($request->getContent(), Category::class, 'json');
    $category->setCreatedAt(new DateTimeImmutable());

    $this->manager->persist($category);
    $this->manager->flush();
    $responseData = $this->serializer->serialize($category, 'json');
    $location = $this->urlGenerator->generate(
      'app_api_category_show',
      ['id' => $category->getId()],
      UrlGeneratorInterface::ABSOLUTE_URL,
    );
    return new JsonResponse($responseData, Response::HTTP_CREATED, ["Location" => $location], true);
  }


  #[Route('/{id}', name: 'show', methods: 'GET')]
  #[
    OA\Get(
      path: "/api/category/{id}",
      summary: "Afficher une catégorie par son ID",
      parameters: [new OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "ID de la catégorie à afficher",
        schema: new OA\Schema(type: "integer")
      )],
      responses: [
        new OA\Response(
          response: 200,
          description: "Catégorie trouvée avec succès",
          content: new OA\JsonContent(
            type: "object",
            properties: [
              new OA\Property(property: "id", type: "integer", example: "1"),
              new OA\Property(property: "title", type: "string", example: "Nom de la Catégorie"),
              new OA\Property(property: "createdAt", type: "string", format: "date-time"),
              new OA\Property(property: "updatedAt", type: "string", format: "date-time"),
            ]
          )
        ),
        new OA\Response(
          response: 404,
          description: "Catégorie non trouvé"
        )
      ]
    )
  ]
  public function show(int $id): JsonResponse
  {
    $category = $this->repository->findOneBy(['id' => $id]);

    if ($category) {
      $responseData = $this->serializer->serialize($category, 'json');

      return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }

    return new JsonResponse(null, Response::HTTP_NOT_FOUND);
  }


  #[Route('/{id}', name: 'edit', methods: 'PUT')]
  #[
    OA\Put(
      path: "/api/category/{id}",
      summary: "Modifier une catégorie par son ID",
      parameters: [new OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "ID de la catégorie à modifier",
        schema: new OA\Schema(type: "integer")
      )],
      requestBody: new OA\RequestBody(
        required: true,
        description: "Données de la catégorie à créer",
        content: new OA\JsonContent(
          type: "object",
          properties: [
            new OA\Property(property: "id", type: "integer", example: "1"),
            new OA\Property(property: "title", type: "string", example: "Nom de la Catégorie modifiée"),
            new OA\Property(property: "createdAt", type: "string", format: "date-time"),
            new OA\Property(property: "updatedAt", type: "string", format: "date-time"),
          ]
        )
      ),
      responses: [
        new OA\Response(
          response: 204,
          description: "Catégorie modifiée avec succès",
        ),
        new OA\Response(
          response: 404,
          description: "Catégorie non trouvée"
        )
      ]
    )
  ]
  public function edit(int $id, Request $request): JsonResponse
  {
    $category = $this->repository->findOneBy(['id' => $id]);

    if ($category) {
      $category = $this->serializer->deserialize(
        $request->getContent(),
        Category::class,
        'json',
        [AbstractNormalizer::OBJECT_TO_POPULATE => $category]
      );
      $category->setUpdatedAt(new DateTimeImmutable());
      $this->manager->flush();

      return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    return new JsonResponse(null, Response::HTTP_NOT_FOUND);
  }

  #[Route('/{id}', name: 'delete', methods: 'DELETE')]
  #[
    OA\Delete(
      path: "/api/category/{id}",
      summary: "Supprimer une Catégorie par son ID",
      parameters: [new OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "ID de la Catégorie à supprimer",
        schema: new OA\Schema(type: "integer")
      )],
      responses: [
        new OA\Response(
          response: 204,
          description: "Catégorie supprimé avec succès",
        ),
        new OA\Response(
          response: 404,
          description: "Catégorie non trouvé"
        )
      ]
    )
  ]
  public function delete(int $id): JsonResponse
  {
    $category = $this->repository->findOneBy(['id' => $id]);

    if ($category) {
      $this->manager->remove($category);
      $this->manager->flush();

      return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    return new JsonResponse(null, Response::HTTP_NOT_FOUND);
  }
}
