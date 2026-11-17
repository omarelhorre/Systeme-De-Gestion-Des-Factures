<?php if (!isset($facture) || !$facture) { http_response_code(404); exit; } ?>
<?php // Détail d'une facture, inclus par facturesList.php. ?>
<a class="lien-retour" href="<?= link ?>facture/all"><i class="fa-solid fa-arrow-left"></i> Retour aux factures</a>
<header class="entete ligne">
    <div>
        <h1 class="titre">Facture N° <?= (int)$facture['id'] ?></h1>
        <p class="sous-titre"><?= texteFacture($facture['prenom'].' '.$facture['nom']) ?></p>
    </div>
    <?php list($libelle, $classe) = etatFacture($facture['etat'], $facture['dateGeneration'], $facture['modePaiement']); ?><span class="etat <?= $classe ?>"><?= $libelle ?></span>
</header>

<div class="grille">
    <section class="carte large">
        <h2 class="titre-carte">Montant TTC</h2>
        <p class="chiffre geant"><?= $facture['prixTTC'] === null ? '—' : texteFacture(number_format((float)$facture['prixTTC'], 2, ',', ' ')) ?><span class="unite">DH</span></p>
        <dl class="details" style="margin-top: 1.25rem">
            <dt>Client</dt><dd><?= texteFacture($facture['prenom'].' '.$facture['nom']) ?></dd>
            <dt>Email</dt><dd><?= texteFacture($facture['email']) ?></dd>
            <dt>Adresse</dt><dd><?= texteFacture($facture['adresse']) ?></dd>
            <dt>Date de génération</dt><dd><?= $jourFacture($facture['dateGeneration']) ?></dd>
            <dt>Période de facturation</dt><dd><?= $jourFacture($facture['dateDebut']) ?> — <?= $facture['dateFin'] ? $jourFacture($facture['dateFin']) : 'Non renseignée' ?></dd>
            <dt>Date de paiement</dt><dd><?= $facture['datePaiement'] ? $jourFacture($facture['datePaiement']) : 'Aucun paiement enregistré' ?></dd>
            <?php if ($facture['datePaiement']) { ?>
            <dt>Mode de paiement</dt><dd><?= $facture['modePaiement'] === 'en ligne' ? 'En ligne par carte (CMI) · transaction '.texteFacture($facture['referencePaiement']) : 'Espèces' ?></dd>
            <?php } ?>
        </dl>
    </section>

    <div class="pile" style="grid-column: span 2">
        <section class="carte">
            <h2 class="titre-carte">Document</h2>
            <div class="actions" style="margin-top: 1rem">
                <a class="bouton contour" target="_blank" rel="noopener" href="<?= link ?>facture/pdf/<?= (int)$facture['id'] ?>"><i class="fa-solid fa-file-pdf"></i> Ouvrir le PDF</a>
                <a class="bouton contour" href="<?= link ?>facture/telecharger/<?= (int)$facture['id'] ?>"><i class="fa-solid fa-download"></i> Télécharger</a>
            </div>
        </section>

        <?php if ($facture['etat'] === 'Impayée' && $facture['datePaiement'] === null) { ?>
        <section class="carte">
            <h2 class="titre-carte">Paiement en espèces</h2>
            <form class="pile" style="margin-top: 1rem; gap: 0.75rem" method="post" action="<?= link ?>facture/payer/<?= (int)$facture['id'] ?>">
                <input type="hidden" name="csrf" value="<?= texteFacture($_SESSION['csrf_factures']) ?>">
                <label class="case"><input type="checkbox" name="confirmation" value="oui" required> <span>Je confirme avoir reçu le montant de cette facture en espèces.</span></label>
                <p class="note">La date du paiement sera enregistrée aujourd’hui. Cette confirmation est définitive.</p>
                <div class="actions fin">
                    <button class="bouton accent" type="submit"><i class="fa-solid fa-check"></i> Marquer comme payée</button>
                </div>
            </form>
        </section>
        <?php } ?>
    </div>
</div>
