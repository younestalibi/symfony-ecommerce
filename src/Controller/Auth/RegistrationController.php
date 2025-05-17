<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Form\RegistrationForm;
use App\Security\AppAuthenticator;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public const HOME_ROUTE = 'app_frontend_home';

    public function __construct(private EmailVerifier $emailVerifier) {}

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute(self::HOME_ROUTE);
        }
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('admin@admin.com', 'Admin bot'))
                    ->to((string) $user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('email/confirmation_email.html.twig')
            );

            // do anything else you need here, like send an email

            return $security->login($user, AppAuthenticator::class, 'main');
        }

        return $this->render('auth/registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            /** @var User $user */
            $user = $this->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email address has been verified.');
        return $this->redirectToRoute(self::HOME_ROUTE);
    }

    #[Route('/resend-verification-email', name: 'app_resend_verification_email')]
    public function resendVerificationEmail(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Your email is already verified.');
            return $this->redirectToRoute(self::HOME_ROUTE);
        }

        // Re-send the confirmation email
        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('admin@admin.com', 'Admin bot'))
                ->to((string) $user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('email/confirmation_email.html.twig')
        );

        $this->addFlash('success', 'Verification email has been resent.');

        return $this->redirectToRoute(self::HOME_ROUTE);
    }
}
