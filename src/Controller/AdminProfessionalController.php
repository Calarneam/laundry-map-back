<?php

namespace App\Controller;

use App\Entity\Administrator;
use App\Entity\Enum\ProfessionalInteractionHistoryAction;
use App\Entity\Enum\ProfessionalStatus;
use App\Entity\Professional;
use App\Entity\ProfessionalInteractionHistory;
use App\Repository\ProfessionalRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/pro', name: 'admin_professionals_')]
class AdminProfessionalController extends AbstractApiController
{
    /**
     * Get all pending professional accounts
     * GET /api/admin/pro/pending
     */
    #[Route('/pending', name: 'list_pending', methods: ['GET'])]
    public function listPendingProfessionals(
        UserRepository $userRepository,
        int $page = 1,
        int $limit = 10
    ): JsonResponse {
        try {

            return $this->json([
                'message' => 'api.messages.success',
                'data' => $userRepository->findPendingProfessionals($page, $limit)
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update professional account status (approve or reject)
     * PATCH /api/admin/pro/{id}/status
     */
    #[Route('/{id}/status', name: 'update_status', methods: ['PATCH'])]
    public function updateProfessionalStatus(
        int $id,
        Request $request,
        ProfessionalRepository $professionalRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            // Validate required fields
            if (!isset($data['status']) || !isset($data['reason'])) {
                return $this->json([
                    'error' => 'api.messages.missing_fields'
                ], Response::HTTP_BAD_REQUEST);
            }

            $status = $data['status'];
            $reason = $data['reason'];

            // Validate status value
            if (!in_array($status, ['validated', 'refused'])) {
                return $this->json([
                    'error' => 'api.messages.invalid_status'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate reason is not empty
            if (empty($reason) || !is_string($reason)) {
                return $this->json([
                    'error' => 'api.messages.reason_required'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Get professional
            $professional = $professionalRepository->find($id);
            if (!$professional) {
                return $this->json([
                    'error' => 'api.messages.professional_not_found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Check if professional is in pending status
            if ($professional->getStatus() !== ProfessionalStatus::Pending) {
                return $this->json([
                    'error' => 'api.messages.professional_not_pending'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Get current admin
            $admin = $this->getUser();
            
            // Create interaction history
            $history = new ProfessionalInteractionHistory();
            $history->setAdministrator($admin);
            $history->setProfessional($professional);
            $history->setActionReason($reason);
            $history->setDate(new \DateTimeImmutable());

            // Apply action
            if ($status === 'validated') {
                // Accept professional
                $professional->setStatus(ProfessionalStatus::Validated);
                $professional->setValidationDate(new \DateTimeImmutable());

                $history->setAction(ProfessionalInteractionHistoryAction::Validated);
                
                // Send email notification
                $this->sendAcceptanceEmail($professional, $reason, $mailer);

                $message = 'api.messages.professional_approved';
            } else {
                // Reject professional
                $professional->setStatus(ProfessionalStatus::Refused);

                $history->setAction(ProfessionalInteractionHistoryAction::Refused);
                
                // Send email notification
                $this->sendRejectionEmail($professional, $reason, $mailer);

                $message = 'api.messages.professional_rejected';
            }
            
            
            $entityManager->persist($history);
            $entityManager->flush();

            return $this->json([
                'message' => $message,
                'data' => [
                    'id' => $professional->getId(),
                    'status' => $professional->getStatus()->value,
                    'companyName' => $professional->getCompanyName(),
                    'email' => $professional->getUser()->getEmail(),
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Send acceptance email to professional
     */
    private function sendAcceptanceEmail(Professional $professional, string $reason, MailerInterface $mailer): void
    {
        $email = (new Email())
            ->from('noreply@laundrymap.com')
            ->to($professional->getUser()->getEmail())
            ->subject('Votre compte professionnel a été approuvé')
            ->html($this->renderAcceptanceTemplate($professional, $reason));

        try {
            $mailer->send($email);
        } catch (\Exception $e) {
            // Log error but don't throw - email is not critical
        }
    }

    /**
     * Send rejection email to professional
     */
    private function sendRejectionEmail(Professional $professional, string $reason, MailerInterface $mailer): void
    {
        $email = (new Email())
            ->from('noreply@laundrymap.com')
            ->to($professional->getUser()->getEmail())
            ->subject('Décision concernant votre compte professionnel')
            ->html($this->renderRejectionTemplate($professional, $reason));

        try {
            $mailer->send($email);
        } catch (\Exception $e) {
            // Log error but don't throw - email is not critical
        }
    }

    /**
     * Render acceptance email template
     */
    private function renderAcceptanceTemplate(Professional $professional, string $reason): string
    {
        return <<<HTML
        <h2>Bienvenue sur LaundryMap!</h2>
        <p>Nous sommes heureux de vous confirmer que votre compte professionnel a été approuvé.</p>
        <p><strong>Entreprise:</strong> {$professional->getCompanyName()}</p>
        <p><strong>Motif:</strong> {$reason}</p>
        <p>Vous pouvez désormais accéder à toutes les fonctionnalités de la plateforme.</p>
        <p>Cordialement,<br/>L'équipe LaundryMap</p>
        HTML;
    }

    /**
     * Render rejection email template
     */
    private function renderRejectionTemplate(Professional $professional, string $reason): string
    {
        return <<<HTML
        <h2>Décision concernant votre compte professionnel</h2>
        <p>Nous regrettons de vous informer que votre demande de compte professionnel sur LaundryMap a été refusée.</p>
        <p><strong>Entreprise:</strong> {$professional->getCompanyName()}</p>
        <p><strong>Motif:</strong> {$reason}</p>
        <p>Si vous avez des questions, veuillez nous contacter.</p>
        <p>Cordialement,<br/>L'équipe LaundryMap</p>
        HTML;
    }
}

