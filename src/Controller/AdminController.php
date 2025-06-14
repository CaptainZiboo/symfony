<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\Notification;
use App\Form\ProductForm;
use App\Form\ProductType;
use App\Message\AddPointsToActiveUsers;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin', name: 'app_admin')]
    public function index(ProductRepository $productRepository, UserRepository $userRepository): Response
    {
        return $this->render('admin/index.html.twig', [
            'products' => $productRepository->findAll(),
            'users' => $userRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/product/new', name: 'admin_product_new')]
    public function newProduct(Request $request, EntityManagerInterface $em): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductForm::class, $product);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $product->setCreatedBy($this->getUser());
            $em->persist($product);

            // Notification admin
            $notification = new Notification();
            $notification->setLabel(sprintf(
                'Ajout du produit "%s" (%s) par %s %s (%s) le %s à %s.',
                $product->getName(),
                $product->getId(),
                $this->getUser()->getFirstName(),
                $this->getUser()->getLastName(),
                $this->getUser()->getEmail(),
                (new \DateTime())->format('d/m/Y'),
                (new \DateTime())->format('H:i')
            ));
            $notification->setUser($this->getUser());
            $em->persist($notification);

            $em->flush();

            $this->addFlash('success', 'Produit ajouté.');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('admin/product_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/product/{id}/edit', name: 'admin_product_edit')]
    public function editProduct(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        if ($product->getCreatedBy() !== $this->getUser()) {
            throw new AccessDeniedException('You can only edit your own products.');
        }

        $form = $this->createForm(ProductForm::class, $product);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Notification admin
            $notification = new Notification();
            $notification->setLabel(sprintf(
                'Modification du produit "%s" (%s) par %s %s (%s) le %s à %s.',
                $product->getName(),
                $product->getId(),
                $this->getUser()->getFirstName(),
                $this->getUser()->getLastName(),
                $this->getUser()->getEmail(),
                (new \DateTime())->format('d/m/Y'),
                (new \DateTime())->format('H:i')
            ));
            $notification->setUser($this->getUser());
            $em->persist($notification);

            $em->flush();

            $this->addFlash('success', 'Produit modifié.');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('admin/product_form.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/product/{id}/delete', name: 'admin_product_delete', methods: ['POST'])]
    public function deleteProduct(Product $product, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($product->getCreatedBy() !== $this->getUser()) {
            throw new AccessDeniedException('You can only delete your own products.');
        }

        $em->remove($product);

        // Notification admin
        $notification = new Notification();
        $notification->setLabel(
            sprintf(
                'Suppression du produit "%s" (%s) par %s %s (%s) le %s à %s.',
                $product->getName(),
                $product->getId(),
                $user->getFirstName(),
                $user->getLastName(),
                $user->getEmail(),
                (new \DateTime())->format('d/m/Y'),
                (new \DateTime())->format('H:i')
            )
        );
        $notification->setUser($this->getUser());
        $em->persist($notification);

        $em->flush();

        $this->addFlash('success', 'Produit supprimé.');
        return $this->redirectToRoute('app_admin');
    }


    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/products', name: 'admin_products')]
    public function products(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        return $this->render('admin/products.html.twig', [
            'products' => $products,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/users', name: 'admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/user/{id}/toggle', name: 'admin_user_toggle', methods: ['POST'])]
    public function toggleUser(User $user, EntityManagerInterface $em): Response
    {
        $user->setActive(!$user->isActive());
        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('admin_users');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/users/add-points', name: 'admin_users_add_points', methods: ['POST'])]
    public function addPointsToActiveUsers(MessageBusInterface $bus): Response
    {
        $bus->dispatch(new AddPointsToActiveUsers());

        $this->addFlash('success', 'La demande d\'ajout de points a été envoyée (traitée en tâche de fond).');
        return $this->redirectToRoute('app_admin');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/notifications', name: 'admin_notifications')]
    public function notifications(NotificationRepository $notificationRepository): Response
    {
        $notifications = $notificationRepository->findBy([], ['createdAt' => 'DESC']);
        return $this->render('admin/notifications.html.twig', [
            'notifications' => $notifications,
        ]);
    }
}