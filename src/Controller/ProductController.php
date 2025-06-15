<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ProductController extends AbstractController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/product', name: 'app_product')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/product/{id}', name: 'app_product_view', requirements: ['id' => '\d+'])]
    public function view(Product $product): Response
    {
        return $this->render('product/view.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/product/{id}/buy', name: 'app_product_buy', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function buy(Product $product, EntityManagerInterface $em, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isActive()) {
            $this->addFlash('error', 'Votre compte est désactivé, vous ne pouvez pas acheter de produits.');
            return $this->redirectToRoute('app_product_view', ['id' => $product->getId()]);
        }

        if ($user->getPoints() < $product->getPrice()) {
            $this->addFlash('error', 'Vous n\'avez pas assez de points pour acheter ce produit.');
            return $this->redirectToRoute('app_product_view', ['id' => $product->getId()]);
        }

        $user->setPoints($user->getPoints() - $product->getPrice());
        $em->persist($user);

        $notification = new Notification();
        $notification->setLabel(sprintf(
            'L\'utilisateur %s %s (%s) a acheté le produit "%s" le %s à %s.',
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $product->getName(),
            (new \DateTime())->format('d/m/Y'),
            (new \DateTime())->format('H:i')
        ));
        $notification->setUser($user);
        $notification->setCreatedAt(new \DateTimeImmutable());
        $em->persist($notification);

        $em->flush();

        $this->addFlash(
            'success',
            sprintf(
                'Achat effectué avec succès ! Il vous reste %d points.',
                $user->getPoints()
            )
        );

        // Redirection dynamique selon la page d'origine
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }
        return $this->redirectToRoute('app_product_view', ['id' => $product->getId()]);
    }
}