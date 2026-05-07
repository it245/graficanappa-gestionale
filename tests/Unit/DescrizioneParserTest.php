<?php

namespace Tests\Unit;

use App\Helpers\DescrizioneParser;
use PHPUnit\Framework\TestCase;

/**
 * Baseline test suite per {@see DescrizioneParser}.
 *
 * Cattura il comportamento ATTUALE del parser (snapshot). Eventuali modifiche
 * future al parser che cambino questi output devono essere consapevoli e i test
 * vanno aggiornati di conseguenza.
 */
class DescrizioneParserTest extends TestCase
{
    // -----------------------------------------------------------------------
    // parseColori
    // -----------------------------------------------------------------------

    /**
     * @dataProvider provideColoriCases
     */
    public function test_parse_colori_baseline(?string $descrizione, ?string $cliente, ?string $reparto, string $expected): void
    {
        $this->assertSame(
            $expected,
            DescrizioneParser::parseColori($descrizione, $cliente, $reparto)
        );
    }

    public static function provideColoriCases(): array
    {
        return [
            // Caso 1: 4C esplicito
            'stampa a 4 colori → 4C' => [
                'Astuccio stampa a 4 colori su carta GC1',
                'CLIENTE GENERICO',
                '',
                '4C',
            ],

            // Caso 2: 4 colori + pantone
            '4 colori + pantone → 4C + pantone' => [
                'stampa a 4 colori + pantone 185 su carta',
                'CLIENTE X',
                '',
                '4C + pantone 185',
            ],

            // Caso 3: 5/5 fronte/retro identico → 5C
            '5/5 colori → 5C' => [
                'stampa 5/5 colori su carta',
                'CLIENTE X',
                '',
                '5C',
            ],

            // Caso 4: input null → default 4C
            'descrizione null → default 4C' => [
                null,
                null,
                null,
                '4C',
            ],

            // Caso 5: descrizione vuota → default 4C
            'descrizione vuota → 4C' => [
                '',
                'CLIENTE X',
                '',
                '4C',
            ],

            // Caso 6: Italiana Confetti senza info → 4C + DRIP OFF
            'italiana confetti senza info → 4C + DRIP OFF' => [
                'astuccio',
                'ITALIANA CONFETTI SRL',
                '',
                '4C + DRIP OFF',
            ],

            // Caso 7: reparto digitale forza 4C anche con descrizione "5 colori"
            'reparto digitale forza 4C' => [
                'stampa a 5 colori',
                'CLIENTE X',
                'finitura digitale',
                '4C',
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // parseFustella
    // -----------------------------------------------------------------------

    /**
     * @dataProvider provideFustellaCases
     */
    public function test_parse_fustella_baseline(?string $descrizione, ?string $cliente, ?string $note, ?string $expected): void
    {
        $this->assertSame(
            $expected,
            DescrizioneParser::parseFustella($descrizione, $cliente, $note)
        );
    }

    public static function provideFustellaCases(): array
    {
        return [
            // Caso 1: FS standard 4 cifre
            'FS0902 standard' => [
                'Astuccio FS0902 stampa 4 colori',
                '',
                '',
                'FS0902',
            ],

            // Caso 2: FS 4 cifre diverso
            'FS2820' => [
                'art. FS2820 cioccolatini',
                '',
                '',
                'FS2820',
            ],

            // Caso 3: codice in note prestampa
            'FS in note prestampa' => [
                'astuccio generico',
                '',
                'NUOVA FUSTELLA FS1610',
                'FS1610',
            ],

            // Caso 4: nessun codice → null
            'nessun codice → null' => [
                'astuccio senza codici',
                'CLIENTE X',
                '',
                null,
            ],

            // Caso 5: tutti null → null
            'tutto null → null' => [
                null,
                null,
                null,
                null,
            ],

            // Caso 6: fallback Italiana Confetti AST 1 KG
            'fallback italiana confetti 1 KG' => [
                'AST. 1 KG cioccolatini misti',
                'ITALIANA CONFETTI SRL',
                '',
                'FS0898',
            ],

            // Caso 7: fallback Italiana Confetti AST 500
            'fallback italiana confetti 500g' => [
                'AST. 500 cioccolatini misti',
                'ITALIANA CONFETTI SRL',
                '',
                'FS0044',
            ],

            // Caso 8: KS standard
            'KS1234 standard' => [
                'art. KS1234 etichetta',
                '',
                '',
                'KS1234',
            ],
        ];
    }
}
