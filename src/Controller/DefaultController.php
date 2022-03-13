<?php

namespace App\Controller;

use App\Email\Mailer;
use App\Entity\Contact;
use App\Form\NewPasswordType;

use App\Security\UserConfirmationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'default_index')]
    public function index(): Response
    {
        return $this->render('default/home.html.twig');
    }

    #[Route("/confirm-user/{token}", name: "default_confirm_token")]
    public function confirmUser(string $token, Request $request, UserConfirmationService $userConfirmationService)
    {

        $form = $this->createForm(NewPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //vérification on ne sait jamais
            $password = $request->request->get("new_password")["plainPassword"]["first"];
            $confirmPassword = $request->request->get("new_password")["plainPassword"]["second"];

            if ($password == $confirmPassword) {
                $userConfirmationService->confirmUser($token, $password);
            }


            return $this->redirect('http://localhost:8081');
        }

        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
            'form' => $form->createView()
        ]);
    }


    #[Route("api/meeting/email/{id}", name: "default_meeting_email")]
    public function confirmMettingEmail(Contact $contact, Mailer $mailer)
    {
        $mailer->sendMeetingMailV2($contact);

        return new JsonResponse('ok', Response::HTTP_OK);
    }

    #[Route("validate/date/{id}/{uid}", name: "default_date_validation")]
    public function contactDateValidation(Contact  $contact, string $uid,  EntityManagerInterface $em)
    {
        $meetingDate = null;

        if ($contact->getManagement()["contactAdministrationMeeting"]["isUserValidation"]) {
            //vérifier si une date n'a pas été sélectionnée.
            foreach ($contact->getManagement()["contactAdministrationMeeting"]["proposedDates"] as $key => $dateValue) {

                if ($dateValue["isOk"]) {
                    $meetingDate = $dateValue["newDate"];
                }
            }
        } else {

            //vérifier si une date n'a pas été sélectionnée.
            foreach ($contact->getManagement()["contactAdministrationMeeting"]["proposedDates"] as $key => $dateValue) {


                if ($dateValue["uid"]  == $uid && !$dateValue["isOk"]) {
                    $newManagement = $contact->getManagement();
                    $newManagement["contactAdministrationMeeting"]["proposedDates"][$key]["isOk"] = true;
                    $newManagement["contactAdministrationMeeting"]["isUserValidation"]  = true;

                    $contact->setManagement($newManagement);
                    $contact->setState("Réponse du candidat ok.");
                    $em->flush();

                    $meetingDate = $dateValue["newDate"];
                }
            }
        }

        return $this->render('email/date_validation/user_date_validation.html.twig', compact('meetingDate'));
    }


    #[Route("/test/email", name: "test_email_api")]
    public function testEmailApi(TokenStorageInterface $tokenStorageInterface)
    {
        dd($tokenStorageInterface->getToken());
        return $this->render('email/test_email.html.twig', [
            "name" => "<p><strong>je suis ici dans mon template</strong> je suis la</p> "
        ]);
    }
}
