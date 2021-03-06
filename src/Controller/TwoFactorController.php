<?php

namespace App\Controller;

use App\Entity\U2fKey;
use App\Entity\User;
use App\Form\EnableTwoFactorType;
use App\Repository\U2fKeyRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Security\TwoFactor\BackupCodeGenerator;
use App\Security\Voter\ProfileVoter;
use App\Security\Voter\U2fKeyVoter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManager;
use SchulIT\CommonBundle\Form\ConfirmType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/two_factor")
 */
class TwoFactorController extends AbstractController {

    const TWO_FACTOR_EMAIL_CSRF_TOKEN = 'two-factor-csrf';
    const GOOGLE_SECRET_KEY = 'google-code';

    private $userRepository;

    public function __construct(UserRepositoryInterface $repository) {
        $this->userRepository = $repository;
    }

    /**
     * @Route("", name="two_factor")
     */
    public function twoFactorAuthentication(Request $request, TrustedDeviceManager $trustedDeviceManager,
                                            FirewallMap $firewallMap, CsrfTokenManagerInterface $tokenManager) {
        $this->denyAccessUnlessGranted(ProfileVoter::USE_2FA);

        /** @var User $user */
        $user = $this->getUser();
        $isGoogleEnabled = $user->isGoogleAuthenticatorEnabled();
        $backupCodes = $user->getBackupCodes();

        $csrfToken = $tokenManager
            ->getToken(static::TWO_FACTOR_EMAIL_CSRF_TOKEN);

        $isTrustedDevice = $trustedDeviceManager->isTrustedDevice($user, $firewallMap->getFirewallConfig($request)->getName());

        $isU2fEnabled = $user->isU2FAuthEnabled();

        return $this->render('profile/two_factor/index.html.twig', [
            'isGoogleEnabled' => $isGoogleEnabled,
            'isU2fEnabled' => $isU2fEnabled,
            'backupCodes' => $backupCodes,
            'csrfToken' => $csrfToken,
            'isTrustedDevice' => $isTrustedDevice,
            'u2fKeys' => $user->getU2FKeys()
        ]);
    }

    /**
     * @Route("/google/enable", name="enable_google_two_factor")
     */
    public function enableGoogleTwoFactorAuthentication(Request $request, BackupCodeGenerator $backupCodeGenerator, GoogleAuthenticator $googleAuthenticator) {
        $this->denyAccessUnlessGranted(ProfileVoter::USE_2FA);

        /** @var User $user */
        $user = $this->getUser();

        if($user->isGoogleAuthenticatorEnabled()) {
            return $this->redirectToRoute('two_factor');
        }

        $secret = $googleAuthenticator->generateSecret();
        $form = $this->createForm(EnableTwoFactorType::class, [
            'secret' => $secret
        ]);
        $form->handleRequest($request);

        /*
         * setup fake user to get QR code content
         */
        $secret = $form->get('secret')->getData();
        $fakeUser = (new User())
            ->setUsername($user->getUsername());
        $fakeUser->setGoogleAuthenticatorSecret($secret);
        $qrContent = $googleAuthenticator->getQRContent($fakeUser);

        if($form->isSubmitted() && $form->isValid()) {
            $code = $form->get('code')->getData();

            if($googleAuthenticator->checkCode($fakeUser, $code)) {
                $user->setGoogleAuthenticatorSecret($secret);
                $user->setBackupCodes($backupCodeGenerator->generateCodes());

                $this->userRepository->persist($user);

                $this->addFlash('success', 'two_factor.google.enable.success');
                return $this->redirectToRoute('two_factor');
            } else {
                $form->get('code')
                    ->addError(new FormError('two_factor.google.enable.wrong_code'));
            }
        }

        return $this->render('profile/two_factor/enable_google.html.twig', [
            'form' => $form->createView(),
            'secret' => $secret,
            'qrContent' => $qrContent
        ]);
    }

    /**
     * @Route("/google/codes/regenerate", name="regenerate_backup_codes", methods={"POST"})
     */
    public function regenerateBackupCodes(Request $request, BackupCodeGenerator $backupCodeGenerator) {
        $this->denyAccessUnlessGranted(ProfileVoter::USE_2FA);

        $token = $request->request->get('_csrf_token');

        /** @var User $user */
        $user = $this->getUser();

        if($user->isGoogleAuthenticatorEnabled() !== true) {
            return $this->redirectToRoute('two_factor');
        }

        if(!$this->isCsrfTokenValid(static::TWO_FACTOR_EMAIL_CSRF_TOKEN, $token)) {
            $this->addFlash('error', 'two_factor.invalid_csrf');
            return $this->redirectToRoute('two_factor');
        }

        $user->setBackupCodes($backupCodeGenerator->generateCodes());

        $this->userRepository->persist($user);

        $this->addFlash('success', 'two_factor.google.backup.regenreate_success');
        return $this->redirectToRoute('two_factor');
    }

    /**
     * @Route("/google/disable", name="disable_google_two_factor", methods={"POST"})
     */
    public function disableGoogleTwoFactorAuthentication(Request $request) {
        $this->denyAccessUnlessGranted(ProfileVoter::USE_2FA);

        $token = $request->request->get('_csrf_token');

        if(!$this->isCsrfTokenValid(static::TWO_FACTOR_EMAIL_CSRF_TOKEN, $token)) {
            $this->addFlash('error', 'two_factor.invalid_csrf');
            return $this->redirectToRoute('two_factor');
        }

        /** @var User $user */
        $user = $this->getUser();

        $user->setGoogleAuthenticatorSecret(null);
        $user->emptyBackupCodes();

        $this->userRepository->persist($user);

        $this->addFlash('success', 'two_factor.google.disable.success');
        return $this->redirectToRoute('two_factor');
    }

    /**
     * @Route("/u2f/{uuid}/remove", name="remove_u2f_device")
     */
    public function removeU2FDevice(U2fKey $key, Request $request, TranslatorInterface $translator, U2fKeyRepositoryInterface $repository) {
        $this->denyAccessUnlessGranted(ProfileVoter::USE_2FA);

        $this->denyAccessUnlessGranted(U2fKeyVoter::REMOVE, $key);

        $form = $this->createForm(ConfirmType::class, null, [
            'header' => $translator->trans('two_factor.u2f.remove.label'),
            'message' => $translator->trans('two_factor.u2f.remove.confirm', [
                '%name%' => $key->getName()
            ])
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $repository->remove($key);

            $this->addFlash('success', 'two_factor.u2f.remove.success');
            return $this->redirectToRoute('two_factor');
        }

        return $this->render('profile/two_factor/remove_u2f.html.twig', [
            'form' => $form->createView()
        ]);
    }
}