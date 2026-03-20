"""
Batch converter ArtiosCAD .ARD → PDF
Usa pywinauto per automazione UI di ArtiosCAD (Esko).

Flusso per ogni file:
  1. Ctrl+O → finestra standard "Apri" → inserisci percorso → Enter
  2. File → Esporta → PDF → finestra impostazioni driver → OK
  3. Finestra standard "Salva con nome" → inserisci percorso → Enter
  4. Chiudi file (Ctrl+W) senza salvare

Uso: py -3 convert_ard.py

IMPORTANTE:
- Non toccare mouse/tastiera durante l'esecuzione
- Lanciare di sera/notte per file numerosi (3488 file ~ diverse ore)
- ArtiosCAD deve essere CHIUSO prima di lanciare lo script
"""

import os
import sys
import time
import glob
import logging
import subprocess
import argparse
from datetime import datetime

try:
    from pywinauto import Application, keyboard
except ImportError:
    print("ERRORE: pywinauto non installato. Esegui: py -3 -m pip install pywinauto")
    sys.exit(1)

# ============================================================
# CONFIGURAZIONE — MODIFICA QUESTI PERCORSI
# ============================================================
CARTELLA_ARD = r"\\CARTOTECNICA\Users\mirko.GRNAPPA\Desktop\BACKUP PROGETTI"
CARTELLA_PDF = os.path.join(CARTELLA_ARD, "PDF_EXPORT")
ARTIOSCAD_EXE = r"C:\Esko\Artios\ArtiosCAD21.03\Program\artioscad.exe"

# Timeout e pause (secondi)
TIMEOUT_OPEN = 15       # Attesa apertura file
PAUSA_TRA_FILE = 2      # Pausa tra un file e l'altro
PAUSA_DOPO_EXPORT = 5   # Pausa dopo ogni export

# ============================================================
# SETUP LOGGING
# ============================================================
os.makedirs(CARTELLA_PDF, exist_ok=True)
log_file = os.path.join(CARTELLA_ARD, f"convert_log_{datetime.now().strftime('%Y%m%d_%H%M%S')}.txt")
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.FileHandler(log_file, encoding='utf-8'),
        logging.StreamHandler()
    ]
)
log = logging.getLogger(__name__)


def trova_file_ard():
    """Trova tutti i file .ARD nella cartella sorgente (ricorsivo)."""
    pattern = os.path.join(CARTELLA_ARD, "**", "*.ard")
    files = glob.glob(pattern, recursive=True)
    # Cerca anche .ARD maiuscolo (Windows è case-insensitive ma glob no)
    pattern2 = os.path.join(CARTELLA_ARD, "**", "*.ARD")
    files2 = glob.glob(pattern2, recursive=True)
    tutti = list(set(files + files2))
    # Escludi file nella cartella di output
    tutti = [f for f in tutti if CARTELLA_PDF not in f]
    tutti.sort()
    return tutti


def avvia_artioscad():
    """Avvia ArtiosCAD e attendi che sia pronto."""
    log.info(f"Avvio ArtiosCAD: {ARTIOSCAD_EXE}")
    subprocess.Popen([ARTIOSCAD_EXE])
    time.sleep(12)  # Attendi avvio iniziale

    # Connettiti al processo
    for tentativo in range(10):
        try:
            app = Application(backend='uia').connect(path=ARTIOSCAD_EXE, timeout=10)
            log.info("ArtiosCAD connesso!")
            time.sleep(3)
            return app
        except Exception:
            log.info(f"Attesa connessione... tentativo {tentativo+1}/10")
            time.sleep(5)

    log.error("Impossibile connettersi ad ArtiosCAD")
    sys.exit(1)


def chiudi_popup_iniziale(app):
    """Chiude l'eventuale popup 'What's new' all'avvio."""
    for _ in range(3):
        try:
            time.sleep(2)
            main_win = app.top_window()
            main_win.set_focus()
            time.sleep(0.5)
            # Prova a cliccare X o premere Esc/Enter per chiudere
            try:
                close_btn = main_win.child_window(title="Chiudi", control_type="Button")
                if close_btn.exists(timeout=1):
                    close_btn.click()
                    continue
            except Exception:
                pass
            keyboard.send_keys('{VK_ESCAPE}')
            time.sleep(1)
            keyboard.send_keys('{ENTER}')
            time.sleep(1)
        except Exception:
            pass


def apri_file(app, filepath):
    """Apre un file .ARD in ArtiosCAD con Ctrl+O.
    La finestra Apri di ArtiosCAD è personalizzata (non standard Windows).
    Il campo 'Nome file:' è in basso — usiamo diversi metodi per trovarlo.
    """
    log.info(f"Apertura: {os.path.basename(filepath)}")
    try:
        main_win = app.top_window()
        main_win.set_focus()
        time.sleep(0.5)

        # Ctrl+O per aprire
        keyboard.send_keys('^o')
        time.sleep(3)

        # Trova la finestra di dialogo "Apri"
        dlg = None
        for tentativo in range(5):
            try:
                dlg = app.window(title_re='.*([Aa]pri|[Oo]pen).*')
                if dlg.exists(timeout=3):
                    break
            except Exception:
                pass
            time.sleep(1)

        if dlg is None or not dlg.exists(timeout=2):
            log.warning("Finestra Apri non trovata")
            keyboard.send_keys('{VK_ESCAPE}')
            return False

        dlg.set_focus()
        time.sleep(0.5)

        # Trova il campo "Nome file:" — finestra personalizzata ArtiosCAD
        edit = None

        # Metodo 1: cerca Edit per titolo
        for label in ["Nome file:", "File name:", "Nome File:", "Nome file"]:
            try:
                edit = dlg.child_window(title=label, control_type="Edit")
                if edit.exists(timeout=1):
                    break
                edit = None
            except Exception:
                edit = None

        # Metodo 2: cerca qualsiasi Edit nella finestra
        if edit is None:
            try:
                edits = dlg.children(control_type="Edit")
                if edits:
                    edit = edits[-1]  # L'ultimo Edit è di solito "Nome file"
            except Exception:
                pass

        # Metodo 3: cerca ComboBox con Edit dentro
        if edit is None:
            try:
                combos = dlg.children(control_type="ComboBox")
                for combo in combos:
                    try:
                        edit = combo.child_window(control_type="Edit")
                        if edit.exists(timeout=1):
                            break
                        edit = None
                    except Exception:
                        edit = None
            except Exception:
                pass

        # Metodo 4: Tab fino al campo nome file e scrivi
        if edit is None:
            log.info("  Campo non trovato con UI, provo con Tab...")
            dlg.set_focus()
            time.sleep(0.3)
            # Tab fino al campo nome file (di solito in fondo)
            for _ in range(15):
                keyboard.send_keys('{TAB}')
                time.sleep(0.1)
            keyboard.send_keys('^a')  # Seleziona tutto
            time.sleep(0.1)
            keyboard.send_keys(filepath, with_spaces=True, pause=0.01)
            time.sleep(0.5)
            keyboard.send_keys('{ENTER}')
            time.sleep(TIMEOUT_OPEN)
            return True

        # Usa il campo Edit trovato
        edit.set_focus()
        time.sleep(0.3)
        try:
            edit.set_text("")
        except Exception:
            keyboard.send_keys('^a')
            time.sleep(0.1)
        keyboard.send_keys(filepath, with_spaces=True, pause=0.01)
        time.sleep(0.5)
        keyboard.send_keys('{ENTER}')
        time.sleep(TIMEOUT_OPEN)

        return True
    except Exception as e:
        log.error(f"Errore apertura: {e}")
        keyboard.send_keys('{VK_ESCAPE}')
        return False


def esporta_pdf(app, nome_base):
    """
    Esporta il file corrente in PDF.
    Flusso: File → Esporta → PDF → OK (impostazioni driver) → Salva con nome
    """
    output_path = os.path.join(CARTELLA_PDF, nome_base + '.pdf')
    log.info(f"  Export PDF: {nome_base}")
    try:
        main_win = app.top_window()
        main_win.set_focus()
        time.sleep(0.5)

        # --- Step 1: Naviga menu File → Esporta → PDF ---
        # Usa la barra dei menu tramite pywinauto
        try:
            main_win.menu_select("File->Esporta->PDF")
            time.sleep(2)
        except Exception:
            # Fallback: navigazione via tastiera
            log.info("  Menu select fallito, provo via tastiera...")
            keyboard.send_keys('%f')  # Alt+F
            time.sleep(1.5)
            # Naviga fino a "Esporta" — è verso la fine del menu
            # Cerchiamo di cliccare direttamente
            try:
                menu = main_win.child_window(title="Esporta", control_type="MenuItem")
                menu.click_input()
                time.sleep(1)
                submenu = main_win.child_window(title="PDF", control_type="MenuItem")
                submenu.click_input()
                time.sleep(2)
            except Exception:
                # Ultimo fallback: usa frecce tastiera
                log.info("  Provo navigazione con frecce...")
                keyboard.send_keys('{VK_ESCAPE}')
                time.sleep(0.5)
                keyboard.send_keys('%f')
                time.sleep(1)
                # Scorri fino a Esporta (circa 15 voci giù)
                for _ in range(18):
                    keyboard.send_keys('{DOWN}')
                    time.sleep(0.1)
                keyboard.send_keys('{RIGHT}')  # Apri sottomenu
                time.sleep(0.5)
                # Scorri fino a PDF nel sottomenu
                for _ in range(17):
                    keyboard.send_keys('{DOWN}')
                    time.sleep(0.1)
                keyboard.send_keys('{ENTER}')
                time.sleep(2)

        # --- Step 2: Finestra impostazioni driver PDF → clicca OK ---
        dlg_driver = None
        for tentativo in range(5):
            try:
                # La finestra ha titolo "PDF" con bottoni OK/Annulla
                dlg_driver = app.window(title="PDF")
                if dlg_driver.exists(timeout=3):
                    break
            except Exception:
                pass
            time.sleep(1)

        if dlg_driver and dlg_driver.exists(timeout=2):
            log.info("  Finestra impostazioni PDF trovata, clicco OK...")
            dlg_driver.set_focus()
            time.sleep(0.3)
            try:
                btn_ok = dlg_driver.child_window(title="OK", control_type="Button")
                btn_ok.click()
            except Exception:
                keyboard.send_keys('{ENTER}')
            time.sleep(2)
        else:
            log.warning("  Finestra impostazioni driver PDF non trovata")
            keyboard.send_keys('{VK_ESCAPE}')
            return False

        # --- Step 3: Finestra standard "Salva con nome" ---
        dlg_salva = None
        for tentativo in range(5):
            try:
                dlg_salva = app.window(title_re='.*([Ss]alva|[Ss]ave).*')
                if dlg_salva.exists(timeout=3):
                    break
            except Exception:
                pass
            time.sleep(1)

        if dlg_salva is None or not dlg_salva.exists(timeout=2):
            log.warning("  Finestra Salva con nome non trovata")
            keyboard.send_keys('{VK_ESCAPE}')
            return False

        dlg_salva.set_focus()
        time.sleep(0.5)

        # Trova campo nome file
        edit = None
        for label in ["Nome file:", "File name:", "Nome File:"]:
            try:
                edit = dlg_salva.child_window(title=label, control_type="Edit")
                if edit.exists(timeout=2):
                    break
            except Exception:
                continue

        if edit is None or not edit.exists(timeout=2):
            try:
                edit = dlg_salva.child_window(control_type="ComboBox", found_index=0).child_window(control_type="Edit")
            except Exception:
                log.error("  Campo nome file non trovato in Salva con nome")
                keyboard.send_keys('{VK_ESCAPE}')
                return False

        edit.set_focus()
        edit.set_text("")
        time.sleep(0.3)
        edit.type_keys(output_path, with_spaces=True, pause=0.01)
        time.sleep(0.5)
        keyboard.send_keys('{ENTER}')
        time.sleep(PAUSA_DOPO_EXPORT)

        # Gestisci eventuale popup "Sovrascrivi?"
        try:
            confirm = app.window(title_re='.*([Cc]onferm|[Ss]ostitui|[Rr]eplace|[Oo]verwrite).*')
            if confirm.exists(timeout=2):
                # Cerca bottone Si/Yes
                for label in ['&Sì', 'Sì', 'Si', '&Si', '&Yes', 'Yes']:
                    try:
                        btn = confirm.child_window(title=label, control_type="Button")
                        if btn.exists(timeout=1):
                            btn.click()
                            break
                    except Exception:
                        continue
                time.sleep(1)
        except Exception:
            pass

        log.info(f"  PDF salvato: {output_path}")
        return True

    except Exception as e:
        log.error(f"  Errore export PDF: {e}")
        keyboard.send_keys('{VK_ESCAPE}')
        time.sleep(0.5)
        keyboard.send_keys('{VK_ESCAPE}')
        return False


def chiudi_file(app):
    """Chiude il file corrente senza salvare."""
    try:
        main_win = app.top_window()
        main_win.set_focus()
        time.sleep(0.3)
        keyboard.send_keys('^w')  # Ctrl+W per chiudere
        time.sleep(1.5)

        # Se chiede "Vuoi salvare?", clicca "No"
        try:
            dlg = app.window(title_re='.*([Ss]alv|[Ss]ave|ArtiosCAD).*')
            if dlg.exists(timeout=2):
                for label in ['&No', 'No', 'Non salvare', "Don't Save"]:
                    try:
                        btn = dlg.child_window(title=label, control_type="Button")
                        if btn.exists(timeout=1):
                            btn.click()
                            break
                    except Exception:
                        continue
                time.sleep(1)
        except Exception:
            pass
    except Exception as e:
        log.warning(f"  Errore chiusura file: {e}")


def main():
    print("=" * 60)
    print("  ArtiosCAD Batch Converter  ARD → PDF")
    print("=" * 60)
    print()

    log.info(f"Cartella sorgente: {CARTELLA_ARD}")
    log.info(f"Cartella PDF:      {CARTELLA_PDF}")
    print()

    # Trova file ARD
    files = trova_file_ard()
    if not files:
        log.error(f"Nessun file .ARD trovato in: {CARTELLA_ARD}")
        sys.exit(1)

    # Controlla quanti sono già convertiti
    gia_fatti = 0
    da_fare = []
    for f in files:
        nome_base = os.path.splitext(os.path.basename(f))[0]
        if os.path.exists(os.path.join(CARTELLA_PDF, nome_base + '.pdf')):
            gia_fatti += 1
        else:
            da_fare.append(f)

    log.info(f"File .ARD totali:    {len(files)}")
    log.info(f"Già convertiti:      {gia_fatti}")
    log.info(f"Da convertire:       {len(da_fare)}")
    print()

    if not da_fare:
        log.info("Tutti i file sono già stati convertiti!")
        sys.exit(0)

    # Limita numero file se richiesto
    parser = argparse.ArgumentParser()
    parser.add_argument('--limit', type=int, default=0, help='Numero massimo di file da convertire (0=tutti)')
    parser.add_argument('--no-launch', action='store_true', help='Non avviare ArtiosCAD, connettiti a quello già aperto')
    args = parser.parse_args()
    if args.limit > 0:
        da_fare = da_fare[:args.limit]
        log.info(f"Limitato a:          {len(da_fare)} file")

    # Chiedi conferma
    risposta = input(f"Convertire {len(da_fare)} file in PDF? (s/n): ").strip().lower()
    if risposta != 's':
        print("Annullato.")
        sys.exit(0)

    # Avvia o connetti ArtiosCAD
    if args.no_launch:
        log.info("Connessione ad ArtiosCAD già aperto...")
        try:
            app = Application(backend='uia').connect(path=ARTIOSCAD_EXE, timeout=15)
            log.info("ArtiosCAD connesso!")
        except Exception:
            log.error("ArtiosCAD non trovato. Aprilo manualmente prima di lanciare lo script.")
            sys.exit(1)
    else:
        app = avvia_artioscad()
        chiudi_popup_iniziale(app)

    # Contatori
    successi = 0
    errori = []
    inizio = datetime.now()

    for i, filepath in enumerate(da_fare, 1):
        nome_base = os.path.splitext(os.path.basename(filepath))[0]
        log.info(f"\n{'='*50}")
        log.info(f"File {i}/{len(da_fare)}: {nome_base}")
        log.info(f"{'='*50}")

        # Apri file
        if not apri_file(app, filepath):
            errori.append((filepath, "Errore apertura"))
            continue

        time.sleep(2)  # Attendi caricamento completo

        # Export PDF
        if esporta_pdf(app, nome_base):
            successi += 1
        else:
            errori.append((filepath, "Errore export PDF"))

        # Chiudi file
        chiudi_file(app)
        time.sleep(PAUSA_TRA_FILE)

        # Progresso ogni 10 file
        if i % 10 == 0:
            elapsed = datetime.now() - inizio
            media = elapsed.total_seconds() / i
            rimanenti = (len(da_fare) - i) * media
            log.info(f"  Progresso: {i}/{len(da_fare)} — "
                     f"~{int(rimanenti/60)} min rimanenti")

    # Report finale
    durata = datetime.now() - inizio
    print()
    print("=" * 60)
    log.info("REPORT FINALE")
    log.info(f"File da convertire: {len(da_fare)}")
    log.info(f"PDF creati:         {successi}")
    log.info(f"Errori:             {len(errori)}")
    log.info(f"Durata:             {durata}")

    if errori:
        log.info("\nFile con errori:")
        for filepath, motivo in errori:
            log.info(f"  {os.path.basename(filepath)} — {motivo}")

    log.info(f"\nLog salvato in: {log_file}")
    print("=" * 60)


if __name__ == '__main__':
    main()
