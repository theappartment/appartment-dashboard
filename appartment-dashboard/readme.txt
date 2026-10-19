=== app[art]ment Dashboard ===
Contributors: appartment
Requires at least: 6.2
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPL-2.0-or-later

Bacheca WordPress essenziale con contatti app[art]ment.

== Installazione ==
1. Da Plugin > Aggiungi nuovo plugin > Carica plugin, selezionare appartment-dashboard.zip.
2. Installare e attivare. Aprire Bacheca.

== Comportamento ==
Mostra esclusivamente il riquadro powered by e, quando disponibili:
- Il riepilogo originale Site Kit, con setup completato e accesso autenticato o condiviso autorizzato da Site Kit.
- Il riepilogo originale Stato WooCommerce per gli utenti autorizzati. Vendite, ordini e scorte dipendono dalle funzioni e dai permessi WooCommerce.

Gli altri widget e il pannello di benvenuto standard vengono rimossi dalla bacheca durante la richiesta. Non vengono cancellate impostazioni o dati. Gli avvisi amministrativi standard vengono raccolti nel Centro notifiche.
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

== Centro notifiche ==
Dalla 1.2.0 gli avvisi amministrativi standard sono nascosti per impostazione predefinita. Aprire Notifiche nella barra superiore o il pulsante fisso in basso per consultare quelli della schermata corrente e usare le azioni originali. Il pulsante Mostra avvisi nelle pagine disattiva il nascondimento; la preferenza è locale al browser, separata per utente e sito.
La pagina Notifiche nel menu mostra lo storico con ricerca, filtro per tipo e cancellazione del proprio archivio. Vengono raccolti gli avvisi delle pagine visitate, anche quelli aggiunti dinamicamente; non è possibile recuperare retroattivamente avvisi mai visualizzati o già eliminati da altri plugin.
Lo storico contiene solo testo, tipo, pagina di origine e data; è separato per utente, sito e contesto amministrativo. Massimo 100 voci, finestra visibile di 30 giorni; le voci scadute vengono rimosse al successivo salvataggio. Nessun dato viene inviato a servizi esterni. Tornare alla pagina di origine per verificare se un avviso è ancora attuale ed eseguire azioni.
Le validazioni nei moduli, i messaggi inline, le notifiche interne del block editor e i banner con markup non standard non vengono spostati. La funzione richiede JavaScript; se disabilitato gli avvisi restano visibili normalmente. Non sopprime le callback degli altri plugin e non cancella i loro avvisi.

== Changelog ==
= 1.2.0 =
Aggiunge nascondimento degli avvisi standard, pannello della schermata corrente con contatore e azioni originali, storico personale con ricerca, filtri e cancellazione. Supporta avvisi dinamici e contesti sito, rete e utente.

= 1.1.3 =
Release senza modifiche funzionali per verificare il rilevamento e l’installazione degli aggiornamenti GitHub dalla versione 1.1.2.

= 1.1.2 =
Corregge Verifica di nuovo: le versioni 1.1.0 e 1.1.1 potevano riutilizzare la cache GitHub per 6 ore anche durante la verifica manuale. Installare manualmente 1.1.2 per applicare subito la correzione oppure attendere il normale controllo dopo la scadenza della cache.
