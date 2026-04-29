# Jarvis MVP — Voice Loop Test

MVP minimo: ascolta microfono → trascrive → Claude → risposta vocale.

## Setup

### 1. Crea API key Anthropic

Vai su https://console.anthropic.com/settings/keys → crea API key.

Setta env var Windows (PowerShell admin):
```powershell
setx ANTHROPIC_API_KEY "sk-ant-..."
```

Chiudi e riapri terminale.

### 2. Installa dipendenze

```powershell
cd C:\Users\Giovanni\jarvis-test
pip install SpeechRecognition pyttsx3 anthropic pyaudio
```

Se `pyaudio` fallisce su Windows:
```powershell
pip install pipwin
pipwin install pyaudio
```

### 3. Avvia

Doppio click `setup.bat` oppure:
```powershell
python jarvis.py
```

## Uso

- Parla in italiano dopo "[ascolto...]"
- Jarvis risponde in voce
- Dire "stop", "ferma", "esci" o "basta" per uscire
- Ctrl+C per stop forzato

## Struttura

- `jarvis.py` — script principale
- `setup.bat` — installer + launcher
- `requirements.txt` — dipendenze (TODO)

## Costi

- Claude API: ~$0.001-0.01 per scambio (claude-opus-4-5)
- Google Speech-to-Text: free tier 60 min/mese (poi $0.006/15s)
- Windows TTS (pyttsx3): gratis

Stima uso casual: <$5/mese.

## Limiti MVP

- Italiano riconoscimento dipende da accento + microfono
- Voce TTS Windows nativa (robotica) — upgrade a ElevenLabs/Google TTS per voce naturale
- No tools/funzioni MES (solo chat)
- No memoria persistente (riparte ad ogni avvio)
- No interfaccia grafica (solo terminale)

## Prossimi step

1. **Tools MES**: Claude function calling per query commesse, dashboard, ecc
2. **TTS migliore**: ElevenLabs API (voce naturale italiana)
3. **Whisper locale**: invece di Google STT (privacy + offline)
4. **UI Three.js**: arc reactor animato
5. **Hand tracking**: MediaPipe gesture control
