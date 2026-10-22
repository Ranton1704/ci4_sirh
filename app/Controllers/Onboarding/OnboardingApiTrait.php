<?php

namespace App\Controllers\Onboarding;

use App\Services\OnboardingService;

/**
 * Helpers communs aux contrôleurs REST du module Onboarding
 */
trait OnboardingApiTrait
{
    protected function service(): OnboardingService
    {
        return service('onboarding');
    }

    /**
     * Corps de la requête (JSON ou formulaire)
     */
    protected function payload(): array
    {
        $body = trim((string) $this->request->getBody());
        if ($body !== '' && str_contains($this->request->getHeaderLine('Content-Type'), 'json')) {
            $data = json_decode($body, true);

            return is_array($data) ? $data : [];
        }

        return $this->request->getPost() ?: $this->request->getRawInput();
    }

    /**
     * Utilisateur à l'origine de l'action (en-tête X-Utilisateur-Id ou champ utilisateur_id)
     */
    protected function utilisateurId(array $payload = []): ?int
    {
        $id = $this->request->getHeaderLine('X-Utilisateur-Id') ?: ($payload['utilisateur_id'] ?? null);

        return is_numeric($id) ? (int) $id : null;
    }
}
