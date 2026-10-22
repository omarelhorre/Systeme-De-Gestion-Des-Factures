<?php if (!isset($facture) || !$facture) { http_response_code(404); exit; } ?>
<a href="?action=liste">← Retour aux factures</a>
<h1>Facture #<?= (int)$facture['id'] ?></h1>
<dl>
<dt>Client</dt><dd><?= texteFacture($facture['nom'].' '.$facture['prenom']) ?></dd>
<dt>Email</dt><dd><?= texteFacture($facture['email']) ?></dd>
<dt>Adresse</dt><dd><?= texteFacture($facture['adresse']) ?></dd>
<dt>Date de génération</dt><dd><?= texteFacture($facture['dateGeneration']) ?></dd>
<dt>Période de facturation</dt><dd><?= texteFacture($facture['dateDebut']) ?> — <?= texteFacture($facture['dateFin'] ?? 'Non renseignée') ?></dd>
<dt>Montant TTC</dt><dd><?= $facture['prixTTC'] === null ? 'Non renseigné' : texteFacture(number_format((float)$facture['prixTTC'], 2, ',', ' ')).' DH' ?></dd>
<dt>État</dt><dd><?= texteFacture($facture['etat']) ?></dd>
<dt>Date de paiement</dt><dd><?= texteFacture($facture['datePaiement'] ?? 'Aucun paiement enregistré') ?></dd>
</dl>
<div class="filters"><a class="filter" target="_blank" rel="noopener" href="?action=pdf&amp;id=<?= (int)$facture['id'] ?>">Ouvrir le PDF</a><a class="filter" href="?action=pdf&amp;id=<?= (int)$facture['id'] ?>&amp;telecharger=1">Télécharger le PDF</a></div>
<?php if ($facture['etat'] === 'Impayée' && $facture['datePaiement'] === null) { ?>
<form method="post" action="?action=payer&amp;id=<?= (int)$facture['id'] ?>" class="payment">
<input type="hidden" name="csrf" value="<?= texteFacture($_SESSION['csrf_factures']) ?>">
<label><input type="checkbox" name="confirmation" value="oui" required> Je confirme avoir reçu le montant de cette facture en espèces.</label>
<p>La date du paiement sera enregistrée aujourd’hui. Cette confirmation est définitive.</p>
<button class="connexion-btn" type="submit">Marquer comme payée</button>
</form>
<?php } ?>
