<?php

declare(strict_types=1);

namespace ChordPro;

use ChordPro\Notation\ChordNotationInterface;
use PHPUnit\Event\Runtime\PHP;

/**
 * A class for chord manipulations.
 */
class Chord
{
    public const ROOT_CHORDS = ['F#m', 'C#m', 'G#m', 'D#m', 'A#m', 'E#m', 'Dbm', 'Abm', 'Ebm', 'Bbm', 'Fb', 'Cb', 'Gb', 'Db', 'Ab', 'Eb', 'Bb', 'A#', 'F#', 'C#', 'G#', 'D#', 'Fm', 'Cm', 'Gm', 'Dm', 'Am', 'Em', 'Bm', 'F', 'C', 'G', 'D', 'A', 'E', 'B'];

    /**
     * The main chord.
     */
    private string $rootChord = '';

    /**
     * The extension of the chord.
     */
    private string $ext = '';

    /**
     * Was the chord recognized?
     */
    private bool $isKnown = false;

    /**
     * The static cache with notation root chords.
     *
     * @var array<array<string, string>>
     */
    private static array $notationRootChords = [];

    /**
     * @param string $originalName The original name of the chord.
     * @param ChordNotationInterface[] $sourceNotations The notations to use, ordered by precedence.
     */
    public function __construct(private string $originalName, array $sourceNotations = [])
    {
        foreach ($sourceNotations as $sourceNotation) {
            $notationRootChords = $this->getNotationRootChords($sourceNotation);
            foreach ($notationRootChords as $notationRootChord => $rootChord) {
                if (str_starts_with($originalName, $notationRootChord)) {
                    $originalName = $rootChord . substr($originalName, strlen($notationRootChord));
                    break;
                }
            }
        }

        foreach (self::ROOT_CHORDS as $rootChord) {
            if (str_starts_with($originalName, $rootChord)) {
                $this->rootChord = $rootChord;
                $this->ext = substr($originalName, strlen($rootChord));
                $this->isKnown = true;
                break;
            }
        }
        if (!isset($this->rootChord)) {
            $this->isKnown = false;
        }
    }

    /**
     * Create multiple chords from slices like [C/E].
     *
     * @param string $text The text to parse.
     * @param ChordNotationInterface[] $notations The notations to use, ordered by precedence.
     *
     * @return Chord[]
     */
    public static function fromSlice(string $text, array $notations = []): array
    {
        if ($text === '') {
            return [];
        }
        $chords = explode('/', $text);
        $result = [];
        foreach ($chords as $chord) {
            $result[] = new Chord($chord, $notations);
        }
        return $result;
    }

    /**
     * Get the notation root chords for a notation.
     *
     * @return array<string>
     */
    private function getNotationRootChords(ChordNotationInterface $notation): array
    {
        if (!isset(self::$notationRootChords[$notation::class])) {
            foreach (self::ROOT_CHORDS as $rootChord) {
                $convertedChord = $notation->convertChordRootToNotation($rootChord);
                if ($convertedChord !== $rootChord) {
                    self::$notationRootChords[$notation::class][$convertedChord] = $rootChord;
                }
            }
            // Sort by length of the notation root chord, descending.
            $keys = array_map('strlen', array_keys(self::$notationRootChords[$notation::class]));
            array_multisort($keys, SORT_DESC, self::$notationRootChords[$notation::class]);
        }
        return self::$notationRootChords[$notation::class];
    }

    public function isKnown(): bool
    {
        return $this->isKnown;
    }

    public function isMinor(): bool
    {
        return substr($this->rootChord, -1) === 'm';
    }

    public function getRootChord(?ChordNotationInterface $targetNotation = null): string
    {
        if (!is_null($targetNotation)) {
            return $targetNotation->convertChordRootToNotation($this->rootChord);
        }
        return $this->rootChord;
    }

    public function getExt(?ChordNotationInterface $targetNotation = null): string
    {
        if (!is_null($targetNotation)) {
            return $targetNotation->convertExtToNotation($this->ext);
        }
        return $this->ext;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function transposeTo(string $rootChord): void
    {
        $this->rootChord = $rootChord;
    }

}
