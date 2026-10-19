# app[art]ment Dashboard

Plugin WordPress con widget contatti app[art]ment, riepilogo Site Kit e Stato WooCommerce, se disponibili per l'utente. Gli altri widget vengono rimossi dalla bacheca.

## Installazione

Scaricare `appartment-dashboard.zip` dalla [release più recente](https://github.com/theappartment/appartment-dashboard/releases/latest), caricarlo da **Plugin → Aggiungi nuovo → Carica plugin** e attivarlo. Chi usa la versione 1.0.x deve caricare manualmente la versione 1.1.0 una volta.

## Pubblicare aggiornamenti

1. Incrementare `Version` e la versione CSS nel file principale, e `Stable tag` nel readme.
2. Eseguire `sh scripts/package.sh` per verificare e creare lo ZIP.
3. Salvare le modifiche nel repository e inviarle a GitHub.
4. Creare una release stabile con tag corrispondente, per esempio `v1.1.1`, allegando **appartment-dashboard.zip**. Il pacchetto deve contenere la cartella `appartment-dashboard/`.

WordPress leggerà le release stabili tramite l'API GitHub e proporrà l'aggiornamento. La cache dura 6 ore; **Bacheca → Aggiornamenti → Verifica di nuovo** la svuota. Gli aggiornamenti automatici si possono abilitare dalla schermata Plugin. Nessun token GitHub richiesto sui siti.

Non usare gli archivi automatici “Source code” come pacchetto installabile. Repository privati e versioni prerelease non sono supportati.

## Verifica

Il comando di packaging controlla la sintassi e scenari simulati per i widget e l'updater. L'installazione e l'aggiornamento su una vera istanza WordPress richiedono una verifica su staging. Dettagli nel readme del plugin.
