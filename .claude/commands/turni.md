# Turni Settimanali — MES Grafica Nappa

Quando l'utente fornisce una tabella turni (anche in formato grezzo copia-incolla), analizzala ed esegui l'inserimento nel database.

## FORMATO INPUT

L'utente incolla una tabella con colonne:
- **dipendente** — cognome e nome (es. "MENALE BENITO")
- **ruolo** — reparto/ruolo (opzionale, non salvato nei turni)
- **date** — una o più colonne con date (es. "13/04", "14/04", ecc.)

Valori turno possibili:
- `T` = turno unico (giornata intera, ~06:00-22:00)
- `1` = primo turno (~06:00-14:00)
- `2` = secondo turno (~14:00-22:00)
- `3` = terzo turno (~22:00-06:00)
- `R` = riposo
- `F` = ferie
- vuoto = nessun turno (sabato/domenica senza lavoro)

## PROCEDURA

### Step 1: Parsa i dati
- Identifica le date dalle intestazioni colonna
- Converti date relative (es. "13/04") in date assolute (es. "2026-04-13") usando l'anno corrente
- Identifica dipendente e turno per ogni cella
- Normalizza i nomi in UPPERCASE

### Step 2: Genera il comando artisan
Aggiorna il file `app/Console/Commands/SeedTurni.php` con i nuovi dati:
- Sostituisci l'array `$giorni` con le date della settimana fornita
- Sostituisci l'array `$dati` con i dipendenti e turni forniti
- Celle vuote = non inserire (skip)

### Step 3: Committa e pusha
```
git add app/Console/Commands/SeedTurni.php
git commit -m "Turni: aggiornamento settimana DD/MM - DD/MM"
git push origin master
```

### Step 4: Istruzioni per il server
Dopo il push, dire all'utente di eseguire sul server (.60):
```powershell
git pull origin master
php artisan turni:seed
```

### Step 5: Conferma
Dire all'utente di verificare su `http://192.168.1.60/admin/turni`

## REGOLE
- La tabella DB è `turni` con colonne: `cognome_nome`, `data`, `turno`
- Usa `updateOrInsert` per non duplicare
- I nomi devono essere in UPPERCASE (tranne apostrofi tipo D'ORAZIO)
- Se l'utente fornisce date senza anno, usa l'anno corrente
- Se un dipendente ha cella vuota per un giorno, non inserire quel record
- Se il ruolo contiene info utile (es. "Stampatore 1"), ignoralo comunque — non è salvato nella tabella turni
- Non modificare la struttura della tabella, solo i dati
