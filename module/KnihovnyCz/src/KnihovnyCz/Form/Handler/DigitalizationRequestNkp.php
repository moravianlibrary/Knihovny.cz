<?php

declare(strict_types=1);

namespace KnihovnyCz\Form\Handler;

use KnihovnyCz\Db\Service\NkpDigitalizationRequestsServiceInterface;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\RendererInterface;
use VuFind\Config\Config;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Form\Form;
use VuFind\Form\Handler\Email;
use VuFind\Mailer\Mailer;

/**
 * Class DigitalizationRequestNkp
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\Form\Handler
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
class DigitalizationRequestNkp extends Email
{
    /**
     * Constructor
     *
     * @param RendererInterface                         $viewRenderer View renderer
     * @param Config                                    $config       Main config
     * @param Mailer                                    $mailer       Mailer
     * @param NkpDigitalizationRequestsServiceInterface $service      Database service
     */
    public function __construct(
        RendererInterface $viewRenderer,
        Config $config,
        Mailer $mailer,
        protected NkpDigitalizationRequestsServiceInterface $service,
    ) {
        parent::__construct($viewRenderer, $config, $mailer);
    }

    /**
     * Get data from submitted form and process them.
     *
     * @param Form                 $form   Submitted form
     * @param Params               $params Request params
     * @param ?UserEntityInterface $user   Authenticated user
     *
     * @return bool
     */
    public function handle(
        Form $form,
        Params $params,
        ?UserEntityInterface $user = null
    ): bool {
        if ($user) {
            $count = $this->service->countUserRequestsInCurrentMonth($user->getCatUsername());
            if ($count >= 3) {
                $this->logError('User could add maximum 3 requests at month');
                return false;
            }
        }

        $count = $this->service->countAllRequestsInCurrentMonth();
        if ($count >= 50) {
            $this->logError('Global limit of 50 requests per month reached');
            return false;
        }

        // Send email (parent functionality)
        $result = $this->sendMainEmail($form, $params, $user);
        if (!$result) {
            return false;
        }

        // Save to database
        $this->saveToDatabase($form, $params, $user);

        $this->sendEmailToRequester($form, $params, $user);
        return true;
    }

    /**
     * Save request to database
     *
     * @param Form                 $form   Submitted form
     * @param Params               $params Request params
     * @param ?UserEntityInterface $user   Authenticated user
     *
     * @return void
     */
    protected function saveToDatabase(
        Form $form,
        Params $params,
        ?UserEntityInterface $user = null
    ): void {
        $fields = $form->mapRequestParamsToFieldValues($params->fromPost());
        $requestData = array_column($fields, 'value', 'name');

        $entity = $this->service->createEntity();
        $entity->setCatUsername($user?->getCatUsername());
        $entity->setCreated(new \DateTime());
        $entity->setRequestData(json_encode($requestData));
        $this->service->persistEntity($entity);
    }

    /**
     * Get data from submitted form and process them.
     *
     * @param Form                 $form   Submitted form
     * @param Params               $params Request params
     * @param ?UserEntityInterface $user   Authenticated user
     *
     * @return bool
     */
    public function sendMainEmail(
        Form $form,
        Params $params,
        ?UserEntityInterface $user = null
    ): bool {
        $postParams = $params->fromPost();
        $fields = $form->mapRequestParamsToFieldValues($postParams);
        $emailMessage = $this->viewRenderer->render(
            'Email/form.phtml',
            compact('fields')
        );

        [$senderName, $senderEmail] = $this->getSender($form);

        $replyToName = $params->fromPost(
            'name',
            $user ? trim($user->getFirstname() . ' ' . $user->getLastname()) : ''
        );
        $replyToEmail = $params->fromPost('email', $user?->getEmail());
        $recipients = $form->getRecipient($postParams);
        $emailSubject = $form->getEmailSubject($postParams);

        $result = true;
        foreach ($recipients as $recipient) {
            if ($recipient['email']) {
                $success = $this->sendEmail(
                    $recipient['name'] ?? '',
                    $recipient['email'],
                    $senderName,
                    $senderEmail,
                    $replyToName,
                    $replyToEmail,
                    $emailSubject,
                    $emailMessage
                );
            } else {
                $this->logError('Form recipient email missing; check recipient_email in config.ini.');
                $success = false;
            }

            $result = $result && $success;
        }
        return $result;
    }

    /**
     * Send email to requester as confirmation
     *
     * @param Form                 $form   Submitted form
     * @param Params               $params Request params
     * @param ?UserEntityInterface $user   Authenticated user
     *
     * @return void
     */
    public function sendEmailToRequester(
        Form $form,
        Params $params,
        ?UserEntityInterface $user = null
    ): void {
        $postParams = $params->fromPost();
        $this->debug('Sending confirmation email to requester', ['postParams' => $postParams]);

        // Extract citation from form data
        $fields = $form->mapRequestParamsToFieldValues($postParams);
        $requestData = array_column($fields, 'value', 'name');
        $citation = $requestData['citation'] ?? '';

        try {
            $emailMessage = $this->viewRenderer->render('Email/digitalization-confirmation-nkp.phtml', ['citation' => $citation]);
            $this->debug('Email template successfully generated');
        } catch (\Throwable $e) {
            $this->logError('Chyba při renderování šablony e-mailu: ' . $e->getMessage(), ['exception' => $e]);
            return;
        }
        $userName = $params->fromPost(
            'name',
            $user ? trim($user->getFirstname() . ' ' . $user->getLastname()) : ''
        );
        $userEmail = $params->fromPost('email', $user?->getEmail());
        $froms = $form->getRecipient($postParams);
        $from = $froms[0] ?? ['name' => $userName, 'email' => $userEmail];

        $this->debug('Confirmation email sender', [
            'senderName' => $userName,
            'senderEmail' => $userEmail,
        ]);
        $emailSubject = $form->getEmailSubject($postParams);
        $this->debug('Confirmation email subject', [
            'emailSubject' => $emailSubject,
        ]);
        $success = $this->sendEmail(
            $userName,
            $userEmail,
            $from['name'],
            $from['email'],
            $from['name'],
            $from['email'],
            $emailSubject,
            $emailMessage
        );
        if (!$success) {
            $this->logError('Odeslání potvrzovacího e-mailu žadateli selhalo', [
                'to' => $userEmail,
                'subject' => $emailSubject,
            ]);
        } else {
            $this->debug('Confirmation email to requester sent successfully', [
                'to' => $userEmail,
                'subject' => $emailSubject,
            ]);
        }
    }
}
