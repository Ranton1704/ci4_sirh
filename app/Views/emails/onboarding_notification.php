<p>Bonjour,</p>
<p>Une tâche d'onboarding vous concerne :</p>
<ul>
    <li><strong>Item :</strong> <?= esc($item['titre']) ?></li>
    <li><strong>Employé :</strong> #<?= esc($item['employe_id']) ?></li>
    <li><strong>Catégorie :</strong> <?= esc($item['categorie']) ?></li>
    <li><strong>Statut :</strong> <?= esc($item['statut']) ?></li>
    <?php if (! empty($item['date_echeance'])): ?>
    <li><strong>Échéance :</strong> <?= esc($item['date_echeance']) ?></li>
    <?php endif ?>
</ul>
<?php if (! empty($item['description'])): ?>
<p><?= nl2br(esc($item['description'])) ?></p>
<?php endif ?>
<p>— SIRH, module Onboarding</p>
