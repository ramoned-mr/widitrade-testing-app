<?php

namespace App\Service\User;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailManager
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private ?string $lastError = null;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    /**
     * Devuelve el último mensaje de error capturado
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Envía un correo electrónico a un usuario o dirección de correo
     *
     * @param string $subject Asunto del correo
     * @param string $template Ruta a la plantilla Twig
     * @param string $recipient Usuario o dirección de correo destinatario
     * @param array $context Variables para la plantilla
     * @return bool True si se envió con éxito, False en caso contrario
     */
    public function sendEmail(string $subject, string $template, string $recipient, array $context = []): bool
    {
        $this->lastError = null;

        try {
            if (!isset($_ENV['EMAIL_SENDER']) || empty($_ENV['EMAIL_SENDER'])) {
                $this->lastError = "EMAIL_SENDER no está configurado en el archivo .env";
                $this->logger->error($this->lastError);
                return false;
            }

            $senderEmail = $_ENV['EMAIL_SENDER'];

            $email = (new TemplatedEmail())
                ->from(new Address($senderEmail))
                ->subject($subject)
                ->htmlTemplate($template)
                ->to(new Address($recipient));

            $email->context($context);

            $this->mailer->send($email);
            return true;
        } catch (TransportExceptionInterface $e) {
            $this->lastError = 'Error de transporte al enviar el email: ' . $e->getMessage();
            $this->logger->error($this->lastError, [
                'exception' => $e,
                'recipient' => $recipient,
                'subject' => $subject,
                'template' => $template
            ]);
            return false;
        } catch (\Exception $e) {
            $this->lastError = 'Error inesperado al enviar el email: ' . $e->getMessage();
            $this->logger->error($this->lastError, [
                'exception' => $e,
                'recipient' => $recipient,
                'subject' => $subject,
                'template' => $template,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}