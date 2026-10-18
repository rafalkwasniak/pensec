<?php

namespace Tests\Unit;

use App\Services\NarrativePrompt;
use PHPUnit\Framework\TestCase;

class NarrativePromptTest extends TestCase
{
    public function test_it_splits_well_formed_markers(): void
    {
        $answer = "### SEKCJA: podsumowanie\nOcena.\n### SEKCJA: wektory\nUstalenia.";

        $sections = NarrativePrompt::split($answer);

        $this->assertSame('Ocena.', $sections['podsumowanie']);
        $this->assertSame('Ustalenia.', $sections['wektory']);
    }

    /**
     * The model occasionally drops the ###, misspells SEKCJA or adds Polish
     * diacritics to the key. All three must still be recognised, and the marker
     * must never leak into the previous section's body.
     */
    public function test_it_tolerates_malformed_markers_without_leaking_them(): void
    {
        $answer = implode("\n", [
            '### SEKCJA: wektory',
            'Omówienie ustaleń.',
            'SEKCJA: urządzenia',          // no ###, diacritic key
            'Opis wykrytych urządzeń.',
            'SECCJA: uslugi',              // misspelled, no ###
            'Opis usług.',
        ]);

        $sections = NarrativePrompt::split($answer);

        $this->assertSame('Omówienie ustaleń.', $sections['wektory']);
        $this->assertArrayHasKey('urzadzenia', $sections);
        $this->assertSame('Opis wykrytych urządzeń.', $sections['urzadzenia']);
        $this->assertSame('Opis usług.', $sections['uslugi']);

        // The literal marker text must not survive in any rendered body.
        foreach ($sections as $body) {
            $this->assertStringNotContainsStringIgnoringCase('SEKCJA', $body);
            $this->assertStringNotContainsStringIgnoringCase('SECCJA', $body);
        }
    }

    public function test_it_recognises_the_single_letter_secja_misspelling(): void
    {
        // Seen in a real generation: "SECJA: urzadzenia" with one middle letter.
        $answer = "### SEKCJA: wektory\nUstalenia.\nSECJA: urzadzenia\nOpis urządzeń.";

        $sections = NarrativePrompt::split($answer);

        $this->assertSame('Ustalenia.', $sections['wektory']);
        $this->assertSame('Opis urządzeń.', $sections['urzadzenia']);
    }

    public function test_it_strips_stray_cjk_tokens_from_the_prose(): void
    {
        $answer = "### SEKCJA: uslugi\nSprawdzano 端口 53, 80 i 1900.";

        $sections = NarrativePrompt::split($answer);

        $this->assertSame('Sprawdzano 53, 80 i 1900.', $sections['uslugi']);
    }

    public function test_it_ignores_text_before_the_first_marker_and_unknown_keys(): void
    {
        $answer = "Śmieci przed pierwszym znacznikiem.\n### SEKCJA: nieznany\nCoś.\n### SEKCJA: diagnostyka\nKondycja sieci.";

        $sections = NarrativePrompt::split($answer);

        $this->assertArrayNotHasKey('nieznany', $sections);
        $this->assertSame('Kondycja sieci.', $sections['diagnostyka']);
    }
}
