"""
JARVIS MVP — voice loop minimo (Python 3.14 compatibile, senza pyaudio)
Ascolta microfono → trascrive (Google STT) → Claude → risposta vocale.
Per fermare: dire "stop" o Ctrl+C.

Setup:
  py -m pip install sounddevice numpy SpeechRecognition pyttsx3 anthropic
  setx ANTHROPIC_API_KEY "sk-ant-..."  (richiede restart shell)
  py jarvis.py
"""
import os
import sys
import io
import wave
import time
import numpy as np
import sounddevice as sd
import speech_recognition as sr
import pyttsx3
import anthropic

# === Config ===
MODEL = "claude-opus-4-5"
SAMPLE_RATE = 16000
CHANNELS = 1
SILENCE_THRESHOLD = 500  # amplitude soglia silenzio
SILENCE_DURATION = 1.5   # secondi silenzio per fine frase
MAX_RECORD = 15          # secondi max registrazione

SYSTEM_PROMPT = """Sei Jarvis, assistente AI italiano per Giovanni Pietropaolo,
responsabile IT di Grafica Nappa srl (tipografia ad Aversa).
Rispondi in italiano, breve e diretto. Massimo 2-3 frasi.
Se non sai rispondere, dillo onestamente."""

# === Setup ===
api_key = os.getenv("ANTHROPIC_API_KEY")
if not api_key:
    print("ERRORE: ANTHROPIC_API_KEY non settata.")
    print('Eseguire: setx ANTHROPIC_API_KEY "sk-ant-..."')
    print("Poi riavviare PowerShell.")
    sys.exit(1)

client = anthropic.Anthropic(api_key=api_key)
recognizer = sr.Recognizer()

# TTS Windows nativo
engine = pyttsx3.init()
engine.setProperty('rate', 180)
for voice in engine.getProperty('voices'):
    if 'italian' in voice.name.lower() or 'it' in voice.id.lower():
        engine.setProperty('voice', voice.id)
        break

conversation = []


def speak(text: str):
    print(f"\033[36mJarvis:\033[0m {text}")
    engine.say(text)
    engine.runAndWait()


def record_until_silence() -> bytes:
    """Registra audio fino a silenzio prolungato. Ritorna bytes WAV."""
    print("\033[33m[ascolto...]\033[0m", end='', flush=True)

    chunk_duration = 0.1  # secondi per chunk
    chunk_size = int(SAMPLE_RATE * chunk_duration)
    audio_chunks = []
    silence_chunks = 0
    max_silence_chunks = int(SILENCE_DURATION / chunk_duration)
    max_chunks = int(MAX_RECORD / chunk_duration)

    has_speech = False

    with sd.InputStream(samplerate=SAMPLE_RATE, channels=CHANNELS, dtype='int16') as stream:
        for _ in range(max_chunks):
            data, _ = stream.read(chunk_size)
            audio_chunks.append(data.copy())

            # Calcola amplitude RMS
            amplitude = np.abs(data).mean()

            if amplitude > SILENCE_THRESHOLD:
                silence_chunks = 0
                has_speech = True
            else:
                silence_chunks += 1

            # Se ha parlato e ora è silenzio prolungato, stop
            if has_speech and silence_chunks >= max_silence_chunks:
                break

    print(" [stop]")

    if not has_speech:
        return b""

    # Combina chunks in WAV bytes
    audio_data = np.concatenate(audio_chunks).flatten()
    buf = io.BytesIO()
    with wave.open(buf, 'wb') as wf:
        wf.setnchannels(CHANNELS)
        wf.setsampwidth(2)  # int16
        wf.setframerate(SAMPLE_RATE)
        wf.writeframes(audio_data.tobytes())
    return buf.getvalue()


def transcribe(wav_bytes: bytes) -> str:
    """Google Speech-to-Text via SpeechRecognition (richiede internet)."""
    if not wav_bytes:
        return ""
    audio_file = io.BytesIO(wav_bytes)
    with sr.AudioFile(audio_file) as source:
        audio = recognizer.record(source)
    try:
        text = recognizer.recognize_google(audio, language="it-IT")
        return text
    except sr.UnknownValueError:
        return ""
    except sr.RequestError as e:
        print(f"\033[31mErrore Google STT: {e}\033[0m")
        return ""


def ask_claude(user_text: str) -> str:
    conversation.append({"role": "user", "content": user_text})
    history = conversation[-12:]
    msg = client.messages.create(
        model=MODEL,
        max_tokens=300,
        system=SYSTEM_PROMPT,
        messages=history,
    )
    reply = msg.content[0].text
    conversation.append({"role": "assistant", "content": reply})
    return reply


def main():
    print("=" * 50)
    print("JARVIS MVP — voice loop")
    print("Per uscire: dire 'stop' / 'esci' / 'basta' o Ctrl+C")
    print("=" * 50)

    speak("Jarvis pronto. Come posso aiutarti?")

    while True:
        try:
            wav = record_until_silence()
            if not wav:
                print("\033[90m[silenzio, riprovo...]\033[0m")
                continue

            text = transcribe(wav)
            if not text:
                print("\033[90m[non capito]\033[0m")
                continue

            print(f"\033[32mTu:\033[0m {text}")

            if any(w in text.lower() for w in ["stop", "ferma", "esci", "basta"]):
                speak("Ok, a presto.")
                break

            reply = ask_claude(text)
            speak(reply)
        except KeyboardInterrupt:
            print("\n[ctrl+c] uscita")
            break
        except Exception as e:
            print(f"\033[31mErrore: {e}\033[0m")
            import traceback
            traceback.print_exc()


if __name__ == "__main__":
    main()
