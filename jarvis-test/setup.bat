@echo off
echo === Jarvis MVP setup ===
echo.
cd /d C:\Users\Giovanni\jarvis-test

echo [1/3] Installazione dipendenze Python...
pip install SpeechRecognition pyttsx3 anthropic pyaudio
if errorlevel 1 (
    echo.
    echo Errore pyaudio. Prova:
    echo   pip install pipwin
    echo   pipwin install pyaudio
    pause
    exit /b 1
)

echo.
echo [2/3] Verifica ANTHROPIC_API_KEY...
if "%ANTHROPIC_API_KEY%"=="" (
    echo.
    echo ATTENZIONE: ANTHROPIC_API_KEY non settata.
    echo Vai su https://console.anthropic.com/settings/keys per crearne una.
    echo Poi esegui:
    echo   setx ANTHROPIC_API_KEY "sk-ant-..."
    echo Chiudi e riapri il terminale.
    pause
    exit /b 1
)
echo OK: ANTHROPIC_API_KEY trovata

echo.
echo [3/3] Avvio Jarvis...
echo Per fermare: dire "stop" o premere Ctrl+C
echo.
python jarvis.py
pause
