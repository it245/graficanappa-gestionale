"""
PII Pseudonimizer: maschera nomi clienti/operatori prima di inviarli all'LLM
e ripristina nei testi di risposta.

Riduce esposizione di dati personali (GDPR) verso Anthropic API.
Mantiene mapping per-request (no persistenza).

Campi mascherati di default:
  - cliente_nome → "CLIENTE_001", "CLIENTE_002", ...
  - operatore_prinect, operatore_nome → "OPERATORE_001", ...
  - cognome_nome (presenze) → "DIPENDENTE_001", ...

Codici commessa, descrizioni articoli, fasi, P.IVA, importi → NON mascherati
(non sono PII identificative o sono pubbliche).
"""
import re
from typing import Any


class Pseudonymizer:
    """Mappa identificatori reali → token. Per-request, non persistente."""

    # Campi JSON che contengono PII
    PII_FIELDS = {
        'cliente_nome': 'CLIENTE',
        'cliente': 'CLIENTE',
        'operatore_prinect': 'OPERATORE',
        'operatore_nome': 'OPERATORE',
        'operatore': 'OPERATORE',
        'nome': 'OPERATORE',
        'cognome_nome': 'DIPENDENTE',
        'dipendente': 'DIPENDENTE',
        'responsabile': 'OPERATORE',
    }

    def __init__(self):
        self.real_to_token: dict[str, str] = {}
        self.token_to_real: dict[str, str] = {}
        self.counters: dict[str, int] = {}

    def _token_for(self, value: str, category: str) -> str:
        """Ritorna token esistente o genera nuovo per (value, category)."""
        key = f"{category}::{value}"
        if key in self.real_to_token:
            return self.real_to_token[key]
        self.counters[category] = self.counters.get(category, 0) + 1
        token = f"{category}_{self.counters[category]:03d}"
        self.real_to_token[key] = token
        self.token_to_real[token] = value
        return token

    def mask(self, data: Any) -> Any:
        """Sostituisce ricorsivamente valori PII con token nei dict/list."""
        if isinstance(data, dict):
            masked = {}
            for k, v in data.items():
                category = self.PII_FIELDS.get(k.lower())
                if category and isinstance(v, str) and v.strip():
                    masked[k] = self._token_for(v.strip(), category)
                else:
                    masked[k] = self.mask(v)
            return masked
        if isinstance(data, list):
            return [self.mask(x) for x in data]
        return data

    def restore(self, text: str) -> str:
        """Sostituisce token → valori reali nel testo finale di risposta."""
        if not self.token_to_real:
            return text
        # Replace più lungo prima per evitare collisioni ("CLIENTE_010" prima di "CLIENTE_01")
        for token in sorted(self.token_to_real.keys(), key=len, reverse=True):
            text = text.replace(token, self.token_to_real[token])
        return text

    def stats(self) -> dict:
        return {
            'totale_token': len(self.token_to_real),
            'per_categoria': dict(self.counters),
        }
