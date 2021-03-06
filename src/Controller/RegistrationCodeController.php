<?php

namespace App\Controller;

use App\Entity\RegistrationCode;
use App\Form\AttributeDataTrait;
use App\Form\ImportRegistrationCodesFlow;
use App\Form\RegistrationCodeType;
use App\Import\ImportRegistrationCodeData;
use App\Import\RecordInvalidException;
use App\Import\RegistrationCsvImportHelper;
use App\Repository\RegistrationCodeRepositoryInterface;
use App\Service\AttributePersister;
use App\View\Filter\UserTypeFilter;
use Exception;
use League\Csv\Exception as LeagueException;
use Psr\Log\LoggerInterface;
use SchulIT\CommonBundle\Form\ConfirmType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/admin/registration_codes")
 */
class RegistrationCodeController extends AbstractController {

    use AttributeDataTrait;

    private const CodesPerPage = 25;

    private $repository;

    public function __construct(RegistrationCodeRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    /**
     * @Route("", name="registration_codes")
     */
    public function index(Request $request, UserTypeFilter $typeFilter) {
        $typeView = $typeFilter->handle($request->query->get('type'));
        $query = $request->query->get('q');
        $page = $request->query->getInt('page');

        $paginator = $this->repository->getPaginatedUsers(static::CodesPerPage, $page, $typeView->getCurrentType(), $query);
        $pages = 1;
        if($paginator->count() > 0) {
            $pages = ceil((float)$paginator->count() / static::CodesPerPage);
        }

        return $this->render('codes/index.html.twig', [
            'codes' => $paginator->getIterator(),
            'page' => $page,
            'pages' => $pages,
            'types' => $typeView,
            'query' => $query
        ]);
    }

    /**
     * @Route("/remove/redeemed", name="remove_redeemed_codes")
     */
    public function removeRedeemed(Request $request) {
        $form = $this->createForm(ConfirmType::class, null, [
            'message' => 'codes.remove_redeemed.confirm'
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $this->repository->removeRedeemed();
            $this->addFlash('success', 'codes.remove_redeemed.success');
            return $this->redirectToRoute('registration_codes');
        }

        return $this->render('codes/remove_redeemed.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/add", name="add_registration_code")
     */
    public function add(Request $request) {
        $code = new RegistrationCode();
        $form = $this->createForm(RegistrationCodeType::class, $code);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $this->repository->persist($code);
            $this->addFlash('success', 'codes.add.success');

            return $this->redirectToRoute('registration_codes');
        }

        return $this->render('codes/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{uuid}/edit", name="edit_registration_code")
     */
    public function edit(RegistrationCode $code, Request $request, AttributePersister $attributePersister) {
        $form = $this->createForm(RegistrationCodeType::class, $code);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $this->repository->persist($code);

            $attributeData = $this->getAttributeData($form);
            $attributePersister->persistRegistrationCodeAttributes($attributeData, $code);

            $this->addFlash('success', 'codes.edit.success');

            return $this->redirectToRoute('registration_codes');
        }

        return $this->render('codes/edit.html.twig', [
            'form' => $form->createView(),
            'code' => $code
        ]);
    }

    /**
     * @Route("/{uuid}/remove", name="remove_registration_code")
     */
    public function remove(RegistrationCode $code, Request $request) {
        $form = $this->createForm(ConfirmType::class, [], [
            'message' => 'codes.remove.confirm',
            'message_parameters' => [
                '%code%' => $code->getCode()
            ]
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $this->repository->remove($code);

            $this->addFlash('success', 'codes.remove.success');
            return $this->redirectToRoute('registration_codes');
        }

        return $this->render('codes/remove.html.twig', [
            'code' => $code,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/import", name="import_registration_codes")
     */
    public function import(ImportRegistrationCodesFlow $flow, RegistrationCsvImportHelper $helper, TranslatorInterface $translator, LoggerInterface $logger) {
        $data = new ImportRegistrationCodeData();
        $flow->bind($data);

        $form = null;

        $form = $flow->createForm();

        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                try {
                    $form = $flow->createForm();
                } catch(RecordInvalidException $e) {
                    $flow->reset();
                    $form->addError(new FormError($translator->trans('import.error.invalid_record', [
                        '%field%' => $e->getField(),
                        '%offset%' => $e->getIndex()
                    ])));
                } catch (LeagueException $e) {
                    $logger->error('Error parsing CSV file.', [
                        'exception' => $e
                    ]);
                    $flow->reset();
                    $form->addError(new FormError('import.error.csv'));
                } catch (Exception $e) {
                    $logger->error('Error parsing CSV file.', [
                        'exception' => $e
                    ]);
                    $flow->reset();
                    $form->addError(new FormError('import.error.unknown'));
                }
            } else {
                try {
                    foreach ($data->getCodes() as $code) {
                        $this->repository->persist($code);
                    }

                    $this->addFlash('success', 'import.codes.success');
                    return $this->redirectToRoute('registration_codes');
                } catch (Exception $e) {
                    $form->addError(new FormError('import.error.unknown'));

                    $logger->error('Error persisting imported registration codes.', [
                        'exception' => $e
                    ]);
                }
            }
        }

        return $this->render('codes/import.html.twig', [
            'form' => $form->createView(),
            'flow' => $flow,
            'headers' => $helper->getHeaders(),
            'required' => $helper->getRequiredHeaders()
        ]);
    }


}