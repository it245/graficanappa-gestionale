"""
JARVIS MVP — voice loop minimo
Ascolta microfono → trascrive → Claude → risposta vocale.
Per fermare: dire "stop" o Ctrl+C.

Setup:
  pip install SpeechRecognition pyttsx3 anthropic pyaudio
  set ANTHROPIC_API_KEY=sk-ant-...
  python jarvis.py
"""
import os
import sys
import speech_recognition as sr
import pyttsx3
import anthropic

# === Config ===
MODEL = "claude-opus-4-5"  # claude-opus-4-7 se disponibile
SYSTEM_PROMPT = """Sei Jarvis, assistente AI italiano per Giovanni Pietropaolo,
responsabile IT di Grafica Nappa srl (tipografia ad Aversa).
Rispondi in italiano, breve e diretto. Massimo 2-3 frasi.
Se non sai rispondere, dillo onestamente."""

# === Setup ===
api_key = os.getenv("ANTHROPIC_API_KEY")
if not api_key:
    print("ERRORE: ANTHROPIC_API_KEY non settata. Eseguire:")
    print('  set ANTHROPIC_API_KEY=sk-ant-...')
    sys.exit(1)

client = anthropic.Anthropic(api_key=api_key)
recognizer = sr.Recognizer()
recognizer.pause_threshold = 1.0  # secondi di silenzio per fine frase

# TTS Windows nativo
engine = pyttsx3.init()
engine.setProperty('rate', 180)  # parole/min

# Trova voce italiana se presente
for voice in engine.getProperty('voices'):
    if 'italian' in voice.name.lower() or 'it' in voice.id.lower():
        engine.setProperty('voice', voice.id)
        break

# Conversation memory (ultimi 6 turn)
conversation = []


def speak(text: str):
    print(f"\033[36mJarvis:\033[0m {text}")
    engine.say(text)
    engine.runAndWait()


def listen() -> str:
    with sr.Microphone() as source:
        recognizer.adjust_for_ambient_noise(source, duration=0.3)
        print("\033[33m[ascolto...]\033[0m")
        try:
            audio = recognizer.listen(source, timeout=5, phrase_time_limit=15)
        except sr.WaitTimeoutError:
            return ""
    try:
        text = recognizer.recognize_google(audio, language="it-IT")
        print(f"\033[32mTu:\033[0m {text}")
        return text
    except sr.UnknownValueError:
        return ""
    except sr.RequestError as e:
        print(f"\033[31mErrore Google STT: {e}\033[0m")
        return ""


def ask_claude(user_text: str) -> str:
    conversation.append({"role": "user", "content": user_text})
    # Tieni solo ultimi 12 messaggi (6 turn)
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
    speak("Jarvis pronto. Come posso aiutarti?")
    while True:
        try:
            text = listen()
            if not text:
                continue
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


if __name__ == "__main__":
    main()
