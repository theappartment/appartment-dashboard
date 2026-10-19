=== app[art]ment Dashboard ===
Contributors: appartment
Requires at least: 6.2
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPL-2.0-or-later

Bacheca WordPress essenziale con contatti app[art]ment.

== Installazione ==
1. Da Plugin > Aggiungi nuovo plugin > Carica plugin, selezionare appartment-dashboard.zip.
2. Installare e attivare. Aprire Bacheca.

== Comportamento ==
Mostra esclusivamente il riquadro powered by e, quando disponibili:
- Il riepilogo originale Site Kit, con setup completato e accesso autenticato o condiviso autorizzato da Site Kit.
- Il riepilogo originale Stato WooCommerce per gli utenti autorizzati. Vendite, ordini e scorte dipendono dalle funzioni e dai permessi WooCommerce.

Gli altri widget e il pannello di benvenuto standard vengono rimossi dalla bacheca durante la richiesta. Non vengono cancellate impostazioni o dati. Le notifiche amministrative restano visibili.
I widget consentiti vengono mostrati anche se precedentemente nascosti nelle Impostazioni schermata; possono ancora essere ridotti e spostati.
Il plugin non attiva o configura Site Kit e WooCommerce e non forza widget che questi plugin non registrano (per esempio se WooCommerce Admin è disabilitato).
La bacheca amministrativa di rete multisito non viene modificata.
Disattivando il plugin torna la bacheca ordinaria, secondo le preferenze già salvate.

== Marchio e contatti ==
Il widget utilizza il logo ufficiale fornito, incluso localmente in assets/logoappartment.png senza richieste esterne. Contatti e orari sono ripresi dallo screenshot e sono modificabili nel file appartment-dashboard.php. Lo stile è in assets/dashboard.css.

== Verifica ==
Verificati sintassi PHP e scenari di selezione widget con un ambiente simulato. Prima dell'utilizzo in produzione verificare su staging con le versioni installate di WordPress, Site Kit e WooCommerce, anche con ruoli diversi. Nessun sito WordPress è incluso in questo progetto.

== Aggiornamenti GitHub ==
Dalla versione 1.1.0 gli aggiornamenti provengono dalle release pubbliche di https://github.com/theappartment/appartment-dashboard.
Installare manualmente questa versione una volta per abilitare il sistema. Le successive saranno proposte nella schermata aggiornamenti di WordPress; gli aggiornamenti automatici restano una scelta dell'amministratore.
Il controllo segue la pianificazione di WordPress e usa una cache di 6 ore (15 minuti in caso di errore). Dalla versione 1.1.2, il pulsante Verifica di nuovo svuota la cache GitHub e quella degli aggiornamenti plugin prima del controllo WordPress.
Vengono considerate solo release stabili con tag vX.Y.Z o X.Y.Z e allegato appartment-dashboard.zip. Il codice sorgente ZIP generato automaticamente da GitHub non viene utilizzato.
Il plugin contatta api.github.com per le release e github.com per scaricare gli aggiornamenti; non invia credenziali o dati di analisi del sito.

== Changelog ==
= 1.1.2 =
Corregge Verifica di nuovo: le versioni 1.1.0 e 1.1.1 potevano riutilizzare la cache GitHub per 6 ore anche durante la verifica manuale. Installare manualmente 1.1.2 per applicare subito la correzione oppure attendere il normale controllo dopo la scadenza della cache.
