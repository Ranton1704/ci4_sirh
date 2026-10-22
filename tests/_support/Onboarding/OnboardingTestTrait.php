<?php

namespace Tests\Support\Onboarding;

use CodeIgniter\Events\Events;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\Mock\MockEmail;
use Config\Email;

/**
 * Prépare la base et un faux service email.
 * La classe de test doit définir `protected $namespace = null;` (migrations App + Tests\\Support).
 */
trait OnboardingTestTrait
{
    protected MockEmail $email;

    /**
     * @var list<array<string, mixed>>
     */
    protected array $emailsSent = [];

    protected function setUpOnboarding(): void
    {
        $config                          = config(Email::class);
        $config->onboardingNotifications = true;
        $config->onboardingRecipients    = 'rh@sirh.test';

        $this->email      = new MockEmail($config);
        $this->emailsSent = [];
        Services::injectMock('email', $this->email);
        Events::removeAllListeners('email');
        Events::on('email', function (array $archive): void {
            $this->emailsSent[] = $archive;
        });
        Services::resetSingle('onboarding');

        $this->db->table('utilisateur')->insertBatch([
            ['email' => 'it@sirh.test', 'role' => 'responsable', 'actif' => true],
            ['email' => 'manager@sirh.test', 'role' => 'manager', 'actif' => true],
        ]);
    }

    protected function utilisateurId(string $email): int
    {
        return (int) $this->db->table('utilisateur')->where('email', $email)->get()->getRow()->id;
    }
}
