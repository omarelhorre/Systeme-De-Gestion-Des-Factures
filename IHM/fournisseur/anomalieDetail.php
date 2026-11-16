<a href="<?= link ?>declaration/anomalies">← Retour aux anomalies</a>
<h1>Corriger la déclaration #<?= (int)$anomalie['id'] ?></h1>
<p>Client : <strong><?= texteAnomalie($anomalie['prenom'] . ' ' . $anomalie['nom']) ?></strong> — relevé du <?= texteAnomalie($anomalie['dateSaisie']) ?></p>
<p>Adresse : <?= texteAnomalie($anomalie['adresse']) ?></p>
<p class="notice"><?= texteAnomalie($anomalie['statut']) ?></p>
<?php if ($erreur !== ''): ?><p class="correction-error" role="alert"><?= texteAnomalie($erreur) ?></p><?php endif; ?>
<?php if ($precedente): ?>
    <p>Dernier relevé valide du <?= texteAnomalie($precedente['dateSaisie']) ?> : eau <?= texteAnomalie($precedente['indexEau']) ?> m³, électricité <?= texteAnomalie($precedente['indexElectricite']) ?> kWh.</p>
<?php endif; ?>
<div class="meter-photos">
    <figure><img src="<?= link ?>declaration/photoEau/<?= (int)$anomalie['id'] ?>" alt="Photo du compteur d’eau"><figcaption>Compteur d’eau</figcaption></figure>
    <figure><img src="<?= link ?>declaration/photoElectricite/<?= (int)$anomalie['id'] ?>" alt="Photo du compteur d’électricité"><figcaption>Compteur d’électricité</figcaption></figure>
</div>
<?php if ($precedente): ?>
<form method="post" action="<?= link ?>declaration/corriger/<?= (int)$anomalie['id'] ?>" class="correction-form">
    <input type="hidden" name="csrf" value="<?= texteAnomalie($_SESSION['csrf_anomalies']) ?>">
    <label>Index eau corrigé (m³)<input type="text" inputmode="decimal" name="indexEau" value="<?= texteAnomalie($_POST['indexEau'] ?? $anomalie['indexEau']) ?>" required></label>
    <label>Index électricité corrigé (kWh)<input type="text" inputmode="decimal" name="indexElectricite" value="<?= texteAnomalie($_POST['indexElectricite'] ?? $anomalie['indexElectricite']) ?>" required></label>
    <label><input type="checkbox" name="confirmation" value="oui" required> J’ai vérifié les photos et les index corrigés. La facture sera calculée avec ces valeurs.</label>
    <button class="connexion-btn" type="submit">Valider la correction</button>
</form>
<?php else: ?>
<p class="correction-error">Aucun relevé précédent valide. La correction ne peut pas être facturée.</p>
<?php endif; ?>
